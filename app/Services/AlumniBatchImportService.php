<?php

namespace App\Services;

use App\Models\AlumniImportBatch;
use App\Models\User;
use App\Support\SystemOptions;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;

class AlumniBatchImportService
{
    public const REQUIRED_COLUMNS = [
        'No.',
        'Alumni_IDNUM',
        'STUDENT_IDNUM',
        'L_NAME',
        'F_NAME',
        'M_NAME',
        'BIRTHDATE',
        'COURSE',
        'MAJOR',
        'DT_GRAD',
    ];

    public function import(UploadedFile $file): array
    {
        return $this->importMany([$file]);
    }

    /**
     * @param  array<int, UploadedFile>  $files
     */
    public function importMany(array $files): array
    {
        $files = array_values(array_filter($files, fn ($file) => $file instanceof UploadedFile));

        if ($files === []) {
            throw new RuntimeException('Please choose at least one CSV file to import.');
        }

        if (count($files) > 3) {
            throw new RuntimeException('You can upload a maximum of 3 CSV files at one time.');
        }

        $fileBatches = [];

        foreach ($files as $file) {
            $this->validateCsvFile($file);

            [$headers, $rows] = $this->readCsv($file->getRealPath() ?: '');
            $headerValidation = $this->validateHeaders($headers);
            $headerValidation['file_name'] = $file->getClientOriginalName();

            if (! $headerValidation['valid']) {
                return [
                    'success' => false,
                    'imported' => 0,
                    'updated' => 0,
                    'skipped' => [],
                    'years' => [],
                    'files_count' => count($files),
                    'message' => 'Batch import stopped because '.$file->getClientOriginalName().' is missing required CSV columns.',
                    'header_validation' => $headerValidation,
                    'file_results' => [],
                    'batches' => [],
                ];
            }

            $fileBatches[] = [
                'file_name' => $file->getClientOriginalName(),
                'rows' => $rows,
                'header_validation' => $headerValidation,
            ];
        }

        $imported = 0;
        $updated = 0;
        $skipped = [];
        $importedRecords = [];
        $fileResults = [];
        $batches = [];

        DB::transaction(function () use ($fileBatches, &$imported, &$updated, &$skipped, &$importedRecords, &$fileResults, &$batches) {
            foreach ($fileBatches as $fileBatch) {
                $fileImported = 0;
                $fileUpdated = 0;
                $fileSkipped = 0;
                $batch = $this->createImportBatch($fileBatch['file_name'], count($fileBatch['rows']));

                foreach ($fileBatch['rows'] as $index => $row) {
                    $normalized = $this->normalizeImportRow($row);

                    if (! $this->rowHasValues($normalized)) {
                        continue;
                    }

                    if ($normalized['firstname'] === null || $normalized['lastname'] === null) {
                        $skipped[] = [
                            'file' => $fileBatch['file_name'],
                            'row' => $index + 2,
                            'reason' => 'Missing first name or last name.',
                        ];
                        $fileSkipped++;

                        continue;
                    }

                    if ($normalized['birthdate'] === null) {
                        $skipped[] = [
                            'file' => $fileBatch['file_name'],
                            'row' => $index + 2,
                            'reason' => 'Missing or invalid birthdate.',
                        ];
                        $fileSkipped++;

                        continue;
                    }

                    $existing = $this->findExistingUser($normalized);
                    $payload = $this->buildUserPayload($normalized, (string) $batch->id, $existing);

                    if ($existing) {
                        $existing->update($payload);
                        $updated++;
                        $fileUpdated++;
                        $user = $existing->fresh();
                    } else {
                        $payload['email'] = $this->generatedEmail($normalized);
                        $payload['password'] = Hash::make(Str::random(32));
                        $user = User::create($payload);
                        $imported++;
                        $fileImported++;
                    }

                    if ($user instanceof User) {
                        $importedRecords[] = $this->verificationRecordFor($user);
                    }
                }

                $batch->forceFill([
                    'imported_count' => $fileImported,
                    'updated_count' => $fileUpdated,
                    'skipped_count' => $fileSkipped,
                ])->save();

                $fileResults[] = [
                    'batch_id' => (string) $batch->id,
                    'batch_name' => $batch->name,
                    'file_name' => $fileBatch['file_name'],
                    'imported' => $fileImported,
                    'updated' => $fileUpdated,
                    'skipped' => $fileSkipped,
                ];

                $batches[] = [
                    'id' => (string) $batch->id,
                    'name' => $batch->name,
                    'file_name' => $batch->source_file_name,
                ];
            }
        });

        $this->mergeVerificationFallbackRecords($importedRecords);

        $years = collect($importedRecords)
            ->pluck('year_graduated')
            ->filter()
            ->unique()
            ->sortDesc()
            ->values()
            ->all();

        return [
            'success' => true,
            'imported' => $imported,
            'updated' => $updated,
            'skipped' => $skipped,
            'years' => $years,
            'files_count' => count($files),
            'message' => 'Batch import completed for '.count($files).' CSV '.Str::plural('file', count($files)).". Imported {$imported} new alumni and updated {$updated} existing alumni. These records are now available for alumni registration verification.",
            'header_validation' => $fileBatches[0]['header_validation'] ?? [
                'valid' => true,
                'expected' => self::REQUIRED_COLUMNS,
                'actual' => self::REQUIRED_COLUMNS,
                'missing' => [],
                'extra' => [],
                'ignored' => [],
                'matched' => self::REQUIRED_COLUMNS,
            ],
            'file_results' => $fileResults,
            'batches' => $batches,
        ];
    }

