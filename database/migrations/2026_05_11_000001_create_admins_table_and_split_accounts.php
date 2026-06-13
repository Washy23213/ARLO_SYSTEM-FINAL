<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admins', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('firstname')->nullable();
            $table->string('lastname')->nullable();
            $table->string('email')->unique();
            $table->string('password');
            $table->string('phone', 30)->nullable();
            $table->string('role', 40)->default('admin')->index();
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement(
                "ALTER TABLE admins ADD CONSTRAINT admins_role_check CHECK (role IN ('super_admin', 'admin'))"
            );
        }

        if (Schema::hasTable('system_logs') && ! Schema::hasColumn('system_logs', 'admin_id')) {
            Schema::table('system_logs', function (Blueprint $table) {
                $table->uuid('admin_id')->nullable()->after('user_id')->index();
            });
        }

        $this->copyAdminUsers();
        $this->dropLegacyUserForeignKeys();
        $this->cleanAdminOwnedColumns();
        $this->moveAdminLogs();
        $this->deleteAdminRowsFromUsers();
        $this->addAdminForeignKeys();
    }

    public function down(): void
    {
        $this->dropAdminForeignKeys();

        if (Schema::hasTable('admins') && Schema::hasTable('users')) {
            DB::table('admins')
                ->orderBy('created_at')
                ->get()
                ->each(function (object $admin): void {
                    DB::table('users')->updateOrInsert(
                        ['id' => $admin->id],
                        [
                            'firstname' => $admin->firstname,
                            'lastname' => $admin->lastname,
                            'email' => $admin->email,
                            'password' => $admin->password,
                            'phone' => $admin->phone,
                            'role' => $admin->role,
                            'is_admin' => true,
                            'email_verified_at' => $admin->email_verified_at,
                            'remember_token' => $admin->remember_token,
                            'created_at' => $admin->created_at ?? now(),
                            'updated_at' => now(),
                        ]
                    );
                });
        }

        if (Schema::hasTable('system_logs') && Schema::hasColumn('system_logs', 'admin_id')) {
            DB::table('system_logs')
                ->whereNull('user_id')
                ->whereNotNull('admin_id')
                ->update(['user_id' => DB::raw('admin_id')]);

            Schema::table('system_logs', function (Blueprint $table) {
                $table->dropColumn('admin_id');
            });
        }

        if (DB::getDriverName() === 'pgsql' && Schema::hasTable('admins')) {
            DB::statement('ALTER TABLE admins DROP CONSTRAINT IF EXISTS admins_role_check');
        }

        Schema::dropIfExists('admins');
        $this->restoreLegacyUserForeignKeys();
    }

    private function copyAdminUsers(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        DB::table('users')
            ->where(function ($query) {
                $query->whereIn('role', [User::ROLE_SUPER_ADMIN, User::ROLE_ADMIN])
                    ->orWhere('is_admin', true);
            })
            ->orderBy('created_at')
            ->get()
            ->each(function (object $user): void {
                DB::table('admins')->updateOrInsert(
                    ['email' => $user->email],
                    [
                        'id' => $user->id,
                        'firstname' => $user->firstname,
                        'lastname' => $user->lastname,
                        'email' => $user->email,
                        'password' => $user->password,
                        'phone' => $user->phone ?? null,
                        'role' => $user->role === User::ROLE_SUPER_ADMIN ? 'super_admin' : 'admin',
                        'email_verified_at' => $user->email_verified_at,
                        'remember_token' => $user->remember_token ?? Str::random(10),
                        'created_at' => $user->created_at ?? now(),
                        'updated_at' => now(),
                    ]
                );
            });
    }

    private function cleanAdminOwnedColumns(): void
    {
        $adminIds = Schema::hasTable('admins') ? DB::table('admins')->pluck('id')->all() : [];

        foreach ([
            ['jobs', 'created_by'],
            ['jobs', 'archived_by'],
            ['announcements', 'created_by'],
            ['announcements', 'archived_by'],
            ['feedback', 'assigned_to'],
            ['job_applications', 'reviewed_by'],
        ] as [$table, $column]) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
                continue;
            }

            DB::table($table)
                ->whereNotNull($column)
                ->when($adminIds !== [], fn ($query) => $query->whereNotIn($column, $adminIds))
                ->when($adminIds === [], fn ($query) => $query)
                ->update([$column => null]);
        }
    }

    private function moveAdminLogs(): void
    {
        if (! Schema::hasTable('system_logs') || ! Schema::hasColumn('system_logs', 'admin_id')) {
            return;
        }

        $adminIds = DB::table('admins')->pluck('id')->all();

        if ($adminIds === []) {
            return;
        }

        DB::table('system_logs')
            ->whereIn('user_id', $adminIds)
            ->update([
                'admin_id' => DB::raw('user_id'),
                'user_id' => null,
            ]);
    }

    private function deleteAdminRowsFromUsers(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasTable('admins')) {
            return;
        }

        $adminIds = DB::table('admins')->pluck('id')->all();

        if ($adminIds === []) {
            return;
        }

        DB::table('users')
            ->whereIn('id', $adminIds)
            ->delete();
    }

    private function dropLegacyUserForeignKeys(): void
    {
        foreach ([
            ['jobs', 'created_by'],
            ['jobs', 'archived_by'],
            ['announcements', 'created_by'],
            ['announcements', 'archived_by'],
            ['feedback', 'assigned_to'],
            ['job_applications', 'reviewed_by'],
            ['system_logs', 'user_id'],
            ['sessions', 'user_id'],
        ] as [$table, $column]) {
            $this->dropForeignIfPossible($table, $column);
        }
    }

    private function addAdminForeignKeys(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        foreach ([
            ['jobs', 'created_by'],
            ['jobs', 'archived_by'],
            ['announcements', 'created_by'],
            ['announcements', 'archived_by'],
            ['feedback', 'assigned_to'],
            ['job_applications', 'reviewed_by'],
            ['system_logs', 'admin_id'],
        ] as [$table, $column]) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
                continue;
            }

            try {
                Schema::table($table, function (Blueprint $tableBlueprint) use ($column) {
                    $tableBlueprint->foreign($column)->references('id')->on('admins')->nullOnDelete();
                });
            } catch (Throwable) {
                // Keep the migration idempotent across local schemas with hand-edited constraints.
            }
        }
    }

    private function dropAdminForeignKeys(): void
    {
        foreach ([
            ['jobs', 'created_by'],
            ['jobs', 'archived_by'],
            ['announcements', 'created_by'],
            ['announcements', 'archived_by'],
            ['feedback', 'assigned_to'],
            ['job_applications', 'reviewed_by'],
            ['system_logs', 'admin_id'],
        ] as [$table, $column]) {
            $this->dropForeignIfPossible($table, $column);
        }
    }

    private function restoreLegacyUserForeignKeys(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        foreach ([
            ['jobs', 'created_by', 'null'],
            ['jobs', 'archived_by', 'null'],
            ['announcements', 'created_by', 'null'],
            ['announcements', 'archived_by', 'null'],
            ['feedback', 'assigned_to', 'null'],
            ['job_applications', 'reviewed_by', 'null'],
            ['system_logs', 'user_id', 'null'],
            ['sessions', 'user_id', 'cascade'],
        ] as [$table, $column, $deleteAction]) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
                continue;
            }

            try {
                Schema::table($table, function (Blueprint $tableBlueprint) use ($column, $deleteAction) {
                    $foreign = $tableBlueprint->foreign($column)->references('id')->on('users');
                    $deleteAction === 'cascade' ? $foreign->cascadeOnDelete() : $foreign->nullOnDelete();
                });
            } catch (Throwable) {
                //
            }
        }
    }

    private function dropForeignIfPossible(string $table, string $column): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return;
        }

        try {
            Schema::table($table, function (Blueprint $tableBlueprint) use ($column) {
                $tableBlueprint->dropForeign([$column]);
            });
        } catch (Throwable) {
            //
        }
    }
};
