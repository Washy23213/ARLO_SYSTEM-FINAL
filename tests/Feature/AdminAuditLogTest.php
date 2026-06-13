<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\PortalIndicatorState;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminAuditLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_actions_write_audit_logs(): void
    {
        $admin = User::factory()->admin()->create();

        $createResponse = $this->actingAs($admin)->post(route('admin.announcements.store'), [
            'title' => 'Audit Test Announcement',
            'content' => 'This announcement is used to verify audit logging behavior.',
            'type' => 'Update',
            'priority' => 'Medium',
            'target_audience' => 'All',
            'is_published' => '1',
        ]);

        $createResponse->assertRedirect(route('admin.announcements'));

        $announcement = DB::table('announcements')
            ->where('title', 'Audit Test Announcement')
            ->first();

        $this->assertNotNull($announcement);

        $this->assertDatabaseHas('system_logs', [
            'user_id' => $admin->id,
            'action' => 'admin.announcements.create',
        ]);

        $archiveResponse = $this->actingAs($admin)->delete(route('admin.announcements.delete', $announcement->id));

        $archiveResponse->assertRedirect(route('admin.announcements'));

        $this->assertDatabaseHas('system_logs', [
            'user_id' => $admin->id,
            'action' => 'admin.announcements.archive',
        ]);
    }

    public function test_admin_can_view_and_filter_the_audit_logs_page(): void
    {
        $admin = User::factory()->admin()->create([
            'firstname' => 'Audit',
            'lastname' => 'Admin',
            'email' => 'audit.admin@example.com',
        ]);

        $otherAdmin = User::factory()->admin()->create([
            'firstname' => 'Second',
            'lastname' => 'Admin',
            'email' => 'second.admin@example.com',
        ]);

        DB::table('system_logs')->insert([
            [
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'user_id' => $admin->id,
                'action' => 'admin.jobs.create',
                'description' => 'Created a new QA Analyst job posting.',
                'ip_address' => '127.0.0.1',
                'user_agent' => 'PHPUnit',
                'created_at' => now(),
            ],
            [
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'user_id' => $otherAdmin->id,
                'action' => 'admin.announcements.create',
                'description' => 'Published a graduate tracer reminder.',
                'ip_address' => '127.0.0.2',
                'user_agent' => 'PHPUnit',
                'created_at' => now()->subHour(),
            ],
        ]);

        $response = $this->actingAs($admin)->get(route('admin.audit-logs', [
            'search' => 'QA Analyst',
            'action' => 'admin.jobs.create',
        ]));

        $response->assertOk();
        $response->assertSee('Audit Logs');
        $response->assertSee('All Actions');
        $response->assertSee('Actions Today');
        $response->assertSee('Admins Who Made Changes');
        $response->assertSee('Admin Activity');
        $response->assertSee('View names below');
        $response->assertSee('Second Admin');
        $response->assertSee('second.admin@example.com');
        $response->assertSee('Latest Action By');
        $response->assertSee('Jobs Create');
        $response->assertSee('Audit Admin');
        $response->assertSee('Created a new QA Analyst job posting.');
        $response->assertDontSee('Published a graduate tracer reminder.');
    }

    public function test_authentication_events_write_audit_logs(): void
    {
        $admin = User::factory()->admin()->create([
            'email' => 'audit.login@example.com',
        ]);

        $this->post(route('login.submit'), [
            'email' => $admin->email,
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('email');

        $this->assertDatabaseHas('system_logs', [
            'user_id' => null,
            'action' => 'auth.login.failed',
            'description' => 'Failed login attempt for audit.login@example.com.',
        ]);

        $this->post(route('login.submit'), [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertDatabaseHas('system_logs', [
            'admin_id' => $admin->id,
            'action' => 'auth.admin.login',
        ]);

        $this->post(route('logout'))->assertRedirect('/login');

        $this->assertDatabaseHas('system_logs', [
            'admin_id' => $admin->id,
            'action' => 'auth.admin.logout',
        ]);
    }

    public function test_alumni_events_write_audit_logs_for_admin_review(): void
    {
        $alumni = User::factory()->create([
            'is_admin' => false,
            'email' => 'audit.alumni@example.com',
        ]);
        $jobId = (string) Str::uuid();

        DB::table('jobs')->insert([
            'id' => $jobId,
            'title' => 'Audit QA Analyst',
            'company' => 'CMU Digital Services',
            'description' => 'A job posting used to verify audit logging.',
            'requirements' => 'Testing experience',
            'location' => 'Main Campus',
            'type' => 'Full-time',
            'contact_email' => 'jobs@example.com',
            'is_active' => true,
            'views_count' => 0,
            'applications_count' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($alumni)->post(route('feedback.submit'), [
            'subject' => 'Audit feedback trail',
            'message' => 'Please record this feedback in the audit log.',
            'type' => 'General Feedback',
        ])->assertRedirect(route('feedback.form'));

        $this->assertDatabaseHas('system_logs', [
            'user_id' => $alumni->id,
            'action' => 'alumni.feedback.submit',
        ]);

        $this->actingAs($alumni)->post(route('job.apply', $jobId), [
            'cover_letter' => 'Please consider my application for this audit test role.',
            'resume_url' => 'https://example.com/resume.pdf',
        ])->assertRedirect(route('job.detail', $jobId));

        $this->assertDatabaseHas('system_logs', [
            'user_id' => $alumni->id,
            'action' => 'alumni.jobs.apply',
        ]);

        $this->actingAs($alumni)->post(route('jobs.submit'), [
            'title' => 'Audit Systems Assistant',
            'company' => 'CMU Digital Services',
            'type' => 'Full-time',
            'location' => 'Main Campus',
            'contact_email' => 'jobs@example.com',
            'application_deadline' => now()->addMonth()->toDateString(),
            'description' => 'This submitted job should appear in the audit trail for admins.',
            'requirements' => 'Good communication skills',
        ])->assertRedirect(route('jobs'));

        $this->assertDatabaseHas('system_logs', [
            'user_id' => $alumni->id,
            'action' => 'alumni.jobs.submit',
        ]);
    }

    public function test_admin_sidebar_indicators_clear_after_opening_announcements_and_audit_logs(): void
    {
        $admin = User::factory()->admin()->create();

        DB::table('announcements')->insert([
            'id' => (string) Str::uuid(),
            'title' => 'Fresh Admin Announcement',
            'content' => 'A fresh announcement that should trigger the sidebar indicator.',
            'type' => 'Update',
            'priority' => 'Medium',
            'target_audience' => 'All',
            'is_published' => true,
            'views_count' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('system_logs')->insert([
            'id' => (string) Str::uuid(),
            'user_id' => $admin->id,
            'action' => 'admin.audit.review',
            'description' => 'A fresh log entry that should trigger the audit indicator.',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'created_at' => now(),
        ]);

        $this->actingAs($admin);

        $response = $this
            ->withSession([
                PortalIndicatorState::sessionKeyFor(PortalIndicatorState::ADMIN_ANNOUNCEMENTS, $admin->id) => now()->subDay()->toIso8601String(),
                PortalIndicatorState::sessionKeyFor(PortalIndicatorState::ADMIN_AUDIT_LOGS, $admin->id) => now()->subDay()->toIso8601String(),
            ])
            ->get(route('admin.dashboard'));

        $response->assertOk();

        $content = $response->getContent();

        $this->assertMatchesRegularExpression(
            '/Notifications<\/span>\s*<\/span>\s*<span class="alumni-sidebar-link__badge">1<\/span>/s',
            $content
        );
        $this->assertMatchesRegularExpression(
            '/Audit Logs<\/span>\s*<\/span>\s*<span class="alumni-sidebar-link__badge">1<\/span>/s',
            $content
        );

        $this->get(route('admin.announcements'))->assertOk();
        $this->get(route('admin.audit-logs'))->assertOk();

        $followUp = $this->get(route('admin.dashboard'));
        $followUp->assertOk();

        $followUpContent = $followUp->getContent();

        $this->assertDoesNotMatchRegularExpression(
            '/Notifications<\/span>\s*<\/span>\s*<span class="alumni-sidebar-link__badge">1<\/span>/s',
            $followUpContent
        );
        $this->assertDoesNotMatchRegularExpression(
            '/Audit Logs<\/span>\s*<\/span>\s*<span class="alumni-sidebar-link__badge">1<\/span>/s',
            $followUpContent
        );
    }

    public function test_admin_sidebar_indicators_stay_cleared_after_logout_and_login_without_new_items(): void
    {
        $admin = User::factory()->admin()->create([
            'email' => 'admin.indicators@example.com',
        ]);

        DB::table('announcements')->insert([
            'id' => (string) Str::uuid(),
            'title' => 'Persistent Admin Announcement',
            'content' => 'An announcement used to verify admin indicator persistence.',
            'type' => 'Update',
            'priority' => 'Medium',
            'target_audience' => 'All',
            'is_published' => true,
            'views_count' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('system_logs')->insert([
            'id' => (string) Str::uuid(),
            'user_id' => $admin->id,
            'action' => 'admin.audit.review',
            'description' => 'An audit entry used to verify admin indicator persistence.',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'created_at' => now(),
        ]);

        DB::table('feedback')->insert([
            'id' => (string) Str::uuid(),
            'user_id' => $admin->id,
            'subject' => 'Persistent feedback reminder',
            'message' => 'An open feedback item used to verify admin indicator persistence.',
            'type' => 'General Feedback',
            'status' => 'Open',
            'priority' => 'Medium',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($admin);

        $beforeOpeningPages = $this
            ->withSession([
                PortalIndicatorState::sessionKeyFor(PortalIndicatorState::ADMIN_ANNOUNCEMENTS, $admin->id) => now()->subDay()->toIso8601String(),
                PortalIndicatorState::sessionKeyFor(PortalIndicatorState::ADMIN_AUDIT_LOGS, $admin->id) => now()->subDay()->toIso8601String(),
                PortalIndicatorState::sessionKeyFor(PortalIndicatorState::ADMIN_FEEDBACK, $admin->id) => now()->subDay()->toIso8601String(),
            ])
            ->get(route('admin.dashboard'));

        $beforeOpeningPages->assertOk();
        $beforeContent = $beforeOpeningPages->getContent();

        $this->assertMatchesRegularExpression(
            '/Notifications<\/span>\s*<\/span>\s*<span class="alumni-sidebar-link__badge">1<\/span>/s',
            $beforeContent
        );
        $this->assertMatchesRegularExpression(
            '/Feedback<\/span>\s*<\/span>\s*<span class="alumni-sidebar-link__badge">1<\/span>/s',
            $beforeContent
        );
        $this->assertMatchesRegularExpression(
            '/Audit Logs<\/span>\s*<\/span>\s*<span class="alumni-sidebar-link__badge">1<\/span>/s',
            $beforeContent
        );

        $this->get(route('admin.announcements'))->assertOk();
        $this->get(route('admin.feedback'))->assertOk();
        $this->get(route('admin.audit-logs'))->assertOk();

        $this->post(route('logout'))->assertRedirect('/login');
        $this->flushSession();

        $this->post(route('login.submit'), [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertRedirect(route('admin.dashboard'));

        $afterLogin = $this->get(route('admin.dashboard'));
        $afterLogin->assertOk();
        $afterContent = $afterLogin->getContent();

        $this->assertDoesNotMatchRegularExpression(
            '/Notifications<\/span>\s*<\/span>\s*<span class="alumni-sidebar-link__badge">\d+<\/span>/s',
            $afterContent
        );
        $this->assertDoesNotMatchRegularExpression(
            '/Feedback<\/span>\s*<\/span>\s*<span class="alumni-sidebar-link__badge">\d+<\/span>/s',
            $afterContent
        );
        $this->assertDoesNotMatchRegularExpression(
            '/Audit Logs<\/span>\s*<\/span>\s*<span class="alumni-sidebar-link__badge">\d+<\/span>/s',
            $afterContent
        );
    }
}
