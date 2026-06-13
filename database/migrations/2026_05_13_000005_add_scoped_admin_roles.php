<?php

use App\Models\Admin;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql' || ! Schema::hasTable('admins')) {
            return;
        }

        $allowedRoles = collect(Admin::ROLES)
            ->map(fn (string $role): string => "'{$role}'")
            ->implode(', ');

        DB::statement('ALTER TABLE admins DROP CONSTRAINT IF EXISTS admins_role_check');
        DB::statement("ALTER TABLE admins ADD CONSTRAINT admins_role_check CHECK (role IN ({$allowedRoles}))");
    }

    public function down(): void
    {
        if (! Schema::hasTable('admins')) {
            return;
        }

        DB::table('admins')
            ->whereIn('role', [Admin::ROLE_ALUMNI_RECORDS_MANAGER, Admin::ROLE_JOB_MANAGER])
            ->update(['role' => Admin::ROLE_ADMIN]);

        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE admins DROP CONSTRAINT IF EXISTS admins_role_check');
        DB::statement("ALTER TABLE admins ADD CONSTRAINT admins_role_check CHECK (role = 'admin')");
    }
};
