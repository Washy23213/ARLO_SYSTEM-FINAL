<?php

use App\Models\Admin;
use App\Models\SuperAdmin;
use App\Models\User;
use App\Services\FacebookPageAnnouncementSynchronizer;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Str;
use Symfony\Component\Console\Command\Command;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('user:set-role {email} {role}', function (string $email, string $role) {
    $email = Str::lower(trim($email));
    $role = trim($role);

    $validRoles = array_merge([User::ROLE_ALUMNI, User::ROLE_SUPER_ADMIN], Admin::ROLES);

    if (! in_array($role, $validRoles, true)) {
        $this->error('Invalid role. Use one of: alumni, admin, alumni_records_manager, job_manager, super_admin.');

        return Command::FAILURE;
    }

    if ($role === User::ROLE_ALUMNI) {
        $user = User::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->first();

        if (! $user) {
            $this->error("No alumni account was found for {$email}.");

            return Command::FAILURE;
        }

        $user->forceFill([
            'role' => User::ROLE_ALUMNI,
            'is_admin' => false,
        ])->save();

        $this->info("Updated {$user->email} to Alumni.");

        return Command::SUCCESS;
    }

    if ($role === User::ROLE_SUPER_ADMIN) {
        $superAdmin = SuperAdmin::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->first();

        if ($superAdmin) {
            $this->info("{$superAdmin->email} is already a Super admin.");

            return Command::SUCCESS;
        }

        $admin = Admin::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->first();

        if (! $admin) {
            $this->error("No regular admin account was found for {$email}.");

            return Command::FAILURE;
        }

        DB::transaction(function () use ($admin): void {
            SuperAdmin::query()->create([
                'id' => $admin->id,
                'firstname' => $admin->firstname,
                'lastname' => $admin->lastname,
                'email' => $admin->email,
                'password' => $admin->password,
                'phone' => $admin->phone,
                'email_verified_at' => $admin->email_verified_at,
            ]);

            if (
                DB::getSchemaBuilder()->hasTable('system_logs')
                && DB::getSchemaBuilder()->hasColumn('system_logs', 'admin_id')
                && DB::getSchemaBuilder()->hasColumn('system_logs', 'super_admin_id')
            ) {
                DB::table('system_logs')
                    ->where('admin_id', $admin->id)
                    ->update([
                        'super_admin_id' => $admin->id,
                        'admin_id' => null,
                    ]);
            }

            $admin->delete();
        });

        $this->info("Moved {$email} from admins to super_admins.");

        return Command::SUCCESS;
    }

    $superAdmin = SuperAdmin::query()
        ->whereRaw('LOWER(email) = ?', [$email])
        ->first();

    if ($superAdmin) {
        DB::transaction(function () use ($superAdmin, $role): void {
            Admin::query()->create([
                'id' => $superAdmin->id,
                'firstname' => $superAdmin->firstname,
                'lastname' => $superAdmin->lastname,
                'email' => $superAdmin->email,
                'password' => $superAdmin->password,
                'phone' => $superAdmin->phone,
                'role' => $role,
                'email_verified_at' => $superAdmin->email_verified_at,
            ]);

            if (
                DB::getSchemaBuilder()->hasTable('system_logs')
                && DB::getSchemaBuilder()->hasColumn('system_logs', 'admin_id')
                && DB::getSchemaBuilder()->hasColumn('system_logs', 'super_admin_id')
            ) {
                DB::table('system_logs')
                    ->where('super_admin_id', $superAdmin->id)
                    ->update([
                        'admin_id' => $superAdmin->id,
                        'super_admin_id' => null,
                    ]);
            }

            $superAdmin->delete();
        });

        $this->info("Moved {$email} from super_admins to admins.");

        return Command::SUCCESS;
    }

    $admin = Admin::query()
        ->whereRaw('LOWER(email) = ?', [$email])
        ->first();

    if (! $admin) {
        $this->error("No admin or super admin account was found for {$email}.");

        return Command::FAILURE;
    }

    $admin->forceFill([
        'role' => $role,
    ])->save();

    $this->info("Updated {$admin->email} to {$admin->roleLabel()}.");

    return Command::SUCCESS;
})->purpose('Move an existing account between super_admin, admin roles, or alumni');

Artisan::command('user:make-super-admin {email}', function (string $email) {
    $email = Str::lower(trim($email));
    $existingSuperAdmin = SuperAdmin::query()
        ->whereRaw('LOWER(email) = ?', [$email])
        ->first();

    if ($existingSuperAdmin) {
        $this->info("{$existingSuperAdmin->email} is already a Super admin.");

        return Command::SUCCESS;
    }

    $admin = Admin::query()
        ->whereRaw('LOWER(email) = ?', [$email])
        ->first();

    if (! $admin) {
        $this->error("No admin account was found for {$email}.");

        return Command::FAILURE;
    }

    DB::transaction(function () use ($admin): void {
        SuperAdmin::query()->create([
            'id' => $admin->id,
            'firstname' => $admin->firstname,
            'lastname' => $admin->lastname,
            'email' => $admin->email,
            'password' => $admin->password,
            'phone' => $admin->phone,
            'email_verified_at' => $admin->email_verified_at,
        ]);

        if (
            DB::getSchemaBuilder()->hasTable('system_logs')
            && DB::getSchemaBuilder()->hasColumn('system_logs', 'admin_id')
            && DB::getSchemaBuilder()->hasColumn('system_logs', 'super_admin_id')
        ) {
            DB::table('system_logs')
                ->where('admin_id', $admin->id)
                ->update([
                    'super_admin_id' => $admin->id,
                    'admin_id' => null,
                ]);
        }

        $admin->delete();
    });

    $this->info("Moved {$email} from admins to super_admins.");

    return Command::SUCCESS;
})->purpose('Promote an existing admin account to super admin');

