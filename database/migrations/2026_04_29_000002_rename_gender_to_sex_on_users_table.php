<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('users', 'gender')) {
            if (! Schema::hasColumn('users', 'sex')) {
                Schema::table('users', function (Blueprint $table) {
                    $table->renameColumn('gender', 'sex');
                });

                return;
            }

            DB::table('users')
                ->whereNull('sex')
                ->update(['sex' => DB::raw('gender')]);

            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('gender');
            });

            return;
        }

        if (! Schema::hasColumn('users', 'sex')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('sex', 20)->nullable()->after('phone');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'sex')) {
            if (! Schema::hasColumn('users', 'gender')) {
                Schema::table('users', function (Blueprint $table) {
                    $table->renameColumn('sex', 'gender');
                });

                return;
            }

            DB::table('users')
                ->whereNull('gender')
                ->update(['gender' => DB::raw('sex')]);

            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('sex');
            });
        }
    }
};
