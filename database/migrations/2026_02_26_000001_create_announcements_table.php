<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('announcements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title', 255);
            $table->text('content');
            $table->string('type', 50)->nullable();
            $table->string('priority', 20)->default('Medium');
            $table->string('target_audience', 100)->default('All');
            $table->string('image_url', 500)->nullable();
            $table->string('attachment_url', 500)->nullable();
            $table->timestampTz('event_date')->nullable();
            $table->string('event_location', 255)->nullable();
            $table->boolean('is_published')->default(false);
            $table->timestampTz('published_at')->nullable();
            $table->timestampTz('expires_at')->nullable();
            $table->unsignedInteger('views_count')->default(0);
            $table->uuid('created_by')->nullable();
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcements');
    }
};
