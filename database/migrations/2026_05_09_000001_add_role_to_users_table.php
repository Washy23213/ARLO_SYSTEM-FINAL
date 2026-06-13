<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'role')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('role', 20)
                    ->default('alumni')
                    ->after('is_admin')
                    ->index();
            });
        }

        DB::table('users')
            ->where('is_admin', true)
            ->update(['role' => 'admin']);

        DB::table('users')
            ->where(function ($query) {
                $query->where('is_admin', false)
                    ->orWhereNull('is_admin');
            })
            ->update(['role' => 'alumni']);

        if (! DB::table('users')->where('role', 'super_admin')->exists()) {
            $firstAdminId = DB::table('users')
                ->where('role', 'admin')
                ->orderBy('created_at')
                ->value('id');

            if ($firstAdminId !== null) {
                DB::table('users')
                    ->where('id', $firstAdminId)
                    ->update([
                        'role' => 'super_admin',
                        'is_admin' => true,
                    ]);
            }
        }

        DB::table('users')
            ->whereIn('role', ['super_admin', 'admin'])
            ->update(['is_admin' => true]);

        DB::table('users')
            ->where('role', 'alumni')
            ->update(['is_admin' => false]);

        if (DB::getDriverName() === 'pgsql') {
            DB::statement(
                "ALTER TABLE users ADD CONSTRAINT users_role_check CHECK (role IN ('super_admin', 'admin', 'alumni'))"
            );
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_role_check');
        }

        if (Schema::hasColumn('users', 'role')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropIndex(['role']);
                $table->dropColumn('role');
            });
        }
    }
};
