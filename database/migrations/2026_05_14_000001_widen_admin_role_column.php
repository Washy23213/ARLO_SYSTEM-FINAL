<?php

use App\Models\Admin;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('admins') || ! Schema::hasColumn('admins', 'role')) {
            return;
        }

        $allowedRoles = collect(Admin::ROLES)
            ->map(fn (string $role): string => "'{$role}'")
            ->implode(', ');

        match (DB::getDriverName()) {
            'pgsql' => [
                DB::statement('ALTER TABLE admins ALTER COLUMN role TYPE VARCHAR(40)'),
                DB::statement('ALTER TABLE admins DROP CONSTRAINT IF EXISTS admins_role_check'),
                DB::statement("ALTER TABLE admins ADD CONSTRAINT admins_role_check CHECK (role IN ({$allowedRoles}))"),
            ],
            'mysql', 'mariadb' => DB::statement("ALTER TABLE admins MODIFY role VARCHAR(40) NOT NULL DEFAULT 'admin'"),
            'sqlsrv' => DB::statement('ALTER TABLE admins ALTER COLUMN role NVARCHAR(40) NOT NULL'),
            default => null,
        };
    }

    public function down(): void
    {
        if (! Schema::hasTable('admins') || ! Schema::hasColumn('admins', 'role')) {
            return;
        }

        DB::table('admins')
            ->whereIn('role', [Admin::ROLE_ALUMNI_RECORDS_MANAGER])
            ->update(['role' => Admin::ROLE_ADMIN]);

        match (DB::getDriverName()) {
            'pgsql' => [
                DB::statement('ALTER TABLE admins DROP CONSTRAINT IF EXISTS admins_role_check'),
                DB::statement("ALTER TABLE admins ADD CONSTRAINT admins_role_check CHECK (role = 'admin')"),
                DB::statement('ALTER TABLE admins ALTER COLUMN role TYPE VARCHAR(20)'),
            ],
            'mysql', 'mariadb' => DB::statement("ALTER TABLE admins MODIFY role VARCHAR(20) NOT NULL DEFAULT 'admin'"),
            'sqlsrv' => DB::statement('ALTER TABLE admins ALTER COLUMN role NVARCHAR(20) NOT NULL'),
            default => null,
        };
    }
};
