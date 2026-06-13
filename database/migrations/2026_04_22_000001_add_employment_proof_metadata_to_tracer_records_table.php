<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tracer_records', function (Blueprint $table) {
            $table->string('employment_proof_disk', 50)->nullable()->after('employment_proof_path');
            $table->string('employment_proof_original_name', 255)->nullable()->after('employment_proof_disk');
            $table->string('employment_proof_mime_type', 120)->nullable()->after('employment_proof_original_name');
            $table->unsignedBigInteger('employment_proof_size_bytes')->nullable()->after('employment_proof_mime_type');
            $table->timestamp('employment_proof_uploaded_at')->nullable()->after('employment_proof_size_bytes');
        });

        $privateDisk = Storage::disk('private');
        $publicDisk = Storage::disk('public');

        DB::table('tracer_records')
            ->select(['id', 'employment_proof_path', 'employment_proof_disk', 'created_at', 'updated_at'])
            ->whereNotNull('employment_proof_path')
            ->whereRaw("TRIM(employment_proof_path) <> ''")
            ->orderBy('id')
            ->cursor()
            ->each(function (object $record) use ($privateDisk, $publicDisk): void {
                $path = trim((string) $record->employment_proof_path);
                if ($path === '') {
                    return;
                }

                if ($publicDisk->exists($path) && !$privateDisk->exists($path)) {
                    try {
                        $privateDisk->put($path, $publicDisk->get($path));
                    } catch (\Throwable $exception) {
                        // Keep the original file in place if the move fails.
                    }
                }

                if ($privateDisk->exists($path) && $publicDisk->exists($path)) {
                    $publicDisk->delete($path);
                }

                $resolvedDisk = $privateDisk->exists($path)
                    ? 'private'
                    : ($publicDisk->exists($path)
                        ? 'public'
                        : (trim((string) ($record->employment_proof_disk ?? '')) ?: 'public'));

                $resolvedStorage = $resolvedDisk === 'private' ? $privateDisk : $publicDisk;
                $mimeType = null;
                $sizeBytes = null;

                if ($resolvedStorage->exists($path)) {
                    try {
                        $mimeType = $resolvedStorage->mimeType($path);
                    } catch (\Throwable $exception) {
                        $mimeType = null;
                    }

                    try {
                        $sizeBytes = $resolvedStorage->size($path);
                    } catch (\Throwable $exception) {
                        $sizeBytes = null;
                    }
                }

                DB::table('tracer_records')
                    ->where('id', $record->id)
                    ->update([
                        'employment_proof_disk' => $resolvedDisk,
                        'employment_proof_original_name' => basename($path),
                        'employment_proof_mime_type' => $mimeType,
                        'employment_proof_size_bytes' => $sizeBytes,
                        'employment_proof_uploaded_at' => $record->updated_at ?? $record->created_at ?? now(),
                    ]);
            });
    }

    public function down(): void
    {
        Schema::table('tracer_records', function (Blueprint $table) {
            $table->dropColumn([
                'employment_proof_disk',
                'employment_proof_original_name',
                'employment_proof_mime_type',
                'employment_proof_size_bytes',
                'employment_proof_uploaded_at',
            ]);
        });
    }
};
