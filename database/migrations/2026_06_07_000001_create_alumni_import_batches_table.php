<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('alumni_import_batches')) {
            Schema::create('alumni_import_batches', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->string('name');
                $table->string('source_file_name')->nullable();
                $table->string('imported_by_guard', 40)->nullable();
                $table->uuid('imported_by_id')->nullable()->index();
                $table->timestampTz('imported_at')->nullable()->index();
                $table->unsignedInteger('rows_count')->default(0);
                $table->unsignedInteger('imported_count')->default(0);
                $table->unsignedInteger('updated_count')->default(0);
                $table->unsignedInteger('skipped_count')->default(0);
                $table->timestamps();
            });
        }

        if (Schema::hasTable('users') && ! Schema::hasColumn('users', 'alumni_import_batch_id')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->uuid('alumni_import_batch_id')->nullable()->index()->after('batch_imported_at');
            });
        }

        $this->backfillLegacyBatch();
    }

    public function down(): void
    {
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'alumni_import_batch_id')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->dropColumn('alumni_import_batch_id');
            });
        }

        Schema::dropIfExists('alumni_import_batches');
    }

    private function backfillLegacyBatch(): void
    {
        if (
            ! Schema::hasTable('users')
            || ! Schema::hasTable('alumni_import_batches')
            || ! Schema::hasColumn('users', 'alumni_import_batch_id')
        ) {
            return;
        }

        $legacyQuery = DB::table('users')
            ->whereNull('alumni_import_batch_id')
            ->where(function ($query): void {
                if (Schema::hasColumn('users', 'is_batch_imported')) {
                    $query->where('is_batch_imported', true);
                }

                $query
                    ->orWhereRaw("LOWER(COALESCE(email, '')) LIKE ?", ['%@oarl-batch.local'])
                    ->orWhereRaw("UPPER(COALESCE(alumni_idnum, '')) LIKE ?", ['OARL-ALU-%']);
            });

        $legacyCount = (clone $legacyQuery)->count();

        if ($legacyCount === 0) {
            return;
        }

        $batchId = (string) Str::uuid();
        $now = now();

        DB::table('alumni_import_batches')->insert([
            'id' => $batchId,
            'name' => 'Legacy imported alumni',
            'source_file_name' => 'Existing imported records',
            'imported_at' => $now,
            'rows_count' => $legacyCount,
            'imported_count' => $legacyCount,
            'updated_count' => 0,
            'skipped_count' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $legacyQuery->update(['alumni_import_batch_id' => $batchId]);
    }
};
