<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jobs', function (Blueprint $table) {
            if (!Schema::hasColumn('jobs', 'archived_at')) {
                $table->timestamp('archived_at')->nullable()->after('updated_at');
            }
            if (!Schema::hasColumn('jobs', 'archived_by')) {
                $table->uuid('archived_by')->nullable()->after('archived_at');
            }
        });

        Schema::table('announcements', function (Blueprint $table) {
            if (!Schema::hasColumn('announcements', 'archived_at')) {
                $table->timestampTz('archived_at')->nullable()->after('updated_at');
            }
            if (!Schema::hasColumn('announcements', 'archived_by')) {
                $table->uuid('archived_by')->nullable()->after('archived_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('jobs', function (Blueprint $table) {
            if (Schema::hasColumn('jobs', 'archived_by')) {
                $table->dropColumn('archived_by');
            }
            if (Schema::hasColumn('jobs', 'archived_at')) {
                $table->dropColumn('archived_at');
            }
        });

        Schema::table('announcements', function (Blueprint $table) {
            if (Schema::hasColumn('announcements', 'archived_by')) {
                $table->dropColumn('archived_by');
            }
            if (Schema::hasColumn('announcements', 'archived_at')) {
                $table->dropColumn('archived_at');
            }
        });
    }
};
