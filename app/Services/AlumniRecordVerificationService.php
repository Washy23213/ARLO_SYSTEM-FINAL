<?php

namespace App\Services;

use App\Support\SystemOptions;
use Carbon\Carbon;
use SimpleXMLElement;
use ZipArchive;

class AlumniRecordVerificationService
{
    public function verify(array $candidate): ?array
    {
        foreach ($this->records() as $record) {
            if ($this->matchesRecord($candidate, $record)) {
                return $record;
            }
        }

        return null;
    }

    public function matchesRecord(array $candidate, array $record): bool
    {
        return $this->normalize((string) ($candidate['firstname'] ?? '')) === $this->normalize((string) ($record['first_name'] ?? $record['firstname'] ?? ''))
            && $this->normalize((string) ($candidate['lastname'] ?? '')) === $this->normalize((string) ($record['last_name'] ?? $record['lastname'] ?? ''))
            && $this->middleNamesMatch(
                $candidate['middlename'] ?? null,
                $record['middle_name'] ?? $record['middlename'] ?? null,
            )
            && $this->birthdatesMatch(
                $candidate['birthdate'] ?? $candidate['birth_date'] ?? $candidate['birthday'] ?? null,
                $record['birthdate'] ?? $record['birth_date'] ?? $record['birthday'] ?? $record['date_of_birth'] ?? $record['dob'] ?? null,
            )
            && $this->studentNumbersMatch(
                $candidate['studentIdnum'] ?? $candidate['student_idnum'] ?? $candidate['student_number'] ?? null,
                $record['student_idnum'] ?? $record['student_number'] ?? $record['studentIdnum'] ?? null,
            )
            && $this->collegesMatch(
                $candidate['collegeGraduated'] ?? $candidate['college_graduated'] ?? null,
                $record['college_graduated'] ?? $record['collegeGraduated'] ?? null,
                $record['degree_graduated'] ?? $record['degreeGraduated'] ?? null,
            )
            && $this->degreeProgramsMatch(
                $candidate['degreeGraduated'] ?? $candidate['degree_graduated'] ?? null,
                $record['degree_graduated'] ?? $record['degreeGraduated'] ?? null,
                $record['college_graduated'] ?? $record['collegeGraduated'] ?? null,
            )
            && $this->normalizeYear($candidate['yearGraduated'] ?? $candidate['year_graduated'] ?? null) === $this->normalizeYear($record['year_graduated'] ?? $record['yearGraduated'] ?? null);
    }

    public function resolvedCollegeGraduated(array $record): ?string
    {
        $recordedCollege = SystemOptions::clean($record['college_graduated'] ?? $record['collegeGraduated'] ?? null);
        $canonicalCollege = SystemOptions::canonical($recordedCollege, SystemOptions::departments());

        if ($canonicalCollege !== null) {
            return $canonicalCollege;
        }

        return $this->departmentForProgram($recordedCollege)
            ?? $this->departmentForProgram(SystemOptions::clean($record['degree_graduated'] ?? $record['degreeGraduated'] ?? null))
            ?? $recordedCollege;
    }

    public function resolvedDegreeGraduated(array $record): ?string
    {
        $recordedDegree = SystemOptions::clean($record['degree_graduated'] ?? $record['degreeGraduated'] ?? null);

        if ($this->departmentForProgram($recordedDegree) !== null) {
            return $recordedDegree;
        }

        $recordedCollege = SystemOptions::clean($record['college_graduated'] ?? $record['collegeGraduated'] ?? null);

        return $this->departmentForProgram($recordedCollege) !== null
            ? $recordedCollege
            : $recordedDegree;
    }

    public function records(): array
    {
        static $cache = null;
        static $cachedSignature = null;

        $path = $this->recordsPath();
        $signature = $this->recordSourceSignature($path);

        if ($cache !== null && $cachedSignature === $signature) {
            return $cache;
        }

        $cachedSignature = $signature;
        $cache = $this->mergeRecordSets(
            $this->loadRecordsFromSpreadsheet($path),
            $this->loadRecordsFromJsonFallback($path),
        );

        return $cache;
    }

