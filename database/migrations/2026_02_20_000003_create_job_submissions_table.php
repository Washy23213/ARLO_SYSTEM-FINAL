<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('job_submissions', function (Blueprint $table) {
            $table->id();
            $table->string('user_id')->index();
            $table->string('title');
            $table->string('company');
            $table->string('type')->nullable();
            $table->string('location')->nullable();
            $table->string('contact_email');
            $table->date('application_deadline')->nullable();
            $table->text('description');
            $table->text('requirements')->nullable();
            $table->string('status')->default('pending')->index(); // pending | approved | rejected
            $table->text('admin_notes')->nullable();
            $table->string('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_submissions');
    }
};
