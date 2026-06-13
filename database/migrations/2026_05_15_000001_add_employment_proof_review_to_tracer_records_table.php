<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tracer_records', function (Blueprint $table) {
            $table->string('employment_proof_status', 20)->nullable()->after('employment_proof_uploaded_at')->index();
            $table->string('employment_proof_reviewed_by_guard', 30)->nullable()->after('employment_proof_status');
            $table->uuid('employment_proof_reviewed_by_id')->nullable()->after('employment_proof_reviewed_by_guard')->index();
            $table->timestamp('employment_proof_reviewed_at')->nullable()->after('employment_proof_reviewed_by_id');
            $table->text('employment_proof_rejection_note')->nullable()->after('employment_proof_reviewed_at');
        });

        DB::table('tracer_records')
            ->whereNotNull('employment_proof_path')
            ->whereRaw("TRIM(COALESCE(employment_proof_path, '')) <> ''")
            ->whereNull('employment_proof_status')
            ->update(['employment_proof_status' => 'pending']);
    }

    public function down(): void
    {
        Schema::table('tracer_records', function (Blueprint $table) {
            $table->dropColumn([
                'employment_proof_status',
                'employment_proof_reviewed_by_guard',
                'employment_proof_reviewed_by_id',
                'employment_proof_reviewed_at',
                'employment_proof_rejection_note',
            ]);
        });
    }
};
