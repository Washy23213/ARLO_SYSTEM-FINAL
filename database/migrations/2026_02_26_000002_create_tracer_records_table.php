<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tracer_records', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->date('tracer_date');
            $table->boolean('is_employed')->nullable();
            $table->string('employment_type', 50)->nullable();
            $table->string('current_position', 200)->nullable();
            $table->string('current_company', 200)->nullable();
            $table->string('monthly_income', 100)->nullable();
            $table->string('industry', 100)->nullable();
            $table->unsignedTinyInteger('job_satisfaction')->nullable();
            $table->text('skills_used')->nullable();
            $table->boolean('further_education')->default(false);
            $table->string('education_level', 100)->nullable();
            $table->text('challenges_faced')->nullable();
            $table->text('recommendations')->nullable();
            $table->boolean('contact_permission')->default(true);
            $table->timestampsTz();

            $table->unique(['user_id', 'tracer_date']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tracer_records');
    }
};