    public function recordsPath(): string
    {
        return storage_path('app/alumni-records/cmu_alumni_records.xlsx');
    }

    protected function recordSourceSignature(string $spreadsheetPath): string
    {
        $jsonPath = $this->jsonRecordsPath($spreadsheetPath);

        return implode('|', [
            is_file($spreadsheetPath) ? (string) filemtime($spreadsheetPath) : 'missing',
            is_file($jsonPath) ? (string) filemtime($jsonPath) : 'missing',
        ]);
    }

    protected function jsonRecordsPath(string $spreadsheetPath): ?string
    {
        $jsonPath = preg_replace('/\.xlsx$/i', '.json', $spreadsheetPath);

        return is_string($jsonPath) ? $jsonPath : null;
    }

    protected function mergeRecordSets(array ...$recordSets): array
    {
        $merged = [];

        foreach ($recordSets as $records) {
            foreach ($records as $record) {
                if (! is_array($record) || ! $this->rowHasValues($record)) {
                    continue;
                }

                $merged[$this->recordKey($record)] = $record;
            }
        }

        return array_values($merged);
    }

    protected function recordKey(array $record): string
    {
        $identity = $record['alumni_idnum'] ?? $record['student_idnum'] ?? $record['student_number'] ?? null;

        if (is_string($identity) && trim($identity) !== '') {
            return 'id:'.mb_strtolower(trim($identity));
        }

        return 'name:'.mb_strtolower(implode('|', [
            $record['last_name'] ?? $record['lastname'] ?? '',
            $record['first_name'] ?? $record['firstname'] ?? '',
            $record['middle_name'] ?? $record['middlename'] ?? '',
            $record['birthdate'] ?? $record['birth_date'] ?? $record['birthday'] ?? $record['date_of_birth'] ?? '',
            $record['year_graduated'] ?? $record['yearGraduated'] ?? '',
            $record['college_graduated'] ?? $record['collegeGraduated'] ?? '',
            $record['degree_graduated'] ?? $record['degreeGraduated'] ?? '',
        ]));
    }

    protected function loadRecordsFromSpreadsheet(string $path): array
    {
        if (!is_file($path)) {
            return $this->loadRecordsFromJsonFallback($path);
        }

        if (!class_exists(ZipArchive::class)) {
            return $this->loadRecordsFromJsonFallback($path);
        }

        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            return $this->loadRecordsFromJsonFallback($path);
        }

        $worksheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $sharedStringsXml = $zip->getFromName('xl/sharedStrings.xml');
        $zip->close();

        if ($worksheetXml === false) {
            return $this->loadRecordsFromJsonFallback($path);
        }

        $rows = $this->extractRows($worksheetXml, $sharedStringsXml ?: null);
        if (count($rows) <= 1) {
            return [];
        }

        $header = array_shift($rows);
        $keys = array_map(fn ($value) => $this->normalizeHeader((string) $value), $header);

        $records = [];

        foreach ($rows as $row) {
            $record = [];

            foreach ($keys as $index => $key) {
                if ($key === '') {
                    continue;
                }

                $record[$key] = SystemOptions::clean(isset($row[$index]) ? (string) $row[$index] : null);
            }

            if (($record['year_graduated'] ?? null) === null && ($record['dt_grad'] ?? null) !== null) {
                $record['year_graduated'] = $this->graduationYearFromDate($record['dt_grad']);
            }

            if ($this->rowHasValues($record)) {
                $records[] = $record;
            }
        }

