<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_applications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('job_id');
            $table->uuid('user_id');
            $table->text('cover_letter')->nullable();
            $table->string('resume_url', 500)->nullable();
            $table->string('status', 50)->default('Pending');
            $table->timestampTz('applied_at')->useCurrent();
            $table->timestampTz('reviewed_at')->nullable();
            $table->uuid('reviewed_by')->nullable();
            $table->text('notes')->nullable();
            $table->timestampsTz();

            $table->unique(['job_id', 'user_id']);
            $table->index('job_id');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_applications');
    }
};
