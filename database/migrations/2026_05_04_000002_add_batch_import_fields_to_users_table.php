<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'alumni_idnum')) {
                $table->string('alumni_idnum', 100)->nullable()->index();
            }

            if (! Schema::hasColumn('users', 'student_idnum')) {
                $table->string('student_idnum', 100)->nullable()->index();
            }

            if (! Schema::hasColumn('users', 'middlename')) {
                $table->string('middlename', 100)->nullable();
            }

            if (! Schema::hasColumn('users', 'course')) {
                $table->string('course', 200)->nullable();
            }

            if (! Schema::hasColumn('users', 'major')) {
                $table->string('major', 200)->nullable();
            }

            if (! Schema::hasColumn('users', 'dt_grad')) {
                $table->date('dt_grad')->nullable();
            }

            if (! Schema::hasColumn('users', 'is_batch_imported')) {
                $table->boolean('is_batch_imported')->default(false)->index();
            }

            if (! Schema::hasColumn('users', 'batch_imported_at')) {
                $table->timestampTz('batch_imported_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            foreach ([
                'batch_imported_at',
                'is_batch_imported',
                'dt_grad',
                'major',
                'course',
                'middlename',
                'student_idnum',
                'alumni_idnum',
            ] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
