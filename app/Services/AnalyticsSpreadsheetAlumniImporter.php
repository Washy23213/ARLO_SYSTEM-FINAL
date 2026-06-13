<?php

namespace App\Services;

use App\Models\TracerRecord;
use App\Models\User;
use App\Support\SystemOptions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class AnalyticsSpreadsheetAlumniImporter
{
    /**
 * Columns expected in the Excel-compatible CSV template.
     *
     * These map directly to the user/tracer fields used by admin analytics.
     * Import notes are written to system_logs so they appear in Audit Logs.
     */
    public const COLUMNS = [
        'email',
        'password',
        'firstname',
        'lastname',
        'phone',
        'sex',
        'college',
        'degree',
        'graduation_year',
        'employment_status',
        'industry',
        'location',
        'salary_range',
        'current_position',
        'company',
        'skills',
        'achievements',
        'tracer_date',
        'employment_type',
        'present_employment_status',
        'monthly_income',
        'curriculum_relevant_to_first_job',
        'time_to_first_job',
        'facilities_satisfaction',
        'teaching_faculty_enthusiastic',
        'teaching_concepts_clear',
        'teaching_faculty_knowledgeable',
        'teaching_commitment',
        'degree_program_quality',
        'overall_recommend_institution',
        'overall_objectives_met',
        'overall_university_experience',
        'audit_action',
        'audit_description',
    ];

    public function import(string $path): array
    {
        $rows = $this->readCsvRows($path);
        $imported = 0;
        $updated = 0;
        $skipped = [];

        DB::transaction(function () use ($rows, &$imported, &$updated, &$skipped) {
            foreach ($rows as $index => $row) {
                $email = $this->clean($row['email'] ?? null);

                if ($email === null || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $skipped[] = [
                        'row' => $index + 2,
                        'reason' => 'Missing or invalid email.',
                    ];

                    continue;
                }

                $existingUser = User::where('email', $email)->first();
                $user = $this->upsertUser($row, $email);
                $this->upsertTracerRecord($row, $user);
                $this->upsertAuditLog($row, $user, $existingUser !== null);

                $existingUser ? $updated++ : $imported++;
            }
        });

        Cache::forget('admin-analytics:payload:v16');
        Cache::forget('admin-analytics:payload:v17');

        return [
            'imported' => $imported,
            'updated' => $updated,
            'skipped' => $skipped,
            'total_rows' => count($rows),
        ];
    }

    protected function readCsvRows(string $path): array
    {
        $resolvedPath = $this->resolvePath($path);

        if (! is_file($resolvedPath)) {
            throw new RuntimeException("Import file not found: {$path}");
        }

        $handle = fopen($resolvedPath, 'rb');
        if ($handle === false) {
            throw new RuntimeException("Unable to open import file: {$path}");
        }

        $header = fgetcsv($handle);
        if (! is_array($header)) {
            fclose($handle);

            return [];
        }

        $keys = array_map(fn ($value) => $this->normalizeHeader((string) $value), $header);
        $rows = [];

        while (($values = fgetcsv($handle)) !== false) {
            $row = [];

            foreach ($keys as $index => $key) {
                if ($key === '') {
                    continue;
                }

                $row[$key] = $this->clean($values[$index] ?? null);
            }

            if ($this->rowHasValues($row)) {
                $rows[] = $row;
            }
        }

        fclose($handle);

        return $rows;
    }

    protected function resolvePath(string $path): string
    {
        $path = trim($path);

        if (preg_match('/^[A-Za-z]:[\\\\\\/]/', $path) || str_starts_with($path, '/') || str_starts_with($path, '\\')) {
            return $path;
        }

        return base_path($path);
    }

    protected function upsertUser(array $row, string $email): User
    {
        $employmentStatus = $this->normalizeEmploymentStatus($row['employment_status'] ?? $row['employed_after_grad'] ?? null);

        $updates = [
            'firstname' => $this->clean($row['firstname'] ?? null) ?: 'Dummy',
            'lastname' => $this->clean($row['lastname'] ?? null) ?: 'Alumni',
            'password' => $this->clean($row['password'] ?? null) ?: 'password',
            'phone' => $this->clean($row['phone'] ?? null),
            'sex' => $this->normalizeSex($row['sex'] ?? null),
            'department' => $this->clean($row['college'] ?? $row['department'] ?? null),
            'degree' => $this->clean($row['degree'] ?? null),
            'year_graduated' => $this->integerOrNull($row['graduation_year'] ?? $row['year_graduated'] ?? null),
            'employed_after_grad' => $employmentStatus,
            'industry' => $this->clean($row['industry'] ?? null),
            'current_position' => $this->clean($row['current_position'] ?? null),
            'company' => $this->clean($row['company'] ?? null),
            'location' => $this->clean($row['location'] ?? null),
            'salary_range' => SystemOptions::salaryRangeLabel($this->clean($row['salary_range'] ?? null)),
            'skills' => $this->clean($row['skills'] ?? null),
            'achievements' => $this->clean($row['achievements'] ?? null),
            'role' => User::ROLE_ALUMNI,
            'is_admin' => false,
            'email_verified_at' => now(),
            'data_privacy_consent' => true,
            'data_privacy_consented_at' => now(),
        ];

        if (Schema::hasColumn('users', 'newsletter_opt_in')) {
            $updates['newsletter_opt_in'] = true;
        }

        return User::updateOrCreate(['email' => $email], $updates);
    }

    protected function upsertTracerRecord(array $row, User $user): void
    {
        $tracerDate = $this->dateOrToday($row['tracer_date'] ?? null);
        $employmentType = $this->clean($row['employment_type'] ?? null)
            ?: $this->employmentTypeFromStatus($row['employment_status'] ?? null);

        TracerRecord::updateOrCreate(
            [
                'user_id' => $user->id,
                'tracer_date' => $tracerDate->toDateString(),
            ],
            [
                'is_employed' => $this->isEmployed($employmentType),
                'employment_type' => $employmentType,
                'current_position' => $this->clean($row['current_position'] ?? null),
                'current_company' => $this->clean($row['company'] ?? null),
                'monthly_income' => SystemOptions::presentJobEarningsRangeForIncome($row['monthly_income'] ?? null),
                'industry' => $this->clean($row['industry'] ?? null),
                'skills_used' => $this->clean($row['skills'] ?? null),
                'curriculum_relevant_to_first_job' => $this->booleanOrNull($row['curriculum_relevant_to_first_job'] ?? null),
                'employed_after_graduation' => $this->isEmployed($employmentType),
                'time_to_first_job' => $this->clean($row['time_to_first_job'] ?? null),
                'presently_employed' => $this->isEmployed($employmentType) ? 'Yes' : 'No',
                'present_employment_status' => $this->clean($row['present_employment_status'] ?? null),
                'present_job_monthly_earnings' => SystemOptions::salaryRangeLabel($this->clean($row['salary_range'] ?? null)),
                'teaching_faculty_enthusiastic' => $this->ratingOrNull($row['teaching_faculty_enthusiastic'] ?? null),
                'teaching_concepts_clear' => $this->ratingOrNull($row['teaching_concepts_clear'] ?? null),
                'teaching_faculty_knowledgeable' => $this->ratingOrNull($row['teaching_faculty_knowledgeable'] ?? null),
                'teaching_commitment' => $this->ratingOrNull($row['teaching_commitment'] ?? null),
                'degree_program_quality' => $this->ratingOrNull($row['degree_program_quality'] ?? null),
                'facilities_satisfaction' => $this->clean($row['facilities_satisfaction'] ?? null),
                'overall_recommend_institution' => $this->ratingOrNull($row['overall_recommend_institution'] ?? null),
                'overall_objectives_met' => $this->ratingOrNull($row['overall_objectives_met'] ?? null),
                'overall_university_experience' => $this->ratingOrNull($row['overall_university_experience'] ?? null),
                'further_education' => false,
                'contact_permission' => true,
            ]
        );
    }

    protected function upsertAuditLog(array $row, User $user, bool $updated): void
    {
        $action = $this->clean($row['audit_action'] ?? null) ?: 'IMPORT_ALUMNI_SPREADSHEET';
        $description = $this->clean($row['audit_description'] ?? null)
            ?: sprintf(
                '%s alumni analytics spreadsheet row for %s.',
                $updated ? 'Updated' : 'Imported',
                $user->email
            );

        $exists = DB::table('system_logs')
            ->where('user_id', $user->id)
            ->where('action', $action)
            ->where('description', $description)
            ->exists();

        if ($exists) {
            return;
        }

        DB::table('system_logs')->insert([
            'id' => (string) str()->uuid(),
            'user_id' => $user->id,
            'action' => $action,
            'description' => $description,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Artisan analytics:import-alumni-spreadsheet',
            'created_at' => now(),
        ]);
    }

    protected function normalizeHeader(string $value): string
    {
        $value = preg_replace('/^\xEF\xBB\xBF/', '', $value) ?? $value;
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/', '_', $value) ?? $value;

        return trim($value, '_');
    }

    protected function rowHasValues(array $row): bool
    {
        foreach ($row as $value) {
            if ($value !== null && $value !== '') {
                return true;
            }
        }

        return false;
    }

    protected function clean(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    protected function normalizeSex(mixed $value): ?string
    {
        return match (strtolower((string) $this->clean($value))) {
            'm', 'male' => 'Male',
            'f', 'female' => 'Female',
            default => $this->clean($value),
        };
    }

    protected function normalizeEmploymentStatus(mixed $value): ?string
    {
        return match (strtolower(str_replace(['-', '_'], ' ', (string) $this->clean($value)))) {
            'employed', 'employee', 'full time', 'part time', 'self employed' => 'Yes',
            'unemployed', 'not employed' => 'No',
            default => $this->clean($value),
        };
    }

    protected function employmentTypeFromStatus(mixed $value): string
    {
        return match (strtolower(str_replace(['-', '_'], ' ', (string) $this->clean($value)))) {
            'self employed' => 'Self-employed',
            'unemployed', 'not employed' => 'Unemployed',
            default => 'Full-time',
        };
    }

    protected function isEmployed(?string $employmentType): ?bool
    {
        if ($employmentType === null) {
            return null;
        }

        return ! in_array(strtolower($employmentType), ['unemployed', 'not employed'], true);
    }

    protected function integerOrNull(mixed $value): ?int
    {
        $value = $this->clean($value);

        return $value !== null && preg_match('/^-?\d+$/', $value) ? (int) $value : null;
    }

    protected function ratingOrNull(mixed $value): ?int
    {
        $rating = $this->integerOrNull($value);

        return $rating === null ? null : max(1, min(5, $rating));
    }

    protected function booleanOrNull(mixed $value): ?bool
    {
        $value = strtolower((string) $this->clean($value));

        return match ($value) {
            'yes', 'y', 'true', '1' => true,
            'no', 'n', 'false', '0' => false,
            default => null,
        };
    }

    protected function dateOrToday(mixed $value): Carbon
    {
        $value = $this->clean($value);

        if ($value === null) {
            return now();
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return now();
        }
    }
}