        return $records;
    }

    protected function loadRecordsFromJsonFallback(string $spreadsheetPath): array
    {
        $jsonPath = $this->jsonRecordsPath($spreadsheetPath);
        if (!is_string($jsonPath) || !is_file($jsonPath)) {
            return [];
        }

        $json = (string) file_get_contents($jsonPath);
        $json = preg_replace('/^\xEF\xBB\xBF/', '', $json) ?? $json;
        $decoded = json_decode($json, true);
        if (!is_array($decoded)) {
            return [];
        }

        return array_values(array_filter($decoded, fn ($row) => is_array($row)));
    }

    protected function rowHasValues(array $record): bool
    {
        foreach ($record as $value) {
            if ($value !== null && $value !== '') {
                return true;
            }
        }

        return false;
    }

    protected function extractRows(string $worksheetXml, ?string $sharedStringsXml = null): array
    {
        $worksheet = simplexml_load_string($worksheetXml);
        if (!$worksheet instanceof SimpleXMLElement || !isset($worksheet->sheetData)) {
            return [];
        }

        $sharedStrings = $this->extractSharedStrings($sharedStringsXml);
        $rows = [];

        foreach ($worksheet->sheetData->row as $row) {
            $currentRow = [];
            $maxIndex = -1;

            foreach ($row->c as $cell) {
                $cellReference = (string) $cell['r'];
                $columnLetters = preg_replace('/\d+/', '', $cellReference);
                $columnIndex = $this->columnIndexFromLetters($columnLetters);
                $currentRow[$columnIndex] = $this->extractCellValue($cell, $sharedStrings);
                $maxIndex = max($maxIndex, $columnIndex);
            }

            if ($maxIndex < 0) {
                continue;
            }

            $normalizedRow = array_fill(0, $maxIndex + 1, '');
            foreach ($currentRow as $index => $value) {
                $normalizedRow[$index] = $value;
            }

            $rows[] = $normalizedRow;
        }

        return $rows;
    }

    protected function extractSharedStrings(?string $sharedStringsXml): array
    {
        if ($sharedStringsXml === null || trim($sharedStringsXml) === '') {
            return [];
        }

        $sharedStringsDoc = simplexml_load_string($sharedStringsXml);
        if (!$sharedStringsDoc instanceof SimpleXMLElement) {
            return [];
        }

        $strings = [];

        foreach ($sharedStringsDoc->si as $sharedString) {
            $strings[] = $this->extractRichTextValue($sharedString);
        }

        return $strings;
    }

    protected function extractCellValue(SimpleXMLElement $cell, array $sharedStrings): string
    {
        $type = (string) $cell['t'];

        return match ($type) {
            'inlineStr' => $this->extractRichTextValue($cell->is),
            's' => $sharedStrings[(int) ($cell->v ?? 0)] ?? '',
            'str' => (string) ($cell->v ?? ''),
            default => (string) ($cell->v ?? ''),
        };
    }

    protected function extractRichTextValue(?SimpleXMLElement $node): string
    {
        if (!$node instanceof SimpleXMLElement) {
            return '';
        }

        if (isset($node->t)) {
            return (string) $node->t;
        }

        $chunks = [];
        foreach ($node->r as $run) {
            $chunks[] = (string) ($run->t ?? '');
        }

        return implode('', $chunks);
    }

    protected function columnIndexFromLetters(string $letters): int
    {
        $letters = strtoupper(trim($letters));
        $index = 0;

        foreach (str_split($letters) as $letter) {
            $index = ($index * 26) + (ord($letter) - 64);
        }

        return max($index - 1, 0);
    }

    protected function normalizeHeader(string $value): string
    {
        $normalized = strtolower(trim($value));

        return match ($normalized) {
            'last name', 'lastname', 'l name', 'l_name' => 'last_name',
            'first name', 'firstname', 'f name', 'f_name' => 'first_name',
            'middle name', 'middlename', 'm name', 'm_name' => 'middle_name',
            'suffix' => 'suffix',
            'college graduated', 'college', 'course' => 'college_graduated',
            'degree graduated', 'degree', 'course graduated', 'major' => 'degree_graduated',
            'year graduated', 'graduation year' => 'year_graduated',
            'student number', 'student no.', 'student no', 'student id', 'student id number', 'student_id_number', 'student_idnum' => 'student_number',
            'alumni id', 'alumni id number', 'alumni_idnum' => 'alumni_idnum',
            'date graduated', 'graduation date', 'dt_grad' => 'dt_grad',
            'birthdate', 'birth date', 'birthday', 'date of birth', 'dob' => 'birthdate',
            'sex', 'gender' => 'sex',
            'status' => 'status',
            default => str_replace(' ', '_', preg_replace('/[^a-z0-9]+/i', ' ', $normalized)),
        };
    }

    protected function middleNamesMatch(?string $submitted, ?string $recorded): bool
    {
        $submitted = $this->normalize($submitted);
        $recorded = $this->normalize($recorded);

        if ($submitted === $recorded) {
            return true;
        }

        if ($submitted === '' || $recorded === '') {
            return false;
        }

        return mb_substr($submitted, 0, 1) === mb_substr($recorded, 0, 1);
    }

    protected function birthdatesMatch(mixed $submitted, mixed $recorded): bool
    {
        $submitted = $this->normalizeDate($submitted);
        $recorded = $this->normalizeDate($recorded);

        return $submitted !== '' && $recorded !== '' && $submitted === $recorded;
    }

    protected function studentNumbersMatch(?string $submitted, ?string $recorded): bool
    {
        $submitted = $this->normalizeIdentifier($submitted);
        $recorded = $this->normalizeIdentifier($recorded);

        if ($submitted === '' || $recorded === '') {
            return true;
        }

        return $submitted === $recorded;
    }

    protected function collegesMatch(?string $submitted, ?string $recorded, ?string $recordedDegree = null): bool
    {
        $submitted = $this->normalize($submitted);
        $recorded = $this->normalize($recorded);

        if ($submitted === '' || $recorded === '') {
            return true;
        }

        if ($submitted === $recorded) {
            return true;
        }

        foreach ([$recorded, $recordedDegree] as $possibleProgram) {
            $department = $this->departmentForProgram($possibleProgram);

            if ($department !== null && $this->normalize($department) === $submitted) {
                return true;
            }
        }

        return false;
    }

    protected function degreeProgramsMatch(?string $submitted, ?string $recorded, ?string $fallbackRecorded = null): bool
    {
        $submitted = $this->normalizeDegreeProgram($submitted);
        $recorded = $this->normalizeDegreeProgram($recorded);
        $fallbackRecorded = $this->normalizeDegreeProgram($fallbackRecorded);

        if ($submitted === '' || ($recorded === '' && $fallbackRecorded === '')) {
            return true;
        }

        return $submitted === $recorded || $submitted === $fallbackRecorded;
    }

    protected function departmentForProgram(?string $program): ?string
    {
        $normalizedProgram = $this->normalizeDegreeProgram($program);

        if ($normalizedProgram === '') {
            return null;
        }

        foreach (SystemOptions::programCatalogByDepartment() as $department => $groups) {
            foreach ($groups as $programs) {
                foreach ($programs as $candidate) {
                    if ($this->normalizeDegreeProgram($candidate) === $normalizedProgram) {
                        return $department;
                    }
                }
            }
        }

        return null;
    }

    protected function normalize(?string $value): string
    {
        $cleaned = SystemOptions::clean($value) ?? '';

        return mb_strtolower($cleaned);
    }

    protected function normalizeIdentifier(?string $value): string
    {
        $normalized = $this->normalize($value);

        return preg_replace('/[^a-z0-9]+/u', '', $normalized) ?? '';
    }

    protected function normalizeDate(mixed $value): string
    {
        $value = SystemOptions::clean($value === null ? null : (string) $value);

        if ($value === null) {
            return '';
        }

        if (is_numeric($value) && (float) $value > 20000) {
            return Carbon::create(1899, 12, 30)->addDays((int) $value)->toDateString();
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            return '';
        }
    }

    protected function graduationYearFromDate(mixed $value): ?string
    {
        $date = $this->normalizeDate($value);

        return $date !== '' ? substr($date, 0, 4) : null;
    }

    protected function normalizeDegreeProgram(?string $value): string
    {
        $normalized = $this->normalize($value);
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

    protected function normalizeYear(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        return (string) (int) $value;
    }
}
