<?php

namespace App\Services;

use App\Models\User;
use App\Support\SystemOptions;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AdminAnalyticsService
{
    public function __construct(
        protected AlumniDashboardService $alumniDashboardService
    ) {}

    public function analyticsPayload(array $filters = [], bool $forceRefresh = false): array
    {
        $filters = $this->normalizeFilters($filters);

        if ($this->usesDefaultFilters($filters)) {
            if ($forceRefresh) {
                Cache::forget($this->cacheKey());
            }

            return Cache::remember($this->cacheKey(), now()->addMinutes(5), function () use ($filters) {
                return $this->buildPayload($filters);
            });
        }

        return $this->buildPayload($filters);
    }

    protected function buildPayload(array $filters): array
    {
        $totalUsers = $this->filteredUsersCount($filters);
        $alumniAccountSummary = $this->alumniAccountSummary($filters, $totalUsers);
        $employmentStatus = $this->employmentStatusData($filters, $totalUsers);
        $curriculumRelevanceData = $this->curriculumRelevanceData($filters);
        $firstJobTimeData = $this->firstJobTimeData($filters);
        $presentEmploymentStatusData = $this->presentEmploymentStatusData($filters);
        $facilitiesSatisfactionData = $this->facilitiesSatisfactionData($filters);
        $teachingFeedbackData = $this->teachingFeedbackData($filters);
        $overallExperienceData = $this->overallExperienceData($filters);
        $satisfactionRatingsData = $this->satisfactionRatingsData($filters);
        $sharedAnalytics = $this->alumniDashboardService->payload([
            'employment_range' => 12,
            'traced_mode' => 'year',
            'date_from' => $filters['date_from'],
            'date_to' => $filters['date_to'],
            'sex' => $filters['sex'],
            'civil_status' => $filters['civil_status'],
            'age_range' => $filters['age_range'],
            'age_from' => $filters['age_from'],
            'age_to' => $filters['age_to'],
            'college' => $filters['college'],
            'degree' => $filters['degree'],
            'graduation_year' => $filters['graduation_year'],
            'graduation_year_from' => $filters['graduation_year_from'],
            'graduation_year_to' => $filters['graduation_year_to'],
            'employment_status' => $filters['employment_status'],
            'industry' => $filters['industry'],
            'location' => $filters['location'],
            'salary_range' => $filters['salary_range'],
        ]);

        return [
            'totalUsers' => $totalUsers,
            'alumniAccountSummary' => $alumniAccountSummary,
            'activeSessions' => $this->activeSessions(),
            'jobApplications' => $this->jobApplicationsCount($filters),
            'dataCompleteness' => $this->calculateDataCompleteness($totalUsers, $filters),
            'userGrowth' => $this->userGrowthData($filters),
            'jobApplicationsTrend' => $this->jobApplicationsTrendData($filters),
            'collegeDistribution' => $this->collegeDistributionData($filters),
            'employmentStatus' => $employmentStatus,
            'curriculumRelevanceData' => $curriculumRelevanceData,
            'firstJobTimeData' => $firstJobTimeData,
            'presentEmploymentStatusData' => $presentEmploymentStatusData,
            'facilitiesSatisfactionData' => $facilitiesSatisfactionData,
            'teachingFeedbackData' => $teachingFeedbackData,
            'overallExperienceData' => $overallExperienceData,
            'satisfactionRatingsData' => $satisfactionRatingsData,
            'feedbacksData' => $this->feedbacksData($filters),
            'graduationYearTrends' => $this->graduationYearTrendData($filters),
            'industryGrowth' => $this->industryGrowthData($filters),
            'recentActivity' => $this->recentActivity($filters),
            'employmentRate' => $this->safeRate($employmentStatus['employed'], $totalUsers),
            'selfEmploymentRate' => $this->safeRate($employmentStatus['self_employed'], $totalUsers),
            'unemploymentRate' => $this->safeRate($employmentStatus['unemployed'], $totalUsers),
            'employmentTrends' => $sharedAnalytics['employmentTrends'],
            'tracedGraduates' => $sharedAnalytics['tracedGraduates'],
            'industryData' => $sharedAnalytics['industryData'],
            'employabilityData' => $sharedAnalytics['employabilityData'],
            'globalData' => $sharedAnalytics['globalData'],
            'reasonsData' => $sharedAnalytics['reasonsData'],
            'filters' => $filters,
            'filterOptions' => $this->filterOptions($filters),
            'ui' => $this->uiState($filters),
            'csvNameSummary' => $this->nameSummaryRecords($this->latestTracerDetailRows($filters)),
        ];
    }

    public function csvNameSummary(array $filters = []): array
    {
        $filters = $this->normalizeFilters($filters);

        return $this->nameSummaryRecords($this->latestTracerDetailRows($filters));
    }

    public function employabilityCsvExport(array $filters = []): array
    {
        $filters = $this->normalizeFilters($filters);
        $rows = $this->latestTracerDetailRows($filters);
        $generatedAt = now();
        $csvRows = [
            ['Employability Report'],
            ['Generated', $generatedAt->format('F j, Y h:i A')],
            ['Scope', $this->exportScopeLine($filters)],
            [],
            [
                'Tracer No.',
                'Name',
                'Course',
                'Year',
                'Employment Status',
                'Name of Company',
                'Address',
                'Occupation',
                'If unemployed, reason for unemployment',
            ],
        ];

        foreach ($rows as $index => $row) {
            $employmentStatus = $this->employmentStatusLabel($row);

            $csvRows[] = [
                $this->displayTracerNumber($row, $index + 1),
                $this->alumniName($row),
                $this->exportValue($row->degree ?? null),
                $this->exportValue($row->year_graduated ?? null),
                $employmentStatus,
                $this->exportValue($row->company_name ?? null),
                $this->exportValue($row->address ?? null),
                $this->exportValue($row->occupation ?? null),
                mb_strtolower($employmentStatus) === 'unemployed'
                    ? $this->exportValue($row->unemployment_reason ?? null)
                    : '',
            ];
        }

        $csvRows = array_merge($csvRows, $this->nameSummaryRows($rows));

        return [
            'filename' => 'arlo-employability-report-'.$generatedAt->format('Y-m-d').'.csv',
            'rows' => $csvRows,
        ];
    }

    public function satisfactionCsvExport(array $filters = []): array
    {
        $filters = $this->normalizeFilters($filters);
        $rows = $this->latestTracerDetailRows($filters);
        $generatedAt = now();
        $csvRows = [
            ['Satisfaction Report'],
            ['Generated', $generatedAt->format('F j, Y h:i A')],
            ['Scope', $this->exportScopeLine($filters)],
            [],
            [
                'Tracer No.',
                'Name',
                'Course',
                'Year',
                'Satisfaction',
                'Comment/Suggestion',
            ],
        ];

        foreach ($rows as $index => $row) {
            $csvRows[] = [
                $this->displayTracerNumber($row, $index + 1),
                $this->alumniName($row),
                $this->exportValue($row->degree ?? null),
                $this->exportValue($row->year_graduated ?? null),
                $this->satisfactionLabel($row),
                $this->suggestionSummary($row),
            ];
        }

        $csvRows = array_merge($csvRows, $this->nameSummaryRows($rows));

        return [
            'filename' => 'arlo-satisfaction-report-'.$generatedAt->format('Y-m-d').'.csv',
            'rows' => $csvRows,
        ];
    }

    protected function activeSessions(): int
    {
        if (! $this->tableExists('sessions')) {
            return 0;
        }

        return DB::table('sessions')
            ->where('last_activity', '>=', now()->subMinutes(30)->timestamp)
            ->count();
    }

    protected function jobApplicationsCount(array $filters): int
    {
        if (! $this->tableExists('job_submissions')) {
            return 0;
        }

        if (! $this->hasUserScopedFilters($filters)) {
            return DB::table('job_submissions')->count();
        }

        $query = DB::table('job_submissions as js')
            ->join('users as u', 'u.id', '=', 'js.user_id');

        $this->applyUserFilters($query, $filters, 'u');

        return $query->count();
    }

    protected function calculateDataCompleteness(int $totalUsers, array $filters): float
    {
        if ($totalUsers === 0) {
            return 0;
        }

        $query = DB::table('users as u');
        $this->applyUserFilters($query, $filters, 'u');

        $completeProfiles = $query
            ->whereNotNull('department')
            ->whereNotNull('degree')
            ->whereNotNull('year_graduated')
            ->whereNotNull('employed_after_grad')
            ->count();

        return round(($completeProfiles / $totalUsers) * 100, 1);
    }

    protected function userGrowthData(array $filters): array
    {
        $months = $this->monthBucketsForDateRange($filters);
        $monthExpression = $this->monthPeriodExpression('created_at');
        $startMonth = $months->first()->copy();
        $endMonth = $months->last()->copy()->endOfMonth();

        $query = DB::table('users as u');
        $this->applyUserFilters($query, $filters, 'u');

        $counts = $query
            ->selectRaw("{$monthExpression} as period_key, COUNT(*) as total")
            ->where('u.created_at', '>=', $startMonth)
            ->where('u.created_at', '<=', $endMonth)
            ->groupByRaw($monthExpression)
            ->orderByRaw($monthExpression)
            ->get()
            ->mapWithKeys(fn ($row) => [(string) $row->period_key => (int) $row->total]);

        return [
            'labels' => $months->map(fn ($date) => $date->format('M'))->values()->all(),
            'data' => $months->map(fn ($date) => (int) ($counts[$date->format('Y-m')] ?? 0))->values()->all(),
        ];
    }

    protected function jobApplicationsTrendData(array $filters): array
    {
        $days = $this->dayBucketsForDateRange($filters);
        $labels = $days->map(fn ($day) => $day->format('M j'))->values()->all();

        if (! $this->tableExists('job_submissions')) {
            return ['labels' => $labels, 'data' => array_fill(0, count($labels), 0)];
        }

        $dayExpression = $this->dayPeriodExpression('js.created_at');
        $query = DB::table('job_submissions as js');

        if ($this->hasUserScopedFilters($filters)) {
            $query->join('users as u', 'u.id', '=', 'js.user_id');
            $this->applyUserFilters($query, $filters, 'u');
        }

        $counts = $query
            ->selectRaw("{$dayExpression} as period_key, COUNT(*) as total")
            ->where('js.created_at', '>=', $days->first()->copy())
            ->where('js.created_at', '<=', $days->last()->copy()->endOfDay())
            ->groupByRaw($dayExpression)
            ->orderByRaw($dayExpression)
            ->get()
            ->mapWithKeys(fn ($row) => [(string) $row->period_key => (int) $row->total]);

        return [
            'labels' => $labels,
            'data' => $days->map(fn ($day) => (int) ($counts[$day->format('Y-m-d')] ?? 0))->values()->all(),
        ];
    }

    protected function collegeDistributionData(array $filters): array
    {
        $query = DB::table('users as u');
        $this->applyUserFilters($query, $filters, 'u');

        $rows = $query
            ->selectRaw("COALESCE(NULLIF(TRIM(department), ''), 'Unknown') as department_name, COUNT(*) as total")
            ->groupByRaw("COALESCE(NULLIF(TRIM(department), ''), 'Unknown')")
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        if ($rows->isEmpty()) {
            return ['labels' => ['No Data'], 'data' => [0]];
        }

        return [
            'labels' => $rows->pluck('department_name')->values()->all(),
            'data' => $rows->pluck('total')->map(fn ($value) => (int) $value)->values()->all(),
        ];
    }

    protected function employmentStatusData(array $filters, int $totalUsers): array
    {
        if (! $this->tableExists('tracer_records')) {
            $fallbackQuery = DB::table('users as u');
            $this->applyUserFilters($fallbackQuery, $filters, 'u');

            $employed = (clone $fallbackQuery)->where('u.employed_after_grad', 'Yes')->count();
            $unemployed = (clone $fallbackQuery)->where('u.employed_after_grad', 'No')->count();
            $unknown = max(0, $totalUsers - $employed - $unemployed);

            return [
                'labels' => ['Employed', 'Unemployed', 'Self-Employed', 'Unknown'],
                'data' => [$employed, $unemployed, 0, $unknown],
                'employed' => $employed,
                'unemployed' => $unemployed,
                'self_employed' => 0,
                'unknown' => $unknown,
            ];
        }

        $latestTracerSubquery = $this->latestTracerSubquery();

        $query = DB::table('users as u')
            ->leftJoinSub($latestTracerSubquery, 'latest_tracer', 'latest_tracer.user_id', '=', 'u.id')
            ->selectRaw("
                COUNT(DISTINCT CASE
                    WHEN LOWER(COALESCE(latest_tracer.employment_type, '')) IN ('full-time', 'part-time', 'employed', 'employee') THEN u.id
                    WHEN latest_tracer.user_id IS NULL AND u.employed_after_grad = 'Yes' THEN u.id
                    ELSE NULL
                END) as employed,
                COUNT(DISTINCT CASE
                    WHEN LOWER(COALESCE(latest_tracer.employment_type, '')) IN ('self-employed', 'self employed') THEN u.id
                    ELSE NULL
                END) as self_employed,
                COUNT(DISTINCT CASE
                    WHEN LOWER(COALESCE(latest_tracer.employment_type, '')) = 'unemployed' THEN u.id
                    WHEN latest_tracer.user_id IS NULL AND u.employed_after_grad = 'No' THEN u.id
                    ELSE NULL
                END) as unemployed
            ");

        $this->applyUserFilters($query, $filters, 'u');

        $counts = $query->first();

        $employed = (int) ($counts->employed ?? 0);
        $selfEmployed = (int) ($counts->self_employed ?? 0);
        $unemployed = (int) ($counts->unemployed ?? 0);
        $unknown = max(0, $totalUsers - $employed - $selfEmployed - $unemployed);

        return [
            'labels' => ['Employed', 'Unemployed', 'Self-Employed', 'Unknown'],
            'data' => [$employed, $unemployed, $selfEmployed, $unknown],
            'employed' => $employed,
            'unemployed' => $unemployed,
            'self_employed' => $selfEmployed,
            'unknown' => $unknown,
        ];
    }

    protected function graduationYearTrendData(array $filters): array
    {
        $query = DB::table('users as u');
        $this->applyUserFilters($query, $filters, 'u');

        $rows = $query
            ->selectRaw('year_graduated as year_label, COUNT(*) as total')
            ->whereNotNull('u.year_graduated')
            ->groupBy('year_graduated')
            ->orderBy('year_graduated')
            ->get();

        if ($rows->isEmpty()) {
            return ['labels' => ['No Data'], 'data' => [0]];
        }

        return [
            'labels' => $rows->pluck('year_label')->map(fn ($value) => (string) $value)->values()->all(),
            'data' => $rows->pluck('total')->map(fn ($value) => (int) $value)->values()->all(),
        ];
    }

    protected function industryGrowthData(array $filters): array
    {
        $months = $this->monthBucketsForDateRange($filters);
        $labels = $months->map(fn ($date) => $date->format('M'))->values()->all();

        if (! $this->tableExists('tracer_records')) {
            return ['labels' => $labels, 'datasets' => []];
        }

        $topIndustriesQuery = DB::table('tracer_records as tr')
            ->join('users as u', 'u.id', '=', 'tr.user_id');

        $this->applyUserFilters($topIndustriesQuery, $filters, 'u');

        $topIndustries = $topIndustriesQuery
            ->selectRaw("COALESCE(NULLIF(TRIM(tr.industry), ''), 'Others') as industry_name, COUNT(*) as total")
            ->groupByRaw("COALESCE(NULLIF(TRIM(tr.industry), ''), 'Others')")
            ->orderByDesc('total')
            ->limit(3)
            ->pluck('industry_name');

        if ($topIndustries->isEmpty()) {
            return ['labels' => $labels, 'datasets' => []];
        }

        $datasets = $topIndustries->map(function ($industry) use ($months, $filters) {
            return [
                'label' => $industry,
                'data' => $months->map(function ($month) use ($industry, $filters) {
                    $query = DB::table('tracer_records as tr')
                        ->join('users as u', 'u.id', '=', 'tr.user_id');

                    $this->applyUserFilters($query, $filters, 'u');

                    return $query
                        ->whereRaw("COALESCE(NULLIF(TRIM(tr.industry), ''), 'Others') = ?", [$industry])
                        ->whereBetween('tr.tracer_date', [$month->copy(), $month->copy()->endOfMonth()])
                        ->count();
                })->values()->all(),
            ];
        })->values()->all();

        return ['labels' => $labels, 'datasets' => $datasets];
    }

    protected function curriculumRelevanceData(array $filters): array
    {
        $query = $this->latestTracerReportQuery($filters);

        if ($query === null) {
            return ['labels' => ['No Data'], 'data' => [0]];
        }

        $labelExpression = $this->booleanLabelExpression(
            'latest_tracer.curriculum_relevant_to_first_job',
            'Yes',
            'No',
            'No Answer'
        );

        $rows = $query
            ->selectRaw("{$labelExpression} as label, COUNT(*) as total")
            ->groupByRaw($labelExpression)
            ->get();

        return $this->orderedDistributionData($rows, ['Yes', 'No', 'No Answer']);
    }

    protected function firstJobTimeData(array $filters): array
    {
        $query = $this->latestTracerReportQuery($filters);

        if ($query === null) {
            return ['labels' => ['No Data'], 'data' => [0]];
        }

        $labelExpression = "
            CASE
                WHEN COALESCE(NULLIF(TRIM(latest_tracer.time_to_first_job), ''), '') = '' THEN 'No Answer'
                WHEN TRIM(latest_tracer.time_to_first_job) = 'Other' THEN 'Other'
                ELSE TRIM(latest_tracer.time_to_first_job)
            END
        ";

        $rows = $query
            ->selectRaw("{$labelExpression} as label, COUNT(*) as total")
            ->groupByRaw($labelExpression)
            ->get();

        return $this->orderedDistributionData(
            $rows,
            array_merge(SystemOptions::firstJobTimeOptions(), ['No Answer'])
        );
    }

    protected function presentEmploymentStatusData(array $filters): array
    {
        $query = $this->latestTracerReportQuery($filters);

        if ($query === null) {
            return ['labels' => ['No Data'], 'data' => [0]];
        }

        $labelExpression = "
            CASE
                WHEN COALESCE(NULLIF(TRIM(latest_tracer.present_employment_status), ''), '') = '' THEN 'No Answer'
                ELSE TRIM(latest_tracer.present_employment_status)
            END
        ";

        $rows = $query
            ->selectRaw("{$labelExpression} as label, COUNT(*) as total")
            ->groupByRaw($labelExpression)
            ->get();

        return $this->orderedDistributionData(
            $rows,
            array_merge(SystemOptions::presentEmploymentStatuses(), ['No Answer'])
        );
    }

    protected function facilitiesSatisfactionData(array $filters): array
    {
        $query = $this->latestTracerReportQuery($filters);

        if ($query === null) {
            return ['labels' => ['No Data'], 'data' => [0]];
        }

        $labelExpression = "
            CASE
                WHEN COALESCE(NULLIF(TRIM(latest_tracer.facilities_satisfaction), ''), '') = '' THEN 'No Answer'
                ELSE TRIM(latest_tracer.facilities_satisfaction)
            END
        ";

        $rows = $query
            ->selectRaw("{$labelExpression} as label, COUNT(*) as total")
            ->groupByRaw($labelExpression)
            ->get();

        return $this->orderedDistributionData(
            $rows,
            array_merge(SystemOptions::facilitiesSatisfactionOptions(), ['No Answer'])
        );
    }

    protected function teachingFeedbackData(array $filters): array
    {
        return $this->averageRatingBreakdownData($filters, [
            ['label' => 'Faculty enthusiasm', 'column' => 'teaching_faculty_enthusiastic'],
            ['label' => 'Explains concepts', 'column' => 'teaching_concepts_clear'],
            ['label' => 'Field knowledge', 'column' => 'teaching_faculty_knowledgeable'],
            ['label' => 'Teaching commitment', 'column' => 'teaching_commitment'],
            ['label' => 'Degree program quality', 'column' => 'degree_program_quality'],
        ]);
    }

    protected function overallExperienceData(array $filters): array
    {
        return $this->averageRatingBreakdownData($filters, [
            ['label' => 'Recommend institution', 'column' => 'overall_recommend_institution'],
            ['label' => 'Objectives fulfilled', 'column' => 'overall_objectives_met'],
            ['label' => 'University experience', 'column' => 'overall_university_experience'],
        ]);
    }

    protected function averageRatingBreakdownData(array $filters, array $items): array
    {
        $query = $this->latestTracerReportQuery($filters);

        if ($query === null) {
            return ['labels' => ['No Data'], 'data' => [0]];
        }

        $selects = collect($items)
            ->map(function (array $item, int $index) {
                $column = $item['column'];

                return "AVG(CASE WHEN latest_tracer.{$column} BETWEEN 1 AND 5 THEN latest_tracer.{$column} ELSE NULL END) as rating_{$index}";
            })
            ->implode(', ');
        $row = $query->selectRaw($selects)->first();
        $labels = [];
        $data = [];

        foreach ($items as $index => $item) {
            $value = $row?->{'rating_'.$index} ?? null;

            if ($value === null) {
                continue;
            }

            $labels[] = $item['label'];
            $data[] = round((float) $value, 1);
        }

        if ($labels === []) {
            return ['labels' => ['No Data'], 'data' => [0]];
        }

        return ['labels' => $labels, 'data' => $data];
    }

    protected function satisfactionRatingsData(array $filters): array
    {
        $query = $this->latestTracerReportQuery($filters);

        if ($query === null) {
            return ['labels' => ['No Data'], 'data' => [0]];
        }

        $teachingColumns = [
            'teaching_faculty_enthusiastic',
            'teaching_concepts_clear',
            'teaching_faculty_knowledgeable',
            'teaching_commitment',
            'degree_program_quality',
        ];
        $overallColumns = [
            'overall_recommend_institution',
            'overall_objectives_met',
            'overall_university_experience',
        ];
        $sumExpression = fn (array $columns) => collect($columns)
            ->map(fn (string $column) => "COALESCE(CASE WHEN latest_tracer.{$column} BETWEEN 1 AND 5 THEN latest_tracer.{$column} ELSE NULL END, 0)")
            ->implode(' + ');
        $countExpression = fn (array $columns) => collect($columns)
            ->map(fn (string $column) => "CASE WHEN latest_tracer.{$column} BETWEEN 1 AND 5 THEN 1 ELSE 0 END")
            ->implode(' + ');
        $facilitiesRatingExpression = "
            CASE
                WHEN LOWER(TRIM(COALESCE(latest_tracer.facilities_satisfaction, ''))) = 'very satisfied' THEN 5
                WHEN LOWER(TRIM(COALESCE(latest_tracer.facilities_satisfaction, ''))) = 'satisfied' THEN 3
                WHEN LOWER(TRIM(COALESCE(latest_tracer.facilities_satisfaction, ''))) = 'not satisfied' THEN 1
                ELSE NULL
            END
        ";

        $row = $query
            ->selectRaw("
                SUM({$sumExpression($teachingColumns)}) as teaching_sum,
                SUM({$countExpression($teachingColumns)}) as teaching_count,
                SUM({$facilitiesRatingExpression}) as facilities_sum,
                COUNT({$facilitiesRatingExpression}) as facilities_count,
                SUM({$sumExpression($overallColumns)}) as overall_sum,
                SUM({$countExpression($overallColumns)}) as overall_count
            ")
            ->first();

        $sections = [
            ['label' => 'Teaching Feedback', 'sum' => (float) ($row->teaching_sum ?? 0), 'count' => (int) ($row->teaching_count ?? 0)],
            ['label' => 'University Facilities', 'sum' => (float) ($row->facilities_sum ?? 0), 'count' => (int) ($row->facilities_count ?? 0)],
            ['label' => 'Overall Experience', 'sum' => (float) ($row->overall_sum ?? 0), 'count' => (int) ($row->overall_count ?? 0)],
        ];
        $labels = [];
        $data = [];

        foreach ($sections as $section) {
            if ($section['count'] <= 0) {
                continue;
            }

            $labels[] = $section['label'];
            $data[] = round($section['sum'] / $section['count'], 1);
        }

        if ($labels === []) {
            return ['labels' => ['No Data'], 'data' => [0]];
        }

        return ['labels' => $labels, 'data' => $data];
    }

    protected function feedbacksData(array $filters): array
    {
        if (! $this->tableExists('feedback')) {
            return ['labels' => ['No Data'], 'data' => [0]];
        }

        $query = DB::table('feedback as f')
            ->join('users as u', 'u.id', '=', 'f.user_id');

        $this->applyUserFilters($query, $filters, 'u');

        $rows = $query
            ->selectRaw("COALESCE(NULLIF(TRIM(f.type), ''), 'General Feedback') as label, COUNT(*) as total")
            ->groupByRaw("COALESCE(NULLIF(TRIM(f.type), ''), 'General Feedback')")
            ->get();

        return $this->orderedDistributionData(
            $rows,
            SystemOptions::feedbackTypes()
        );
    }

    protected function recentActivity(array $filters): array
    {
        $activities = [];

        $latestUserQuery = DB::table('users as u')->orderByDesc('u.created_at');
        $this->applyUserFilters($latestUserQuery, $filters, 'u');
        $latestUser = $latestUserQuery->first();
        if ($latestUser) {
            $activities[] = [
                'label' => 'New alumni registration',
                'time' => ! empty($latestUser->created_at)
                    ? Carbon::parse($latestUser->created_at)->diffForHumans()
                    : 'N/A',
            ];
        }

        if ($this->tableExists('jobs')) {
            $latestJob = DB::table('jobs')->orderByDesc('created_at')->first();
            if ($latestJob) {
                $activities[] = [
                    'label' => 'Job posting created',
                    'time' => $latestJob->created_at ? Carbon::parse($latestJob->created_at)->diffForHumans() : 'N/A',
                ];
            }
        }

        if ($this->tableExists('announcements')) {
            $latestAnnouncement = DB::table('announcements')->orderByDesc('created_at')->first();
            if ($latestAnnouncement) {
                $activities[] = [
                    'label' => 'Announcement published',
                    'time' => $latestAnnouncement->created_at ? Carbon::parse($latestAnnouncement->created_at)->diffForHumans() : 'N/A',
                ];
            }
        }

        if ($this->tableExists('job_submissions')) {
            $latestApplicationQuery = DB::table('job_submissions as js')->orderByDesc('js.created_at');
            if ($this->hasUserScopedFilters($filters)) {
                $latestApplicationQuery->join('users as u', 'u.id', '=', 'js.user_id');
                $this->applyUserFilters($latestApplicationQuery, $filters, 'u');
            }
            $latestApplication = $latestApplicationQuery->first();
            if ($latestApplication) {
                $activities[] = [
                    'label' => 'Job opportunity submitted',
                    'time' => $latestApplication->created_at ? Carbon::parse($latestApplication->created_at)->diffForHumans() : 'N/A',
                ];
            }
        }

        return array_slice($activities, 0, 4);
    }

    protected function latestTracerSubquery()
    {
        $tracerNumberSelect = Schema::hasColumn('tracer_records', 'tracer_number')
            ? 'tr1.tracer_number'
            : DB::raw('NULL as tracer_number');

        return DB::table('tracer_records as tr1')
            ->select(
                'tr1.id',
                $tracerNumberSelect,
                'tr1.user_id',
                'tr1.tracer_date',
                'tr1.employment_type',
                'tr1.current_position',
                'tr1.current_company',
                'tr1.job_satisfaction',
                'tr1.curriculum_relevant_to_first_job',
                'tr1.time_to_first_job',
                'tr1.present_employment_status',
                'tr1.facilities_satisfaction',
                'tr1.curriculum_suggestions',
                'tr1.employability_suggestions',
                'tr1.facility_suggestions',
                'tr1.teaching_faculty_enthusiastic',
                'tr1.teaching_concepts_clear',
                'tr1.teaching_faculty_knowledgeable',
                'tr1.teaching_commitment',
                'tr1.degree_program_quality',
                'tr1.overall_recommend_institution',
                'tr1.overall_objectives_met',
                'tr1.overall_university_experience',
                'tr1.challenges_faced',
                'tr1.recommendations'
            )
            ->whereRaw('
                tr1.id = (
                    SELECT tr2.id
                    FROM tracer_records tr2
                    WHERE tr2.user_id = tr1.user_id
                    ORDER BY tr2.tracer_date DESC, tr2.created_at DESC, tr2.id DESC
                    LIMIT 1
                )
            ');
    }

    protected function latestTracerReportQuery(array $filters)
    {
        if (! $this->tableExists('tracer_records')) {
            return null;
        }

        $query = DB::table('users as u')
            ->joinSub($this->latestTracerSubquery(), 'latest_tracer', 'latest_tracer.user_id', '=', 'u.id');

        $this->applyUserFilters($query, $filters, 'u');

        return $query;
    }

    protected function latestTracerDetailRows(array $filters)
    {
        $query = DB::table('users as u');

        if ($this->tableExists('tracer_records')) {
            $query->leftJoinSub($this->latestTracerSubquery(), 'latest_tracer', 'latest_tracer.user_id', '=', 'u.id')
                ->select([
                    'u.id as user_id',
                    'u.firstname',
                    'u.lastname',
                    'u.degree',
                    'u.year_graduated',
                    'u.employed_after_grad',
                    'u.location as address',
                    'latest_tracer.id as tracer_id',
                    'latest_tracer.tracer_number',
                    'latest_tracer.tracer_date',
                    'latest_tracer.employment_type',
                    'latest_tracer.job_satisfaction',
                    'latest_tracer.facilities_satisfaction',
                    'latest_tracer.teaching_faculty_enthusiastic',
                    'latest_tracer.teaching_concepts_clear',
                    'latest_tracer.teaching_faculty_knowledgeable',
                    'latest_tracer.teaching_commitment',
                    'latest_tracer.degree_program_quality',
                    'latest_tracer.overall_recommend_institution',
                    'latest_tracer.overall_objectives_met',
                    'latest_tracer.overall_university_experience',
                    'latest_tracer.curriculum_suggestions',
                    'latest_tracer.employability_suggestions',
                    'latest_tracer.facility_suggestions',
                    'latest_tracer.recommendations',
                    'latest_tracer.challenges_faced as unemployment_reason',
                    DB::raw("COALESCE(NULLIF(TRIM(latest_tracer.current_company), ''), NULLIF(TRIM(u.company), '')) as company_name"),
                    DB::raw("COALESCE(NULLIF(TRIM(latest_tracer.current_position), ''), NULLIF(TRIM(u.current_position), '')) as occupation"),
                ]);
        } else {
            $query->select([
                'u.id as user_id',
                'u.firstname',
                'u.lastname',
                'u.degree',
                'u.year_graduated',
                'u.employed_after_grad',
                'u.location as address',
                'u.company as company_name',
                'u.current_position as occupation',
                DB::raw('NULL as tracer_id'),
                DB::raw('NULL as tracer_number'),
                DB::raw('NULL as tracer_date'),
                DB::raw('NULL as employment_type'),
                DB::raw('NULL as job_satisfaction'),
                DB::raw('NULL as facilities_satisfaction'),
                DB::raw('NULL as teaching_faculty_enthusiastic'),
                DB::raw('NULL as teaching_concepts_clear'),
                DB::raw('NULL as teaching_faculty_knowledgeable'),
                DB::raw('NULL as teaching_commitment'),
                DB::raw('NULL as degree_program_quality'),
                DB::raw('NULL as overall_recommend_institution'),
                DB::raw('NULL as overall_objectives_met'),
                DB::raw('NULL as overall_university_experience'),
                DB::raw('NULL as curriculum_suggestions'),
                DB::raw('NULL as employability_suggestions'),
                DB::raw('NULL as facility_suggestions'),
                DB::raw('NULL as recommendations'),
                DB::raw('NULL as unemployment_reason'),
            ]);
        }

        $this->applyUserFilters($query, $filters, 'u');

        return $query
            ->orderByRaw("COALESCE(NULLIF(TRIM(u.degree), ''), 'ZZZ')")
            ->orderBy('u.year_graduated')
            ->orderBy('u.lastname')
            ->orderBy('u.firstname')
            ->get();
    }

    protected function exportScopeLine(array $filters): string
    {
        $ui = $this->uiState($filters);
        $scope = [
            'College: '.$ui['selectedCollegeLabel'],
            'Course: '.$ui['selectedDegreeLabel'],
            'Year: '.$ui['selectedGraduationYearLabel'],
            'Sex: '.$ui['selectedSexLabel'],
            'Age: '.$ui['selectedAgeRangeLabel'],
            'Employment: '.$ui['selectedEmploymentStatusLabel'],
        ];

        if ($filters['industry'] !== null) {
            $scope[] = 'Industry: '.$ui['selectedIndustryLabel'];
        }

        if ($filters['location'] !== null) {
            $scope[] = 'Location: '.$ui['selectedLocationLabel'];
        }

        if ($filters['salary_range'] !== null) {
            $scope[] = 'Salary: '.$ui['selectedSalaryRangeLabel'];
        }

        return implode(' | ', $scope);
    }

    protected function nameSummaryRecords($rows): array
    {
        return $rows
            ->values()
            ->map(fn ($row, int $index) => [
                'tracerNumber' => $this->displayTracerNumber($row, $index + 1),
                'name' => $this->alumniName($row),
                'course' => $this->exportValue($row->degree ?? null),
                'year' => $this->exportValue($row->year_graduated ?? null),
                'employmentStatus' => $this->employmentStatusLabel($row),
                'company' => $this->exportValue($row->company_name ?? null),
                'address' => $this->exportValue($row->address ?? null),
                'occupation' => $this->exportValue($row->occupation ?? null),
                'unemploymentReason' => mb_strtolower($this->employmentStatusLabel($row)) === 'unemployed'
                    ? $this->exportValue($row->unemployment_reason ?? null)
                    : '',
            ])
            ->values()
            ->all();
    }

    protected function nameSummaryRows($rows): array
    {
        $summaryRecords = $this->nameSummaryRecords($rows);
        $summaryRows = [
            [],
            ['Names Summary'],
            ['Total Names', count($summaryRecords)],
            [],
            [
                'Tracer No.',
                'Name',
                'Course',
                'Year',
                'Employment Status',
                'Name of Company',
                'Address',
                'Occupation',
                'If unemployed, reason for unemployment',
            ],
        ];

        foreach ($summaryRecords as $record) {
            $summaryRows[] = [
                $record['tracerNumber'],
                $record['name'],
                $record['course'],
                $record['year'],
                $record['employmentStatus'],
                $record['company'],
                $record['address'],
                $record['occupation'],
                $record['unemploymentReason'],
            ];
        }

        if ($summaryRecords === []) {
            $summaryRows[] = ['', 'No names matched the current filters.', '', '', '', '', '', '', ''];
        }

        return $summaryRows;
    }

    protected function displayTracerNumber(object $row, int $fallbackIndex): string
    {
        $tracerNumber = $this->cleanExportText($row->tracer_number ?? null);

        return $tracerNumber ?: sprintf('UTF-%05dA', $fallbackIndex);
    }

    protected function alumniName(object $row): string
    {
        $name = trim(implode(' ', array_filter([
            $this->cleanExportText($row->firstname ?? null),
            $this->cleanExportText($row->lastname ?? null),
        ])));

        return $name ?: 'N/A';
    }

    protected function employmentStatusLabel(object $row): string
    {
        $employmentType = mb_strtolower($this->cleanExportText($row->employment_type ?? null));

        if (in_array($employmentType, ['full-time', 'part-time', 'employed', 'employee'], true)) {
            return 'Employed';
        }

        if (in_array($employmentType, ['self-employed', 'self employed'], true)) {
            return 'Self-Employed';
        }

        if ($employmentType === 'unemployed') {
            return 'Unemployed';
        }

        $fallback = mb_strtolower($this->cleanExportText($row->employed_after_grad ?? null));

        return match ($fallback) {
            'yes' => 'Employed',
            'no' => 'Unemployed',
            default => 'Unknown',
        };
    }

    protected function satisfactionLabel(object $row): string
    {
        $facilitySatisfaction = $this->cleanExportText($row->facilities_satisfaction ?? null);

        if ($facilitySatisfaction !== '') {
            return $facilitySatisfaction;
        }

        $ratings = collect([
            $row->job_satisfaction ?? null,
            $row->teaching_faculty_enthusiastic ?? null,
            $row->teaching_concepts_clear ?? null,
            $row->teaching_faculty_knowledgeable ?? null,
            $row->teaching_commitment ?? null,
            $row->degree_program_quality ?? null,
            $row->overall_recommend_institution ?? null,
            $row->overall_objectives_met ?? null,
            $row->overall_university_experience ?? null,
        ])
            ->filter(fn ($value) => is_numeric($value) && (float) $value > 0)
            ->map(fn ($value) => (float) $value)
            ->values();

        if ($ratings->isEmpty()) {
            return 'No Answer';
        }

        return 'Average '.number_format($ratings->avg(), 1).'/5';
    }

    protected function suggestionSummary(object $row): string
    {
        $suggestions = collect([
            'Curriculum' => $row->curriculum_suggestions ?? null,
            'Employability' => $row->employability_suggestions ?? null,
            'Facilities' => $row->facility_suggestions ?? null,
            'Recommendation' => $row->recommendations ?? null,
        ])
            ->map(fn ($value) => $this->cleanExportText($value))
            ->filter()
            ->map(fn ($value, $label) => "{$label}: {$value}")
            ->values();

        return $suggestions->isEmpty() ? 'N/A' : $suggestions->implode(' | ');
    }

    protected function exportValue(mixed $value): string
    {
        $cleaned = $this->cleanExportText($value);

        return $cleaned === '' ? 'N/A' : $cleaned;
    }

    protected function cleanExportText(mixed $value): string
    {
        return trim(preg_replace('/\s+/', ' ', (string) ($value ?? '')));
    }

    protected function monthBuckets(int $count = 6)
    {
        return collect(range($count - 1, 0))
            ->map(fn ($offset) => now()->startOfMonth()->subMonths($offset))
            ->values();
    }

    protected function dayBuckets(int $count = 7)
    {
        return collect(range($count - 1, 0))
            ->map(fn ($offset) => now()->startOfDay()->subDays($offset))
            ->values();
    }

    protected function monthBucketsForDateRange(array $filters)
    {
        $start = $this->dateFilterStart($filters)?->copy()->startOfMonth();
        $end = $this->dateFilterEnd($filters)?->copy()->startOfMonth();

        if ($start === null || $end === null) {
            return $this->monthBuckets((int) ($filters['user_growth_range'] ?? 6));
        }

        if ($start->greaterThan($end)) {
            [$start, $end] = [$end, $start];
        }

        $months = collect();
        $cursor = $start->copy();

        while ($cursor->lessThanOrEqualTo($end)) {
            $months->push($cursor->copy());
            $cursor->addMonth();
        }

        return $months->take(-24)->values();
    }

    protected function dayBucketsForDateRange(array $filters)
    {
        $start = $this->dateFilterStart($filters)?->copy()->startOfDay();
        $end = $this->dateFilterEnd($filters)?->copy()->startOfDay();

        if ($start === null || $end === null) {
            return $this->dayBuckets((int) ($filters['applications_range'] ?? 7));
        }

        if ($start->greaterThan($end)) {
            [$start, $end] = [$end, $start];
        }

        $days = collect();
        $cursor = $start->copy();

        while ($cursor->lessThanOrEqualTo($end)) {
            $days->push($cursor->copy());
            $cursor->addDay();
        }

        return $days->take(-62)->values();
    }

    protected function dateFilterStart(array $filters): ?Carbon
    {
        return $this->parseFilterDate($filters['date_from'] ?? null);
    }

    protected function dateFilterEnd(array $filters): ?Carbon
    {
        return $this->parseFilterDate($filters['date_to'] ?? null);
    }

    protected function parseFilterDate(mixed $value): ?Carbon
    {
        $value = $this->cleanFilterValue(is_string($value) ? $value : null);

        if ($value === null) {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    protected function monthPeriodExpression(string $column): string
    {
        return DB::getDriverName() === 'sqlite'
            ? "strftime('%Y-%m', {$column})"
            : "TO_CHAR(DATE_TRUNC('month', {$column}), 'YYYY-MM')";
    }

    protected function dayPeriodExpression(string $column): string
    {
        return DB::getDriverName() === 'sqlite'
            ? "date({$column})"
            : "TO_CHAR(DATE_TRUNC('day', {$column}), 'YYYY-MM-DD')";
    }

    protected function tableExists(string $table): bool
    {
        return Schema::hasTable($table);
    }

    protected function filteredUsersCount(array $filters): int
    {
        $query = DB::table('users as u');
        $this->applyUserFilters($query, $filters, 'u');

        return $query->count();
    }

    protected function alumniAccountSummary(array $filters, ?int $analyticsMatched = null): array
    {
        $rosterFilters = $filters;
        $rosterFilters['employment_status'] = null;
        $rosterFilters['industry'] = null;
        $rosterFilters['location'] = null;
        $rosterFilters['salary_range'] = null;

        $registeredQuery = DB::table('users as u');
        $this->applyUserFilters($registeredQuery, $rosterFilters, 'u');

        $notRegisteredQuery = DB::table('users as u');
        $this->applyUserFilters($notRegisteredQuery, $rosterFilters, 'u', false);
        $this->applyCsvOnlyAlumniFilter($notRegisteredQuery, 'u');

        $tracerRespondents = 0;

        if ($this->tableExists('tracer_records')) {
            $tracerQuery = DB::table('tracer_records as tr')
                ->join('users as u', 'u.id', '=', 'tr.user_id');

            $this->applyUserFilters($tracerQuery, $filters, 'u');
            $tracerRespondents = $tracerQuery->distinct()->count('tr.user_id');
        }

        return [
            'roster_total' => (clone $registeredQuery)->count(),
            'registered' => $registeredQuery->count(),
            'not_registered' => $notRegisteredQuery->count(),
            'analytics_matched' => $analyticsMatched ?? $this->filteredUsersCount($filters),
            'tracer_respondents' => $tracerRespondents,
        ];
    }

    protected function filterOptions(array $filters = []): array
    {
        return [
            'colleges' => $this->availableColleges(),
            'degrees' => $this->availableDegrees($filters['college'] ?? null),
            'sexes' => $this->availableSexes(),
            'civilStatuses' => $this->availableCivilStatuses(),
            'ageRanges' => $this->ageRangeOptions(),
            'graduationYears' => $this->availableGraduationYears(),
            'employmentStatuses' => [
                ['value' => 'employed', 'label' => 'Employed'],
                ['value' => 'self_employed', 'label' => 'Self-Employed'],
                ['value' => 'unemployed', 'label' => 'Unemployed'],
                ['value' => 'unknown', 'label' => 'Unknown'],
            ],
            'industries' => $this->availableIndustries(),
            'locations' => $this->availableLocations(),
            'salaryRanges' => $this->availableSalaryRanges(),
        ];
    }

    protected function availableColleges(): array
    {
        $query = DB::table('users');
        $this->applyAlumniAccountScope($query);

        $databaseColleges = $query
            ->selectRaw("COALESCE(NULLIF(TRIM(department), ''), 'Unknown Department') as department_name")
            ->distinct()
            ->orderBy('department_name')
            ->pluck('department_name')
            ->values()
            ->all();

        return $this->mergeOptionLists(SystemOptions::departments(), $databaseColleges);
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

    protected function availableDegrees(?string $college = null): array
    {
        $preferred = $college !== null && trim($college) !== ''
            ? SystemOptions::programsForDepartment($college)
            : collect(SystemOptions::departmentProgramOptions())->flatten()->values()->all();

        $query = DB::table('users')
            ->selectRaw("COALESCE(NULLIF(TRIM(degree), ''), NULL) as option_value")
            ->whereNotNull('degree')
            ->whereRaw("TRIM(degree) <> ''");

        $this->applyAlumniAccountScope($query);

        if ($college !== null && trim($college) !== '') {
            $query->whereRaw(
                "LOWER(COALESCE(NULLIF(TRIM(department), ''), 'Unknown Department')) = ?",
                [mb_strtolower(trim($college))]
            );
        }

        $databaseDegrees = $query
            ->distinct()
            ->orderBy('option_value')
            ->pluck('option_value')
            ->filter(fn ($value) => is_string($value) && trim($value) !== '')
            ->values()
            ->all();

        return $this->mergeOptionLists($preferred, $databaseDegrees);
    }

    protected function availableSexes(): array
    {
        return ['Male', 'Female'];
    }

    protected function availableCivilStatuses(): array
    {
        return ['Single', 'Married', 'Divorced', 'Widowed', 'Separated'];
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

    protected function availableIndustries(): array
    {
        return $this->mergeOptionLists(
            SystemOptions::industries(),
            $this->availableDistinctValues('industry')
        );
    }

    protected function availableLocations(): array
    {
        return $this->mergeOptionLists(
            SystemOptions::locations(),
            $this->availableDistinctValues('location')
        );
    }

    protected function availableSalaryRanges(): array
    {
        return $this->mergeOptionLists(
            SystemOptions::salaryRanges(),
            array_map(
                fn ($value) => SystemOptions::salaryRangeLabel($value) ?? $value,
                $this->availableDistinctValues('salary_range')
            )
        );
    }

    protected function availableDistinctValues(string $column): array
    {
        $query = DB::table('users')
            ->selectRaw("COALESCE(NULLIF(TRIM({$column}), ''), NULL) as option_value")
            ->whereNotNull($column)
            ->whereRaw("TRIM({$column}) <> ''");

        $this->applyAlumniAccountScope($query);

        return $query
            ->distinct()
            ->orderBy('option_value')
            ->pluck('option_value')
            ->filter(fn ($value) => is_string($value) && trim($value) !== '')
            ->values()
            ->all();
    }

    protected function mergeOptionLists(array $preferred, array $additional): array
    {
        return collect($preferred)
            ->concat($additional)
            ->filter(fn ($value) => is_string($value) && trim($value) !== '')
            ->map(fn ($value) => trim($value))
            ->unique(fn ($value) => mb_strtolower($value))
            ->values()
            ->all();
    }

    protected function orderedDistributionData($rows, array $preferredOrder): array
    {
        $counts = collect($rows)
            ->mapWithKeys(fn ($row) => [(string) $row->label => (int) $row->total]);

        $labels = [];
        $data = [];

        foreach ($preferredOrder as $label) {
            if (! $counts->has($label)) {
                continue;
            }

            $labels[] = $label;
            $data[] = $counts->pull($label);
        }

        foreach ($counts as $label => $total) {
            $labels[] = (string) $label;
            $data[] = (int) $total;
        }

        if ($labels === []) {
            return ['labels' => ['No Data'], 'data' => [0]];
        }

        return ['labels' => $labels, 'data' => $data];
    }

    protected function booleanLabelExpression(
        string $column,
        string $trueLabel,
        string $falseLabel,
        string $emptyLabel
    ): string {
        return "
            CASE
                WHEN {$column} IS TRUE THEN '{$trueLabel}'
                WHEN {$column} IS FALSE THEN '{$falseLabel}'
                ELSE '{$emptyLabel}'
            END
        ";
    }

    protected function normalizeFilters(array $filters): array
    {
        $dateFrom = $this->normalizeDateFilter($filters['date_from'] ?? null);
        $dateTo = $this->normalizeDateFilter($filters['date_to'] ?? null);

        if ($dateFrom !== null && $dateTo !== null && Carbon::parse($dateFrom)->greaterThan(Carbon::parse($dateTo))) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }

        $userGrowthRange = (int) ($filters['user_growth_range'] ?? 6);
        if (! in_array($userGrowthRange, [3, 6, 12], true)) {
            $userGrowthRange = 6;
        }

        $applicationsRange = (int) ($filters['applications_range'] ?? 7);
        if (! in_array($applicationsRange, [7, 14, 30], true)) {
            $applicationsRange = 7;
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
            'user_growth_range' => $userGrowthRange,
            'applications_range' => $applicationsRange,
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
            'charts' => $this->normalizeChartSelection($filters['charts'] ?? []),
        ];
    }

    protected function usesDefaultFilters(array $filters): bool
    {
        return $filters['user_growth_range'] === 6
            && $filters['applications_range'] === 7
            && $filters['date_from'] === null
            && $filters['date_to'] === null
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
            && $filters['salary_range'] === null
            && $this->chartSelectionIsDefault($filters['charts']);
    }

    protected function cacheKey(): string
    {
        return 'admin-analytics:payload:v20';
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

    protected function defaultChartSelection(): array
    {
        return [
            'employmentTrendsChart',
            'tracedGraduatesChart',
            'industryChart',
            'employabilityChart',
            'globalDistributionChart',
            'reasonsChart',
            'collegeDistributionChart',
            'employmentStatusChart',
            'graduationYearChart',
        ];
    }

    protected function availableChartSelection(): array
    {
        return [
            'employmentTrendsChart',
            'tracedGraduatesChart',
            'industryChart',
            'employabilityChart',
            'globalDistributionChart',
            'reasonsChart',
            'userGrowthChart',
            'jobApplicationsChart',
            'collegeDistributionChart',
            'employmentStatusChart',
            'graduationYearChart',
            'industryGrowthChart',
            'feedbacksChart',
            'curriculumRelevanceChart',
            'firstJobTimeChart',
            'presentEmploymentStatusChart',
            'teachingFeedbackChart',
            'facilitiesSatisfactionChart',
            'overallExperienceChart',
        ];
    }

    protected function normalizeChartSelection(mixed $charts): array
    {
        $allowed = $this->availableChartSelection();
        $selected = collect((array) $charts)
            ->map(fn ($chart) => is_string($chart) ? trim($chart) : '')
            ->filter()
            ->unique()
            ->filter(fn ($chart) => in_array($chart, $allowed, true))
            ->values()
            ->all();

        return $selected === [] ? $this->defaultChartSelection() : $selected;
    }

    protected function chartSelectionIsDefault(array $charts): bool
    {
        $defaultCharts = $this->defaultChartSelection();

        return count($charts) === count($defaultCharts)
            && empty(array_diff($charts, $defaultCharts))
            && empty(array_diff($defaultCharts, $charts));
    }

    protected function applyUserFilters(Builder $query, array $filters, string $alias = 'users', bool $registeredOnly = true): void
    {
        $this->applyAlumniAccountScope($query, $alias, $registeredOnly);

        if ($filters['college'] !== null) {
            $query->whereRaw(
                "LOWER(COALESCE(NULLIF(TRIM({$alias}.department), ''), 'Unknown Department')) = ?",
                [mb_strtolower($filters['college'])]
            );
        }

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

        if (($filters['graduation_year_from'] ?? null) !== null) {
            $query->where("{$alias}.year_graduated", '>=', $filters['graduation_year_from']);
        }

        if (($filters['graduation_year_to'] ?? null) !== null) {
            $query->where("{$alias}.year_graduated", '<=', $filters['graduation_year_to']);
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
    }

    protected function applyAlumniAccountScope(Builder $query, string $alias = 'users', bool $registeredOnly = true): void
    {
        if (Schema::hasColumn('users', 'role')) {
            $query->where("{$alias}.role", User::ROLE_ALUMNI);
        }

        if ($registeredOnly) {
            $this->applyRegisteredAlumniFilter($query, $alias);
        }
    }

    protected function applyRegisteredAlumniFilter(Builder $query, string $alias = 'users'): void
    {
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

    protected function applyCsvOnlyAlumniFilter(Builder $query, string $alias = 'users'): void
    {
        $query->where(function (Builder $csvOnlyQuery) use ($alias): void {
            if (Schema::hasColumn('users', 'is_batch_imported')) {
                $csvOnlyQuery->where("{$alias}.is_batch_imported", true);
            }

            if (Schema::hasColumn('users', 'email')) {
                $method = Schema::hasColumn('users', 'is_batch_imported') ? 'orWhereRaw' : 'whereRaw';
                $csvOnlyQuery->{$method}("LOWER(COALESCE({$alias}.email, '')) LIKE ?", ['%@oarl-batch.local']);
            }
        });
    }

    protected function uiState(array $filters): array
    {
        return [
            'hasFilters' => $filters['user_growth_range'] !== 6
                || $filters['applications_range'] !== 7
                || ! empty($filters['date_from'])
                || ! empty($filters['date_to'])
                || ! empty($filters['college'])
                || ! empty($filters['degree'])
                || ! empty($filters['sex'])
                || ! empty($filters['civil_status'])
                || ! empty($filters['age_range'])
                || ! empty($filters['age_from'])
                || ! empty($filters['age_to'])
                || ! empty($filters['graduation_year'])
                || ! empty($filters['graduation_year_from'])
                || ! empty($filters['graduation_year_to'])
                || ! empty($filters['employment_status'])
                || ! empty($filters['industry'])
                || ! empty($filters['location'])
                || ! empty($filters['salary_range'])
                || ! $this->chartSelectionIsDefault($filters['charts'] ?? []),
            'dateRangeLabel' => $this->dateRangeLabel($filters),
            'userGrowthRangeLabel' => $this->dateRangeLabel($filters) ?: 'Last '.$filters['user_growth_range'].' Months',
            'applicationsRangeLabel' => $this->dateRangeLabel($filters) ?: 'Last '.$filters['applications_range'].' Days',
            'selectedCollegeLabel' => $filters['college'] ?: 'All Colleges',
            'selectedGraduationYearLabel' => $this->graduationYearRangeLabel($filters),
            'selectedDegreeLabel' => $filters['degree'] ?: 'All Degrees',
            'selectedSexLabel' => $filters['sex'] ?: 'All Sexes',
            'selectedCivilStatusLabel' => $filters['civil_status'] ?: 'All Civil Statuses',
            'selectedAgeRangeLabel' => $this->ageRangeLabel($filters['age_from'], $filters['age_to']),
            'selectedEmploymentStatusLabel' => match ($filters['employment_status']) {
                'employed' => 'Employed',
                'self_employed' => 'Self-Employed',
                'unemployed' => 'Unemployed',
                'unknown' => 'Unknown',
                default => 'All Employment Statuses',
            },
            'selectedIndustryLabel' => $filters['industry'] ?: 'All Industries',
            'selectedLocationLabel' => $filters['location'] ?: 'All Locations',
            'selectedSalaryRangeLabel' => $filters['salary_range'] ?: 'All Salary Ranges',
            'selectedChartCount' => count($filters['charts'] ?? []),
            'hasCustomChartSelection' => ! $this->chartSelectionIsDefault($filters['charts'] ?? []),
            'defaultChartIds' => $this->defaultChartSelection(),
        ];
    }

    protected function dateRangeLabel(array $filters): ?string
    {
        $from = $filters['date_from'] ?? null;
        $to = $filters['date_to'] ?? null;

        if ($from === null && $to === null) {
            return null;
        }

        if ($from !== null && $to !== null) {
            return Carbon::parse($from)->format('M j, Y').' to '.Carbon::parse($to)->format('M j, Y');
        }

        if ($from !== null) {
            return 'From '.Carbon::parse($from)->format('M j, Y');
        }

        return 'Until '.Carbon::parse($to)->format('M j, Y');
    }

    protected function graduationYearRangeLabel(array $filters): string
    {
        $from = $filters['graduation_year_from'] ?? null;
        $to = $filters['graduation_year_to'] ?? null;

        if ($from === null && $to === null) {
            return 'All Years';
        }

        if ($from !== null && $to !== null) {
            return $from === $to ? (string) $from : "{$from} to {$to}";
        }

        return $from !== null ? "{$from} and newer" : "{$to} and older";
    }

    protected function hasUserScopedFilters(array $filters): bool
    {
        return $filters['college'] !== null
            || $filters['degree'] !== null
            || $filters['sex'] !== null
            || ($filters['civil_status'] ?? null) !== null
            || ($filters['age_range'] ?? null) !== null
            || ($filters['age_from'] ?? null) !== null
            || ($filters['age_to'] ?? null) !== null
            || ($filters['date_from'] ?? null) !== null
            || ($filters['date_to'] ?? null) !== null
            || $filters['graduation_year'] !== null
            || ($filters['graduation_year_from'] ?? null) !== null
            || ($filters['graduation_year_to'] ?? null) !== null
            || $filters['employment_status'] !== null
            || $filters['industry'] !== null
            || $filters['location'] !== null
            || $filters['salary_range'] !== null;
    }

    protected function applyExactTextFilter(Builder $query, string $column, string $value): void
    {
        $query->whereRaw(
            "LOWER(COALESCE(NULLIF(TRIM({$column}), ''), '')) = ?",
            [mb_strtolower($value)]
        );
    }

    protected function ageRangeLabel(?int $ageFrom, ?int $ageTo): string
    {
        if ($ageFrom === null && $ageTo === null) {
            return 'All Age Ranges';
        }

        if ($ageFrom !== null && $ageTo !== null) {
            return $ageFrom === $ageTo ? $ageFrom.' years old' : $ageFrom.' - '.$ageTo;
        }

        return $ageFrom !== null ? $ageFrom.' and above' : $ageTo.' and below';
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

        if (! $this->tableExists('tracer_records')) {
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

    protected function safeRate(int $part, int $total): float
    {
        if ($total === 0) {
            return 0;
        }

        return round(($part / $total) * 100, 1);
    }
}
