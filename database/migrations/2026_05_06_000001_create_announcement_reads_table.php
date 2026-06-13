<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('announcement_reads')) {
            return;
        }

        Schema::create('announcement_reads', function (Blueprint $table) {
            $table->id();
            $table->uuid('announcement_id');
            $table->uuid('user_id');
            $table->timestampTz('read_at')->nullable();
            $table->timestampsTz();

            $table->unique(['announcement_id', 'user_id']);
            $table->index(['user_id', 'read_at']);
            $table->foreign('announcement_id')->references('id')->on('announcements')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcement_reads');
    }
};