Artisan::command('facebook:sync-announcements {--limit=100}', function () {
    $limit = max((int) $this->option('limit'), 1);
    $result = app(FacebookPageAnnouncementSynchronizer::class)->syncRecentPosts($limit);

    $message = (string) ($result['message'] ?? 'Facebook announcement sync finished.');

    if (! empty($result['success'])) {
        $this->info($message);

        return Command::SUCCESS;
    }

    $this->error($message);

    return Command::FAILURE;
})->purpose('Import recent Facebook Page posts into announcements');

Artisan::command('facebook:diagnose {postId?} {--title=}', function (?string $postId = null) {
    $version = (string) config('services.facebook.graph_version', 'v25.0');
    $pageId = trim((string) config('services.facebook.page_id'));
    $token = trim((string) config('services.facebook.page_access_token'));

    if ($pageId === '' || $token === '') {
        $this->error('Facebook Page ID or access token is missing.');

        return Command::FAILURE;
    }

    try {
        $response = Http::acceptJson()
            ->timeout(15)
            ->get("https://graph.facebook.com/{$version}/{$pageId}", [
                'fields' => 'id,name,username,link,is_published,verification_status',
                'access_token' => $token,
            ]);
    } catch (\Throwable $exception) {
        $message = preg_replace('/([?&]access_token=)[^&\s]+/i', '$1[redacted]', $exception->getMessage()) ?? $exception->getMessage();
        $this->error('Facebook request failed: '.Str::limit($message, 220));

        return Command::FAILURE;
    }

    if ($response->successful()) {
        $this->info('Facebook Page token is valid.');
        $this->line('Page: '.((string) $response->json('name', $pageId)));
        $this->line('Page link: '.((string) $response->json('link', 'N/A')));
        $this->line('Page published: '.json_encode($response->json('is_published')));

        $title = trim((string) $this->option('title'));

        if (blank($postId) && $title !== '' && DB::getSchemaBuilder()->hasTable('announcements')) {
            $announcement = DB::table('announcements')
                ->select(['title', 'facebook_post_id', 'facebook_post_url'])
                ->where('title', 'like', "%{$title}%")
                ->whereNotNull('facebook_post_id')
                ->latest('updated_at')
                ->first();

            $postId = $announcement?->facebook_post_id;

            if ($announcement) {
                $this->newLine();
                $this->line('Local announcement: '.$announcement->title);
                $this->line('Local saved link: '.$announcement->facebook_post_url);
            }
        }

        if (blank($postId)) {
            return Command::SUCCESS;
        }

        $postId = trim((string) $postId);
        $postResponse = Http::acceptJson()
            ->timeout(15)
            ->get("https://graph.facebook.com/{$version}/{$postId}", [
                'fields' => 'id,from,message,permalink_url,is_published,privacy,status_type,created_time,updated_time',
                'access_token' => $token,
            ]);

        $this->newLine();
        $this->line("Post API status: {$postResponse->status()}");

        if (! $postResponse->successful()) {
            $this->error('Facebook post check failed: '.Str::limit((string) $postResponse->json('error.message', 'Unknown Facebook API error.'), 220));

            return Command::FAILURE;
        }

        $this->line('Post ID: '.((string) $postResponse->json('id', $postId)));
        $this->line('Post from: '.((string) $postResponse->json('from.name', 'N/A')));
        $this->line('Graph permalink: '.((string) $postResponse->json('permalink_url', 'N/A')));
        $this->line('Published: '.json_encode($postResponse->json('is_published')));
        $this->line('Privacy: '.json_encode($postResponse->json('privacy')));
        $this->line('Status type: '.((string) $postResponse->json('status_type', 'N/A')));
        $this->line('Message: '.Str::limit((string) $postResponse->json('message', ''), 120));

        $feedResponse = Http::acceptJson()
            ->timeout(15)
            ->get("https://graph.facebook.com/{$version}/{$pageId}/posts", [
                'fields' => 'id',
                'limit' => 100,
                'access_token' => $token,
            ]);

        if ($feedResponse->successful()) {
            $foundInFeed = collect($feedResponse->json('data', []))
                ->contains(fn (array $post) => (string) ($post['id'] ?? '') === $postId);

            $this->line('Found in latest 100 Page posts: '.($foundInFeed ? 'YES' : 'NO'));
        } else {
            $this->warn('Could not check Page feed membership.');
        }

        return Command::SUCCESS;
    }

    $errorMessage = (string) $response->json('error.message', 'Unknown Facebook API error.');
    $errorCode = (string) $response->json('error.code', '');
    $errorSubcode = (string) $response->json('error.error_subcode', '');

    $this->error("Facebook check failed ({$response->status()}): ".Str::limit($errorMessage, 220));

    if ($errorCode === '190') {
        $this->warn('The access token is invalid, expired, or revoked. Generate a new Page access token.');
    }

    if ($errorSubcode !== '') {
        $this->line("Facebook error subcode: {$errorSubcode}");
    }

    return Command::FAILURE;
})->purpose('Check the configured Facebook Page access token');

