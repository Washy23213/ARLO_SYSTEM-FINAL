<?php

namespace App\Services;

use App\Models\User;
use App\Support\SystemOptions;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AlumniDashboardService
{
    public function payload(array $filters = []): array
    {
        $filters = $this->normalizeFilters($filters);

        if ($this->usesDefaultFilters($filters)) {
            return Cache::remember('alumni-dashboard:payload:v5', now()->addMinutes(5), function () use ($filters) {
                return $this->buildPayload($filters);
            });
        }

        return $this->buildPayload($filters);
    }

    protected function buildPayload(array $filters): array
    {
        $latestTracerSubquery = DB::table('tracer_records as tr1')
            ->select('tr1.user_id', 'tr1.employment_type')
            ->whereRaw('tr1.tracer_date = (SELECT MAX(tr2.tracer_date) FROM tracer_records tr2 WHERE tr2.user_id = tr1.user_id)');

        $employmentCountsQuery = DB::query()
            ->fromSub($latestTracerSubquery, 'latest_tracer')
            ->join('users as u', 'u.id', '=', 'latest_tracer.user_id')
            ->selectRaw("
                SUM(CASE WHEN LOWER(COALESCE(latest_tracer.employment_type, '')) IN ('full-time', 'part-time', 'employed', 'employee') THEN 1 ELSE 0 END) as employed,
                SUM(CASE WHEN LOWER(COALESCE(latest_tracer.employment_type, '')) IN ('self-employed', 'self employed') THEN 1 ELSE 0 END) as self_employed,
                SUM(CASE WHEN LOWER(COALESCE(latest_tracer.employment_type, '')) = 'unemployed' THEN 1 ELSE 0 END) as unemployed
            ");

        $this->applyUserFilters($employmentCountsQuery, $filters, 'u');
        $employmentCounts = $employmentCountsQuery->first();

        $totalAlumniQuery = DB::table('users as u');
        $this->applyUserFilters($totalAlumniQuery, $filters, 'u');

        $payload = [
            'totalAlumni' => $totalAlumniQuery->count(),
            'employed' => (int) ($employmentCounts->employed ?? 0),
            'selfEmployed' => (int) ($employmentCounts->self_employed ?? 0),
            'unemployed' => (int) ($employmentCounts->unemployed ?? 0),
            'recentAlumni' => $this->recentAlumni($latestTracerSubquery, $filters),
            'employmentTrends' => $this->employmentTrends($filters),
            'industryData' => $this->industryData($filters),
            'tracedGraduates' => $this->tracedGraduates($filters),
            'collegeTrends' => $this->collegeTrends($latestTracerSubquery, $filters),
            'employabilityData' => $this->employabilityData($latestTracerSubquery, $filters),
            'globalData' => $this->globalData($filters),
            'feedbacksData' => $this->feedbacksData($filters),
            'reasonsData' => $this->reasonsData($filters),
            'filters' => $filters,
            'filterOptions' => $this->filterOptions(),
        ];

        return $this->withFallbacks($payload);
    }

    protected function recentAlumni($latestTracerSubquery, array $filters)
    {
        $query = DB::table('users as u')
            ->leftJoinSub(
                DB::table('tracer_records as tr')
                    ->select('tr.user_id', 'tr.employment_type', 'tr.industry', 'tr.tracer_date')
                    ->whereRaw('tr.tracer_date = (SELECT MAX(tr2.tracer_date) FROM tracer_records tr2 WHERE tr2.user_id = tr.user_id)'),
                'latest_tracer',
                'latest_tracer.user_id',
                '=',
                'u.id'
            )
            ->select(
                'u.id',
                'u.firstname',
                'u.lastname',
                'u.year_graduated',
                'u.degree',
                DB::raw("
                    CASE
                        WHEN LOWER(COALESCE(latest_tracer.employment_type, '')) IN ('full-time', 'part-time', 'employed', 'employee') THEN 'Employed'
                        WHEN LOWER(COALESCE(latest_tracer.employment_type, '')) IN ('self-employed', 'self employed') THEN 'Self-Employed'
                        WHEN LOWER(COALESCE(latest_tracer.employment_type, '')) = 'unemployed' THEN 'Unemployed'
                        WHEN u.employed_after_grad = 'Yes' THEN 'Employed'
                        WHEN u.employed_after_grad = 'No' THEN 'Unemployed'
                        ELSE 'Unknown'
                    END as employment_status
                "),
                DB::raw('COALESCE(latest_tracer.industry, u.industry) as industry')
            );

        $this->applyUserFilters($query, $filters, 'u');

        return $query
            ->orderByDesc('u.created_at')
            ->limit(5)
            ->get();
    }

    protected function employmentTrends(array $filters): array
    {
        $months = $filters['employment_range'];

        if (!Schema::hasTable('tracer_records')) {
            return $this->fallbackEmploymentTrends($filters);
        }

        $buckets = $this->monthBuckets($months);
        $monthExpression = $this->monthPeriodExpression('tr.tracer_date');
        $rows = DB::table('tracer_records as tr')
            ->join('users as u', 'u.id', '=', 'tr.user_id')
            ->selectRaw("
                {$monthExpression} as month_key,
                SUM(CASE WHEN LOWER(COALESCE(tr.employment_type, '')) IN ('full-time', 'part-time', 'employed', 'employee') THEN 1 ELSE 0 END) as employed_count,
                SUM(CASE WHEN LOWER(COALESCE(tr.employment_type, '')) IN ('self-employed', 'self employed') THEN 1 ELSE 0 END) as self_employed_count,
                SUM(CASE WHEN LOWER(COALESCE(tr.employment_type, '')) = 'unemployed' THEN 1 ELSE 0 END) as unemployed_count
            ")
            ->where('tr.tracer_date', '>=', $buckets->first()->copy()->startOfMonth()->toDateString());

        $this->applyUserFilters($rows, $filters, 'u');

        $rows = $rows->groupByRaw($monthExpression)
            ->orderByRaw($monthExpression)
            ->get()
            ->keyBy('month_key');

        return [
            'labels' => $buckets->map(fn (Carbon $date) => $date->format('M'))->values()->all(),
            'employed' => $buckets->map(fn (Carbon $date) => $this->seriesValue($rows, $date->format('Y-m'), 'employed_count'))->values()->all(),
            'selfEmployed' => $buckets->map(fn (Carbon $date) => $this->seriesValue($rows, $date->format('Y-m'), 'self_employed_count'))->values()->all(),
            'unemployed' => $buckets->map(fn (Carbon $date) => $this->seriesValue($rows, $date->format('Y-m'), 'unemployed_count'))->values()->all(),
        ];
    }

    protected function fallbackEmploymentTrends(array $filters): array
    {
        $months = $filters['employment_range'];
        $fallbackQuery = DB::table('users as u');
        $this->applyUserFilters($fallbackQuery, $filters, 'u');

        $fallback = $fallbackQuery
            ->selectRaw("
                SUM(CASE WHEN u.employed_after_grad = 'Yes' THEN 1 ELSE 0 END) as employed_count,
                SUM(CASE WHEN u.employed_after_grad = 'No' THEN 1 ELSE 0 END) as unemployed_count
            ")
            ->first();

        $buckets = $this->monthBuckets($months);
        $employed = array_fill(0, max(0, $months - 1), 0);
        $selfEmployed = array_fill(0, max(0, $months - 1), 0);
        $unemployed = array_fill(0, max(0, $months - 1), 0);
        $employed[] = (int) ($fallback->employed_count ?? 0);
        $selfEmployed[] = 0;
        $unemployed[] = (int) ($fallback->unemployed_count ?? 0);

        return [
            'labels' => $buckets->map(fn (Carbon $date) => $date->format('M'))->values()->all(),
            'employed' => $employed,
            'selfEmployed' => $selfEmployed,
            'unemployed' => $unemployed,
        ];
    }

    protected function industryData(array $filters): array
    {
        $rows = collect();

        if (Schema::hasTable('tracer_records')) {
            $query = DB::table('tracer_records as tr')
                ->join('users as u', 'u.id', '=', 'tr.user_id');

            $this->applyUserFilters($query, $filters, 'u');

            $rows = $query
                ->selectRaw("COALESCE(NULLIF(TRIM(tr.industry), ''), 'Others') as industry_name, COUNT(*) as total")
                ->groupByRaw("COALESCE(NULLIF(TRIM(tr.industry), ''), 'Others')")
                ->orderByDesc('total')
                ->limit(8)
                ->get();
        }

        if ($rows->isEmpty()) {
            $query = DB::table('users as u');
            $this->applyUserFilters($query, $filters, 'u');

            $rows = $query
                ->selectRaw("COALESCE(NULLIF(TRIM(u.industry), ''), 'Others') as industry_name, COUNT(*) as total")
                ->groupByRaw("COALESCE(NULLIF(TRIM(u.industry), ''), 'Others')")
                ->orderByDesc('total')
                ->limit(8)
                ->get();
        }

        return [
            'labels' => $rows->pluck('industry_name')->values()->all(),
            'data' => $rows->pluck('total')->map(fn ($value) => (int) $value)->values()->all(),
        ];
    }

    protected function tracedGraduates(array $filters): array
    {
        if (($filters['traced_mode'] ?? 'year') === 'month') {
            return $this->tracedGraduatesByMonth($filters);
        }

        $rows = collect();

        if (Schema::hasTable('tracer_records')) {
            $query = DB::table('tracer_records as tr')
                ->join('users as u', 'u.id', '=', 'tr.user_id')
                ->whereNotNull('u.year_graduated');

            $this->applyUserFilters($query, $filters, 'u');

            $rows = $query
                ->selectRaw('u.year_graduated as year_label, COUNT(DISTINCT tr.user_id) as total')
                ->groupBy('u.year_graduated')
                ->orderBy('u.year_graduated')
                ->get();
        }

        if ($rows->isEmpty()) {
            $query = DB::table('users as u')->whereNotNull('u.year_graduated');
            $this->applyUserFilters($query, $filters, 'u');

            $rows = $query
                ->selectRaw('u.year_graduated as year_label, COUNT(*) as total')
                ->groupBy('u.year_graduated')
                ->orderBy('u.year_graduated')
                ->get();
        }

        return [
            'labels' => $rows->pluck('year_label')->map(fn ($value) => (string) $value)->values()->all(),
            'data' => $rows->pluck('total')->map(fn ($value) => (int) $value)->values()->all(),
        ];
    }

    protected function tracedGraduatesByMonth(array $filters): array
    {
        $buckets = $this->monthBuckets(12);
        $monthExpression = $this->monthPeriodExpression('tr.tracer_date');
        $rows = collect();

        if (Schema::hasTable('tracer_records')) {
            $query = DB::table('tracer_records as tr')
                ->join('users as u', 'u.id', '=', 'tr.user_id')
                ->where('tr.tracer_date', '>=', $buckets->first()->copy()->startOfMonth()->toDateString());

            $this->applyUserFilters($query, $filters, 'u');

            $rows = $query
                ->selectRaw("{$monthExpression} as month_key, COUNT(DISTINCT tr.user_id) as total")
                ->groupByRaw($monthExpression)
                ->orderByRaw($monthExpression)
                ->get()
                ->keyBy('month_key');
        }

        if ($rows->isEmpty()) {
            $fallbackQuery = DB::table('users as u')
                ->where('u.created_at', '>=', $buckets->first()->copy()->startOfMonth());

            $this->applyUserFilters($fallbackQuery, $filters, 'u');

            $rows = $fallbackQuery
                ->selectRaw("{$this->monthPeriodExpression('u.created_at')} as month_key, COUNT(*) as total")
                ->groupByRaw($this->monthPeriodExpression('u.created_at'))
                ->orderByRaw($this->monthPeriodExpression('u.created_at'))
                ->get()
                ->keyBy('month_key');
        }

        return [
            'labels' => $buckets->map(fn (Carbon $date) => $date->format('M Y'))->values()->all(),
            'data' => $buckets->map(fn (Carbon $date) => $this->seriesValue($rows, $date->format('Y-m'), 'total'))->values()->all(),
        ];
    }

    protected function collegeTrends($latestTracerSubquery, array $filters): array
    {
        $query = DB::table('users as u')
            ->leftJoinSub($latestTracerSubquery, 'latest_tracer', 'latest_tracer.user_id', '=', 'u.id')
            ->selectRaw("
                COALESCE(NULLIF(TRIM(u.department), ''), 'Unknown Department') as department_name,
                SUM(CASE WHEN LOWER(COALESCE(latest_tracer.employment_type, '')) IN ('full-time', 'part-time', 'employed', 'employee') THEN 1 ELSE 0 END) as employed_count,
                SUM(CASE WHEN LOWER(COALESCE(latest_tracer.employment_type, '')) IN ('self-employed', 'self employed') THEN 1 ELSE 0 END) as self_employed_count,
                SUM(CASE WHEN LOWER(COALESCE(latest_tracer.employment_type, '')) = 'unemployed' THEN 1 ELSE 0 END) as unemployed_count
            ");

        $this->applyUserFilters($query, $filters, 'u');

        $rows = $query
            ->groupByRaw("COALESCE(NULLIF(TRIM(u.department), ''), 'Unknown Department')")
            ->orderByRaw("COALESCE(NULLIF(TRIM(u.department), ''), 'Unknown Department')")
            ->get();

        return [
            'labels' => $rows->pluck('department_name')->values()->all(),
            'employed' => $rows->pluck('employed_count')->map(fn ($value) => (int) $value)->values()->all(),
            'selfEmployed' => $rows->pluck('self_employed_count')->map(fn ($value) => (int) $value)->values()->all(),
            'unemployed' => $rows->pluck('unemployed_count')->map(fn ($value) => (int) $value)->values()->all(),
        ];
    }

    protected function employabilityData($latestTracerSubquery, array $filters): array
    {
        $query = DB::table('users as u')
            ->leftJoinSub($latestTracerSubquery, 'latest_tracer', 'latest_tracer.user_id', '=', 'u.id')
            ->whereNotNull('u.year_graduated');

        $this->applyUserFilters($query, $filters, 'u');

        $rows = $query
            ->selectRaw("
                u.year_graduated as year_label,
                COUNT(u.id) as total_alumni,
                SUM(CASE WHEN LOWER(COALESCE(latest_tracer.employment_type, '')) IN ('full-time', 'part-time', 'employed', 'employee', 'self-employed', 'self employed') THEN 1 ELSE 0 END) as employed_total
            ")
            ->groupBy('u.year_graduated')
            ->orderBy('u.year_graduated')
            ->get();

        return [
            'labels' => $rows->pluck('year_label')->map(fn ($value) => (string) $value)->values()->all(),
            'data' => $rows->map(function ($row) {
                $total = (int) $row->total_alumni;

                return $total === 0 ? 0 : round(((int) $row->employed_total / $total) * 100, 2);
            })->values()->all(),
        ];
    }

    protected function globalData(array $filters): array
    {
        $query = DB::table('users as u');
        $this->applyUserFilters($query, $filters, 'u');

        $rows = $query
            ->selectRaw("COALESCE(NULLIF(TRIM(u.location), ''), 'Unknown') as location_name, COUNT(*) as total")
            ->groupByRaw("COALESCE(NULLIF(TRIM(u.location), ''), 'Unknown')")
            ->orderByDesc('total')
            ->limit(8)
            ->get();

        return [
            'labels' => $rows->pluck('location_name')->values()->all(),
            'data' => $rows->pluck('total')->map(fn ($value) => (int) $value)->values()->all(),
        ];
    }

    protected function feedbacksData(array $filters): array
    {
        if (!Schema::hasTable('feedback')) {
            return ['labels' => [], 'data' => []];
        }

        $query = DB::table('feedback as f')
            ->join('users as u', 'u.id', '=', 'f.user_id');

        $this->applyUserFilters($query, $filters, 'u');

        $rows = $query
            ->selectRaw("COALESCE(NULLIF(TRIM(f.type), ''), 'General') as feedback_type, COUNT(*) as total")
            ->groupByRaw("COALESCE(NULLIF(TRIM(f.type), ''), 'General')")
            ->orderByDesc('total')
            ->get();

        return [
            'labels' => $rows->pluck('feedback_type')->values()->all(),
            'data' => $rows->pluck('total')->map(fn ($value) => (int) $value)->values()->all(),
        ];
    }

    protected function reasonsData(array $filters): array
    {
        if (!Schema::hasTable('tracer_records')) {
            return ['labels' => [], 'data' => []];
        }

        $query = DB::table('tracer_records as tr')
            ->join('users as u', 'u.id', '=', 'tr.user_id')
            ->whereRaw("LOWER(COALESCE(tr.employment_type, '')) = 'unemployed'");

        $this->applyUserFilters($query, $filters, 'u');

        $rows = $query
            ->selectRaw("COALESCE(NULLIF(TRIM(tr.challenges_faced), ''), 'Not Specified') as reason_label, COUNT(*) as total")
            ->groupByRaw("COALESCE(NULLIF(TRIM(tr.challenges_faced), ''), 'Not Specified')")
            ->orderByDesc('total')
            ->limit(8)
            ->get();

        return [
            'labels' => $rows->pluck('reason_label')->values()->all(),
            'data' => $rows->pluck('total')->map(fn ($value) => (int) $value)->values()->all(),
        ];
    }

    protected function filterOptions(): array
    {
        return [
            'colleges' => $this->availableColleges(),
            'graduationYears' => $this->availableGraduationYears(),
            'ageRanges' => $this->ageRangeOptions(),
        ];
    }

    protected function availableColleges(): array
    {
        $query = DB::table('users');
        $this->applyAlumniAccountScope($query);

        return $query
            ->selectRaw("COALESCE(NULLIF(TRIM(department), ''), 'Unknown Department') as department_name")
            ->distinct()
            ->orderBy('department_name')
            ->pluck('department_name')
            ->values()
            ->all();
    }

    protected function availableGraduationYears(): array
    {
        $query = DB::table('users');
        $this->applyAlumniAccountScope($query);

        return $query
            ->whereNotNull('year_graduated')
            ->distinct()
            ->pluck('year_graduated')
            ->map(fn ($value) => (int) $value)
            ->merge(range(2000, (int) date('Y')))
            ->unique()
            ->sortDesc()
            ->values()
            ->all();
    }

    protected function normalizeFilters(array $filters): array
    {
        $dateFrom = $this->normalizeDateFilter($filters['date_from'] ?? null);
        $dateTo = $this->normalizeDateFilter($filters['date_to'] ?? null);

        if ($dateFrom !== null && $dateTo !== null && Carbon::parse($dateFrom)->greaterThan(Carbon::parse($dateTo))) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }

        $employmentRange = (int) ($filters['employment_range'] ?? 12);
        if (!in_array($employmentRange, [3, 6, 12], true)) {
            $employmentRange = 12;
        }

        $tracedMode = (string) ($filters['traced_mode'] ?? 'year');
        if (!in_array($tracedMode, ['year', 'month'], true)) {
            $tracedMode = 'year';
        }

        $college = $this->cleanFilterValue($filters['college'] ?? null);
        $degree = $this->cleanFilterValue($filters['degree'] ?? null);
        $sex = $this->normalizeSexFilter($filters['sex'] ?? null);
        $civilStatus = $this->normalizeCivilStatusFilter($filters['civil_status'] ?? null);
        $legacyAgeRange = $this->normalizeAgeRangeFilter($filters['age_range'] ?? null);
        [$legacyAgeFrom, $legacyAgeTo] = $this->ageRangeBounds($legacyAgeRange);
        $ageFrom = $this->normalizeAgeBoundFilter($filters['age_from'] ?? null) ?? $legacyAgeFrom;
        $ageTo = $this->normalizeAgeBoundFilter($filters['age_to'] ?? null) ?? $legacyAgeTo;
        $graduationYear = $this->normalizeYearFilter($filters['graduation_year'] ?? null);
        $graduationYearFrom = $this->normalizeYearFilter($filters['graduation_year_from'] ?? null) ?? $graduationYear;
        $graduationYearTo = $this->normalizeYearFilter($filters['graduation_year_to'] ?? null) ?? $graduationYear;
        $employmentStatus = $this->cleanFilterValue($filters['employment_status'] ?? null);
        $industry = $this->cleanFilterValue($filters['industry'] ?? null);
        $location = $this->cleanFilterValue($filters['location'] ?? null);
        $salaryRange = SystemOptions::salaryRangeLabel($this->cleanFilterValue($filters['salary_range'] ?? null));

        if ($graduationYearFrom !== null && $graduationYearTo !== null && $graduationYearFrom > $graduationYearTo) {
            [$graduationYearFrom, $graduationYearTo] = [$graduationYearTo, $graduationYearFrom];
        }

        if ($ageFrom !== null && $ageTo !== null && $ageFrom > $ageTo) {
            [$ageFrom, $ageTo] = [$ageTo, $ageFrom];
        }

        if (! in_array($employmentStatus, ['employed', 'self_employed', 'unemployed', 'unknown'], true)) {
            $employmentStatus = null;
        }

        return [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'employment_range' => $employmentRange,
            'traced_mode' => $tracedMode,
            'college' => $college,
            'degree' => $degree,
            'sex' => $sex,
            'civil_status' => $civilStatus,
            'age_range' => $this->ageRangeValueFromBounds($ageFrom, $ageTo),
            'age_from' => $ageFrom,
            'age_to' => $ageTo,
            'graduation_year' => $graduationYearFrom !== null && $graduationYearFrom === $graduationYearTo ? $graduationYearFrom : null,
            'graduation_year_from' => $graduationYearFrom,
            'graduation_year_to' => $graduationYearTo,
            'employment_status' => $employmentStatus,
            'industry' => $industry,
            'location' => $location,
            'salary_range' => $salaryRange,
        ];
    }

    protected function usesDefaultFilters(array $filters): bool
    {
        return $filters['date_from'] === null
            && $filters['date_to'] === null
            && $filters['employment_range'] === 12
            && $filters['traced_mode'] === 'year'
            && $filters['college'] === null
            && $filters['degree'] === null
            && $filters['sex'] === null
            && $filters['civil_status'] === null
            && $filters['age_range'] === null
            && $filters['age_from'] === null
            && $filters['age_to'] === null
            && $filters['graduation_year'] === null
            && $filters['graduation_year_from'] === null
            && $filters['graduation_year_to'] === null
            && $filters['employment_status'] === null
            && $filters['industry'] === null
            && $filters['location'] === null
            && $filters['salary_range'] === null;
    }

    protected function cleanFilterValue(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    protected function normalizeYearFilter(mixed $value): ?int
    {
        $value = trim((string) $value);

        return preg_match('/^\d{4}$/', $value) ? (int) $value : null;
    }

    protected function normalizeSexFilter(?string $value): ?string
    {
        $value = $this->cleanFilterValue($value);

        return match (mb_strtolower((string) $value)) {
            'male' => 'Male',
            'female' => 'Female',
            default => null,
        };
    }

    protected function normalizeCivilStatusFilter(?string $value): ?string
    {
        $value = $this->cleanFilterValue($value);

        return match (mb_strtolower((string) $value)) {
            'single' => 'Single',
            'married' => 'Married',
            'divorced' => 'Divorced',
            'widowed' => 'Widowed',
            'separated' => 'Separated',
            default => null,
        };
    }

    protected function ageRangeOptions(): array
    {
        return [
            ['value' => '18-24', 'label' => '18 - 24'],
            ['value' => '25-34', 'label' => '25 - 34'],
            ['value' => '35-44', 'label' => '35 - 44'],
            ['value' => '45-54', 'label' => '45 - 54'],
            ['value' => '55+', 'label' => '55 and above'],
        ];
    }

    protected function normalizeAgeBoundFilter(mixed $value): ?int
    {
        $value = is_scalar($value) ? trim((string) $value) : '';

        if ($value === '' || ! preg_match('/^\d{1,3}$/', $value)) {
            return null;
        }

        $age = (int) $value;

        return $age >= 0 && $age <= 120 ? $age : null;
    }

    protected function ageRangeBounds(?string $ageRange): array
    {
        return match ($ageRange) {
            '18-24' => [18, 24],
            '25-34' => [25, 34],
            '35-44' => [35, 44],
            '45-54' => [45, 54],
            '55+' => [55, null],
            default => [null, null],
        };
    }

    protected function ageRangeValueFromBounds(?int $ageFrom, ?int $ageTo): ?string
    {
        foreach ($this->ageRangeOptions() as $option) {
            [$optionFrom, $optionTo] = $this->ageRangeBounds($option['value']);

            if ($optionFrom === $ageFrom && $optionTo === $ageTo) {
                return $option['value'];
            }
        }

        return null;
    }

    protected function normalizeAgeRangeFilter(?string $value): ?string
    {
        $value = $this->cleanFilterValue($value);

        if ($value === null) {
            return null;
        }

        $normalizedValue = $this->normalizeAgeRangeKey($value);

        foreach ($this->ageRangeOptions() as $option) {
            if (
                $normalizedValue === $this->normalizeAgeRangeKey($option['value'])
                || $normalizedValue === $this->normalizeAgeRangeKey($option['label'])
            ) {
                return $option['value'];
            }
        }

        return null;
    }

    protected function normalizeAgeRangeKey(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $value = str_replace(['–', '—'], '-', $value);
        $value = str_replace([' years old', ' years', ' yrs old', ' yrs'], '', $value);
        $value = str_replace([' and above', ' above', ' up', ' plus'], '+', $value);
        $value = preg_replace('/\s+/', '', $value) ?? $value;

        return str_replace('to', '-', $value);
    }

    protected function normalizeDateFilter(mixed $value): ?string
    {
        $value = $this->cleanFilterValue(is_string($value) ? $value : null);

        if ($value === null) {
            return null;
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    protected function applyUserFilters(Builder $query, array $filters, string $alias): void
    {
        $this->applyAlumniAccountScope($query, $alias);

        $this->applyCollegeFilter($query, $filters['college'], "{$alias}.department");

        if ($filters['degree'] !== null) {
            $this->applyExactTextFilter($query, "{$alias}.degree", $filters['degree']);
        }

        if ($filters['sex'] !== null && Schema::hasColumn('users', 'sex')) {
            $this->applyExactTextFilter($query, "{$alias}.sex", $filters['sex']);
        }

        if (($filters['civil_status'] ?? null) !== null && Schema::hasColumn('users', 'civil_status')) {
            $this->applyExactTextFilter($query, "{$alias}.civil_status", $filters['civil_status']);
        }

        if (($filters['age_from'] ?? null) !== null || ($filters['age_to'] ?? null) !== null) {
            $this->applyAgeBoundsFilter($query, $filters['age_from'], $filters['age_to'], $alias);
        }

        if (($filters['date_from'] ?? null) !== null) {
            $dateColumn = Schema::hasColumn('users', 'dt_grad') ? "{$alias}.dt_grad" : "{$alias}.created_at";
            $query->whereDate($dateColumn, '>=', $filters['date_from']);
        }

        if (($filters['date_to'] ?? null) !== null) {
            $dateColumn = Schema::hasColumn('users', 'dt_grad') ? "{$alias}.dt_grad" : "{$alias}.created_at";
            $query->whereDate($dateColumn, '<=', $filters['date_to']);
        }

        if ($filters['industry'] !== null) {
            $this->applyExactTextFilter($query, "{$alias}.industry", $filters['industry']);
        }

        if ($filters['location'] !== null) {
            $this->applyExactTextFilter($query, "{$alias}.location", $filters['location']);
        }

        if ($filters['salary_range'] !== null) {
            $this->applyExactTextFilter($query, "{$alias}.salary_range", $filters['salary_range']);
        }

        if ($filters['employment_status'] !== null) {
            $this->applyEmploymentStatusFilter($query, $filters['employment_status'], $alias);
        }

        if (($filters['graduation_year_from'] ?? null) !== null) {
            $query->where("{$alias}.year_graduated", '>=', $filters['graduation_year_from']);
        }

        if (($filters['graduation_year_to'] ?? null) !== null) {
            $query->where("{$alias}.year_graduated", '<=', $filters['graduation_year_to']);
        }
    }

    protected function applyAlumniAccountScope(Builder $query, string $alias = 'users'): void
    {
        if (Schema::hasColumn('users', 'role')) {
            $query->where("{$alias}.role", User::ROLE_ALUMNI);
        }

        if (Schema::hasColumn('users', 'is_batch_imported')) {
            $query->where(function (Builder $registeredQuery) use ($alias): void {
                $registeredQuery
                    ->whereNull("{$alias}.is_batch_imported")
                    ->orWhere("{$alias}.is_batch_imported", false);
            });
        }

        if (Schema::hasColumn('users', 'email')) {
            $query
                ->whereNotNull("{$alias}.email")
                ->whereRaw("TRIM(COALESCE({$alias}.email, '')) <> ''")
                ->whereRaw("LOWER(COALESCE({$alias}.email, '')) NOT LIKE ?", ['%@oarl-batch.local']);
        }
    }

    protected function applyExactTextFilter(Builder $query, string $column, string $value): void
    {
        $query->whereRaw(
            "LOWER(COALESCE(NULLIF(TRIM({$column}), ''), '')) = ?",
            [mb_strtolower($value)]
        );
    }

    protected function applyAgeBoundsFilter(Builder $query, ?int $minimumAge, ?int $maximumAge, string $alias): void
    {
        if (! Schema::hasColumn('users', 'birthdate')) {
            $query->whereRaw('1 = 0');

            return;
        }

        $today = now()->startOfDay();

        if ($minimumAge === null && $maximumAge === null) {
            return;
        }

        $query->whereNotNull("{$alias}.birthdate");

        if ($minimumAge !== null) {
            $query->whereDate("{$alias}.birthdate", '<=', $today->copy()->subYears($minimumAge)->toDateString());
        }

        if ($maximumAge !== null) {
            $query->whereDate("{$alias}.birthdate", '>=', $today->copy()->subYears($maximumAge + 1)->addDay()->toDateString());
        }
    }

    protected function applyEmploymentStatusFilter(Builder $query, string $status, string $alias): void
    {
        $normalizedTypeExpression = "LOWER(COALESCE(NULLIF(TRIM(trf.employment_type), ''), ''))";
        $latestTracerExists = "EXISTS (
            SELECT 1
            FROM tracer_records trf
            WHERE trf.user_id = {$alias}.id
                AND trf.tracer_date = (
                    SELECT MAX(tr2.tracer_date)
                    FROM tracer_records tr2
                    WHERE tr2.user_id = {$alias}.id
                )
        )";
        $latestTracerStatusExists = function (array $statuses) use ($alias, $normalizedTypeExpression): array {
            $placeholders = implode(', ', array_fill(0, count($statuses), '?'));

            return [
                "EXISTS (
                    SELECT 1
                    FROM tracer_records trf
                    WHERE trf.user_id = {$alias}.id
                        AND trf.tracer_date = (
                            SELECT MAX(tr2.tracer_date)
                            FROM tracer_records tr2
                            WHERE tr2.user_id = {$alias}.id
                        )
                        AND {$normalizedTypeExpression} IN ({$placeholders})
                )",
                $statuses,
            ];
        };

        if (! Schema::hasTable('tracer_records')) {
            match ($status) {
                'employed' => $query->where("{$alias}.employed_after_grad", 'Yes'),
                'unemployed' => $query->where("{$alias}.employed_after_grad", 'No'),
                'self_employed' => $query->whereRaw('1 = 0'),
                'unknown' => $query->where(function (Builder $statusQuery) use ($alias) {
                    $statusQuery
                        ->whereNull("{$alias}.employed_after_grad")
                        ->orWhereRaw("TRIM(COALESCE({$alias}.employed_after_grad, '')) = ''");
                }),
                default => null,
            };

            return;
        }

        match ($status) {
            'employed' => $query->where(function (Builder $statusQuery) use ($alias, $latestTracerStatusExists, $latestTracerExists) {
                [$sql, $bindings] = $latestTracerStatusExists(['full-time', 'part-time', 'employed', 'employee']);

                $statusQuery
                    ->whereRaw($sql, $bindings)
                    ->orWhere(function (Builder $fallbackQuery) use ($alias, $latestTracerExists) {
                        $fallbackQuery
                            ->whereRaw("NOT {$latestTracerExists}")
                            ->where("{$alias}.employed_after_grad", 'Yes');
                    });
            }),
            'self_employed' => $query->where(function (Builder $statusQuery) use ($latestTracerStatusExists) {
                [$sql, $bindings] = $latestTracerStatusExists(['self-employed', 'self employed']);

                $statusQuery->whereRaw($sql, $bindings);
            }),
            'unemployed' => $query->where(function (Builder $statusQuery) use ($alias, $latestTracerStatusExists, $latestTracerExists) {
                [$sql, $bindings] = $latestTracerStatusExists(['unemployed']);

                $statusQuery
                    ->whereRaw($sql, $bindings)
                    ->orWhere(function (Builder $fallbackQuery) use ($alias, $latestTracerExists) {
                        $fallbackQuery
                            ->whereRaw("NOT {$latestTracerExists}")
                            ->where("{$alias}.employed_after_grad", 'No');
                    });
            }),
            'unknown' => $query->where(function (Builder $statusQuery) use ($alias, $latestTracerExists) {
                $statusQuery
                    ->whereRaw("NOT {$latestTracerExists}")
                    ->where(function (Builder $fallbackQuery) use ($alias) {
                        $fallbackQuery
                            ->whereNull("{$alias}.employed_after_grad")
                            ->orWhereRaw("TRIM(COALESCE({$alias}.employed_after_grad, '')) = ''");
                    });
            }),
            default => null,
        };
    }

    protected function applyCollegeFilter(Builder $query, ?string $college, string $column): void
    {
        if ($college === null) {
            return;
        }

        $query->whereRaw(
            "LOWER(COALESCE(NULLIF(TRIM({$column}), ''), 'Unknown Department')) = ?",
            [mb_strtolower($college)]
        );
    }

    protected function monthBuckets(int $count): Collection
    {
        return collect(range($count - 1, 0))
            ->map(fn (int $offset) => now()->startOfMonth()->subMonths($offset))
            ->values();
    }

    protected function monthPeriodExpression(string $column): string
    {
        return DB::getDriverName() === 'sqlite'
            ? "strftime('%Y-%m', {$column})"
            : "TO_CHAR(DATE_TRUNC('month', {$column}), 'YYYY-MM')";
    }

    protected function seriesValue(Collection $rows, string $key, string $column): int
    {
        $row = $rows->get($key);

        return (int) ($row->{$column} ?? 0);
    }

    protected function withFallbacks(array $payload): array
    {
        if (empty($payload['employmentTrends']['labels'])) {
            $payload['employmentTrends'] = ['labels' => ['No Data'], 'employed' => [0], 'selfEmployed' => [0], 'unemployed' => [0]];
        }
        if (empty($payload['industryData']['labels'])) {
            $payload['industryData'] = ['labels' => ['No Data'], 'data' => [0]];
        }
        if (empty($payload['tracedGraduates']['labels'])) {
            $payload['tracedGraduates'] = ['labels' => ['No Data'], 'data' => [0]];
        }
        if (empty($payload['collegeTrends']['labels'])) {
            $payload['collegeTrends'] = ['labels' => ['No Data'], 'employed' => [0], 'selfEmployed' => [0], 'unemployed' => [0]];
        }
        if (empty($payload['employabilityData']['labels'])) {
            $payload['employabilityData'] = ['labels' => ['No Data'], 'data' => [0]];
        }
        if (empty($payload['globalData']['labels'])) {
            $payload['globalData'] = ['labels' => ['No Data'], 'data' => [0]];
        }
        if (empty($payload['feedbacksData']['labels'])) {
            $payload['feedbacksData'] = ['labels' => ['No Data'], 'data' => [0]];
        }
        if (empty($payload['reasonsData']['labels'])) {
            $payload['reasonsData'] = ['labels' => ['No Data'], 'data' => [0]];
        }

        return $payload;
    }
}