    protected function validateCsvFile(UploadedFile $file): void
    {
        $extension = strtolower((string) $file->getClientOriginalExtension());

        if ($extension !== 'csv') {
            throw new RuntimeException('Please upload CSV files only. Open your Excel file, choose Save As, then select CSV before uploading.');
        }
    }

    public function templateCsv(): string
    {
        $handle = fopen('php://temp', 'rb+');

        if ($handle === false) {
            throw new RuntimeException('Unable to create batch template.');
        }

        fputcsv($handle, self::REQUIRED_COLUMNS, ',', '"', '\\');

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return (string) $csv;
    }

    public function previewRows(int $limit = 10): array
    {
        return [];
    }

    protected function readCsv(string $path): array
    {
        if ($path === '' || ! is_file($path)) {
            throw new RuntimeException('Upload failed. Please choose the batch CSV file again.');
        }

        $handle = fopen($path, 'rb');

        if ($handle === false) {
            throw new RuntimeException('Unable to read the uploaded file.');
        }

        $headers = fgetcsv($handle, 0, ',', '"', '\\');

        if (! is_array($headers)) {
            fclose($handle);

            throw new RuntimeException('The uploaded file is empty.');
        }

        $allowedKeys = array_flip(array_map(fn ($header) => $this->normalizeHeader($header), self::REQUIRED_COLUMNS));
        $keys = array_map(fn ($header) => $this->normalizeHeader((string) $header), $headers);
        $rows = [];

        while (($values = fgetcsv($handle, 0, ',', '"', '\\')) !== false) {
            $row = [];

            foreach ($keys as $index => $key) {
                if ($key === '' || ! isset($allowedKeys[$key])) {
                    continue;
                }

                $row[$key] = $this->clean($values[$index] ?? null);
            }

            $rows[] = $row;
        }

        fclose($handle);

        return [$headers, $rows];
    }

    protected function validateHeaders(array $headers): array
    {
        $actual = array_map(fn ($value) => trim((string) $value), $headers);
        $actualNormalized = array_map(fn ($value) => $this->normalizeHeader((string) $value), $actual);
        $expected = [];

        foreach (self::REQUIRED_COLUMNS as $column) {
            $expected[$this->normalizeHeader($column)] = $column;
        }

        $actualLookup = array_flip(array_filter($actualNormalized, fn ($value) => $value !== ''));
        $missing = [];
        $matched = [];

        foreach ($expected as $normalized => $label) {
            if (isset($actualLookup[$normalized])) {
                $matched[] = $label;
            } else {
                $missing[] = $label;
            }
        }

        $extra = [];

        foreach ($actual as $index => $label) {
            $normalized = $actualNormalized[$index] ?? '';

            if ($normalized !== '' && ! isset($expected[$normalized])) {
                $extra[] = $label;
            }
        }

        return [
            'valid' => $missing === [],
            'expected' => self::REQUIRED_COLUMNS,
            'actual' => $actual,
            'missing' => $missing,
            'extra' => $extra,
            'ignored' => $extra,
            'matched' => $matched,
        ];
    }

    protected function normalizeImportRow(array $row): array
    {
        $dtGrad = $this->parseDate($row['dt_grad'] ?? null);
        $birthdate = $this->parseDate($row['birthdate'] ?? $row['birth_date'] ?? $row['birthday'] ?? null);
        $course = $this->clean($row['course'] ?? null);
        $major = $this->clean($row['major'] ?? null);
        [$department, $degree] = $this->resolveAcademicFields($course, $major);

        return [
            'row_number' => $this->clean($row['no'] ?? null),
            'alumni_idnum' => $this->clean($row['alumni_idnum'] ?? null),
            'student_idnum' => $this->clean($row['student_idnum'] ?? null),
            'lastname' => $this->clean($row['l_name'] ?? null),
            'firstname' => $this->clean($row['f_name'] ?? null),
            'middlename' => $this->clean($row['m_name'] ?? null),
            'birthdate' => $birthdate,
            'course' => $course,
            'major' => $major,
            'dt_grad' => $dtGrad,
            'year_graduated' => $dtGrad?->year,
            'department' => $department,
            'degree' => $degree,
        ];
    }

