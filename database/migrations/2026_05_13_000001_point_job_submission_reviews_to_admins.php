<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            ! Schema::hasTable('job_submissions')
            || ! Schema::hasTable('admins')
            || ! Schema::hasColumn('job_submissions', 'reviewed_by')
        ) {
            return;
        }

        $this->dropReviewedByForeignKey();

        DB::table('job_submissions')
            ->whereNotNull('reviewed_by')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('admins')
                    ->whereColumn('admins.id', 'job_submissions.reviewed_by');
            })
            ->update(['reviewed_by' => null]);

        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('job_submissions', function (Blueprint $table) {
            $table->foreign('reviewed_by')
                ->references('id')
                ->on('admins')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (
            ! Schema::hasTable('job_submissions')
            || ! Schema::hasTable('users')
            || ! Schema::hasColumn('job_submissions', 'reviewed_by')
        ) {
            return;
        }

        $this->dropReviewedByForeignKey();

        DB::table('job_submissions')
            ->whereNotNull('reviewed_by')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('users')
                    ->whereColumn('users.id', 'job_submissions.reviewed_by');
            })
            ->update(['reviewed_by' => null]);

        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('job_submissions', function (Blueprint $table) {
            $table->foreign('reviewed_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    private function dropReviewedByForeignKey(): void
    {
        if (! Schema::hasTable('job_submissions') || ! Schema::hasColumn('job_submissions', 'reviewed_by')) {
            return;
        }

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE job_submissions DROP CONSTRAINT IF EXISTS job_submissions_reviewed_by_foreign');

            return;
        }

        try {
            Schema::table('job_submissions', function (Blueprint $table) {
                $table->dropForeign(['reviewed_by']);
            });
        } catch (Throwable) {
            //
        }
    }
};
