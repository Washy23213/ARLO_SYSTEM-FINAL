<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'newsletter_opt_in')) {
            Schema::table('users', function (Blueprint $table) {
                $column = $table->boolean('newsletter_opt_in')->default(true);

                if (Schema::hasColumn('users', 'data_privacy_consented_at')) {
                    $column->after('data_privacy_consented_at');
                }
            });
        }

        if (Schema::hasColumn('users', 'newsletter_opt_in')) {
            DB::table('users')->update(['newsletter_opt_in' => true]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'newsletter_opt_in')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('newsletter_opt_in');
            });
        }
    }
};
