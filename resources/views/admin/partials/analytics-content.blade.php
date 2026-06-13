@php
    $filters = array_merge([
        'date_from' => null,
        'date_to' => null,
        'user_growth_range' => 6,
        'applications_range' => 7,
        'sex' => null,
        'civil_status' => null,
        'age_range' => null,
        'age_from' => null,
        'age_to' => null,
        'college' => null,
        'degree' => null,
        'graduation_year' => null,
        'graduation_year_from' => null,
        'graduation_year_to' => null,
        'employment_status' => null,
        'industry' => null,
        'location' => null,
        'salary_range' => null,
        'charts' => [],
    ], $filters ?? []);
    $filterOptions = array_merge([
        'colleges' => [],
        'degrees' => [],
        'sexes' => [],
        'civilStatuses' => [],
        'ageRanges' => [],
        'graduationYears' => [],
        'employmentStatuses' => [],
        'industries' => [],
        'locations' => [],
        'salaryRanges' => [],
    ], $filterOptions ?? []);
    $ui = array_merge([
        'hasFilters' => false,
        'dateRangeLabel' => null,
        'userGrowthRangeLabel' => 'Last 6 Months',
        'applicationsRangeLabel' => 'Last 7 Days',
        'selectedCollegeLabel' => 'All Colleges',
        'selectedGraduationYearLabel' => 'All Years',
        'selectedDegreeLabel' => 'All Degrees',
        'selectedSexLabel' => 'All Sexes',
        'selectedCivilStatusLabel' => 'All Civil Statuses',
        'selectedAgeRangeLabel' => 'All Age Ranges',
        'selectedEmploymentStatusLabel' => 'All Employment Statuses',
        'selectedIndustryLabel' => 'All Industries',
        'selectedLocationLabel' => 'All Locations',
        'selectedSalaryRangeLabel' => 'All Salary Ranges',
        'selectedChartCount' => 0,
        'hasCustomChartSelection' => false,
        'defaultChartIds' => [],
    ], $ui ?? []);
    $curriculumRelevanceData = $curriculumRelevanceData ?? ['labels' => ['No Data'], 'data' => [0]];
    $firstJobTimeData = $firstJobTimeData ?? ['labels' => ['No Data'], 'data' => [0]];
    $presentEmploymentStatusData = $presentEmploymentStatusData ?? ['labels' => ['No Data'], 'data' => [0]];
    $facilitiesSatisfactionData = $facilitiesSatisfactionData ?? ['labels' => ['No Data'], 'data' => [0]];
    $teachingFeedbackData = $teachingFeedbackData ?? ['labels' => ['No Data'], 'data' => [0]];
    $overallExperienceData = $overallExperienceData ?? ['labels' => ['No Data'], 'data' => [0]];
    $feedbacksData = $feedbacksData ?? ['labels' => ['No Data'], 'data' => [0]];
    $employmentTrends = $employmentTrends ?? ['labels' => ['No Data'], 'employed' => [0], 'selfEmployed' => [0], 'unemployed' => [0]];
    $tracedGraduates = $tracedGraduates ?? ['labels' => ['No Data'], 'data' => [0]];
    $industryData = $industryData ?? ['labels' => ['No Data'], 'data' => [0]];
    $employabilityData = $employabilityData ?? ['labels' => ['No Data'], 'data' => [0]];
    $globalData = $globalData ?? ['labels' => ['No Data'], 'data' => [0]];
    $reasonsData = $reasonsData ?? ['labels' => ['No Data'], 'data' => [0]];
    $csvNameSummary = $csvNameSummary ?? [];
    $alumniAccountSummary = array_merge([
        'roster_total' => $totalUsers ?? 0,
        'registered' => 0,
        'not_registered' => 0,
        'analytics_matched' => $totalUsers ?? 0,
        'tracer_respondents' => 0,
    ], $alumniAccountSummary ?? []);
    $hasLabeledDataset = static function (array $dataset, bool $allowZeroValues = false): bool {
        $labels = collect($dataset['labels'] ?? [])
            ->filter(fn ($label) => filled($label) && $label !== 'No Data');
        $values = collect($dataset['data'] ?? []);

        if ($labels->isEmpty() || $values->isEmpty()) {
            return false;
        }

        if ($allowZeroValues) {
            return true;
        }

        return $values->filter(fn ($value) => (float) $value > 0)->isNotEmpty();
    };
    $hasPositiveSeries = static fn (array $series): bool => collect($series)
        ->flatten()
        ->filter(fn ($value) => (float) $value > 0)
        ->isNotEmpty();
    $hasEmploymentTrends = $hasPositiveSeries([
        $employmentTrends['employed'] ?? [],
        $employmentTrends['selfEmployed'] ?? [],
        $employmentTrends['unemployed'] ?? [],
    ]);
    $hasTracedGraduates = $hasLabeledDataset($tracedGraduates);
    $hasIndustryData = $hasLabeledDataset($industryData);
    $hasEmployabilityData = $hasLabeledDataset($employabilityData, true);
    $hasGlobalData = $hasLabeledDataset($globalData);
    $hasReasonsData = $hasLabeledDataset($reasonsData);
    $hasUserGrowth = collect($userGrowth['data'] ?? [])->filter(fn ($value) => (float) $value > 0)->isNotEmpty();
    $hasJobApplicationsTrend = collect($jobApplicationsTrend['data'] ?? [])->filter(fn ($value) => (float) $value > 0)->isNotEmpty();
    $hasCollegeDistribution = collect($collegeDistribution['data'] ?? [])->filter(fn ($value) => (float) $value > 0)->isNotEmpty();
    $hasEmploymentStatus = collect($employmentStatus['data'] ?? [])->filter(fn ($value) => (float) $value > 0)->isNotEmpty();
    $hasCurriculumRelevance = collect($curriculumRelevanceData['data'] ?? [])->filter(fn ($value) => (float) $value > 0)->isNotEmpty();
    $hasFirstJobTime = collect($firstJobTimeData['data'] ?? [])->filter(fn ($value) => (float) $value > 0)->isNotEmpty();
    $hasPresentEmploymentStatus = collect($presentEmploymentStatusData['data'] ?? [])->filter(fn ($value) => (float) $value > 0)->isNotEmpty();
    $hasFacilitiesSatisfaction = collect($facilitiesSatisfactionData['data'] ?? [])->filter(fn ($value) => (float) $value > 0)->isNotEmpty();
    $hasTeachingFeedback = collect($teachingFeedbackData['data'] ?? [])->filter(fn ($value) => (float) $value > 0)->isNotEmpty();
    $hasOverallExperience = collect($overallExperienceData['data'] ?? [])->filter(fn ($value) => (float) $value > 0)->isNotEmpty();
    $hasFeedbacks = collect($feedbacksData['data'] ?? [])->filter(fn ($value) => (float) $value > 0)->isNotEmpty();
    $topLabelFor = static function (array $dataset): string {
        $labels = array_values($dataset['labels'] ?? []);
        $values = array_map(fn ($value) => (float) $value, $dataset['data'] ?? []);

        if ($labels === [] || $values === []) {
            return 'N/A';
        }

        $max = max($values);

        if ($max <= 0) {
            return 'N/A';
        }

        $index = array_search($max, $values, true);

        return $labels[$index] ?? 'N/A';
    };
    $topFirstJobTimeLabel = $topLabelFor($firstJobTimeData);
    $topIndustryLabel = $topLabelFor($industryData);
    $topLocationLabel = $topLabelFor($globalData);
    $analyticsMatchedCount = (int) $alumniAccountSummary['analytics_matched'];
    $tracerRespondentsCount = (int) $alumniAccountSummary['tracer_respondents'];
    $employedAlumniCount = (int) ($employmentStatus['employed'] ?? 0);
    $selfEmployedAlumniCount = (int) ($employmentStatus['self_employed'] ?? 0);
    $unemployedAlumniCount = (int) ($employmentStatus['unemployed'] ?? 0);
    $missingTracerResponses = max(0, $analyticsMatchedCount - $tracerRespondentsCount);
    $summaryInsightCards = collect([
        [
            'show' => $missingTracerResponses > 0,
            'icon' => 'bx-edit-alt',
            'label' => 'Needs Tracer Follow-Up',
            'value' => number_format($missingTracerResponses),
            'note' => 'filtered registered alumni without a tracer form',
        ],
        [
            'show' => $unemployedAlumniCount > 0,
            'icon' => 'bx-briefcase-alt-2',
            'label' => 'Unemployed Alumni',
            'value' => number_format($unemployedAlumniCount),
            'note' => 'may need job support or follow-up',
        ],
        [
            'show' => $selfEmployedAlumniCount > 0,
            'icon' => 'bx-store',
            'label' => 'Self-Employed Alumni',
            'value' => number_format($selfEmployedAlumniCount),
            'note' => 'business or freelance outcomes reported',
        ],
        [
            'show' => $topIndustryLabel !== 'N/A',
            'icon' => 'bx-building-house',
            'label' => 'Top Industry',
            'value' => $topIndustryLabel,
            'note' => 'highest represented industry',
        ],
        [
            'show' => $topLocationLabel !== 'N/A',
            'icon' => 'bx-map',
            'label' => 'Top Location',
            'value' => $topLocationLabel,
            'note' => 'most common recorded alumni location',
        ],
        [
            'show' => (float) $dataCompleteness > 0,
            'icon' => 'bx-check-shield',
            'label' => 'Data Completeness',
            'value' => $dataCompleteness.'%',
            'note' => 'profile and employment fields completed',
        ],
    ])
        ->filter(fn (array $card) => $card['show'])
        ->map(fn (array $card) => collect($card)->except('show')->all())
        ->take(6)
        ->values()
        ->all();
    $hasGraduationYearTrends = collect($graduationYearTrends['data'] ?? [])->filter(fn ($value) => (float) $value > 0)->isNotEmpty();
    $hasIndustryGrowth = collect($industryGrowth['datasets'] ?? [])
        ->flatMap(fn ($dataset) => $dataset['data'] ?? [])
        ->filter(fn ($value) => (float) $value > 0)
        ->isNotEmpty();
    $adminAnalyticsState = [
        'totalUsers' => $totalUsers,
        'activeSessions' => $activeSessions,
        'jobApplications' => $jobApplications,
        'dataCompleteness' => $dataCompleteness,
        'alumniAccountSummary' => $alumniAccountSummary,
        'employmentRate' => $employmentRate,
        'selfEmploymentRate' => $selfEmploymentRate,
        'unemploymentRate' => $unemploymentRate,
        'employmentTrends' => $employmentTrends,
        'tracedGraduates' => $tracedGraduates,
        'industryData' => $industryData,
        'employabilityData' => $employabilityData,
        'globalData' => $globalData,
        'reasonsData' => $reasonsData,
        'userGrowth' => $userGrowth,
        'jobApplicationsTrend' => $jobApplicationsTrend,
        'collegeDistribution' => $collegeDistribution,
        'employmentStatus' => $employmentStatus,
        'curriculumRelevanceData' => $curriculumRelevanceData,
        'firstJobTimeData' => $firstJobTimeData,
        'presentEmploymentStatusData' => $presentEmploymentStatusData,
        'facilitiesSatisfactionData' => $facilitiesSatisfactionData,
        'teachingFeedbackData' => $teachingFeedbackData,
        'overallExperienceData' => $overallExperienceData,
        'feedbacksData' => $feedbacksData,
        'graduationYearTrends' => $graduationYearTrends,
        'industryGrowth' => $industryGrowth,
        'recentActivity' => $recentActivity,
        'csvNameSummary' => $csvNameSummary,
        'filters' => $filters,
        'ui' => $ui,
    ];
    $chartFilterOptions = [
        ['id' => 'employmentTrendsChart', 'label' => 'Employment Trends'],
        ['id' => 'tracedGraduatesChart', 'label' => 'Traced Graduates'],
        ['id' => 'industryChart', 'label' => 'Industry Distribution'],
        ['id' => 'employabilityChart', 'label' => 'Employability Rate'],
        ['id' => 'globalDistributionChart', 'label' => 'Global Distribution'],
        ['id' => 'reasonsChart', 'label' => 'Reasons for Unemployment'],
        ['id' => 'userGrowthChart', 'label' => 'User Growth'],
        ['id' => 'jobApplicationsChart', 'label' => 'Job Opportunity Submissions'],
        ['id' => 'collegeDistributionChart', 'label' => 'Alumni by College'],
        ['id' => 'employmentStatusChart', 'label' => 'Employment Status'],
        ['id' => 'graduationYearChart', 'label' => 'Graduation Year Trends'],
        ['id' => 'industryGrowthChart', 'label' => 'Industry Growth'],
        ['id' => 'feedbacksChart', 'label' => 'Feedback Categories'],
        ['id' => 'curriculumRelevanceChart', 'label' => 'Curriculum Relevance'],
        ['id' => 'firstJobTimeChart', 'label' => 'Time to First Job'],
        ['id' => 'presentEmploymentStatusChart', 'label' => 'Present Employment Status'],
        ['id' => 'teachingFeedbackChart', 'label' => 'Teaching Feedback'],
        ['id' => 'facilitiesSatisfactionChart', 'label' => 'University Facilities'],
        ['id' => 'overallExperienceChart', 'label' => 'Overall Experience'],
    ];
    $selectedChartIds = collect($filters['charts'] ?? [])->filter()->values();
    $selectedChartCount = $selectedChartIds->count();
    $hasCustomChartSelection = (bool) ($ui['hasCustomChartSelection'] ?? false);
    $showChart = static fn (string $chartId): bool => $selectedChartIds->contains($chartId);
    $programOptionsByCollege = \App\Support\SystemOptions::departmentProgramOptions();
    $activeFilterChips = collect([
        ['active' => filled($ui['dateRangeLabel']), 'icon' => 'bx-calendar-event', 'label' => 'Date', 'value' => $ui['dateRangeLabel'], 'target' => 'date_range'],
        ['active' => filled($filters['sex']), 'icon' => 'bx-male-female', 'label' => 'Sex', 'value' => $ui['selectedSexLabel'], 'target' => 'sex'],
        ['active' => filled($filters['civil_status']), 'icon' => 'bx-id-card', 'label' => 'Civil', 'value' => $ui['selectedCivilStatusLabel'], 'target' => 'civil_status'],
        ['active' => filled($filters['age_from']) || filled($filters['age_to']), 'icon' => 'bx-cake', 'label' => 'Age', 'value' => $ui['selectedAgeRangeLabel'], 'target' => 'age_range'],
        ['active' => filled($filters['college']), 'icon' => 'bx-buildings', 'label' => 'College', 'value' => $ui['selectedCollegeLabel'], 'target' => 'college'],
        ['active' => filled($filters['degree']), 'icon' => 'bx-book-open', 'label' => 'Degree', 'value' => $ui['selectedDegreeLabel'], 'target' => 'degree'],
        ['active' => filled($filters['graduation_year_from']) || filled($filters['graduation_year_to']), 'icon' => 'bx-calendar', 'label' => 'Year', 'value' => $ui['selectedGraduationYearLabel'], 'target' => 'graduation_year'],
        ['active' => filled($filters['employment_status']), 'icon' => 'bx-user-check', 'label' => 'Employment', 'value' => $ui['selectedEmploymentStatusLabel'], 'target' => 'employment_status'],
        ['active' => filled($filters['industry']), 'icon' => 'bx-briefcase-alt-2', 'label' => 'Industry', 'value' => $ui['selectedIndustryLabel'], 'target' => 'industry'],
        ['active' => filled($filters['location']), 'icon' => 'bx-map', 'label' => 'Location', 'value' => $ui['selectedLocationLabel'], 'target' => 'location'],
        ['active' => filled($filters['salary_range']), 'icon' => 'bx-wallet-alt', 'label' => 'Salary', 'value' => $ui['selectedSalaryRangeLabel'], 'target' => 'salary_range'],
        ['active' => $hasCustomChartSelection, 'icon' => 'bx-bar-chart-alt-2', 'label' => 'Graphs', 'value' => $selectedChartCount.' selected', 'target' => 'charts'],
    ])->filter(fn (array $chip) => $chip['active'])->values();
    $buildRadioOptions = static function (array $options, string $allLabel): array {
        $normalized = [['value' => '', 'label' => $allLabel]];

        foreach ($options as $option) {
            if (is_array($option)) {
                $normalized[] = [
                    'value' => (string) ($option['value'] ?? ''),
                    'label' => (string) ($option['label'] ?? $option['value'] ?? ''),
                ];

                continue;
            }

            $normalized[] = [
                'value' => (string) $option,
                'label' => (string) $option,
            ];
        }

        return $normalized;
    };
    $defaultVisibleFilterOptions = 3;
    $defaultVisibleFilterGroups = 4;
    $filterGroups = [
        [
            'name' => 'user_growth_range',
            'title' => 'User Growth Window',
            'hint' => 'Registration window.',
            'selected' => (string) ($filters['user_growth_range'] ?? '6'),
            'visibleCount' => $defaultVisibleFilterOptions,
            'options' => [
                ['value' => '6', 'label' => 'Last 6 Months'],
                ['value' => '12', 'label' => 'Last 12 Months'],
                ['value' => '3', 'label' => 'Last 3 Months'],
            ],
        ],
        [
            'name' => 'applications_range',
            'title' => 'Job Submission Window',
            'hint' => 'Alumni job opportunity submission window.',
            'selected' => (string) ($filters['applications_range'] ?? '7'),
            'visibleCount' => $defaultVisibleFilterOptions,
            'options' => [
                ['value' => '7', 'label' => 'Last 7 Days'],
                ['value' => '14', 'label' => 'Last 14 Days'],
                ['value' => '30', 'label' => 'Last 30 Days'],
            ],
        ],
        [
            'name' => 'sex',
            'title' => 'Sex',
            'hint' => 'Male or female scope.',
            'selected' => (string) ($filters['sex'] ?? ''),
            'visibleCount' => $defaultVisibleFilterOptions,
            'options' => $buildRadioOptions($filterOptions['sexes'], 'All Sexes'),
        ],
        [
            'name' => 'age_range',
            'title' => 'Age Range',
            'hint' => 'Age at report date.',
            'selected' => trim((string) ($filters['age_from'] ?? '').' '.(string) ($filters['age_to'] ?? '')),
            'visibleCount' => $defaultVisibleFilterOptions,
            'options' => $buildRadioOptions($filterOptions['ageRanges'], 'All Age Ranges'),
        ],
        [
            'name' => 'college',
            'title' => 'College',
            'hint' => 'College scope.',
            'selected' => (string) ($filters['college'] ?? ''),
            'visibleCount' => $defaultVisibleFilterOptions,
            'options' => $buildRadioOptions($filterOptions['colleges'], 'All Colleges'),
        ],
        [
            'name' => 'degree',
            'title' => 'Degree',
            'hint' => 'Degree scope.',
            'selected' => (string) ($filters['degree'] ?? ''),
            'visibleCount' => $defaultVisibleFilterOptions,
            'options' => $buildRadioOptions($filterOptions['degrees'], 'All Degrees'),
        ],
        [
            'name' => 'graduation_year_from',
            'title' => 'Year From',
            'hint' => 'Start of graduation year range.',
            'selected' => (string) ($filters['graduation_year_from'] ?? ''),
            'visibleCount' => $defaultVisibleFilterOptions,
            'options' => $buildRadioOptions($filterOptions['graduationYears'], 'Any Start'),
        ],
        [
            'name' => 'graduation_year_to',
            'title' => 'Year To',
            'hint' => 'End of graduation year range.',
            'selected' => (string) ($filters['graduation_year_to'] ?? ''),
            'visibleCount' => $defaultVisibleFilterOptions,
            'options' => $buildRadioOptions($filterOptions['graduationYears'], 'Any End'),
        ],
        [
            'name' => 'employment_status',
            'title' => 'Employment Status',
            'hint' => 'Status scope.',
            'selected' => (string) ($filters['employment_status'] ?? ''),
            'visibleCount' => $defaultVisibleFilterOptions,
            'options' => $buildRadioOptions($filterOptions['employmentStatuses'], 'All Employment Statuses'),
        ],
        [
            'name' => 'industry',
            'title' => 'Industry',
            'hint' => 'Industry focus.',
            'selected' => (string) ($filters['industry'] ?? ''),
            'visibleCount' => $defaultVisibleFilterOptions,
            'options' => $buildRadioOptions($filterOptions['industries'], 'All Industries'),
        ],
        [
            'name' => 'location',
            'title' => 'Location',
            'hint' => 'Location scope.',
            'selected' => (string) ($filters['location'] ?? ''),
            'visibleCount' => $defaultVisibleFilterOptions,
            'options' => $buildRadioOptions($filterOptions['locations'], 'All Locations'),
        ],
        [
            'name' => 'salary_range',
            'title' => 'Salary Range',
            'hint' => 'Income band.',
            'selected' => (string) ($filters['salary_range'] ?? ''),
            'visibleCount' => $defaultVisibleFilterOptions,
            'options' => $buildRadioOptions($filterOptions['salaryRanges'], 'All Salary Ranges'),
        ],
    ];
    $filterGroupsCount = count($filterGroups);
    $hasOverflowFilterGroupSelection = collect($filterGroups)
        ->slice($defaultVisibleFilterGroups)
        ->contains(fn (array $group) => filled($group['selected']));
    $reportDisseminationDate = now()->format('F j, Y');
    $reportScopeTitle = ($ui['selectedGraduationYearLabel'] ?? 'All Years') !== 'All Years'
        ? 'SY. '.$ui['selectedGraduationYearLabel'].' GRADUATES TRACER DATA'
        : 'GRADUATES TRACER DATA';
    $reportKpiCards = [
        ['stage' => 'Registered', 'label' => 'How Many Have Registered?', 'value' => number_format((float) $alumniAccountSummary['registered']), 'tone' => 'green', 'note' => 'registered alumni accounts'],
        ['stage' => 'From CSV', 'label' => 'How Many Have Not Yet Registered?', 'value' => number_format((float) $alumniAccountSummary['not_registered']), 'tone' => 'orange', 'note' => 'CSV records waiting for signup'],
        ['stage' => 'Employed', 'label' => 'How Many Are Employed?', 'value' => number_format($employedAlumniCount), 'tone' => 'blue', 'note' => 'employed only'],
        ['stage' => 'Complete', 'label' => 'Data Completeness', 'value' => $dataCompleteness.'%', 'tone' => 'cyan', 'note' => 'profile and employment fields completed'],
    ];
    $collegeTileTones = ['green', 'red', 'yellow', 'blue', 'orange', 'amber', 'rose', 'cyan', 'violet', 'teal'];
