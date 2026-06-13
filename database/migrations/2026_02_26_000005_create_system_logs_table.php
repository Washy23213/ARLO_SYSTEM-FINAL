<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->nullable();
            $table->string('action', 255);
            $table->text('description')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestampTz('created_at')->useCurrent();
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_logs');
    }
};
