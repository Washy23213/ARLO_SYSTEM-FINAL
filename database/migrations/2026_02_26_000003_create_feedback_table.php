<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feedback', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->nullable();
            $table->string('subject', 255);
            $table->text('message');
            $table->string('type', 50)->nullable();
            $table->string('status', 50)->default('Open');
            $table->string('priority', 20)->default('Medium');
            $table->uuid('assigned_to')->nullable();
            $table->timestampTz('resolved_at')->nullable();
            $table->timestampsTz();

            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feedback');
    }
};