@endphp

<div class="space-y-6">
    <div class="analytics-report-layout">
        <aside id="analytics-filter-panel" class="page-section analytics-filter-panel rounded-[1.75rem]" data-filter-jump-target="filters" tabindex="-1">
            <div class="analytics-filter-panel__header">
                <div class="analytics-filter-panel__title">
                    <span class="analytics-filter-panel__glyph">
                        <i class='bx bx-filter-alt'></i>
                    </span>
                    <div>
                        <p class="analytics-filter-panel__eyebrow">Report Inputs</p>
                        <h2 class="text-xl font-bold tracking-tight text-slate-900">Report Filters</h2>
                        <p class="analytics-filter-panel__intro">Set the scope for the report output.</p>
                    </div>
                </div>
                <div class="analytics-filter-panel__status">
                    <p class="analytics-filter-panel__status-label">Applied</p>
                    <p class="analytics-filter-panel__status-value">{{ $activeFilterChips->count() }}</p>
                </div>
            </div>

            <p data-admin-analytics-filter-feedback class="analytics-filter-feedback mt-3 hidden text-sm"></p>

            <div class="analytics-filter-panel__summary">
                @forelse($activeFilterChips as $chip)
                    <button type="button" class="analytics-filter-chip" data-filter-jump="{{ $chip['target'] }}" aria-label="Go to {{ $chip['label'] }} filter">
                        <i class='bx {{ $chip['icon'] }}'></i>
                        <span>
                            <span class="analytics-filter-chip__label">{{ $chip['label'] }}</span>
                            <span class="analytics-filter-chip__value">{{ $chip['value'] }}</span>
                        </span>
                    </button>
                @empty
                    <div class="analytics-filter-panel__empty">
                        <i class='bx bx-filter-alt text-lg'></i>
                        <span>No filters applied yet.</span>
                    </div>
                @endforelse
            </div>

            <form
                method="GET"
                action="{{ route('admin.analytics') }}"
                data-admin-analytics-filter-form
                data-program-catalog="{{ json_encode($programOptionsByCollege) }}"
                class="analytics-filter-form"
            >
                <fieldset id="analytics-filter-date_range" class="analytics-filter-group" data-filter-jump-target="date_range" tabindex="-1">
                    <legend class="analytics-filter-group__title">Date Range</legend>
                    <p class="analytics-filter-group__hint">Use graduation dates for the report scope.</p>
                    <div class="analytics-filter-field-grid">
                        <label class="analytics-filter-field">
                            <span>From</span>
                            <input type="date" name="date_from" value="{{ $filters['date_from'] }}" class="analytics-filter-control" data-admin-analytics-filter-input>
                        </label>
                        <label class="analytics-filter-field">
                            <span>To</span>
                            <input type="date" name="date_to" value="{{ $filters['date_to'] }}" class="analytics-filter-control" data-admin-analytics-filter-input>
                        </label>
                    </div>
                </fieldset>

                <fieldset id="analytics-filter-college" class="analytics-filter-group" data-filter-jump-target="college" tabindex="-1">
                    <legend class="analytics-filter-group__title">College and Program</legend>
                    <p class="analytics-filter-group__hint">The program list changes based on the selected college.</p>
                    <div class="analytics-filter-field-grid">
                        <label class="analytics-filter-field">
                            <span>College</span>
                            <select name="college" class="analytics-filter-control" data-admin-analytics-filter-input data-college-filter-select>
                                <option value="">All Colleges</option>
                                @foreach($filterOptions['colleges'] as $college)
                                    <option value="{{ $college }}" @selected($filters['college'] === $college)>{{ $college }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="analytics-filter-field" id="analytics-filter-degree" data-filter-jump-target="degree" tabindex="-1">
                            <span>Program</span>
                            <select name="degree" class="analytics-filter-control" data-admin-analytics-filter-input data-program-filter-select data-selected-program="{{ $filters['degree'] }}">
                                <option value="">All Programs</option>
                                @foreach($filterOptions['degrees'] as $degree)
                                    <option value="{{ $degree }}" @selected($filters['degree'] === $degree)>{{ $degree }}</option>
                                @endforeach
                            </select>
                        </label>
                    </div>
                </fieldset>

                <fieldset id="analytics-filter-demographics" class="analytics-filter-group" data-filter-jump-target="sex" tabindex="-1">
                    <legend class="analytics-filter-group__title">Alumni Profile</legend>
                    <div class="analytics-filter-field-grid">
                        <label class="analytics-filter-field">
                            <span>Sex</span>
                            <select name="sex" class="analytics-filter-control" data-admin-analytics-filter-input>
                                <option value="">All Sexes</option>
                                @foreach($filterOptions['sexes'] as $sex)
                                    <option value="{{ $sex }}" @selected($filters['sex'] === $sex)>{{ $sex }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="analytics-filter-field" id="analytics-filter-civil_status" data-filter-jump-target="civil_status" tabindex="-1">
                            <span>Civil Status</span>
                            <select name="civil_status" class="analytics-filter-control" data-admin-analytics-filter-input>
                                <option value="">All Civil Statuses</option>
                                @foreach($filterOptions['civilStatuses'] as $civilStatus)
                                    <option value="{{ $civilStatus }}" @selected($filters['civil_status'] === $civilStatus)>{{ $civilStatus }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="analytics-filter-field" id="analytics-filter-age_range" data-filter-jump-target="age_range" tabindex="-1">
                            <span>Age Range</span>
                            <div class="grid grid-cols-2 gap-2">
                                <input type="number" min="0" max="120" inputmode="numeric" name="age_from" value="{{ $filters['age_from'] }}" placeholder="From" aria-label="Age from" class="analytics-filter-control" data-admin-analytics-filter-input>
                                <input type="number" min="0" max="120" inputmode="numeric" name="age_to" value="{{ $filters['age_to'] }}" placeholder="To" aria-label="Age to" class="analytics-filter-control" data-admin-analytics-filter-input>
                            </div>
                        </label>
                    </div>
                </fieldset>

                <fieldset id="analytics-filter-employment_status" class="analytics-filter-group" data-filter-jump-target="employment_status" tabindex="-1">
                    <legend class="analytics-filter-group__title">Employment</legend>
                    <div class="analytics-filter-field-grid">
                        <label class="analytics-filter-field">
                            <span>Status</span>
                            <select name="employment_status" class="analytics-filter-control" data-admin-analytics-filter-input>
                                <option value="">All Employment Statuses</option>
                                @foreach($filterOptions['employmentStatuses'] as $statusOption)
                                    <option value="{{ $statusOption['value'] }}" @selected($filters['employment_status'] === $statusOption['value'])>{{ $statusOption['label'] }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="analytics-filter-field" id="analytics-filter-graduation_year" data-filter-jump-target="graduation_year" tabindex="-1">
                            <span>Year Graduated</span>
                            <div class="grid grid-cols-2 gap-2">
                                <select name="graduation_year_from" class="analytics-filter-control" data-admin-analytics-filter-input aria-label="Year graduated from">
                                    <option value="">From</option>
                                    @foreach($filterOptions['graduationYears'] as $year)
                                        <option value="{{ $year }}" @selected((string) ($filters['graduation_year_from'] ?? '') === (string) $year)>{{ $year }}</option>
                                    @endforeach
                                </select>
                                <select name="graduation_year_to" class="analytics-filter-control" data-admin-analytics-filter-input aria-label="Year graduated to">
                                    <option value="">To</option>
                                    @foreach($filterOptions['graduationYears'] as $year)
                                        <option value="{{ $year }}" @selected((string) ($filters['graduation_year_to'] ?? '') === (string) $year)>{{ $year }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </label>
                    </div>
                </fieldset>

                <fieldset id="analytics-filter-more_scope" class="analytics-filter-group" data-filter-jump-target="industry" tabindex="-1">
                    <legend class="analytics-filter-group__title">Additional Scope</legend>
                    <div class="analytics-filter-field-grid">
                        <label class="analytics-filter-field">
                            <span>Industry</span>
                            <select name="industry" class="analytics-filter-control" data-admin-analytics-filter-input>
                                <option value="">All Industries</option>
                                @foreach($filterOptions['industries'] as $industry)
                                    <option value="{{ $industry }}" @selected($filters['industry'] === $industry)>{{ $industry }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="analytics-filter-field" id="analytics-filter-location" data-filter-jump-target="location" tabindex="-1">
                            <span>Location</span>
                            <select name="location" class="analytics-filter-control" data-admin-analytics-filter-input>
                                <option value="">All Locations</option>
                                @foreach($filterOptions['locations'] as $location)
                                    <option value="{{ $location }}" @selected($filters['location'] === $location)>{{ $location }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="analytics-filter-field analytics-filter-field--wide" id="analytics-filter-salary_range" data-filter-jump-target="salary_range" tabindex="-1">
                            <span>Salary Range</span>
                            <select name="salary_range" class="analytics-filter-control" data-admin-analytics-filter-input>
                                <option value="">All Salary Ranges</option>
                                @foreach($filterOptions['salaryRanges'] as $salaryRange)
                                    <option value="{{ $salaryRange }}" @selected($filters['salary_range'] === $salaryRange)>{{ $salaryRange }}</option>
                                @endforeach
                            </select>
                        </label>
                    </div>
                </fieldset>

                <fieldset id="analytics-filter-charts" class="analytics-filter-group" data-filter-jump-target="charts" tabindex="-1">
                    <legend class="analytics-filter-group__title">Report Graphs</legend>
                    <p class="analytics-filter-group__hint">Common alumni graphs are shown by default. Check or uncheck graphs to customize the report.</p>
                    <div class="analytics-chart-picker">
                        @foreach($chartFilterOptions as $chartOption)
                            <label class="analytics-chart-option">
                                <input
                                    type="checkbox"
                                    name="charts[]"
                                    value="{{ $chartOption['id'] }}"
                                    class="analytics-chart-option__input"
                                    data-admin-analytics-filter-input
                                    @checked($selectedChartIds->contains($chartOption['id']))
                                >
                                <span class="analytics-chart-option__ui">
                                    <span class="analytics-chart-option__box" aria-hidden="true">
                                        <i class='bx bx-check'></i>
                                    </span>
                                    <span>{{ $chartOption['label'] }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>

                <div class="analytics-filter-actions">
                    <a data-admin-analytics-reset-link data-native-nav href="{{ route('admin.analytics') }}" class="analytics-filter-reset {{ $ui['hasFilters'] ? '' : 'hidden' }}">
                        Clear all filters
                        <i class='bx bx-reset text-base'></i>
                    </a>
                </div>
            </form>
        </aside>

        <div id="analyticsVisualizations" data-admin-analytics-visualizations class="analytics-report-layout__main transition-opacity duration-200">
        <section class="arlo-report-sheet" aria-label="OARL tracer form report preview">
            <header class="arlo-report-masthead">
                <div class="arlo-report-masthead__brand">
                    <div class="arlo-report-masthead__identity">
                        <span class="arlo-report-masthead__logo">
                            <img src="{{ asset('images/cmu-sidebar.png') }}" alt="CMU logo">
                        </span>
                        <div>
                            <p class="arlo-report-masthead__university">Central Mindanao University</p>
                            <p class="arlo-report-masthead__address">University Town, Musuan, Bukidnon</p>
                        </div>
                    </div>
                    <div class="arlo-report-masthead__office-block">
                        <p class="arlo-report-masthead__office">Office of the Alumni Relations and Linkages (OARL)</p>
                        <p class="arlo-report-masthead__office-note">Records, partnerships, and graduate outcomes reporting</p>
                    </div>
                </div>
                <div class="arlo-report-titlebar">
                    <h1>CMU-F-A-ARL-003 TRACER FORM REPORT</h1>
                    <span>Dissemination date: {{ $reportDisseminationDate }}</span>
                </div>
            </header>

            <div class="arlo-report-scope">
                <div>
                    <p class="arlo-report-scope__eyebrow">Output Preview</p>
                    <h2>{{ $reportScopeTitle }}</h2>
                </div>
                <div class="arlo-report-scope__meta" aria-label="Report filters">
                    <a href="#analytics-filter-college" class="arlo-report-scope__meta-card" data-filter-jump="college">
                        <span class="arlo-report-scope__meta-label">College</span>
                        <span class="arlo-report-scope__meta-value">{{ $ui['selectedCollegeLabel'] }}</span>
                    </a>
                    <a href="#analytics-filter-degree" class="arlo-report-scope__meta-card" data-filter-jump="degree">
                        <span class="arlo-report-scope__meta-label">Degree</span>
                        <span class="arlo-report-scope__meta-value">{{ $ui['selectedDegreeLabel'] }}</span>
                    </a>
                    <a href="#analytics-filter-sex" class="arlo-report-scope__meta-card" data-filter-jump="sex">
                        <span class="arlo-report-scope__meta-label">Sex</span>
                        <span class="arlo-report-scope__meta-value">{{ $ui['selectedSexLabel'] }}</span>
                    </a>
                    <a href="#analytics-filter-age_range" class="arlo-report-scope__meta-card" data-filter-jump="age_range">
                        <span class="arlo-report-scope__meta-label">Age</span>
                        <span class="arlo-report-scope__meta-value">{{ $ui['selectedAgeRangeLabel'] }}</span>
                    </a>
                    <a href="#analytics-filter-employment_status" class="arlo-report-scope__meta-card" data-filter-jump="employment_status">
                        <span class="arlo-report-scope__meta-label">Employment</span>
                        <span class="arlo-report-scope__meta-value">{{ $ui['selectedEmploymentStatusLabel'] }}</span>
                    </a>
                </div>
            </div>

            <div class="arlo-report-flow-note" aria-label="Report count focus">
                <span>registered accounts</span>
                <i class='bx bx-right-arrow-alt' aria-hidden="true"></i>
                <span>CSV waiting for signup</span>
                <i class='bx bx-right-arrow-alt' aria-hidden="true"></i>
                <span>employed alumni only</span>
                <i class='bx bx-right-arrow-alt' aria-hidden="true"></i>
                <span>data completeness</span>
            </div>

            <div class="arlo-report-kpis" aria-label="Report totals">
                @foreach($reportKpiCards as $card)
                    <article class="arlo-report-kpi arlo-report-kpi--{{ $card['tone'] }}">
                        <div class="arlo-report-kpi__body">
                            @if(!empty($card['stage']))
                                <span class="arlo-report-kpi__stage">{{ $card['stage'] }}</span>
                            @endif
                            <span>{{ $card['label'] }}</span>
                            <strong>{{ $card['value'] }}</strong>
                        </div>
                        <p class="arlo-report-kpi__footer">
                            <span>{{ $card['note'] ?? 'Current filtered scope' }}</span>
                        </p>
                    </article>
                @endforeach
            </div>

            @if($summaryInsightCards !== [])
                <section class="arlo-report-insights" aria-label="Key analytics readouts">
                    <div class="arlo-report-insights__header">
                        <div>
                            <p class="arlo-report-insights__eyebrow">Key Readouts</p>
                            <h3>Current Scope Snapshot</h3>
                        </div>
                    </div>
                    <div class="arlo-report-insights__grid">
                        @foreach($summaryInsightCards as $insight)
                            <article class="arlo-report-insight-card">
                                <span class="arlo-report-insight-card__icon"><i class='bx {{ $insight['icon'] }}'></i></span>
                                <div>
                                    <p class="arlo-report-insight-card__label">{{ $insight['label'] }}</p>
                                    <strong>{{ $insight['value'] }}</strong>
                                    <p>{{ $insight['note'] }}</p>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </section>
            @endif

            @if($analyticsMatchedCount === 0)
                <div class="empty-state-card">
                    <span class="empty-state-card__icon"><i class='bx bx-filter-alt'></i></span>
                    <p class="empty-state-card__title">No alumni match this analytics scope</p>
                    <p class="empty-state-card__body">Change or clear filters to restore report totals, charts, and exportable analytics records.</p>
                </div>
            @elseif($tracerRespondentsCount === 0)
                <div class="empty-state-card">
                    <span class="empty-state-card__icon"><i class='bx bx-edit-alt'></i></span>
                    <p class="empty-state-card__title">No tracer responses in this scope yet</p>
                    <p class="empty-state-card__body">The report can still show roster-based counts, but tracer-only charts need submitted tracer forms.</p>
                </div>
            @endif

            @if((int) $alumniAccountSummary['not_registered'] > 0)
                <div class="empty-state-card">
                    <span class="empty-state-card__icon"><i class='bx bx-user-pin'></i></span>
                    <p class="empty-state-card__title">CSV-only alumni are excluded from analytics exports</p>
                    <p class="empty-state-card__body">
                        {{ number_format((float) $alumniAccountSummary['not_registered']) }} alumni record{{ (int) $alumniAccountSummary['not_registered'] === 1 ? '' : 's' }}
                        in this roster scope came from the CSV import. They remain available for signup verification, but graphs, chart CSVs, PDF summaries, and detailed exports now use registered alumni accounts only.
                    </p>
                </div>
            @endif

            @if($selectedChartIds->isEmpty())
                <div class="empty-state-card">
                    <span class="empty-state-card__icon"><i class='bx bx-bar-chart-alt-2'></i></span>
                    <p class="empty-state-card__title">No graphs selected yet</p>
                    <p class="empty-state-card__body">Choose one or more report graphs from the filter panel to show analytics here.</p>
                </div>
            @endif

        <div class="analytics-chart-grid grid-cols-1 lg:grid-cols-2">
            <div class="{{ $showChart('employmentTrendsChart') ? '' : 'hidden' }} analytics-chart-card analytics-chart-card--span-full bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="analytics-chart-card__header">
                    <div class="analytics-chart-card__heading">
                        <h3 class="text-lg font-semibold text-gray-900">Employment Trends</h3>
                        <p class="mt-1 text-sm text-gray-500">Employment outcome movement across recent months</p>
                    </div>
                    @if($hasEmploymentTrends)
                        <div class="analytics-chart-card__actions">
                            <button type="button" onclick="exportChartAsPdf('employmentTrendsChart', 'employment-trends')" class="border border-gray-300 rounded-md px-2 py-1 text-xs hover:bg-gray-50">PDF</button>
                            <button type="button" onclick="exportChartAsCsv('employmentTrendsChart', 'employment-trends')" class="border border-gray-300 rounded-md px-2 py-1 text-xs hover:bg-gray-50">CSV</button>
                        </div>
                    @endif
                </div>
                @if($hasEmploymentTrends)
                    <div class="analytics-chart-area">
                        <canvas id="employmentTrendsChart" width="400" height="200"></canvas>
                    </div>
                @else
                    <div class="empty-state-card">
                        <span class="empty-state-card__icon"><i class='bx bx-line-chart'></i></span>
                        <p class="empty-state-card__title">No employment trend data yet</p>
                        <p class="empty-state-card__body">Employment movement will appear once alumni submit tracer employment outcomes.</p>
                    </div>
                @endif
            </div>

            <div class="{{ $showChart('tracedGraduatesChart') ? '' : 'hidden' }} analytics-chart-card bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="analytics-chart-card__header">
                    <div class="analytics-chart-card__heading">
                        <h3 class="text-lg font-semibold text-gray-900">Traced Graduates</h3>
                        <p class="mt-1 text-sm text-gray-500">Traced alumni grouped by graduation year</p>
                    </div>
                    @if($hasTracedGraduates)
                        <div class="analytics-chart-card__actions">
                            <button type="button" onclick="exportChartAsPdf('tracedGraduatesChart', 'traced-graduates')" class="border border-gray-300 rounded-md px-2 py-1 text-xs hover:bg-gray-50">PDF</button>
                            <button type="button" onclick="exportChartAsCsv('tracedGraduatesChart', 'traced-graduates')" class="border border-gray-300 rounded-md px-2 py-1 text-xs hover:bg-gray-50">CSV</button>
                        </div>
                    @endif
                </div>
                @if($hasTracedGraduates)
                    <div class="analytics-chart-area">
                        <canvas id="tracedGraduatesChart" width="400" height="200"></canvas>
                    </div>
                @else
                    <div class="empty-state-card">
                        <span class="empty-state-card__icon"><i class='bx bx-id-card'></i></span>
                        <p class="empty-state-card__title">No traced graduates yet</p>
                        <p class="empty-state-card__body">Graduation-year counts will appear when matching alumni or tracer records exist.</p>
                    </div>
                @endif
            </div>

            <div class="{{ $showChart('industryChart') ? '' : 'hidden' }} analytics-chart-card bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="analytics-chart-card__header">
                    <div class="analytics-chart-card__heading">
                        <h3 class="text-lg font-semibold text-gray-900">Industry Distribution</h3>
                        <p class="mt-1 text-sm text-gray-500">Current industry groupings in the active scope</p>
                    </div>
                    @if($hasIndustryData)
                        <div class="analytics-chart-card__actions">
                            <button type="button" onclick="exportChartAsPdf('industryChart', 'industry-distribution')" class="border border-gray-300 rounded-md px-2 py-1 text-xs hover:bg-gray-50">PDF</button>
                            <button type="button" onclick="exportChartAsCsv('industryChart', 'industry-distribution')" class="border border-gray-300 rounded-md px-2 py-1 text-xs hover:bg-gray-50">CSV</button>
                        </div>
                    @endif
                </div>
                @if($hasIndustryData)
                    <div class="analytics-chart-area analytics-chart-area--radial">
                        <canvas id="industryChart" width="400" height="200"></canvas>
                    </div>
                @else
                    <div class="empty-state-card">
                        <span class="empty-state-card__icon"><i class='bx bx-building-house'></i></span>
                        <p class="empty-state-card__title">No industry data yet</p>
                        <p class="empty-state-card__body">Industry groupings will appear once alumni records include industry information.</p>
                    </div>
                @endif
            </div>

            <div class="{{ $showChart('employabilityChart') ? '' : 'hidden' }} analytics-chart-card bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="analytics-chart-card__header">
                    <div class="analytics-chart-card__heading">
                        <h3 class="text-lg font-semibold text-gray-900">Employability Rate</h3>
                        <p class="mt-1 text-sm text-gray-500">Employability percentage by graduation year</p>
                    </div>
                    @if($hasEmployabilityData)
                        <div class="analytics-chart-card__actions">
                            <button type="button" onclick="exportChartAsPdf('employabilityChart', 'employability-rate')" class="border border-gray-300 rounded-md px-2 py-1 text-xs hover:bg-gray-50">PDF</button>
                            <button type="button" onclick="exportChartAsCsv('employabilityChart', 'employability-rate')" class="border border-gray-300 rounded-md px-2 py-1 text-xs hover:bg-gray-50">CSV</button>
                        </div>
                    @endif
                </div>
                @if($hasEmployabilityData)
                    <div class="analytics-chart-area">
                        <canvas id="employabilityChart" width="400" height="200"></canvas>
                    </div>
                @else
                    <div class="empty-state-card">
                        <span class="empty-state-card__icon"><i class='bx bx-trending-up'></i></span>
                        <p class="empty-state-card__title">No employability rate data yet</p>
                        <p class="empty-state-card__body">Employability rates need alumni with graduation years and recorded employment outcomes.</p>
                    </div>
                @endif
            </div>

            <div class="{{ $showChart('globalDistributionChart') ? '' : 'hidden' }} analytics-chart-card bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="analytics-chart-card__header">
                    <div class="analytics-chart-card__heading">
                        <h3 class="text-lg font-semibold text-gray-900">Global Distribution</h3>
                        <p class="mt-1 text-sm text-gray-500">Alumni distribution by recorded location</p>
                    </div>
                    @if($hasGlobalData)
                        <div class="analytics-chart-card__actions">
                            <button type="button" onclick="exportChartAsPdf('globalDistributionChart', 'global-distribution')" class="border border-gray-300 rounded-md px-2 py-1 text-xs hover:bg-gray-50">PDF</button>
                            <button type="button" onclick="exportChartAsCsv('globalDistributionChart', 'global-distribution')" class="border border-gray-300 rounded-md px-2 py-1 text-xs hover:bg-gray-50">CSV</button>
                        </div>
                    @endif
                </div>
                @if($hasGlobalData)
                    <div class="analytics-chart-area">
                        <canvas id="globalDistributionChart" width="400" height="200"></canvas>
                    </div>
                @else
                    <div class="empty-state-card">
                        <span class="empty-state-card__icon"><i class='bx bx-map'></i></span>
                        <p class="empty-state-card__title">No location data yet</p>
                        <p class="empty-state-card__body">Location distribution will appear once alumni records include location information.</p>
                    </div>
                @endif
            </div>

            <div class="{{ $showChart('reasonsChart') ? '' : 'hidden' }} analytics-chart-card bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="analytics-chart-card__header">
                    <div class="analytics-chart-card__heading">
                        <h3 class="text-lg font-semibold text-gray-900">Reasons for Unemployment</h3>
                        <p class="mt-1 text-sm text-gray-500">Latest reported challenges from unemployed alumni</p>
                    </div>
                    @if($hasReasonsData)
                        <div class="analytics-chart-card__actions">
                            <button type="button" onclick="exportChartAsPdf('reasonsChart', 'reasons-for-unemployment')" class="border border-gray-300 rounded-md px-2 py-1 text-xs hover:bg-gray-50">PDF</button>
                            <button type="button" onclick="exportChartAsCsv('reasonsChart', 'reasons-for-unemployment')" class="border border-gray-300 rounded-md px-2 py-1 text-xs hover:bg-gray-50">CSV</button>
                        </div>
                    @endif
                </div>
                @if($hasReasonsData)
                    <div class="analytics-chart-area analytics-chart-area--bar-balanced">
                        <canvas id="reasonsChart" width="400" height="200"></canvas>
                    </div>
                @else
                    <div class="empty-state-card">
                        <span class="empty-state-card__icon"><i class='bx bx-comment-error'></i></span>
                        <p class="empty-state-card__title">No unemployment reasons yet</p>
                        <p class="empty-state-card__body">Reasons will appear when unemployed alumni submit challenges in their tracer forms.</p>
                    </div>
                @endif
            </div>
        </div>

        <div class="analytics-chart-grid grid-cols-1 lg:grid-cols-2">
            <div class="{{ $showChart('userGrowthChart') ? '' : 'hidden' }} analytics-chart-card analytics-chart-card--span-full bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="analytics-chart-card__header">
                    <div class="analytics-chart-card__heading">
                        <h3 class="text-lg font-semibold text-gray-900">User Growth</h3>
                        <p class="mt-1 text-sm text-gray-500">{{ $ui['userGrowthRangeLabel'] }} / {{ $ui['selectedCollegeLabel'] }} / {{ $ui['selectedGraduationYearLabel'] }}</p>
                    </div>
                    @if($hasUserGrowth)
                        <div class="analytics-chart-card__actions">
                            <button type="button" onclick="exportChartAsPdf('userGrowthChart', 'user-growth')" class="border border-gray-300 rounded-md px-2 py-1 text-xs hover:bg-gray-50">PDF</button>
                            <button type="button" onclick="exportChartAsCsv('userGrowthChart', 'user-growth')" class="border border-gray-300 rounded-md px-2 py-1 text-xs hover:bg-gray-50">CSV</button>
                        </div>
                    @endif
                </div>
                @if($hasUserGrowth)
                    <div class="analytics-chart-area">
                        <canvas id="userGrowthChart" width="400" height="200"></canvas>
                    </div>
                @else
                    <div class="empty-state-card">
                        <span class="empty-state-card__icon"><i class='bx bx-line-chart'></i></span>
                        <p class="empty-state-card__title">No user growth data yet</p>
                        <p class="empty-state-card__body">No data for this selection yet.</p>
                    </div>
                @endif
            </div>

            <div class="{{ $showChart('jobApplicationsChart') ? '' : 'hidden' }} analytics-chart-card analytics-chart-card--span-full bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="analytics-chart-card__header">
                    <div class="analytics-chart-card__heading">
                        <h3 class="text-lg font-semibold text-gray-900">Job Opportunity Submissions</h3>
                        <p class="mt-1 text-sm text-gray-500">{{ $ui['applicationsRangeLabel'] }}</p>
                    </div>
                    @if($hasJobApplicationsTrend)
                        <div class="analytics-chart-card__actions">
                            <button type="button" onclick="exportChartAsPdf('jobApplicationsChart', 'job-submissions-trend')" class="border border-gray-300 rounded-md px-2 py-1 text-xs hover:bg-gray-50">PDF</button>
                            <button type="button" onclick="exportChartAsCsv('jobApplicationsChart', 'job-submissions-trend')" class="border border-gray-300 rounded-md px-2 py-1 text-xs hover:bg-gray-50">CSV</button>
                        </div>
                    @endif
                </div>
                @if($hasJobApplicationsTrend)
                    <div class="analytics-chart-area">
                        <canvas id="jobApplicationsChart" width="400" height="200"></canvas>
                    </div>
                @else
                    <div class="empty-state-card">
                        <span class="empty-state-card__icon"><i class='bx bx-file'></i></span>
                        <p class="empty-state-card__title">No job submission trend yet</p>
                        <p class="empty-state-card__body">No data for this selection yet.</p>
                    </div>
                @endif
            </div>
        </div>

        <div class="analytics-chart-grid grid-cols-1 lg:grid-cols-2">
            <div class="{{ $showChart('collegeDistributionChart') ? '' : 'hidden' }} analytics-chart-card bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="analytics-chart-card__header">
                    <div class="analytics-chart-card__heading">
                        <h3 class="text-lg font-semibold text-gray-900">Alumni by College</h3>
                        <p class="mt-1 text-sm text-gray-500">{{ $ui['selectedGraduationYearLabel'] }}</p>
                    </div>
                    @if($hasCollegeDistribution)
                        <div class="analytics-chart-card__actions">
                            <button type="button" onclick="exportChartAsPdf('collegeDistributionChart', 'alumni-by-college')" class="border border-gray-300 rounded-md px-2 py-1 text-xs hover:bg-gray-50">PDF</button>
                            <button type="button" onclick="exportChartAsCsv('collegeDistributionChart', 'alumni-by-college')" class="border border-gray-300 rounded-md px-2 py-1 text-xs hover:bg-gray-50">CSV</button>
                        </div>
                    @endif
                </div>
                @if($hasCollegeDistribution)
                    <div class="analytics-chart-area analytics-chart-area--radial">
                        <canvas id="collegeDistributionChart" width="400" height="200"></canvas>
                    </div>
                @else
                    <div class="empty-state-card">
                        <span class="empty-state-card__icon"><i class='bx bx-buildings'></i></span>
                        <p class="empty-state-card__title">No college distribution available</p>
                        <p class="empty-state-card__body">No data for this selection yet.</p>
                    </div>
                @endif
            </div>

            <div class="{{ $showChart('employmentStatusChart') ? '' : 'hidden' }} analytics-chart-card bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="analytics-chart-card__header">
                    <div class="analytics-chart-card__heading">
                        <h3 class="text-lg font-semibold text-gray-900">Employment Status</h3>
                        <p class="mt-1 text-sm text-gray-500">{{ $ui['selectedCollegeLabel'] }} / {{ $ui['selectedGraduationYearLabel'] }}</p>
                    </div>
                    @if($hasEmploymentStatus)
                        <div class="analytics-chart-card__actions">
                            <button type="button" onclick="exportChartAsPdf('employmentStatusChart', 'employment-status')" class="border border-gray-300 rounded-md px-2 py-1 text-xs hover:bg-gray-50">PDF</button>
                            <button type="button" onclick="exportChartAsCsv('employmentStatusChart', 'employment-status')" class="border border-gray-300 rounded-md px-2 py-1 text-xs hover:bg-gray-50">CSV</button>
                        </div>
                    @endif
                </div>
                @if($hasEmploymentStatus)
                    <div class="analytics-chart-area analytics-chart-area--radial">
                        <canvas id="employmentStatusChart" width="400" height="200"></canvas>
                    </div>
                @else
                    <div class="empty-state-card">
                        <span class="empty-state-card__icon"><i class='bx bx-briefcase'></i></span>
                        <p class="empty-state-card__title">No employment status data yet</p>
                        <p class="empty-state-card__body">No data for this selection yet.</p>
                    </div>
                @endif
            </div>

            <div class="{{ $showChart('graduationYearChart') ? '' : 'hidden' }} analytics-chart-card bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="analytics-chart-card__header">
                    <div class="analytics-chart-card__heading">
                        <h3 class="text-lg font-semibold text-gray-900">Graduation Year Trends</h3>
                        <p class="mt-1 text-sm text-gray-500">{{ $ui['selectedCollegeLabel'] }}</p>
                    </div>
                    @if($hasGraduationYearTrends)
                        <div class="analytics-chart-card__actions">
                            <button type="button" onclick="exportChartAsPdf('graduationYearChart', 'graduation-year-trends')" class="border border-gray-300 rounded-md px-2 py-1 text-xs hover:bg-gray-50">PDF</button>
                            <button type="button" onclick="exportChartAsCsv('graduationYearChart', 'graduation-year-trends')" class="border border-gray-300 rounded-md px-2 py-1 text-xs hover:bg-gray-50">CSV</button>
                        </div>
                    @endif
                </div>
                @if($hasGraduationYearTrends)
                    <div class="analytics-chart-area">
                        <canvas id="graduationYearChart" width="400" height="200"></canvas>
                    </div>
                @else
                    <div class="empty-state-card">
                        <span class="empty-state-card__icon"><i class='bx bx-calendar'></i></span>
                        <p class="empty-state-card__title">No graduation trend data yet</p>
                        <p class="empty-state-card__body">No data for this selection yet.</p>
                    </div>
                @endif
            </div>

            <div class="{{ $showChart('industryGrowthChart') ? '' : 'hidden' }} analytics-chart-card bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="analytics-chart-card__header">
                    <div class="analytics-chart-card__heading">
                        <h3 class="text-lg font-semibold text-gray-900">Industry Growth</h3>
                        <p class="mt-1 text-sm text-gray-500">{{ $ui['userGrowthRangeLabel'] }} / {{ $ui['selectedCollegeLabel'] }}</p>
                    </div>
                    @if($hasIndustryGrowth)
                        <div class="analytics-chart-card__actions">
                            <button type="button" onclick="exportChartAsPdf('industryGrowthChart', 'industry-growth')" class="border border-gray-300 rounded-md px-2 py-1 text-xs hover:bg-gray-50">PDF</button>
                            <button type="button" onclick="exportChartAsCsv('industryGrowthChart', 'industry-growth')" class="border border-gray-300 rounded-md px-2 py-1 text-xs hover:bg-gray-50">CSV</button>
                        </div>
                    @endif
                </div>
                @if($hasIndustryGrowth)
                    <div class="analytics-chart-area">
                        <canvas id="industryGrowthChart" width="400" height="200"></canvas>
                    </div>
                @else
                    <div class="empty-state-card">
                        <span class="empty-state-card__icon"><i class='bx bx-trending-up'></i></span>
                        <p class="empty-state-card__title">No industry growth data yet</p>
                        <p class="empty-state-card__body">No data for this selection yet.</p>
                    </div>
                @endif
            </div>

            <div class="{{ $showChart('feedbacksChart') ? '' : 'hidden' }} analytics-chart-card analytics-chart-card--span-full bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="analytics-chart-card__header">
                    <div class="analytics-chart-card__heading">
                        <h3 class="text-lg font-semibold text-gray-900">Feedback Categories</h3>
                        <p class="mt-1 text-sm text-gray-500">{{ $ui['selectedCollegeLabel'] }} / {{ $ui['selectedGraduationYearLabel'] }}</p>
                    </div>
                    @if($hasFeedbacks)
                        <div class="analytics-chart-card__actions">
                            <button type="button" onclick="exportChartAsPdf('feedbacksChart', 'feedback-categories')" class="border border-gray-300 rounded-md px-2 py-1 text-xs hover:bg-gray-50">PDF</button>
                            <button type="button" onclick="exportChartAsCsv('feedbacksChart', 'feedback-categories')" class="border border-gray-300 rounded-md px-2 py-1 text-xs hover:bg-gray-50">CSV</button>
                        </div>
                    @endif
                </div>
                @if($hasFeedbacks)
                    <div class="analytics-chart-area analytics-chart-area--radial">
                        <canvas id="feedbacksChart" width="400" height="200"></canvas>
                    </div>
                @else
                    <div class="empty-state-card">
                        <span class="empty-state-card__icon"><i class='bx bx-message-rounded-dots'></i></span>
                        <p class="empty-state-card__title">No feedback data yet</p>
                        <p class="empty-state-card__body">No data for this selection yet.</p>
                    </div>
                @endif
            </div>
        </div>

        <div class="analytics-chart-grid grid-cols-1 lg:grid-cols-2">
            <div class="{{ $showChart('curriculumRelevanceChart') ? '' : 'hidden' }} analytics-chart-card bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="analytics-chart-card__header">
                    <div class="analytics-chart-card__heading">
                        <h3 class="text-lg font-semibold text-gray-900">Curriculum Relevance to First Job</h3>
                        <p class="mt-1 text-sm text-gray-500">Latest tracer response per alumni</p>
                    </div>
                    @if($hasCurriculumRelevance)
                        <div class="analytics-chart-card__actions">
                            <button type="button" onclick="exportChartAsPdf('curriculumRelevanceChart', 'curriculum-relevance')" class="border border-gray-300 rounded-md px-2 py-1 text-xs hover:bg-gray-50">PDF</button>
                            <button type="button" onclick="exportChartAsCsv('curriculumRelevanceChart', 'curriculum-relevance')" class="border border-gray-300 rounded-md px-2 py-1 text-xs hover:bg-gray-50">CSV</button>
                        </div>
                    @endif
                </div>
                @if($hasCurriculumRelevance)
                    <div class="analytics-chart-area analytics-chart-area--radial analytics-chart-area--radial-balanced">
                        <canvas id="curriculumRelevanceChart" width="400" height="200"></canvas>
                    </div>
                @else
                    <div class="empty-state-card">
                        <span class="empty-state-card__icon"><i class='bx bx-book-reader'></i></span>
                        <p class="empty-state-card__title">No curriculum relevance data yet</p>
                        <p class="empty-state-card__body">No data for this selection yet.</p>
                    </div>
                @endif
            </div>

            <div class="{{ $showChart('firstJobTimeChart') ? '' : 'hidden' }} analytics-chart-card bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="analytics-chart-card__header">
                    <div class="analytics-chart-card__heading">
                        <h3 class="text-lg font-semibold text-gray-900">Time to First Job</h3>
                        <p class="mt-1 text-sm text-gray-500">Latest tracer response per alumni</p>
                    </div>
                    @if($hasFirstJobTime)
                        <div class="analytics-chart-card__actions">
                            <button type="button" onclick="exportChartAsPdf('firstJobTimeChart', 'time-to-first-job')" class="border border-gray-300 rounded-md px-2 py-1 text-xs hover:bg-gray-50">PDF</button>
                            <button type="button" onclick="exportChartAsCsv('firstJobTimeChart', 'time-to-first-job')" class="border border-gray-300 rounded-md px-2 py-1 text-xs hover:bg-gray-50">CSV</button>
                        </div>
                    @endif
                </div>
                @if($hasFirstJobTime)
                    <div class="analytics-chart-area analytics-chart-area--bar-balanced">
                        <canvas id="firstJobTimeChart" width="400" height="200"></canvas>
                    </div>
                @else
                    <div class="empty-state-card">
                        <span class="empty-state-card__icon"><i class='bx bx-timer'></i></span>
                        <p class="empty-state-card__title">No first-job timing data yet</p>
                        <p class="empty-state-card__body">No data for this selection yet.</p>
                    </div>
                @endif
            </div>

            <div class="{{ $showChart('presentEmploymentStatusChart') ? '' : 'hidden' }} analytics-chart-card analytics-chart-card--span-full bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="analytics-chart-card__header">
                    <div class="analytics-chart-card__heading">
                        <h3 class="text-lg font-semibold text-gray-900">Present Employment Status</h3>
                        <p class="mt-1 text-sm text-gray-500">Latest tracer response per alumni</p>
                    </div>
                    @if($hasPresentEmploymentStatus)
                        <div class="analytics-chart-card__actions">
                            <button type="button" onclick="exportChartAsPdf('presentEmploymentStatusChart', 'present-employment-status')" class="border border-gray-300 rounded-md px-2 py-1 text-xs hover:bg-gray-50">PDF</button>
                            <button type="button" onclick="exportChartAsCsv('presentEmploymentStatusChart', 'present-employment-status')" class="border border-gray-300 rounded-md px-2 py-1 text-xs hover:bg-gray-50">CSV</button>
                        </div>
                    @endif
                </div>
                @if($hasPresentEmploymentStatus)
                    <div class="analytics-chart-area analytics-chart-area--radial">
                        <canvas id="presentEmploymentStatusChart" width="400" height="200"></canvas>
                    </div>
                @else
                    <div class="empty-state-card">
                        <span class="empty-state-card__icon"><i class='bx bx-briefcase-alt-2'></i></span>
                        <p class="empty-state-card__title">No present employment status data yet</p>
                        <p class="empty-state-card__body">No data for this selection yet.</p>
                    </div>
                @endif
            </div>

            <div class="{{ ($showChart('teachingFeedbackChart') || $showChart('facilitiesSatisfactionChart') || $showChart('overallExperienceChart')) ? '' : 'hidden' }} analytics-chart-card--span-full grid grid-cols-1 gap-6 lg:grid-cols-3">
                <div class="{{ $showChart('teachingFeedbackChart') ? '' : 'hidden' }} analytics-chart-card bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <div class="analytics-chart-card__header">
                        <div class="analytics-chart-card__heading">
                            <h3 class="text-lg font-semibold text-gray-900">Teaching Feedback</h3>
                            <p class="mt-1 text-sm text-gray-500">Average rating per teaching question</p>
                        </div>
                        @if($hasTeachingFeedback)
                            <div class="analytics-chart-card__actions">
                                <button type="button" onclick="exportChartAsPdf('teachingFeedbackChart', 'teaching-feedback')" class="border border-gray-300 rounded-md px-2 py-1 text-xs hover:bg-gray-50">PDF</button>
                                <button type="button" onclick="exportChartAsCsv('teachingFeedbackChart', 'teaching-feedback')" class="border border-gray-300 rounded-md px-2 py-1 text-xs hover:bg-gray-50">CSV</button>
                            </div>
                        @endif
                    </div>
                    @if($hasTeachingFeedback)
                        <div class="analytics-chart-area analytics-chart-area--bar-balanced">
                            <canvas id="teachingFeedbackChart" width="400" height="200"></canvas>
                        </div>
                    @else
                        <div class="empty-state-card">
                            <span class="empty-state-card__icon"><i class='bx bx-book-open'></i></span>
                            <p class="empty-state-card__title">No teaching feedback data yet</p>
                            <p class="empty-state-card__body">No data for this selection yet.</p>
                        </div>
                    @endif
                </div>

                <div class="{{ $showChart('facilitiesSatisfactionChart') ? '' : 'hidden' }} analytics-chart-card bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <div class="analytics-chart-card__header">
                        <div class="analytics-chart-card__heading">
                            <h3 class="text-lg font-semibold text-gray-900">University Facilities</h3>
                            <p class="mt-1 text-sm text-gray-500">Latest tracer response per alumni</p>
                        </div>
                        @if($hasFacilitiesSatisfaction)
                            <div class="analytics-chart-card__actions">
                                <button type="button" onclick="exportChartAsPdf('facilitiesSatisfactionChart', 'university-facilities')" class="border border-gray-300 rounded-md px-2 py-1 text-xs hover:bg-gray-50">PDF</button>
                                <button type="button" onclick="exportChartAsCsv('facilitiesSatisfactionChart', 'university-facilities')" class="border border-gray-300 rounded-md px-2 py-1 text-xs hover:bg-gray-50">CSV</button>
                            </div>
                        @endif
                    </div>
                    @if($hasFacilitiesSatisfaction)
                        <div class="analytics-chart-area analytics-chart-area--radial">
                            <canvas id="facilitiesSatisfactionChart" width="400" height="200"></canvas>
                        </div>
                    @else
                        <div class="empty-state-card">
                            <span class="empty-state-card__icon"><i class='bx bx-building-house'></i></span>
                            <p class="empty-state-card__title">No university facilities data yet</p>
                            <p class="empty-state-card__body">No data for this selection yet.</p>
                        </div>
                    @endif
                </div>

                <div class="{{ $showChart('overallExperienceChart') ? '' : 'hidden' }} analytics-chart-card bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <div class="analytics-chart-card__header">
                        <div class="analytics-chart-card__heading">
                            <h3 class="text-lg font-semibold text-gray-900">Overall Experience</h3>
                            <p class="mt-1 text-sm text-gray-500">Average rating per overall experience question</p>
                        </div>
                        @if($hasOverallExperience)
                            <div class="analytics-chart-card__actions">
                                <button type="button" onclick="exportChartAsPdf('overallExperienceChart', 'overall-experience')" class="border border-gray-300 rounded-md px-2 py-1 text-xs hover:bg-gray-50">PDF</button>
                                <button type="button" onclick="exportChartAsCsv('overallExperienceChart', 'overall-experience')" class="border border-gray-300 rounded-md px-2 py-1 text-xs hover:bg-gray-50">CSV</button>
                            </div>
                        @endif
                    </div>
                    @if($hasOverallExperience)
                        <div class="analytics-chart-area analytics-chart-area--bar-balanced">
                            <canvas id="overallExperienceChart" width="400" height="200"></canvas>
                        </div>
                    @else
                        <div class="empty-state-card">
                            <span class="empty-state-card__icon"><i class='bx bx-star'></i></span>
                            <p class="empty-state-card__title">No overall experience data yet</p>
                            <p class="empty-state-card__body">No data for this selection yet.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

            <div class="arlo-report-summary-grid">
        <div class="arlo-report-panel">
            <h3>College Performance</h3>
            <div class="arlo-college-tiles">
                @forelse($collegeDistribution['labels'] as $index => $college)
                    @php
                        $count = $collegeDistribution['data'][$index] ?? 0;
                        $percent = $totalUsers > 0 ? round(($count / $totalUsers) * 100, 1) : 0;
                        $tone = $collegeTileTones[$index % count($collegeTileTones)];
                    @endphp
                    <article class="arlo-college-tile arlo-college-tile--{{ $tone }}">
                        <span class="arlo-college-tile__percent">{{ $percent }}%</span>
                        <div class="arlo-college-tile__body">
                            <p>{{ $college }}</p>
                            <strong>{{ $count }}</strong>
                        </div>
                        <p class="arlo-college-tile__footer">Share of total <span>{{ $percent }}%</span></p>
                    </article>
                @empty
                    <p class="text-sm text-gray-500">No college data yet.</p>
                @endforelse
            </div>
        </div>

        <div class="arlo-report-panel">
            <h3>Recent Activity</h3>
            <div class="space-y-3">
                @forelse($recentActivity as $activity)
                    <div class="flex items-start space-x-3">
                        <i class='bx bx-time text-blue-600 mt-1'></i>
                        <div>
                            <p class="text-sm text-gray-900">{{ $activity['label'] }}</p>
                            <p class="text-xs text-gray-500">{{ $activity['time'] }}</p>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">No recent activity.</p>
                @endforelse
            </div>
        </div>

        <div class="arlo-report-panel">
            <h3>Employment Analytics</h3>
            <div class="space-y-4">
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span class="text-gray-600">Employment Rate</span>
                        <span class="text-green-600 font-medium">{{ $employmentRate }}%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-green-600 h-2 rounded-full" style="width: {{ $employmentRate }}%"></div>
                    </div>
                </div>
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span class="text-gray-600">Self-Employment Rate</span>
                        <span class="text-yellow-600 font-medium">{{ $selfEmploymentRate }}%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-yellow-600 h-2 rounded-full" style="width: {{ $selfEmploymentRate }}%"></div>
                    </div>
                </div>
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span class="text-gray-600">Unemployment Rate</span>
                        <span class="text-red-600 font-medium">{{ $unemploymentRate }}%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-red-600 h-2 rounded-full" style="width: {{ $unemploymentRate }}%"></div>
                    </div>
                </div>
                <div class="pt-2 border-t border-gray-200">
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <p class="text-gray-600">Most Common First Job Time</p>
                            <p class="font-medium text-gray-900">{{ $topFirstJobTimeLabel }}</p>
                        </div>
                        <div>
                            <p class="text-gray-600">Top Industry</p>
                            <p class="font-medium text-gray-900">{{ $industryGrowth['datasets'][0]['label'] ?? 'N/A' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

            <footer class="arlo-report-signatures">
                <span>Dashboard Maintained and Monitored by:</span>
                <span>Evaluated by:</span>
                <span>Approved by:</span>
            </footer>
        </section>
        </div>
    </div>

    <script type="application/json" id="adminAnalyticsState">@json($adminAnalyticsState)</script>
</div>