Artisan::command('mail:test {email}', function (string $email) {
    try {
        Mail::raw('This is a CMU ARLO test email. If you received this, SMTP is working.', function ($message) use ($email) {
            $message->to($email)->subject('CMU ARLO Mail Test');
        });
    } catch (\Throwable $exception) {
        $this->error('Mail send failed: '.Str::limit($exception->getMessage(), 220));

        return Command::FAILURE;
    }

    $this->info("Test email sent to {$email}.");

    return Command::SUCCESS;
})->purpose('Send a test email using the configured mailer');

Artisan::command('demo:accounts', function () {
    if (! app()->environment('production')) {
        $this->error('This command only creates demo accounts in the production environment.');

        return Command::FAILURE;
    }

    if (! Schema::hasTable('admins')) {
        $this->error('The admin account table is missing. Run the production database migrations first.');

        return Command::FAILURE;
    }

    $accounts = [];

    foreach (range(1, 5) as $number) {
        $email = sprintf('arlo.demo%02d@example.test', $number);
        $password = $number === 1 ? 'password' : Str::random(24);
        $user = User::query()->whereRaw('LOWER(email) = ?', [$email])->first();

        if ($user !== null) {
            if ($number === 1) {
                $user->forceFill(['password' => $password])->save();
                $accounts[] = ['Alumni', $email, $password];
                $this->info("Reset the demo password for {$email}.");
            } else {
                $this->warn("Skipped {$email}; that account already exists.");
            }

            continue;
        }

        User::query()->create([
            'firstname' => 'Demo',
            'lastname' => 'Alumni '.$number,
            'email' => $email,
            'email_verified_at' => now(),
            'password' => $password,
            'role' => User::ROLE_ALUMNI,
            'is_admin' => false,
        ]);

        $accounts[] = ['Alumni', $email, $password];
    }

    $adminEmail = 'arlo.admin.demo@example.test';
    $adminExists = Admin::query()->whereRaw('LOWER(email) = ?', [$adminEmail])->exists();
    $emailUsedByOtherAccount = User::query()->whereRaw('LOWER(email) = ?', [$adminEmail])->exists()
        || SuperAdmin::query()->whereRaw('LOWER(email) = ?', [$adminEmail])->exists();

    if ($adminExists) {
        $this->warn("Skipped {$adminEmail}; that admin account already exists.");
    } elseif ($emailUsedByOtherAccount) {
        $this->error("Cannot create {$adminEmail}; another account already uses that email.");
    } else {
        $adminPassword = Str::random(24);

        Admin::query()->create([
            'firstname' => 'Demo',
            'lastname' => 'Administrator',
            'email' => $adminEmail,
            'password' => $adminPassword,
            'role' => Admin::ROLE_ADMIN,
            'email_verified_at' => now(),
        ]);

        $accounts[] = ['Administrator', $adminEmail, $adminPassword];
    }

    if ($accounts === []) {
        $this->warn('No account credentials were changed or created.');

        return Command::SUCCESS;
    }

    $this->newLine();
    $this->table(['Account', 'Email', 'Password'], $accounts);
    $this->warn('The demo01 password is reset to "password" each time. The admin password is shown only when the admin account is first created.');

    return Command::SUCCESS;
})->purpose('Create demo alumni and admin preview accounts in production');

Artisan::command('employment-proof:mark-overdue', function () {
    $cutoff = now()->subWeek();
    $updated = 0;

    User::query()
        ->alumni()
        ->registeredAlumniAccount()
        ->where('created_at', '<=', $cutoff)
        ->whereDoesntHave('tracerRecords', function ($query): void {
            $query
                ->whereNotNull('employment_proof_path')
                ->whereRaw("TRIM(COALESCE(employment_proof_path, '')) <> ''");
        })
        ->chunk(100, function ($users) use (&$updated): void {
            foreach ($users as $user) {
                $user->forceFill([
                    'employed_after_grad' => 'No',
                    'industry' => null,
                    'current_position' => null,
                    'company' => null,
                    'salary_range' => null,
                ])->save();

                $updated++;
            }
        });

    $this->info("Marked {$updated} overdue alumni account(s) as unemployed.");

    return Command::SUCCESS;
})->purpose('Mark registered alumni without employment proof after 7 days as unemployed');

Schedule::command('facebook:sync-announcements --limit=100')
    ->everyMinute()
    ->withoutOverlapping();

Schedule::command('employment-proof:mark-overdue')
    ->dailyAt('01:00')
    ->withoutOverlapping();
