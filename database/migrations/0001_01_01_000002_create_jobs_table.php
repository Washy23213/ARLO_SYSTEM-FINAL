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
        Schema::create('jobs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title', 255);
            $table->string('company', 255);
            $table->text('description')->nullable();
            $table->text('requirements')->nullable();
            $table->string('location', 255)->nullable();
            $table->string('type', 50)->nullable();
            $table->decimal('salary_min', 10, 2)->nullable();
            $table->decimal('salary_max', 10, 2)->nullable();
            $table->string('salary_range', 100)->nullable();
            $table->date('application_deadline')->nullable();
            $table->string('contact_email', 255)->nullable();
            $table->string('contact_phone', 30)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('views_count')->default(0);
            $table->unsignedInteger('applications_count')->default(0);
            $table->uuid('created_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jobs');
    }
};
