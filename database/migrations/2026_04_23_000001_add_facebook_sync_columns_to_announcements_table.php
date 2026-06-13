<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            if (! Schema::hasColumn('announcements', 'facebook_post_id')) {
                $table->string('facebook_post_id', 191)->nullable()->unique()->after('attachment_url');
            }

            if (! Schema::hasColumn('announcements', 'facebook_post_url')) {
                $table->string('facebook_post_url', 500)->nullable()->after('facebook_post_id');
            }

            if (! Schema::hasColumn('announcements', 'facebook_last_synced_at')) {
                $table->timestampTz('facebook_last_synced_at')->nullable()->after('facebook_post_url');
            }
        });
    }

    public function down(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            if (Schema::hasColumn('announcements', 'facebook_last_synced_at')) {
                $table->dropColumn('facebook_last_synced_at');
            }

            if (Schema::hasColumn('announcements', 'facebook_post_url')) {
                $table->dropColumn('facebook_post_url');
            }

            if (Schema::hasColumn('announcements', 'facebook_post_id')) {
                $table->dropUnique(['facebook_post_id']);
                $table->dropColumn('facebook_post_id');
            }
        });
    }
};
