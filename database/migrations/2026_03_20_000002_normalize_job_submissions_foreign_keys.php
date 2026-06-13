<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            DB::table('job_submissions')
                ->whereRaw("user_id !~* '^[0-9a-f-]{36}$'")
                ->delete();

            DB::table('job_submissions')
                ->whereRaw("user_id ~* '^[0-9a-f-]{36}$'")
                ->whereNotExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('users')
                        ->whereRaw('users.id = job_submissions.user_id::uuid');
                })
                ->delete();

            DB::table('job_submissions')
                ->whereNotNull('reviewed_by')
                ->whereRaw("reviewed_by !~* '^[0-9a-f-]{36}$'")
                ->update(['reviewed_by' => null]);

            DB::table('job_submissions')
                ->whereNotNull('reviewed_by')
                ->whereRaw("reviewed_by ~* '^[0-9a-f-]{36}$'")
                ->whereNotExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('users')
                        ->whereRaw('users.id = job_submissions.reviewed_by::uuid');
                })
                ->update(['reviewed_by' => null]);
        } else {
            DB::table('job_submissions')
                ->whereNotNull('user_id')
                ->whereNotExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('users')
                        ->whereColumn('users.id', 'job_submissions.user_id');
                })
                ->delete();

            DB::table('job_submissions')
                ->whereNotNull('reviewed_by')
                ->whereNotExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('users')
                        ->whereColumn('users.id', 'job_submissions.reviewed_by');
                })
                ->update(['reviewed_by' => null]);
        }

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE job_submissions ALTER COLUMN user_id TYPE uuid USING user_id::uuid');
            DB::statement('ALTER TABLE job_submissions ALTER COLUMN reviewed_by TYPE uuid USING NULLIF(reviewed_by, \'\')::uuid');
        } elseif ($driver === 'sqlite') {
            Schema::create('job_submissions_tmp', function (Blueprint $table) {
                $table->id();
                $table->uuid('user_id')->index();
                $table->string('title');
                $table->string('company');
                $table->string('type')->nullable();
                $table->string('location')->nullable();
                $table->string('contact_email');
                $table->date('application_deadline')->nullable();
                $table->text('description');
                $table->text('requirements')->nullable();
                $table->string('status')->default('pending')->index();
                $table->text('admin_notes')->nullable();
                $table->uuid('reviewed_by')->nullable();
                $table->timestamp('reviewed_at')->nullable();
                $table->timestamps();
            });

            DB::table('job_submissions_tmp')->insertUsing(
                [
                    'id',
                    'user_id',
                    'title',
                    'company',
                    'type',
                    'location',
                    'contact_email',
                    'application_deadline',
                    'description',
                    'requirements',
                    'status',
                    'admin_notes',
                    'reviewed_by',
                    'reviewed_at',
                    'created_at',
                    'updated_at',
                ],
                DB::table('job_submissions')->select(
                    'id',
                    'user_id',
                    'title',
                    'company',
                    'type',
                    'location',
                    'contact_email',
                    'application_deadline',
                    'description',
                    'requirements',
                    'status',
                    'admin_notes',
                    'reviewed_by',
                    'reviewed_at',
                    'created_at',
                    'updated_at',
                )
            );

            Schema::drop('job_submissions');
            Schema::rename('job_submissions_tmp', 'job_submissions');
        }

        Schema::table('job_submissions', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('reviewed_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('job_submissions', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['reviewed_by']);
        });

        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE job_submissions ALTER COLUMN user_id TYPE varchar(255) USING user_id::text');
            DB::statement('ALTER TABLE job_submissions ALTER COLUMN reviewed_by TYPE varchar(255) USING reviewed_by::text');
        } elseif ($driver === 'sqlite') {
            Schema::create('job_submissions_tmp', function (Blueprint $table) {
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
                $table->string('status')->default('pending')->index();
                $table->text('admin_notes')->nullable();
                $table->string('reviewed_by')->nullable();
                $table->timestamp('reviewed_at')->nullable();
                $table->timestamps();
            });

            DB::table('job_submissions_tmp')->insertUsing(
                [
                    'id',
                    'user_id',
                    'title',
                    'company',
                    'type',
                    'location',
                    'contact_email',
                    'application_deadline',
                    'description',
                    'requirements',
                    'status',
                    'admin_notes',
                    'reviewed_by',
                    'reviewed_at',
                    'created_at',
                    'updated_at',
                ],
                DB::table('job_submissions')->select(
                    'id',
                    DB::raw('CAST(user_id AS TEXT) as user_id'),
                    'title',
                    'company',
                    'type',
                    'location',
                    'contact_email',
                    'application_deadline',
                    'description',
                    'requirements',
                    'status',
                    'admin_notes',
                    DB::raw('CAST(reviewed_by AS TEXT) as reviewed_by'),
                    'reviewed_at',
                    'created_at',
                    'updated_at',
                )
            );

            Schema::drop('job_submissions');
            Schema::rename('job_submissions_tmp', 'job_submissions');
        }
    }
};
