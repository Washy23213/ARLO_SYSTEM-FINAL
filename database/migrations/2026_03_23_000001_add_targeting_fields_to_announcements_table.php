<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            if (!Schema::hasColumn('announcements', 'target_department')) {
                $table->string('target_department', 150)->nullable()->after('target_audience');
                $table->index('target_department');
            }

            if (!Schema::hasColumn('announcements', 'target_graduation_year')) {
                $table->unsignedSmallInteger('target_graduation_year')->nullable()->after('target_department');
                $table->index('target_graduation_year');
            }
        });
    }

    public function down(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            if (Schema::hasColumn('announcements', 'target_graduation_year')) {
                $table->dropIndex(['target_graduation_year']);
                $table->dropColumn('target_graduation_year');
            }

            if (Schema::hasColumn('announcements', 'target_department')) {
                $table->dropIndex(['target_department']);
                $table->dropColumn('target_department');
            }
        });
    }
};