    protected function buildUserPayload(array $row, ?string $batchId = null, ?User $existing = null): array
    {
        $existingEmail = mb_strtolower(trim((string) ($existing?->email ?? '')));
        $isExistingCsvOnly = $existing === null
            || (bool) ($existing->is_batch_imported ?? false)
            || Str::endsWith($existingEmail, '@oarl-batch.local');

        $payload = [
            'firstname' => $row['firstname'],
            'middlename' => $row['middlename'],
            'lastname' => $row['lastname'],
            'alumni_idnum' => $row['alumni_idnum'],
            'student_idnum' => $row['student_idnum'],
            'birthdate' => $row['birthdate']?->toDateString(),
            'department' => $row['department'],
            'degree' => $row['degree'],
            'course' => $row['course'],
            'major' => $row['major'],
            'dt_grad' => $row['dt_grad']?->toDateString(),
            'year_graduated' => $row['year_graduated'],
            'employed_after_grad' => null,
            'role' => User::ROLE_ALUMNI,
            'is_admin' => false,
            'is_batch_imported' => $isExistingCsvOnly,
            'batch_imported_at' => now(),
            'alumni_import_batch_id' => $batchId,
            'data_privacy_consent' => true,
            'data_privacy_consented_at' => now(),
            'newsletter_opt_in' => true,
        ];

        return array_intersect_key($payload, array_flip(Schema::getColumnListing('users')));
    }

    protected function createImportBatch(string $fileName, int $rowsCount): AlumniImportBatch
    {
        $baseName = trim((string) pathinfo($fileName, PATHINFO_FILENAME));
        $name = $baseName !== '' ? $baseName : $fileName;
        [$actorGuard, $actorId] = $this->currentImportActor();

        return AlumniImportBatch::create([
            'name' => Str::limit($name, 180, ''),
            'source_file_name' => Str::limit($fileName, 255, ''),
            'imported_by_guard' => $actorGuard,
            'imported_by_id' => $actorId,
            'imported_at' => now(),
            'rows_count' => $rowsCount,
            'imported_count' => 0,
            'updated_count' => 0,
            'skipped_count' => 0,
        ]);
    }

    protected function currentImportActor(): array
    {
        foreach (['super_admin', 'admin', 'web'] as $guard) {
            if (Auth::guard($guard)->check()) {
                return [$guard, Auth::guard($guard)->id()];
            }
        }

        return [Auth::getDefaultDriver(), Auth::id()];
    }

    protected function findExistingUser(array $row): ?User
    {
        if ($row['alumni_idnum'] !== null) {
            $match = User::where('alumni_idnum', $row['alumni_idnum'])->first();

            if ($match) {
                return $match;
            }
        }

        if ($row['student_idnum'] !== null) {
            $match = User::where('student_idnum', $row['student_idnum'])->first();

            if ($match) {
                return $match;
            }
        }

        if ($row['year_graduated'] === null) {
            return null;
        }

        return User::query()
            ->whereRaw('LOWER(firstname) = ?', [mb_strtolower((string) $row['firstname'])])
            ->whereRaw('LOWER(lastname) = ?', [mb_strtolower((string) $row['lastname'])])
            ->where('year_graduated', $row['year_graduated'])
            ->when(
                $row['birthdate'] instanceof Carbon && Schema::hasColumn('users', 'birthdate'),
                fn ($query) => $query->whereDate('birthdate', $row['birthdate']->toDateString())
            )
            ->first();
    }

    protected function generatedEmail(array $row): string
    {
        $base = $row['alumni_idnum']
            ?: $row['student_idnum']
            ?: Str::slug(implode('-', array_filter([
                $row['lastname'],
                $row['firstname'],
                $row['year_graduated'],
            ])));

        $base = Str::of($base)
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '.')
            ->trim('.')
            ->toString();

        $base = $base !== '' ? $base : 'alumni.'.Str::random(8);
        $email = "{$base}@oarl-batch.local";
        $counter = 1;

        while (User::where('email', $email)->exists()) {
            $email = "{$base}.{$counter}@oarl-batch.local";
            $counter++;
        }

