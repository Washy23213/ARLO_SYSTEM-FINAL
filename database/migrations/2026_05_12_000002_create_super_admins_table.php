<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('super_admins')) {
            Schema::create('super_admins', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('firstname')->nullable();
                $table->string('lastname')->nullable();
                $table->string('email')->unique();
                $table->string('password');
                $table->string('phone', 30)->nullable();
                $table->timestamp('email_verified_at')->nullable();
                $table->rememberToken();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('system_logs') && ! Schema::hasColumn('system_logs', 'super_admin_id')) {
            Schema::table('system_logs', function (Blueprint $table) {
                $table->uuid('super_admin_id')->nullable()->after('admin_id')->index();
            });
        }

        $this->moveSuperAdminRowsOutOfAdmins();
        $this->addSuperAdminForeignKeys();
        $this->tightenAdminsRoleConstraint();
    }

    public function down(): void
    {
        $this->loosenAdminsRoleConstraint();
        $this->dropSuperAdminForeignKeys();
        $this->moveSuperAdminRowsBackToAdmins();

        if (Schema::hasTable('system_logs') && Schema::hasColumn('system_logs', 'super_admin_id')) {
            Schema::table('system_logs', function (Blueprint $table) {
                $table->dropColumn('super_admin_id');
            });
        }

        Schema::dropIfExists('super_admins');
    }

    private function moveSuperAdminRowsOutOfAdmins(): void
    {
        if (! Schema::hasTable('admins') || ! Schema::hasTable('super_admins')) {
            return;
        }

        $superAdmins = DB::table('admins')
            ->where('role', 'super_admin')
            ->orderBy('created_at')
            ->get();

        if ($superAdmins->isEmpty()) {
            DB::table('admins')
                ->where('role', '!=', 'admin')
                ->update(['role' => 'admin']);

            return;
        }

        $superAdminIds = $superAdmins->pluck('id')->all();

        foreach ($superAdmins as $admin) {
            DB::table('super_admins')->updateOrInsert(
                ['email' => $admin->email],
                [
                    'id' => $admin->id,
                    'firstname' => $admin->firstname,
                    'lastname' => $admin->lastname,
                    'email' => $admin->email,
                    'password' => $admin->password,
                    'phone' => $admin->phone ?? null,
                    'email_verified_at' => $admin->email_verified_at,
                    'remember_token' => $admin->remember_token ?? Str::random(10),
                    'created_at' => $admin->created_at ?? now(),
                    'updated_at' => now(),
                ]
            );
        }

        if (
            Schema::hasTable('system_logs')
            && Schema::hasColumn('system_logs', 'admin_id')
            && Schema::hasColumn('system_logs', 'super_admin_id')
        ) {
            DB::table('system_logs')
                ->whereIn('admin_id', $superAdminIds)
                ->update([
                    'super_admin_id' => DB::raw('admin_id'),
                    'admin_id' => null,
                ]);
        }

        foreach ($this->adminOwnedColumns() as [$table, $column]) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
                continue;
            }

            DB::table($table)
                ->whereIn($column, $superAdminIds)
                ->update([$column => null]);
        }

        DB::table('admins')
            ->whereIn('id', $superAdminIds)
            ->delete();

        DB::table('admins')
            ->where('role', '!=', 'admin')
            ->update(['role' => 'admin']);
    }

    private function moveSuperAdminRowsBackToAdmins(): void
    {
        if (! Schema::hasTable('admins') || ! Schema::hasTable('super_admins')) {
            return;
        }

        $superAdmins = DB::table('super_admins')->orderBy('created_at')->get();

        foreach ($superAdmins as $superAdmin) {
            DB::table('admins')->updateOrInsert(
                ['email' => $superAdmin->email],
                [
                    'id' => $superAdmin->id,
                    'firstname' => $superAdmin->firstname,
                    'lastname' => $superAdmin->lastname,
                    'email' => $superAdmin->email,
                    'password' => $superAdmin->password,
                    'phone' => $superAdmin->phone ?? null,
                    'role' => 'super_admin',
                    'email_verified_at' => $superAdmin->email_verified_at,
                    'remember_token' => $superAdmin->remember_token ?? Str::random(10),
                    'created_at' => $superAdmin->created_at ?? now(),
                    'updated_at' => now(),
                ]
            );
        }

        if (
            Schema::hasTable('system_logs')
            && Schema::hasColumn('system_logs', 'admin_id')
            && Schema::hasColumn('system_logs', 'super_admin_id')
        ) {
            DB::table('system_logs')
                ->whereNull('admin_id')
                ->whereNotNull('super_admin_id')
                ->update(['admin_id' => DB::raw('super_admin_id')]);
        }
    }

    private function tightenAdminsRoleConstraint(): void
    {
        if (DB::getDriverName() !== 'pgsql' || ! Schema::hasTable('admins')) {
            return;
        }

        DB::statement('ALTER TABLE admins DROP CONSTRAINT IF EXISTS admins_role_check');
        DB::statement("ALTER TABLE admins ADD CONSTRAINT admins_role_check CHECK (role = 'admin')");
    }

    private function loosenAdminsRoleConstraint(): void
    {
        if (DB::getDriverName() !== 'pgsql' || ! Schema::hasTable('admins')) {
            return;
        }

        DB::statement('ALTER TABLE admins DROP CONSTRAINT IF EXISTS admins_role_check');
        DB::statement("ALTER TABLE admins ADD CONSTRAINT admins_role_check CHECK (role IN ('super_admin', 'admin'))");
    }

    private function addSuperAdminForeignKeys(): void
    {
        if (
            DB::getDriverName() === 'sqlite'
            || ! Schema::hasTable('system_logs')
            || ! Schema::hasColumn('system_logs', 'super_admin_id')
        ) {
            return;
        }

        try {
            Schema::table('system_logs', function (Blueprint $table) {
                $table->foreign('super_admin_id')->references('id')->on('super_admins')->nullOnDelete();
            });
        } catch (Throwable) {
            //
        }
    }

    private function dropSuperAdminForeignKeys(): void
    {
        if (! Schema::hasTable('system_logs') || ! Schema::hasColumn('system_logs', 'super_admin_id')) {
            return;
        }

        try {
            Schema::table('system_logs', function (Blueprint $table) {
                $table->dropForeign(['super_admin_id']);
            });
        } catch (Throwable) {
            //
        }
    }

    private function adminOwnedColumns(): array
    {
        return [
            ['jobs', 'created_by'],
            ['jobs', 'archived_by'],
            ['announcements', 'created_by'],
            ['announcements', 'archived_by'],
            ['feedback', 'assigned_to'],
            ['job_applications', 'reviewed_by'],
            ['job_submissions', 'reviewed_by'],
        ];
    }
};
