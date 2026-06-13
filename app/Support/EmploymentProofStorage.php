<?php

namespace App\Support;

use App\Models\TracerRecord;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class EmploymentProofStorage
{
    public const PRIVATE_DISK = 'private';
    public const LEGACY_PUBLIC_DISK = 'public';
    public const DIRECTORY = 'tracer-employment-proofs';

    public static function emptyPayload(): array
    {
        return [
            'employment_proof_disk' => null,
            'employment_proof_path' => null,
            'employment_proof_original_name' => null,
            'employment_proof_mime_type' => null,
            'employment_proof_size_bytes' => null,
            'employment_proof_uploaded_at' => null,
            'employment_proof_status' => null,
            'employment_proof_reviewed_by_guard' => null,
            'employment_proof_reviewed_by_id' => null,
            'employment_proof_reviewed_at' => null,
            'employment_proof_rejection_note' => null,
        ];
    }

    public static function payloadFromRecord(?TracerRecord $record): array
    {
        if (!$record || blank($record->employment_proof_path)) {
            return self::emptyPayload();
        }

        return [
            'employment_proof_disk' => $record->employment_proof_disk,
            'employment_proof_path' => $record->employment_proof_path,
            'employment_proof_original_name' => $record->employment_proof_original_name,
            'employment_proof_mime_type' => $record->employment_proof_mime_type,
            'employment_proof_size_bytes' => $record->employment_proof_size_bytes,
            'employment_proof_uploaded_at' => $record->employment_proof_uploaded_at,
            'employment_proof_status' => $record->employmentProofStatus(),
            'employment_proof_reviewed_by_guard' => $record->employment_proof_reviewed_by_guard,
            'employment_proof_reviewed_by_id' => $record->employment_proof_reviewed_by_id,
            'employment_proof_reviewed_at' => $record->employment_proof_reviewed_at,
            'employment_proof_rejection_note' => $record->employment_proof_rejection_note,
        ];
    }

    public static function storeUploadedFile(UploadedFile $file): array
    {
        $storedPath = $file->store(self::DIRECTORY, self::PRIVATE_DISK);

        return [
            'employment_proof_disk' => self::PRIVATE_DISK,
            'employment_proof_path' => $storedPath,
            'employment_proof_original_name' => self::trimmedOriginalName($file),
            'employment_proof_mime_type' => $file->getClientMimeType() ?: $file->getMimeType(),
            'employment_proof_size_bytes' => $file->getSize(),
            'employment_proof_uploaded_at' => now(),
            'employment_proof_status' => TracerRecord::EMPLOYMENT_PROOF_STATUS_PENDING,
            'employment_proof_reviewed_by_guard' => null,
            'employment_proof_reviewed_by_id' => null,
            'employment_proof_reviewed_at' => null,
            'employment_proof_rejection_note' => null,
        ];
    }

    public static function resolveFile(?TracerRecord $record): ?array
    {
        if (!$record || blank($record->employment_proof_path)) {
            return null;
        }

        $path = $record->employment_proof_path;
        $candidateDisks = array_filter([
            $record->employment_proof_disk,
            self::PRIVATE_DISK,
            self::LEGACY_PUBLIC_DISK,
        ]);

        foreach (array_unique($candidateDisks) as $disk) {
            if (Storage::disk($disk)->exists($path)) {
                return [
                    'disk' => $disk,
                    'path' => $path,
                    'download_name' => self::downloadName($record),
                    'mime_type' => $record->employment_proof_mime_type ?: null,
                ];
            }
        }

        return null;
    }

    public static function downloadName(TracerRecord $record): string
    {
        $originalName = trim((string) ($record->employment_proof_original_name ?? ''));
        if ($originalName !== '') {
            return $originalName;
        }

        return basename((string) $record->employment_proof_path);
    }

    protected static function trimmedOriginalName(UploadedFile $file): string
    {
        $name = trim((string) $file->getClientOriginalName());

        if ($name === '') {
            $name = $file->hashName() ?: 'employment-proof';
        }

        return mb_substr($name, 0, 255);
    }
}
