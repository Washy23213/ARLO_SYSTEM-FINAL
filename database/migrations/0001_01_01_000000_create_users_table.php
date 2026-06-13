<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('CREATE EXTENSION IF NOT EXISTS "uuid-ossp"');
        }

        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('firstname', 100);
            $table->string('lastname', 100);
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('phone', 20)->nullable();
            $table->string('department', 100)->nullable();
            $table->string('degree', 200)->nullable();
            $table->integer('year_graduated')->nullable();
            $table->string('employed_after_grad', 10)->nullable();
            $table->string('industry', 100)->nullable();
            $table->string('current_position', 200)->nullable();
            $table->string('company', 200)->nullable();
            $table->string('location', 200)->nullable();
            $table->string('salary_range', 50)->nullable();
            $table->string('linkedin_profile', 500)->nullable();
            $table->text('skills')->nullable();
            $table->text('achievements')->nullable();
            $table->boolean('is_admin')->default(false);
            $table->string('profile_visibility', 20)->default('public_basic');
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->uuid('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
