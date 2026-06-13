<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('jobs')) {
            return;
        }

        Schema::table('jobs', function (Blueprint $table) {
            if (Schema::hasColumn('jobs', 'salary_min')) {
                $table->dropColumn('salary_min');
            }

            if (Schema::hasColumn('jobs', 'salary_max')) {
                $table->dropColumn('salary_max');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('jobs')) {
            return;
        }

        Schema::table('jobs', function (Blueprint $table) {
            if (! Schema::hasColumn('jobs', 'salary_min')) {
                $table->decimal('salary_min', 10, 2)->nullable();
            }

            if (! Schema::hasColumn('jobs', 'salary_max')) {
                $table->decimal('salary_max', 10, 2)->nullable();
            }
        });
    }
};
