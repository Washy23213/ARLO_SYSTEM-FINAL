<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->deleteOrphanedRows();

        Schema::table('jobs', function (Blueprint $table) {
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('archived_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('announcements', function (Blueprint $table) {
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('archived_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('tracer_records', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::table('feedback', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('assigned_to')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('job_applications', function (Blueprint $table) {
            $table->foreign('job_id')->references('id')->on('jobs')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('reviewed_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('system_logs', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('sessions', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sessions', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        Schema::table('system_logs', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        Schema::table('job_applications', function (Blueprint $table) {
            $table->dropForeign(['job_id']);
            $table->dropForeign(['user_id']);
            $table->dropForeign(['reviewed_by']);
        });

        Schema::table('feedback', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['assigned_to']);
        });

        Schema::table('tracer_records', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        Schema::table('announcements', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropForeign(['archived_by']);
        });

        Schema::table('jobs', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropForeign(['archived_by']);
        });
    }

    private function deleteOrphanedRows(): void
    {
        DB::table('tracer_records')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('users')
                    ->whereColumn('users.id', 'tracer_records.user_id');
            })
            ->delete();

        DB::table('feedback')
            ->whereNotNull('user_id')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('users')
                    ->whereColumn('users.id', 'feedback.user_id');
            })
            ->delete();

        DB::table('feedback')
            ->whereNotNull('assigned_to')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('users')
                    ->whereColumn('users.id', 'feedback.assigned_to');
            })
            ->update(['assigned_to' => null]);

        DB::table('job_applications')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('jobs')
                    ->whereColumn('jobs.id', 'job_applications.job_id');
            })
            ->delete();

        DB::table('job_applications')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('users')
                    ->whereColumn('users.id', 'job_applications.user_id');
            })
            ->delete();

        DB::table('job_applications')
            ->whereNotNull('reviewed_by')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('users')
                    ->whereColumn('users.id', 'job_applications.reviewed_by');
            })
            ->update(['reviewed_by' => null]);

        DB::table('jobs')
            ->whereNotNull('created_by')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('users')
                    ->whereColumn('users.id', 'jobs.created_by');
            })
            ->update(['created_by' => null]);

        DB::table('jobs')
            ->whereNotNull('archived_by')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('users')
                    ->whereColumn('users.id', 'jobs.archived_by');
            })
            ->update(['archived_by' => null]);

        DB::table('announcements')
            ->whereNotNull('created_by')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('users')
                    ->whereColumn('users.id', 'announcements.created_by');
            })
            ->update(['created_by' => null]);

        DB::table('announcements')
            ->whereNotNull('archived_by')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('users')
                    ->whereColumn('users.id', 'announcements.archived_by');
            })
            ->update(['archived_by' => null]);

        DB::table('system_logs')
            ->whereNotNull('user_id')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('users')
                    ->whereColumn('users.id', 'system_logs.user_id');
            })
            ->update(['user_id' => null]);

        DB::table('sessions')
            ->whereNotNull('user_id')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('users')
                    ->whereColumn('users.id', 'sessions.user_id');
            })
            ->delete();
    }
};