        return $email;
    }

    protected function verificationRecordFor(User $user): array
    {
        return [
            'first_name' => SystemOptions::clean($user->firstname),
            'middle_name' => SystemOptions::clean($user->middlename),
            'last_name' => SystemOptions::clean($user->lastname),
            'college_graduated' => SystemOptions::clean($user->department ?? $user->course),
            'degree_graduated' => SystemOptions::clean($user->degree ?? $user->course ?? $user->major),
            'year_graduated' => $user->year_graduated,
            'student_number' => SystemOptions::clean($user->student_idnum),
            'student_idnum' => SystemOptions::clean($user->student_idnum),
            'alumni_idnum' => SystemOptions::clean($user->alumni_idnum),
            'birthdate' => $user->birthdate?->toDateString(),
            'course' => SystemOptions::clean($user->course),
            'major' => SystemOptions::clean($user->major),
            'dt_grad' => SystemOptions::clean($user->dt_grad),
        ];
    }

    protected function mergeVerificationFallbackRecords(array $records): void
    {
        if ($records === []) {
            return;
        }

        $configuredPath = config('services.alumni_records_json_path');
        $path = is_string($configuredPath) && trim($configuredPath) !== ''
            ? $configuredPath
            : storage_path('app/alumni-records/cmu_alumni_records.json');
        $directory = dirname($path);

        if (! is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $existing = [];
        if (is_file($path)) {
            $decoded = json_decode((string) file_get_contents($path), true);
            $existing = is_array($decoded) ? array_values(array_filter($decoded, fn ($row) => is_array($row))) : [];
        }

        $merged = [];

        foreach (array_merge($existing, $records) as $record) {
            $merged[$this->verificationRecordKey($record)] = $record;
        }

        file_put_contents($path, json_encode(array_values($merged), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    protected function verificationRecordKey(array $record): string
    {
        $identity = $record['alumni_idnum'] ?? $record['student_idnum'] ?? $record['student_number'] ?? null;

        if (is_string($identity) && trim($identity) !== '') {
            return 'id:'.mb_strtolower(trim($identity));
        }

        return 'name:'.mb_strtolower(implode('|', [
            $record['last_name'] ?? '',
            $record['first_name'] ?? '',
            $record['middle_name'] ?? '',
            $record['birthdate'] ?? '',
            $record['year_graduated'] ?? '',
        ]));
    }

    protected function normalizeHeader(string $value): string
    {
        $value = preg_replace('/^\xEF\xBB\xBF/', '', $value) ?? $value;
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/', '_', $value) ?? $value;

        return trim($value, '_');
    }

    protected function parseDate(mixed $value): ?Carbon
    {
        $value = $this->clean($value);

        if ($value === null) {
            return null;
        }

        if (preg_match('/^\d{4}$/', $value)) {
            return Carbon::create((int) $value, 1, 1)->startOfDay();
        }

        if (is_numeric($value) && (float) $value > 20000) {
            return Carbon::create(1899, 12, 30)->addDays((int) $value)->startOfDay();
        }

        try {
            return Carbon::parse($value)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
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

    protected function resolveAcademicFields(?string $course, ?string $major): array
    {
        $department = SystemOptions::canonical($course, SystemOptions::departments());

        if ($department !== null) {
            return [$department, $major ?: $course];
        }

        $inferredDepartment = $this->departmentForProgram($course);

        if ($inferredDepartment !== null) {
            return [$inferredDepartment, $course];
        }

        return [$course, $major ?: $course];
    }

    protected function departmentForProgram(?string $program): ?string
    {
        $normalizedProgram = $this->normalizeAcademicProgram($program);

        if ($normalizedProgram === '') {
            return null;
        }

        foreach (SystemOptions::programCatalogByDepartment() as $department => $groups) {
            foreach ($groups as $programs) {
                foreach ($programs as $candidate) {
                    if ($this->normalizeAcademicProgram($candidate) === $normalizedProgram) {
                        return $department;
                    }
                }
            }
        }

        return null;
    }

    protected function normalizeAcademicProgram(?string $value): string
    {
        $normalized = mb_strtolower(SystemOptions::clean($value) ?? '');
        if ($normalized === '') {
            return '';
        }

        $replacements = [
            '/\bbs\b/u' => 'bachelor of science',
            '/\bba\b/u' => 'bachelor of arts',
            '/\bma\b/u' => 'master of arts',
            '/\bms\b/u' => 'master of science',
            '/\bph\.?d\.?\b/u' => 'doctor of philosophy',
            '/\bdvm\b/u' => 'doctor of veterinary medicine',
            '/\bmba\b/u' => 'master in business administration',
            '/\bbsed\b/u' => 'bachelor of secondary education',
            '/\bbpe\b/u' => 'bachelor of physical education',
            '/\bbsit\b/u' => 'bachelor of science in information technology',
        ];

        foreach ($replacements as $pattern => $replacement) {
            $normalized = preg_replace($pattern, $replacement, $normalized) ?? $normalized;
        }

        $normalized = str_replace('&', ' and ', $normalized);
        $normalized = preg_replace('/\b(of|in|and)\b/u', ' ', $normalized) ?? $normalized;

        return trim(preg_replace('/\s+/u', ' ', $normalized) ?? $normalized);
    }

}
