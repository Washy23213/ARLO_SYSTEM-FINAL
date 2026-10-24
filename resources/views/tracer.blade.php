@extends('components.layouts.dashboard')

@section('title', 'Tracer Form')

@section('content')
@php
    $latestTracer = $latestTracer ?? null;
    $editingTracer = $editingTracer ?? null;
    $formTracer = $formTracer ?? $latestTracer;
    $tracerHistory = $tracerHistory ?? collect();
    $employmentTypes = $employmentTypes ?? [];
    $industryOptions = $industryOptions ?? [];
    $curriculumCompetencies = $curriculumCompetencies ?? [];
    $curriculumReasonOptions = $curriculumReasonOptions ?? [];
    $firstJobTimeOptions = $firstJobTimeOptions ?? [];
    $firstJobRelatedOptions = $firstJobRelatedOptions ?? [];
    $firstJobFindingOptions = $firstJobFindingOptions ?? [];
    $firstJobEarningsOptions = $firstJobEarningsOptions ?? [];
    $firstJobDurationOptions = $firstJobDurationOptions ?? [];
    $presentEmploymentStatuses = $presentEmploymentStatuses ?? [];
    $businessLineOptions = $businessLineOptions ?? [];
    $jobLevelOptions = $jobLevelOptions ?? [];
    $presentJobReasonOptions = $presentJobReasonOptions ?? [];
    $presentJobEarningsOptions = $presentJobEarningsOptions ?? [];
    $facilitiesSatisfactionOptions = $facilitiesSatisfactionOptions ?? [];
    $agreementScaleOptions = $agreementScaleOptions ?? [];
    $educationLevels = $educationLevels ?? [];
    $jobSatisfactionOptions = $jobSatisfactionOptions ?? [];
    $suppressEditAutoscroll = $suppressEditAutoscroll ?? false;
    $selectedFirstJobCompetencies = old('first_job_useful_competencies', $formTracer?->first_job_useful_competencies ?? []);
    $selectedPresentJobReasons = old('present_job_acceptance_reasons', $formTracer?->present_job_acceptance_reasons ?? []);
    $curriculumRelevantValue = old('curriculum_relevant_to_first_job', $formTracer?->curriculum_relevant_to_first_job);
    $employedAfterGraduationValue = old('employed_after_graduation', $formTracer?->employed_after_graduation);
    $presentlyEmployedValue = old('presently_employed', $formTracer?->presently_employed);
    $hasEmployeeAwardsValue = old('has_employee_awards', $formTracer?->has_employee_awards);
    $hasCurriculumSuggestionsValue = old('has_curriculum_suggestions', $formTracer?->has_curriculum_suggestions);
    $hasEmployabilitySuggestionsValue = old('has_employability_suggestions', $formTracer?->has_employability_suggestions);
    $hasFacilitySuggestionsValue = old('has_facility_suggestions', $formTracer?->has_facility_suggestions);
    $isFirstJobAfterCollegeValue = old('is_first_job_after_college', $formTracer?->is_first_job_after_college);
    $booleanAnswerIsYes = fn ($value) => $value === true || (string) $value === '1';
    $booleanAnswerIsNo = fn ($value) => $value === false || (string) $value === '0';
    $tracerHistoryTotal = $tracerHistoryTotal ?? $tracerHistory->count();
    $tracerHistoryCountLabel = $tracerHistoryTotal > $tracerHistory->count()
        ? $tracerHistory->count() . ' of ' . $tracerHistoryTotal . ' entries'
        : $tracerHistory->count() . ' entries';
    $registrationMissingNote = 'Add this when available so your tracer record stays complete.';
    $employmentConditionalNote = 'Available when Employment Type is Employed or Self-Employed.';
    $educationConditionalNote = 'Available when the further education or training option is checked.';
    $snapshotJumpClass = 'w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-left transition-all duration-200 hover:-translate-y-0.5 hover:border-emerald-300 hover:bg-emerald-50/60 hover:shadow-sm focus:outline-none focus:ring-2 focus:ring-emerald-200';
    $snapshotPrimaryJumpClass = 'w-full rounded-2xl border border-emerald-100 bg-emerald-50/70 px-4 py-3 text-left transition-all duration-200 hover:-translate-y-0.5 hover:border-emerald-300 hover:bg-emerald-50 hover:shadow-sm focus:outline-none focus:ring-2 focus:ring-emerald-200';
    $employmentTypeValue = old('employment_type', $formTracer?->employment_type);
    $currentPositionValue = old('current_position', $formTracer?->current_position);
    $currentCompanyValue = old('current_company', $formTracer?->current_company);
    $monthlyIncomeValue = \App\Support\SystemOptions::presentJobEarningsRangeForIncome(old('monthly_income', $formTracer?->monthly_income));
    $firstJobMonthlyEarningsValue = \App\Support\SystemOptions::firstJobEarningsLabel(old('first_job_monthly_earnings', $formTracer?->first_job_monthly_earnings));
    $presentJobMonthlyEarningsValue = \App\Support\SystemOptions::presentJobEarningsLabel(old('present_job_monthly_earnings', $formTracer?->present_job_monthly_earnings));
    $industryValue = old('industry', $formTracer?->industry ?? auth()->user()?->industry);
    $selectOptionValue = function ($value, array $options) {
        $cleanValue = \App\Support\SystemOptions::clean(is_string($value) ? $value : null);

        if ($cleanValue === null) {
            return null;
        }

        foreach ($options as $option) {
            if (mb_strtolower($cleanValue) === mb_strtolower($option)) {
                return $option;
            }
        }

        return null;
    };
    $industryValue = $selectOptionValue($industryValue, $industryOptions);
    $jobSatisfactionValue = old('job_satisfaction', $formTracer?->job_satisfaction);
    $furtherEducationValue = old('further_education', $formTracer?->further_education);
    $furtherEducationEnabled = filter_var($furtherEducationValue, FILTER_VALIDATE_BOOLEAN);
    $educationLevelValue = old('education_level', $formTracer?->education_level);
    $skillsUsedValue = old('skills_used', $formTracer?->skills_used);
    $challengesFacedValue = old('challenges_faced', $formTracer?->challenges_faced);
    $recommendationsValue = old('recommendations', $formTracer?->recommendations);
    $showTracerAttestation = old('information_attestation') || $errors->has('information_attestation');
    $scholarshipNameOptions = \App\Support\SystemOptions::scholarshipNames();
    $scholarshipUser = auth()->user();
    $storedScholarshipName = $scholarshipUser?->scholarship_name;
    $storedScholarshipUsesOther = $storedScholarshipName && !in_array($storedScholarshipName, $scholarshipNameOptions, true);
    $scholarshipHasValue = old('has_scholarship', $scholarshipUser?->has_scholarship ? '1' : '0');
    $scholarshipNameValue = old(
        'scholarship_name',
        $scholarshipUser?->has_scholarship
            ? ($storedScholarshipUsesOther ? 'Other' : $storedScholarshipName)
            : ''
    );
    $scholarshipNameOtherValue = old(
        'scholarship_name_other',
        $storedScholarshipUsesOther ? $storedScholarshipName : ''
    );
    $scholarshipYearAwardedValue = old('scholarship_year_awarded', $scholarshipUser?->scholarship_year_awarded);
    $scholarshipHelpedValue = old(
        'scholarship_helped_get_job',
        $scholarshipUser?->scholarship_helped_get_job === null
            ? ''
            : ($scholarshipUser->scholarship_helped_get_job ? '1' : '0')
    );
    $scholarshipEditorOpen = $errors->hasAny([
        'has_scholarship',
        'scholarship_name',
        'scholarship_name_other',
        'scholarship_year_awarded',
        'scholarship_helped_get_job',
        'scholarship_attestation',
    ]) || old('_scholarship_form') === '1';
    $showScholarshipAttestation = old('scholarship_attestation') || $errors->has('scholarship_attestation');
    $overviewTracerTargets = [
        'employment_summary' => [
            'label' => 'Employment summary',
            'message' => 'Update your employment summary in the tracer form below.',
        ],
        'latest_tracer_record' => [
            'label' => 'Latest tracer record',
            'message' => ($latestTracer ?? null)
                ? 'Review or update your tracer details in the highlighted form below.'
                : 'Add your first tracer record in the highlighted form below.',
        ],
    ];
    $tracerOverviewFocusField = request()->query('focus_field');
    $tracerOverviewFocusTarget = $overviewTracerTargets[$tracerOverviewFocusField] ?? null;
    $openedFromOverview = request()->query('from') === 'overview' && $tracerOverviewFocusTarget !== null;
    $missingTextAnswer = fn ($value) => blank($value);
    $missingBooleanAnswer = fn ($value) => $value === null || $value === '';
    $tracerMissingAnalyticsFields = [];
    $tracerAnalyticsFieldTargets = [
        'Employment Type' => 'employment_type',
        'Current Position' => 'current_position',
        'Current Company / Business' => 'current_company',
        'Monthly Income Range' => 'monthly_income',
        'Industry' => 'industry',
        'Job Satisfaction' => 'job_satisfaction',
        'Skills Used in Work' => 'skills_used',
        'College curriculum relevance' => 'curriculum_relevant_to_first_job',
        'Useful competencies in first job' => 'first_job_useful_competencies',
        'Reason if curriculum was not relevant' => 'curriculum_not_relevant_reason',
        'Employed after graduation' => 'employed_after_graduation',
        'Time to first job' => 'time_to_first_job',
        'First job related to course' => 'first_job_related_to_course',
        'How you found your first job' => 'first_job_finding_method',
        'First job monthly earnings' => 'first_job_monthly_earnings',
        'First job duration' => 'first_job_duration',
        'Presently employed' => 'presently_employed',
        'Present employment status' => 'present_employment_status',
        'First job after college' => 'is_first_job_after_college',
        'Job level position' => 'job_level',
        'Reasons for accepting present job' => 'present_job_acceptance_reasons',
        'Present job monthly earnings' => 'present_job_monthly_earnings',
        'Employee awards answer' => 'has_employee_awards',
        'Employee awards details' => 'employee_awards',
        'Faculty enthusiasm rating' => 'teaching_faculty_enthusiastic',
        'Faculty concept explanation rating' => 'teaching_concepts_clear',
        'Faculty knowledge rating' => 'teaching_faculty_knowledgeable',
        'Teaching commitment rating' => 'teaching_commitment',
        'Degree program quality rating' => 'degree_program_quality',
        'Curriculum suggestions answer' => 'has_curriculum_suggestions',
        'Curriculum suggestion' => 'curriculum_suggestions',
        'Employability suggestions answer' => 'has_employability_suggestions',
        'Employability suggestion' => 'employability_suggestions',
        'Facilities satisfaction' => 'facilities_satisfaction',
        'Facility suggestions answer' => 'has_facility_suggestions',
        'Facility suggestion' => 'facility_suggestions',
        'Institution recommendation rating' => 'overall_recommend_institution',
        'Objectives met rating' => 'overall_objectives_met',
        'University experience rating' => 'overall_university_experience',
        'Challenges Faced' => 'challenges_faced',
        'Recommendations' => 'recommendations',
    ];
    $addMissingAnalyticsField = function (bool $missing, string $label) use (&$tracerMissingAnalyticsFields, $tracerAnalyticsFieldTargets) {
        if ($missing) {
            $tracerMissingAnalyticsFields[] = [
                'label' => $label,
                'target' => $tracerAnalyticsFieldTargets[$label] ?? null,
            ];
        }
    };
    $employmentTypeRequiresWorkDetails = filled($employmentTypeValue)
        && mb_strtolower((string) $employmentTypeValue) !== 'unemployed';
    $curriculumRelevantYes = $booleanAnswerIsYes($curriculumRelevantValue);
    $curriculumRelevantNo = $booleanAnswerIsNo($curriculumRelevantValue);
    $employedAfterGraduationYes = $booleanAnswerIsYes($employedAfterGraduationValue);
    $presentlyEmployedYes = $presentlyEmployedValue === 'Yes';
    $hasEmployeeAwardsYes = $booleanAnswerIsYes($hasEmployeeAwardsValue);
    $hasCurriculumSuggestionsYes = $booleanAnswerIsYes($hasCurriculumSuggestionsValue);
    $hasEmployabilitySuggestionsYes = $booleanAnswerIsYes($hasEmployabilitySuggestionsValue);
    $hasFacilitySuggestionsYes = $booleanAnswerIsYes($hasFacilitySuggestionsValue);
    $addMissingAnalyticsField($missingTextAnswer($employmentTypeValue), 'Employment Type');
    $addMissingAnalyticsField($employmentTypeRequiresWorkDetails && $missingTextAnswer($currentPositionValue), 'Current Position');
    $addMissingAnalyticsField($employmentTypeRequiresWorkDetails && $missingTextAnswer($currentCompanyValue), 'Current Company / Business');
    $addMissingAnalyticsField($employmentTypeRequiresWorkDetails && $missingTextAnswer($monthlyIncomeValue), 'Monthly Income Range');
    $addMissingAnalyticsField($employmentTypeRequiresWorkDetails && $missingTextAnswer($industryValue), 'Industry');
    $addMissingAnalyticsField($missingTextAnswer($jobSatisfactionValue), 'Job Satisfaction');
    $addMissingAnalyticsField($missingTextAnswer($skillsUsedValue), 'Skills Used in Work');
    $addMissingAnalyticsField($missingBooleanAnswer($curriculumRelevantValue), 'College curriculum relevance');
    $addMissingAnalyticsField($curriculumRelevantYes && empty(array_filter((array) $selectedFirstJobCompetencies)), 'Useful competencies in first job');
    $addMissingAnalyticsField($curriculumRelevantNo && $missingTextAnswer(old('curriculum_not_relevant_reason', $formTracer?->curriculum_not_relevant_reason)), 'Reason if curriculum was not relevant');
    $addMissingAnalyticsField($missingBooleanAnswer($employedAfterGraduationValue), 'Employed after graduation');
    $addMissingAnalyticsField($employedAfterGraduationYes && $missingTextAnswer(old('time_to_first_job', $formTracer?->time_to_first_job)), 'Time to first job');
    $addMissingAnalyticsField($employedAfterGraduationYes && $missingTextAnswer(old('first_job_related_to_course', $formTracer?->first_job_related_to_course)), 'First job related to course');
    $addMissingAnalyticsField($employedAfterGraduationYes && $missingTextAnswer(old('first_job_finding_method', $formTracer?->first_job_finding_method)), 'How you found your first job');
    $addMissingAnalyticsField($employedAfterGraduationYes && $missingTextAnswer($firstJobMonthlyEarningsValue), 'First job monthly earnings');
    $addMissingAnalyticsField($employedAfterGraduationYes && $missingTextAnswer(old('first_job_duration', $formTracer?->first_job_duration)), 'First job duration');
    $addMissingAnalyticsField($missingTextAnswer($presentlyEmployedValue), 'Presently employed');
    $addMissingAnalyticsField($presentlyEmployedYes && $missingTextAnswer(old('present_employment_status', $formTracer?->present_employment_status)), 'Present employment status');
    $addMissingAnalyticsField($presentlyEmployedYes && $missingBooleanAnswer($isFirstJobAfterCollegeValue), 'First job after college');
    $addMissingAnalyticsField($presentlyEmployedYes && $missingTextAnswer(old('job_level', $formTracer?->job_level)), 'Job level position');
    $addMissingAnalyticsField($presentlyEmployedYes && empty(array_filter((array) $selectedPresentJobReasons)), 'Reasons for accepting present job');
    $addMissingAnalyticsField($presentlyEmployedYes && $missingTextAnswer($presentJobMonthlyEarningsValue), 'Present job monthly earnings');
    $addMissingAnalyticsField($presentlyEmployedYes && $missingBooleanAnswer($hasEmployeeAwardsValue), 'Employee awards answer');
    $addMissingAnalyticsField($hasEmployeeAwardsYes && $missingTextAnswer(old('employee_awards', $formTracer?->employee_awards)), 'Employee awards details');
    $addMissingAnalyticsField($missingTextAnswer(old('teaching_faculty_enthusiastic', $formTracer?->teaching_faculty_enthusiastic)), 'Faculty enthusiasm rating');
    $addMissingAnalyticsField($missingTextAnswer(old('teaching_concepts_clear', $formTracer?->teaching_concepts_clear)), 'Faculty concept explanation rating');
    $addMissingAnalyticsField($missingTextAnswer(old('teaching_faculty_knowledgeable', $formTracer?->teaching_faculty_knowledgeable)), 'Faculty knowledge rating');
    $addMissingAnalyticsField($missingTextAnswer(old('teaching_commitment', $formTracer?->teaching_commitment)), 'Teaching commitment rating');
    $addMissingAnalyticsField($missingTextAnswer(old('degree_program_quality', $formTracer?->degree_program_quality)), 'Degree program quality rating');
    $addMissingAnalyticsField($missingBooleanAnswer($hasCurriculumSuggestionsValue), 'Curriculum suggestions answer');
    $addMissingAnalyticsField($hasCurriculumSuggestionsYes && $missingTextAnswer(old('curriculum_suggestions', $formTracer?->curriculum_suggestions)), 'Curriculum suggestion');
    $addMissingAnalyticsField($missingBooleanAnswer($hasEmployabilitySuggestionsValue), 'Employability suggestions answer');
    $addMissingAnalyticsField($hasEmployabilitySuggestionsYes && $missingTextAnswer(old('employability_suggestions', $formTracer?->employability_suggestions)), 'Employability suggestion');
    $addMissingAnalyticsField($missingTextAnswer(old('facilities_satisfaction', $formTracer?->facilities_satisfaction)), 'Facilities satisfaction');
    $addMissingAnalyticsField($missingBooleanAnswer($hasFacilitySuggestionsValue), 'Facility suggestions answer');
    $addMissingAnalyticsField($hasFacilitySuggestionsYes && $missingTextAnswer(old('facility_suggestions', $formTracer?->facility_suggestions)), 'Facility suggestion');
    $addMissingAnalyticsField($missingTextAnswer(old('overall_recommend_institution', $formTracer?->overall_recommend_institution)), 'Institution recommendation rating');
    $addMissingAnalyticsField($missingTextAnswer(old('overall_objectives_met', $formTracer?->overall_objectives_met)), 'Objectives met rating');
    $addMissingAnalyticsField($missingTextAnswer(old('overall_university_experience', $formTracer?->overall_university_experience)), 'University experience rating');
    $addMissingAnalyticsField($missingTextAnswer($challengesFacedValue), 'Challenges Faced');
    $addMissingAnalyticsField($missingTextAnswer($recommendationsValue), 'Recommendations');
    $tracerMissingAnalyticsTargets = array_values(array_unique(array_filter(array_column($tracerMissingAnalyticsFields, 'target'))));
    $hasMissingAnalyticsFields = !empty($tracerMissingAnalyticsTargets);
@endphp
<div class="space-y-6" data-tracer-page-root>
    <div class="grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,1.35fr)_minmax(320px,0.95fr)]">
        <div
            id="tracer-form"
            class="surface-card tracer-form-shell scroll-mt-24 p-6 {{ $editingTracer ? 'tracer-form-shell--editing' : '' }} {{ $openedFromOverview ? 'border border-emerald-200 bg-emerald-50/30 ring-1 ring-emerald-100' : '' }}"
            data-tracer-form
            data-editing="{{ $editingTracer ? 'true' : 'false' }}"
            data-skip-autoscroll="{{ $suppressEditAutoscroll ? 'true' : 'false' }}"
            data-guided-active="{{ $openedFromOverview ? 'true' : 'false' }}"
        >
            <div class="mb-5 flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-xl font-semibold text-slate-900">Tracer Update Form</h2>
                    <p class="mt-1 text-sm text-slate-600">
                        @if($editingTracer)
                            Use the selected record as a starting point, then save it as a new history version dated today.
                        @else
                            Complete the fields below to save a tracer snapshot for your current employment situation. The tracer date is recorded automatically when you save.
                        @endif
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    @if($editingTracer)
                        <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.14em] text-amber-700">Based on {{ $editingTracer->tracer_date?->format('M j, Y') }}</span>
                        <a href="{{ route('tracer.index') }}#tracer-form" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition-all duration-300 hover:-translate-y-0.5 hover:border-emerald-300 hover:text-emerald-700">
                            <i class='bx bx-x text-base'></i>
                            Cancel
                        </a>
                    @endif
                    <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.14em] text-emerald-700">Required for analytics</span>
                </div>
            </div>

            @if($openedFromOverview)
                <div class="mb-5 rounded-2xl border border-emerald-200 bg-emerald-50/80 px-4 py-4 text-sm text-emerald-900" role="status">
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-emerald-800">You are now in Tracer</p>
                    <p class="mt-2">You left the Overview checklist. {{ $tracerOverviewFocusTarget['message'] }}</p>
                    <a href="{{ route('dashboard') }}" class="mt-3 inline-flex items-center gap-2 text-sm font-semibold text-emerald-800 hover:text-emerald-900">
                        <i class='bx bx-arrow-back text-base'></i>
                        Return to Overview
                    </a>
                </div>
            @endif

            @if($errors->any())
                <div class="mb-5 rounded-2xl border border-rose-200 bg-rose-50/90 px-4 py-4 text-sm text-rose-900" role="alert">
                    <p class="font-semibold">Please complete the required tracer questions before saving.</p>
                    <p class="mt-1">{{ $errors->first() }}</p>
                </div>
            @endif

            <div
                class="{{ $hasMissingAnalyticsFields ? '' : 'hidden ' }}mb-5 rounded-2xl border border-amber-200 bg-amber-50/95 px-4 py-4 text-sm text-amber-950 shadow-sm ring-1 ring-amber-100/70"
                role="alert"
                data-tracer-analytics-reminder
                aria-hidden="{{ $hasMissingAnalyticsFields ? 'false' : 'true' }}"
                @if(!$hasMissingAnalyticsFields) hidden @endif
            >
                <div class="flex items-start gap-3">
                    <span class="mt-0.5 inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-700">
                        <i class='bx bx-error-circle text-xl'></i>
                    </span>
                    <div class="min-w-0">
                        <p class="font-semibold">Complete the required alumni analytics questions.</p>
                        <p class="mt-1 leading-6">
                            Some registration questions are still blank on your account. If your account was added by an admin, answer them here before saving your tracer record.
                        </p>
                        <div class="mt-3 flex flex-wrap gap-2" data-tracer-analytics-chip-list>
                            @foreach($tracerAnalyticsFieldTargets as $analyticsFieldLabel => $analyticsFieldTarget)
                                @php
                                    $isMissingAnalyticsField = in_array($analyticsFieldTarget, $tracerMissingAnalyticsTargets, true);
                                @endphp
                                <button
                                    type="button"
                                    class="{{ $isMissingAnalyticsField ? '' : 'hidden ' }}inline-flex items-center rounded-full border border-amber-200 bg-white/80 px-3 py-1 text-xs font-semibold text-amber-900 transition-all duration-200 hover:-translate-y-0.5 hover:border-amber-300 hover:bg-white hover:shadow-sm focus:outline-none focus:ring-2 focus:ring-amber-300"
                                    data-tracer-jump-target="{{ $analyticsFieldTarget }}"
                                    data-tracer-analytics-chip
                                    data-tracer-analytics-target="{{ $analyticsFieldTarget }}"
                                    data-tracer-analytics-missing="{{ $isMissingAnalyticsField ? 'true' : 'false' }}"
                                    aria-label="Go to {{ $analyticsFieldLabel }} field"
                                    @if(!$isMissingAnalyticsField) hidden @endif
                                >
                                    {{ $analyticsFieldLabel }}
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <form
                action="{{ route('tracer.store') }}"
                method="POST"
                enctype="multipart/form-data"
                class="grid grid-cols-1 gap-4 md:grid-cols-2"
                data-tracer-entry-form
                data-tracer-draft-url="{{ route('tracer.draft.show') }}"
                data-tracer-draft-save-url="{{ route('tracer.draft.save') }}"
                data-tracer-draft-enabled="{{ $editingTracer ? 'false' : 'true' }}"
                data-tracer-dashboard-url="{{ route('dashboard') }}"
            >
                @csrf
                @if($editingTracer)
                    <input type="hidden" name="record_id" value="{{ $editingTracer->id }}">
                @endif
                @unless($editingTracer)
                    <div class="md:col-span-2 rounded-2xl border border-emerald-100 bg-emerald-50/70 px-4 py-4" data-tracer-education-summary>
                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-emerald-800">Your CMU education</p>
                        <p class="mt-2 text-sm font-medium text-slate-800">
                            {{ auth()->user()?->department ?: 'College not added' }}
                            <span class="px-1 text-slate-400">·</span>
                            {{ auth()->user()?->degree ?: 'Degree not added' }}
                            <span class="px-1 text-slate-400">·</span>
                            Class of {{ auth()->user()?->year_graduated ?: 'year not added' }}
                        </p>
                        <p class="mt-1 text-xs text-slate-600">These details come from your profile. The questions below focus on your first job and career after graduation.</p>
                    </div>
                @endunless
                <div data-tracer-field-anchor="employment_type">
                    <label class="mb-1 block text-sm font-medium text-slate-700">Employment Type</label>
                    <span class="tracer-select-wrap">
                        <select id="tracer_employment_type" name="employment_type" class="tracer-select w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-100" required>
                            <option value="">Select status</option>
                            @foreach($employmentTypes as $employmentType)
                                <option value="{{ $employmentType }}" {{ old('employment_type', $formTracer?->employment_type) === $employmentType ? 'selected' : '' }}>
                                    {{ $employmentType }}
                                </option>
                            @endforeach
                        </select>
                        <span class="tracer-select-wrap__icon" aria-hidden="true">
                            <i class='bx bx-chevron-down text-lg'></i>
                        </span>
                    </span>
                </div>

                <div data-tracer-employed-field data-tracer-field-anchor="current_position">
                    <label class="mb-1 block text-sm font-medium text-slate-700">Current Position</label>
                    <input name="current_position" type="text" value="{{ $currentPositionValue }}" placeholder="e.g. Systems Analyst" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                    @if(blank($currentPositionValue))
                        <p class="tracer-helper-note"><i class='bx bx-info-circle' aria-hidden="true"></i><span>{{ $employmentTypeValue === 'Unemployed' ? $employmentConditionalNote : $registrationMissingNote }}</span></p>
                    @endif
                </div>
                <div data-tracer-employed-field data-tracer-field-anchor="current_company">
                    <label class="mb-1 block text-sm font-medium text-slate-700">Current Company / Business</label>
                    <input name="current_company" type="text" value="{{ $currentCompanyValue }}" placeholder="Company or business name" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                    @if(blank($currentCompanyValue))
                        <p class="tracer-helper-note"><i class='bx bx-info-circle' aria-hidden="true"></i><span>{{ $employmentTypeValue === 'Unemployed' ? $employmentConditionalNote : $registrationMissingNote }}</span></p>
                    @endif
                </div>

                <div data-tracer-employed-field data-tracer-field-anchor="monthly_income">
                    <label class="mb-1 block text-sm font-medium text-slate-700">Monthly Income Range</label>
                    <span class="tracer-select-wrap">
                        <select name="monthly_income" class="tracer-select w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                            <option value="">Select monthly income range</option>
                            @foreach($presentJobEarningsOptions as $incomeRangeOption)
                                <option value="{{ $incomeRangeOption }}" {{ $monthlyIncomeValue === $incomeRangeOption ? 'selected' : '' }}>
                                    {{ $incomeRangeOption }}
                                </option>
                            @endforeach
                        </select>
                        <span class="tracer-select-wrap__icon" aria-hidden="true">
                            <i class='bx bx-chevron-down text-lg'></i>
                        </span>
                    </span>
                    @if(blank($monthlyIncomeValue))
                        <p class="tracer-helper-note"><i class='bx bx-info-circle' aria-hidden="true"></i><span>{{ $employmentTypeValue === 'Unemployed' ? $employmentConditionalNote : $registrationMissingNote }}</span></p>
                    @endif
                </div>
                <div data-tracer-employed-field data-tracer-field-anchor="industry">
                    <label class="mb-1 block text-sm font-medium text-slate-700">Industry</label>
                    <span class="tracer-select-wrap">
                        <select name="industry" class="tracer-select w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                            <option value="">Select industry</option>
                            @foreach($industryOptions as $industryOption)
                                <option value="{{ $industryOption }}" {{ $industryValue === $industryOption ? 'selected' : '' }}>
                                    {{ $industryOption }}
                                </option>
                            @endforeach
                        </select>
                        <span class="tracer-select-wrap__icon" aria-hidden="true">
                            <i class='bx bx-chevron-down text-lg'></i>
                        </span>
                    </span>
                    @if(blank($industryValue))
                        <p class="tracer-helper-note"><i class='bx bx-info-circle' aria-hidden="true"></i><span>{{ $employmentTypeValue === 'Unemployed' ? $employmentConditionalNote : $registrationMissingNote }}</span></p>
                    @endif
                </div>

                <div data-tracer-field-anchor="job_satisfaction">
                    <label class="mb-1 block text-sm font-medium text-slate-700">Job Satisfaction</label>
                    <span class="tracer-select-wrap">
                        <select name="job_satisfaction" class="tracer-select w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                            <option value="">Select rating</option>
                            @foreach($jobSatisfactionOptions as $score => $label)
                                <option value="{{ $score }}" {{ (string) $jobSatisfactionValue === (string) $score ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                        <span class="tracer-select-wrap__icon" aria-hidden="true">
                            <i class='bx bx-chevron-down text-lg'></i>
                        </span>
                    </span>
                    @if(blank($jobSatisfactionValue))
                        <p class="tracer-helper-note"><i class='bx bx-info-circle' aria-hidden="true"></i><span>{{ $registrationMissingNote }}</span></p>
                    @endif
                </div>
                <div class="flex items-center">
                    <div class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                        <input type="hidden" name="further_education" value="0">
                        <label class="flex items-center gap-3 text-sm text-slate-700">
                            <input id="tracer_further_education" type="checkbox" name="further_education" value="1" class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500" {{ old('further_education', $formTracer?->further_education) ? 'checked' : '' }}>
                            I am currently pursuing further education or training.
                        </label>
                    </div>
                </div>

                <div id="tracer_education_level_group" class="md:col-span-2">
                    <label class="mb-1 block text-sm font-medium text-slate-700">Education Level</label>
                    <span class="tracer-select-wrap">
                        <select id="tracer_education_level" name="education_level" class="tracer-select w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                            <option value="">Select education level</option>
                            @foreach($educationLevels as $educationLevel)
                                <option value="{{ $educationLevel }}" {{ $educationLevelValue === $educationLevel ? 'selected' : '' }}>
                                    {{ $educationLevel }}
                                </option>
                            @endforeach
                        </select>
                        <span class="tracer-select-wrap__icon" aria-hidden="true">
                            <i class='bx bx-chevron-down text-lg'></i>
                        </span>
                    </span>
                    @if(blank($educationLevelValue))
                        <p class="tracer-helper-note"><i class='bx bx-info-circle' aria-hidden="true"></i><span>{{ $furtherEducationEnabled ? $registrationMissingNote : $educationConditionalNote }}</span></p>
                    @endif
                </div>

                <div class="md:col-span-2" data-tracer-field-anchor="skills_used">
                    <label class="mb-1 block text-sm font-medium text-slate-700">Skills Used in Work</label>
                    <textarea name="skills_used" rows="3" placeholder="e.g. SQL, reporting, classroom management, project planning" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-100">{{ $skillsUsedValue }}</textarea>
                    @if(blank($skillsUsedValue))
                        <p class="tracer-helper-note"><i class='bx bx-info-circle' aria-hidden="true"></i><span>{{ $registrationMissingNote }}</span></p>
                    @endif
                </div>
                <div class="md:col-span-2" data-tracer-field-anchor="challenges_faced">
                    <label class="mb-1 block text-sm font-medium text-slate-700">Challenges Faced</label>
                    <textarea name="challenges_faced" rows="3" placeholder="Share job-search or workplace challenges you have faced." class="w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-100">{{ $challengesFacedValue }}</textarea>
                    @if(blank($challengesFacedValue))
                        <p class="tracer-helper-note"><i class='bx bx-info-circle' aria-hidden="true"></i><span>{{ $registrationMissingNote }}</span></p>
                    @endif
                </div>
                <div class="md:col-span-2" data-tracer-field-anchor="recommendations">
                    <label class="mb-1 block text-sm font-medium text-slate-700">Recommendations</label>
                    <textarea name="recommendations" rows="3" placeholder="Suggestions that may help the university improve alumni support." class="w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-100">{{ $recommendationsValue }}</textarea>
                    @if(blank($recommendationsValue))
                        <p class="tracer-helper-note"><i class='bx bx-info-circle' aria-hidden="true"></i><span>{{ $registrationMissingNote }}</span></p>
                    @endif
                </div>

                <div class="md:col-span-2 rounded-3xl border border-slate-200 bg-slate-50/70 p-5">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h3 class="text-base font-semibold text-slate-900">Employment Data</h3>
                            <p class="mt-1 text-sm text-slate-600">Save your first-job and curriculum answers here.</p>
                        </div>
                    </div>

                    <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div class="md:col-span-2" data-tracer-field-anchor="curriculum_relevant_to_first_job">
                            <p class="mb-2 text-sm font-medium text-slate-700">Was your college curriculum relevant to your first job?</p>
                            <div class="flex flex-wrap gap-5">
                                <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                                    <input type="radio" name="curriculum_relevant_to_first_job" value="1" {{ $booleanAnswerIsYes($curriculumRelevantValue) ? 'checked' : '' }}>
                                    <span>Yes</span>
                                </label>
                                <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                                    <input type="radio" name="curriculum_relevant_to_first_job" value="0" {{ $booleanAnswerIsNo($curriculumRelevantValue) ? 'checked' : '' }}>
                                    <span>No</span>
                                </label>
                            </div>
                        </div>

                        <div id="tracer_curriculum_yes_group" class="md:col-span-2" data-tracer-field-anchor="first_job_useful_competencies">
                            <p class="mb-2 text-sm font-medium text-slate-700">Useful competencies in your first job</p>
                            <p class="mb-3 text-xs text-slate-500">You may choose more than one answer.</p>
                            <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                                @foreach($curriculumCompetencies as $competency)
                                    <label class="flex items-start gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700">
                                        <input
                                            type="checkbox"
                                            name="first_job_useful_competencies[]"
                                            value="{{ $competency }}"
                                            class="mt-0.5 h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500"
                                            {{ in_array($competency, $selectedFirstJobCompetencies, true) ? 'checked' : '' }}
                                        >
                                        <span>{{ $competency }}</span>
                                    </label>
                                @endforeach
                            </div>
                            <div id="tracer_first_job_competencies_other_group" class="mt-3">
                                <label class="mb-1 block text-sm font-medium text-slate-700">Other competency</label>
                                <input
                                    type="text"
                                    name="first_job_useful_competencies_other"
                                    value="{{ old('first_job_useful_competencies_other', $formTracer?->first_job_useful_competencies_other) }}"
                                    placeholder="Enter other competency"
                                    class="w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-100"
                                >
                            </div>
                        </div>

                        <div id="tracer_curriculum_no_group" class="md:col-span-2" data-tracer-field-anchor="curriculum_not_relevant_reason">
                            <label class="mb-1 block text-sm font-medium text-slate-700">Reason if not relevant</label>
                            <span class="tracer-select-wrap">
                                <select id="tracer_curriculum_not_relevant_reason" name="curriculum_not_relevant_reason" class="tracer-select w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                                    <option value="">Select reason</option>
                                    @foreach($curriculumReasonOptions as $reasonOption)
                                        <option value="{{ $reasonOption }}" {{ old('curriculum_not_relevant_reason', $formTracer?->curriculum_not_relevant_reason) === $reasonOption ? 'selected' : '' }}>
                                            {{ $reasonOption }}
                                        </option>
                                    @endforeach
                                </select>
                                <span class="tracer-select-wrap__icon" aria-hidden="true">
                                    <i class='bx bx-chevron-down text-lg'></i>
                                </span>
                            </span>
                            <div id="tracer_curriculum_reason_other_group" class="mt-3">
                                <label class="mb-1 block text-sm font-medium text-slate-700">Other reason</label>
                                <input
                                    type="text"
                                    name="curriculum_not_relevant_reason_other"
                                    value="{{ old('curriculum_not_relevant_reason_other', $formTracer?->curriculum_not_relevant_reason_other) }}"
                                    placeholder="Enter other reason"
                                    class="w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-100"
                                >
                            </div>
                        </div>

                        <div class="md:col-span-2" data-tracer-field-anchor="employed_after_graduation">
                            <p class="mb-2 text-sm font-medium text-slate-700">Were you employed after you graduated?</p>
                            <div class="flex flex-wrap gap-5">
                                <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                                    <input type="radio" name="employed_after_graduation" value="1" {{ $booleanAnswerIsYes($employedAfterGraduationValue) ? 'checked' : '' }}>
                                    <span>Yes</span>
                                </label>
                                <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                                    <input type="radio" name="employed_after_graduation" value="0" {{ $booleanAnswerIsNo($employedAfterGraduationValue) ? 'checked' : '' }}>
                                    <span>No</span>
                                </label>
                            </div>
                        </div>

                        <div id="tracer_first_job_details_group" class="md:col-span-2 grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div data-tracer-field-anchor="time_to_first_job">
                                <label class="mb-1 block text-sm font-medium text-slate-700">Time to first job</label>
                                <span class="tracer-select-wrap">
                                    <select id="tracer_time_to_first_job" name="time_to_first_job" class="tracer-select w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                                        <option value="">Select time</option>
                                        @foreach($firstJobTimeOptions as $option)
                                            <option value="{{ $option }}" {{ old('time_to_first_job', $formTracer?->time_to_first_job) === $option ? 'selected' : '' }}>{{ $option }}</option>
                                        @endforeach
                                    </select>
                                    <span class="tracer-select-wrap__icon" aria-hidden="true">
                                        <i class='bx bx-chevron-down text-lg'></i>
                                    </span>
                                </span>
                            </div>
                            <div id="tracer_time_to_first_job_other_group">
                                <label class="mb-1 block text-sm font-medium text-slate-700">Other time</label>
                                <input type="text" name="time_to_first_job_other" value="{{ old('time_to_first_job_other', $formTracer?->time_to_first_job_other) }}" placeholder="Enter other time" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                            </div>
                            <div data-tracer-field-anchor="first_job_related_to_course">
                                <label class="mb-1 block text-sm font-medium text-slate-700">Was your first job related to your course?</label>
                                <span class="tracer-select-wrap">
                                    <select name="first_job_related_to_course" class="tracer-select w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                                        <option value="">Select answer</option>
                                        @foreach($firstJobRelatedOptions as $option)
                                            <option value="{{ $option }}" {{ old('first_job_related_to_course', $formTracer?->first_job_related_to_course) === $option ? 'selected' : '' }}>{{ $option }}</option>
                                        @endforeach
                                    </select>
                                    <span class="tracer-select-wrap__icon" aria-hidden="true">
                                        <i class='bx bx-chevron-down text-lg'></i>
                                    </span>
                                </span>
                            </div>
                            <div data-tracer-field-anchor="first_job_finding_method">
                                <label class="mb-1 block text-sm font-medium text-slate-700">How did you find your first job?</label>
                                <span class="tracer-select-wrap">
                                    <select id="tracer_first_job_finding_method" name="first_job_finding_method" class="tracer-select w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                                        <option value="">Select method</option>
                                        @foreach($firstJobFindingOptions as $option)
                                            <option value="{{ $option }}" {{ old('first_job_finding_method', $formTracer?->first_job_finding_method) === $option ? 'selected' : '' }}>{{ $option }}</option>
                                        @endforeach
                                    </select>
                                    <span class="tracer-select-wrap__icon" aria-hidden="true">
                                        <i class='bx bx-chevron-down text-lg'></i>
                                    </span>
                                </span>
                            </div>
                            <div id="tracer_first_job_finding_other_group">
                                <label class="mb-1 block text-sm font-medium text-slate-700">Other method</label>
                                <input type="text" name="first_job_finding_method_other" value="{{ old('first_job_finding_method_other', $formTracer?->first_job_finding_method_other) }}" placeholder="Enter other method" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                            </div>
                            <div data-tracer-field-anchor="first_job_monthly_earnings">
                                <label class="mb-1 block text-sm font-medium text-slate-700">First job monthly earnings</label>
                                <span class="tracer-select-wrap">
                                    <select name="first_job_monthly_earnings" class="tracer-select w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                                        <option value="">Select earnings</option>
                                        @foreach($firstJobEarningsOptions as $option)
                                            <option value="{{ $option }}" {{ $firstJobMonthlyEarningsValue === $option ? 'selected' : '' }}>{{ $option }}</option>
                                        @endforeach
                                    </select>
                                    <span class="tracer-select-wrap__icon" aria-hidden="true">
                                        <i class='bx bx-chevron-down text-lg'></i>
                                    </span>
                                </span>
                            </div>
                            <div data-tracer-field-anchor="first_job_duration">
                                <label class="mb-1 block text-sm font-medium text-slate-700">How long did you stay in your first job?</label>
                                <span class="tracer-select-wrap">
                                    <select id="tracer_first_job_duration" name="first_job_duration" class="tracer-select w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                                        <option value="">Select duration</option>
                                        @foreach($firstJobDurationOptions as $option)
                                            <option value="{{ $option }}" {{ old('first_job_duration', $formTracer?->first_job_duration) === $option ? 'selected' : '' }}>{{ $option }}</option>
                                        @endforeach
                                    </select>
                                    <span class="tracer-select-wrap__icon" aria-hidden="true">
                                        <i class='bx bx-chevron-down text-lg'></i>
                                    </span>
                                </span>
                            </div>
                            <div id="tracer_first_job_duration_other_group">
                                <label class="mb-1 block text-sm font-medium text-slate-700">Other duration</label>
                                <input type="text" name="first_job_duration_other" value="{{ old('first_job_duration_other', $formTracer?->first_job_duration_other) }}" placeholder="Enter other duration" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="md:col-span-2 rounded-3xl border border-slate-200 bg-slate-50/70 p-5">
                    <div>
                        <h3 class="text-base font-semibold text-slate-900">Employment Status Details</h3>
                        <p class="mt-1 text-sm text-slate-600">Add your current work arrangement and proof on file.</p>
                    </div>

                    <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div class="md:col-span-2" data-tracer-field-anchor="presently_employed">
                            <label class="mb-1 block text-sm font-medium text-slate-700">Are you presently employed?</label>
                            <span class="tracer-select-wrap">
                                <select id="tracer_presently_employed" name="presently_employed" class="tracer-select w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                                    <option value="">Select answer</option>
                                    @foreach(['Yes', 'No', 'Never Employed'] as $option)
                                        <option value="{{ $option }}" {{ $presentlyEmployedValue === $option ? 'selected' : '' }}>{{ $option }}</option>
                                    @endforeach
                                </select>
                                <span class="tracer-select-wrap__icon" aria-hidden="true">
                                    <i class='bx bx-chevron-down text-lg'></i>
                                </span>
                            </span>
                        </div>

                        <div id="tracer_present_employment_details_group" class="md:col-span-2 grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div data-tracer-field-anchor="present_employment_status">
                                <label class="mb-1 block text-sm font-medium text-slate-700">Present employment status</label>
                                <span class="tracer-select-wrap">
                                    <select name="present_employment_status" class="tracer-select w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                                        <option value="">Select status</option>
                                        @foreach($presentEmploymentStatuses as $option)
                                            <option value="{{ $option }}" {{ old('present_employment_status', $formTracer?->present_employment_status) === $option ? 'selected' : '' }}>{{ $option }}</option>
                                        @endforeach
                                    </select>
                                    <span class="tracer-select-wrap__icon" aria-hidden="true">
                                        <i class='bx bx-chevron-down text-lg'></i>
                                    </span>
                                </span>
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-slate-700">Employer or direct supervisor</label>
                                <input type="text" name="direct_supervisor_name" value="{{ old('direct_supervisor_name', $formTracer?->direct_supervisor_name) }}" placeholder="Enter name" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-slate-700">Employer email address</label>
                                <input type="email" name="employer_email" value="{{ old('employer_email', $formTracer?->employer_email) }}" placeholder="Enter email" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                            </div>
                            <div data-tracer-field-anchor="present_job_monthly_earnings">
                                <label class="mb-1 block text-sm font-medium text-slate-700">Present job monthly earnings</label>
                                <span class="tracer-select-wrap">
                                    <select name="present_job_monthly_earnings" class="tracer-select w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                                        <option value="">Select earnings</option>
                                        @foreach($presentJobEarningsOptions as $option)
                                            <option value="{{ $option }}" {{ $presentJobMonthlyEarningsValue === $option ? 'selected' : '' }}>{{ $option }}</option>
                                        @endforeach
                                    </select>
                                    <span class="tracer-select-wrap__icon" aria-hidden="true">
                                        <i class='bx bx-chevron-down text-lg'></i>
                                    </span>
                                </span>
                            </div>
                            <div class="md:col-span-2" data-tracer-field-anchor="is_first_job_after_college">
                                <label class="mb-1 block text-sm font-medium text-slate-700">Is this your first job after college?</label>
                                <div class="flex flex-wrap gap-5">
                                    <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                                        <input type="radio" name="is_first_job_after_college" value="1" {{ $booleanAnswerIsYes($isFirstJobAfterCollegeValue) ? 'checked' : '' }}>
                                        <span>Yes</span>
                                    </label>
                                    <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                                        <input type="radio" name="is_first_job_after_college" value="0" {{ $booleanAnswerIsNo($isFirstJobAfterCollegeValue) ? 'checked' : '' }}>
                                        <span>No</span>
                                    </label>
                                </div>
                            </div>
                            <div data-tracer-field-anchor="job_level">
                                <label class="mb-1 block text-sm font-medium text-slate-700">Job level position</label>
                                <span class="tracer-select-wrap">
                                    <select id="tracer_job_level" name="job_level" class="tracer-select w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                                        <option value="">Select level</option>
                                        @foreach($jobLevelOptions as $option)
                                            <option value="{{ $option }}" {{ old('job_level', $formTracer?->job_level) === $option ? 'selected' : '' }}>{{ $option }}</option>
                                        @endforeach
                                    </select>
                                    <span class="tracer-select-wrap__icon" aria-hidden="true">
                                        <i class='bx bx-chevron-down text-lg'></i>
                                    </span>
                                </span>
                            </div>
                            <div id="tracer_job_level_other_group">
                                <label class="mb-1 block text-sm font-medium text-slate-700">Other job level</label>
                                <input type="text" name="job_level_other" value="{{ old('job_level_other', $formTracer?->job_level_other) }}" placeholder="Enter other job level" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                            </div>
                            <div class="md:col-span-2" data-tracer-field-anchor="present_job_acceptance_reasons">
                                <label class="mb-1 block text-sm font-medium text-slate-700">Reasons for accepting the present job</label>
                                <p class="mb-3 text-xs text-slate-500">You may choose more than one answer.</p>
                                <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                                    @foreach($presentJobReasonOptions as $reasonOption)
                                        <label class="flex items-start gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700">
                                            <input
                                                type="checkbox"
                                                name="present_job_acceptance_reasons[]"
                                                value="{{ $reasonOption }}"
                                                class="mt-0.5 h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500"
                                                {{ in_array($reasonOption, $selectedPresentJobReasons, true) ? 'checked' : '' }}
                                            >
                                            <span>{{ $reasonOption }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                            <div id="tracer_present_job_reason_other_group" class="md:col-span-2">
                                <label class="mb-1 block text-sm font-medium text-slate-700">Other reason</label>
                                <input type="text" name="present_job_acceptance_other" value="{{ old('present_job_acceptance_other', $formTracer?->present_job_acceptance_other) }}" placeholder="Enter other reason" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                            </div>
                            <div class="md:col-span-2" data-tracer-field-anchor="employment_proof">
                                <label class="mb-1 block text-sm font-medium text-slate-700">Employment proof PDF</label>
                                <input type="file" id="tracer_employment_proof" name="employment_proof" accept="application/pdf,.pdf" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                                <p class="mt-1 text-xs text-slate-500">Upload 1 PDF file only. Maximum 10 MB.</p>
                                @if($formTracer?->employment_proof_path)
                                    <div class="mt-2 rounded-xl border border-slate-200 bg-white px-3 py-3">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <p class="text-xs text-slate-500">
                                                Current file on record: {{ $formTracer->employment_proof_original_name ?: basename($formTracer->employment_proof_path) }}
                                            </p>
                                            <span class="rounded-full px-2.5 py-1 text-[11px] font-semibold uppercase tracking-[0.12em] {{ $formTracer->employmentProofIsVerified() ? 'bg-emerald-100 text-emerald-700' : ($formTracer->employmentProofIsRejected() ? 'bg-rose-100 text-rose-700' : 'bg-amber-100 text-amber-700') }}">
                                                {{ $formTracer->employmentProofStatusLabel() }}
                                            </span>
                                        </div>
                                    @if($formTracer->employment_proof_uploaded_at)
                                        <p class="mt-1 text-xs text-slate-400">Uploaded {{ $formTracer->employment_proof_uploaded_at->format('M d, Y g:i A') }}</p>
                                    @endif
                                    @if($formTracer->employmentProofIsRejected() && $formTracer->employment_proof_rejection_note)
                                        <p class="mt-2 text-xs text-rose-600">{{ $formTracer->employment_proof_rejection_note }}</p>
                                    @endif
                                    @if($formTracer->employmentProofIsVerified())
                                    <a
                                        href="{{ route('tracer.proof.download', $formTracer) }}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="mt-2 inline-flex items-center gap-2 text-xs font-semibold text-emerald-700 hover:text-emerald-800"
                                    >
                                        <i class='bx bxs-file-pdf text-sm'></i>
                                        View current proof
                                    </a>
                                    @else
                                        <p class="mt-2 text-xs text-slate-500">Verified proof will appear in your profile after admin review.</p>
                                    @endif
                                    </div>
                                @endif
                            </div>
                            <div class="md:col-span-2" data-tracer-field-anchor="has_employee_awards">
                                <p class="mb-2 text-sm font-medium text-slate-700">Any awards received as an employee?</p>
                                <div class="flex flex-wrap gap-5">
                                    <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                                        <input type="radio" name="has_employee_awards" value="1" {{ $booleanAnswerIsYes($hasEmployeeAwardsValue) ? 'checked' : '' }}>
                                        <span>Yes</span>
                                    </label>
                                    <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                                        <input type="radio" name="has_employee_awards" value="0" {{ $booleanAnswerIsNo($hasEmployeeAwardsValue) ? 'checked' : '' }}>
                                        <span>No</span>
                                    </label>
                                </div>
                            </div>
                            <div id="tracer_employee_awards_group" class="md:col-span-2" data-tracer-field-anchor="employee_awards">
                                <label class="mb-1 block text-sm font-medium text-slate-700">Awards received</label>
                                <textarea name="employee_awards" rows="3" placeholder="List your awards" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-100">{{ old('employee_awards', $formTracer?->employee_awards) }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="md:col-span-2 rounded-3xl border border-slate-200 bg-slate-50/70 p-5">
                    <div>
                        <h3 class="text-base font-semibold text-slate-900">Comments and Suggestions</h3>
                        <p class="mt-1 text-sm text-slate-600">Capture teaching, facilities, and overall satisfaction feedback.</p>
                    </div>

                    <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div data-tracer-field-anchor="teaching_faculty_enthusiastic">
                            <label class="mb-1 block text-sm font-medium text-slate-700">Faculty are enthusiastic about what they teach</label>
                            <span class="tracer-select-wrap">
                                <select name="teaching_faculty_enthusiastic" class="tracer-select w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                                    <option value="">Select rating</option>
                                    @foreach($agreementScaleOptions as $score => $label)
                                        <option value="{{ $score }}" {{ (string) old('teaching_faculty_enthusiastic', $formTracer?->teaching_faculty_enthusiastic) === (string) $score ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                                <span class="tracer-select-wrap__icon" aria-hidden="true">
                                    <i class='bx bx-chevron-down text-lg'></i>
                                </span>
                            </span>
                        </div>
                        <div data-tracer-field-anchor="teaching_concepts_clear">
                            <label class="mb-1 block text-sm font-medium text-slate-700">Faculty explain concepts well</label>
                            <span class="tracer-select-wrap">
                                <select name="teaching_concepts_clear" class="tracer-select w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                                    <option value="">Select rating</option>
                                    @foreach($agreementScaleOptions as $score => $label)
                                        <option value="{{ $score }}" {{ (string) old('teaching_concepts_clear', $formTracer?->teaching_concepts_clear) === (string) $score ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                                <span class="tracer-select-wrap__icon" aria-hidden="true">
                                    <i class='bx bx-chevron-down text-lg'></i>
                                </span>
                            </span>
                        </div>
                        <div data-tracer-field-anchor="teaching_faculty_knowledgeable">
                            <label class="mb-1 block text-sm font-medium text-slate-700">Faculty are knowledgeable in their field</label>
                            <span class="tracer-select-wrap">
                                <select name="teaching_faculty_knowledgeable" class="tracer-select w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                                    <option value="">Select rating</option>
                                    @foreach($agreementScaleOptions as $score => $label)
                                        <option value="{{ $score }}" {{ (string) old('teaching_faculty_knowledgeable', $formTracer?->teaching_faculty_knowledgeable) === (string) $score ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                                <span class="tracer-select-wrap__icon" aria-hidden="true">
                                    <i class='bx bx-chevron-down text-lg'></i>
                                </span>
                            </span>
                        </div>
                        <div data-tracer-field-anchor="teaching_commitment">
                            <label class="mb-1 block text-sm font-medium text-slate-700">Teaching commitment of the faculty</label>
                            <span class="tracer-select-wrap">
                                <select name="teaching_commitment" class="tracer-select w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                                    <option value="">Select rating</option>
                                    @foreach($agreementScaleOptions as $score => $label)
                                        <option value="{{ $score }}" {{ (string) old('teaching_commitment', $formTracer?->teaching_commitment) === (string) $score ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                                <span class="tracer-select-wrap__icon" aria-hidden="true">
                                    <i class='bx bx-chevron-down text-lg'></i>
                                </span>
                            </span>
                        </div>
                        <div class="md:col-span-2" data-tracer-field-anchor="degree_program_quality">
                            <label class="mb-1 block text-sm font-medium text-slate-700">Quality of your degree program</label>
                            <span class="tracer-select-wrap">
                                <select name="degree_program_quality" class="tracer-select w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                                    <option value="">Select rating</option>
                                    @foreach($agreementScaleOptions as $score => $label)
                                        <option value="{{ $score }}" {{ (string) old('degree_program_quality', $formTracer?->degree_program_quality) === (string) $score ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                                <span class="tracer-select-wrap__icon" aria-hidden="true">
                                    <i class='bx bx-chevron-down text-lg'></i>
                                </span>
                            </span>
                        </div>

                        <div class="md:col-span-2" data-tracer-field-anchor="has_curriculum_suggestions">
                            <p class="mb-2 text-sm font-medium text-slate-700">Any curriculum suggestion?</p>
                            <div class="flex flex-wrap gap-5">
                                <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                                    <input type="radio" name="has_curriculum_suggestions" value="1" {{ $booleanAnswerIsYes($hasCurriculumSuggestionsValue) ? 'checked' : '' }}>
                                    <span>Yes</span>
                                </label>
                                <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                                    <input type="radio" name="has_curriculum_suggestions" value="0" {{ $booleanAnswerIsNo($hasCurriculumSuggestionsValue) ? 'checked' : '' }}>
                                    <span>No</span>
                                </label>
                            </div>
                        </div>
                        <div id="tracer_curriculum_suggestions_group" class="md:col-span-2" data-tracer-field-anchor="curriculum_suggestions">
                            <label class="mb-1 block text-sm font-medium text-slate-700">Curriculum suggestion</label>
                            <textarea name="curriculum_suggestions" rows="3" placeholder="Enter your suggestion" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-100">{{ old('curriculum_suggestions', $formTracer?->curriculum_suggestions) }}</textarea>
                        </div>

                        <div class="md:col-span-2" data-tracer-field-anchor="has_employability_suggestions">
                            <p class="mb-2 text-sm font-medium text-slate-700">Any employability suggestion?</p>
                            <div class="flex flex-wrap gap-5">
                                <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                                    <input type="radio" name="has_employability_suggestions" value="1" {{ $booleanAnswerIsYes($hasEmployabilitySuggestionsValue) ? 'checked' : '' }}>
                                    <span>Yes</span>
                                </label>
                                <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                                    <input type="radio" name="has_employability_suggestions" value="0" {{ $booleanAnswerIsNo($hasEmployabilitySuggestionsValue) ? 'checked' : '' }}>
                                    <span>No</span>
                                </label>
                            </div>
                        </div>
                        <div id="tracer_employability_suggestions_group" class="md:col-span-2" data-tracer-field-anchor="employability_suggestions">
                            <label class="mb-1 block text-sm font-medium text-slate-700">Employability suggestion</label>
                            <textarea name="employability_suggestions" rows="3" placeholder="Enter your suggestion" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-100">{{ old('employability_suggestions', $formTracer?->employability_suggestions) }}</textarea>
                        </div>

                        <div data-tracer-field-anchor="facilities_satisfaction">
                            <label class="mb-1 block text-sm font-medium text-slate-700">Facilities satisfaction</label>
                            <span class="tracer-select-wrap">
                                <select name="facilities_satisfaction" class="tracer-select w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                                    <option value="">Select answer</option>
                                    @foreach($facilitiesSatisfactionOptions as $option)
                                        <option value="{{ $option }}" {{ old('facilities_satisfaction', $formTracer?->facilities_satisfaction) === $option ? 'selected' : '' }}>{{ $option }}</option>
                                    @endforeach
                                </select>
                                <span class="tracer-select-wrap__icon" aria-hidden="true">
                                    <i class='bx bx-chevron-down text-lg'></i>
                                </span>
                            </span>
                        </div>
                        <div data-tracer-field-anchor="has_facility_suggestions">
                            <p class="mb-2 text-sm font-medium text-slate-700">Any facility suggestion?</p>
                            <div class="flex flex-wrap gap-5">
                                <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                                    <input type="radio" name="has_facility_suggestions" value="1" {{ $booleanAnswerIsYes($hasFacilitySuggestionsValue) ? 'checked' : '' }}>
                                    <span>Yes</span>
                                </label>
                                <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                                    <input type="radio" name="has_facility_suggestions" value="0" {{ $booleanAnswerIsNo($hasFacilitySuggestionsValue) ? 'checked' : '' }}>
                                    <span>No</span>
                                </label>
                            </div>
                        </div>
                        <div id="tracer_facility_suggestions_group" class="md:col-span-2" data-tracer-field-anchor="facility_suggestions">
                            <label class="mb-1 block text-sm font-medium text-slate-700">Facility suggestion</label>
                            <textarea name="facility_suggestions" rows="3" placeholder="Enter your suggestion" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-100">{{ old('facility_suggestions', $formTracer?->facility_suggestions) }}</textarea>
                        </div>

                        <div data-tracer-field-anchor="overall_recommend_institution">
                            <label class="mb-1 block text-sm font-medium text-slate-700">I would recommend this institution to my peers</label>
                            <span class="tracer-select-wrap">
                                <select name="overall_recommend_institution" class="tracer-select w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                                    <option value="">Select rating</option>
                                    @foreach($agreementScaleOptions as $score => $label)
                                        <option value="{{ $score }}" {{ (string) old('overall_recommend_institution', $formTracer?->overall_recommend_institution) === (string) $score ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                                <span class="tracer-select-wrap__icon" aria-hidden="true">
                                    <i class='bx bx-chevron-down text-lg'></i>
                                </span>
                            </span>
                        </div>
                        <div data-tracer-field-anchor="overall_objectives_met">
                            <label class="mb-1 block text-sm font-medium text-slate-700">My university experience fulfilled my objectives</label>
                            <span class="tracer-select-wrap">
                                <select name="overall_objectives_met" class="tracer-select w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                                    <option value="">Select rating</option>
                                    @foreach($agreementScaleOptions as $score => $label)
                                        <option value="{{ $score }}" {{ (string) old('overall_objectives_met', $formTracer?->overall_objectives_met) === (string) $score ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                                <span class="tracer-select-wrap__icon" aria-hidden="true">
                                    <i class='bx bx-chevron-down text-lg'></i>
                                </span>
                            </span>
                        </div>
                        <div class="md:col-span-2" data-tracer-field-anchor="overall_university_experience">
                            <label class="mb-1 block text-sm font-medium text-slate-700">I am satisfied with my university experience</label>
                            <span class="tracer-select-wrap">
                                <select name="overall_university_experience" class="tracer-select w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                                    <option value="">Select rating</option>
                                    @foreach($agreementScaleOptions as $score => $label)
                                        <option value="{{ $score }}" {{ (string) old('overall_university_experience', $formTracer?->overall_university_experience) === (string) $score ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                                <span class="tracer-select-wrap__icon" aria-hidden="true">
                                    <i class='bx bx-chevron-down text-lg'></i>
                                </span>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="md:col-span-2 rounded-2xl border border-emerald-100 bg-emerald-50/70 px-4 py-4">
                    <input type="hidden" name="contact_permission" value="1">
                    <div class="flex items-start gap-3 text-sm text-slate-700">
                        <span class="mt-0.5 inline-flex h-5 w-5 items-center justify-center rounded-full bg-emerald-600 text-white">
                            <i class='bx bx-check text-sm'></i>
                        </span>
                        <div>
                            <p class="font-medium text-slate-900">Contact permission is already on file.</p>
                            <p class="mt-1">You agreed during registration that the Alumni Relation and Linkages Office may contact you regarding tracer updates, employment follow-ups, and institutional reports.</p>
                        </div>
                    </div>
                </div>

                @if(!(auth()->user()?->data_privacy_consent ?? false))
                    <div class="md:col-span-2 rounded-2xl border border-emerald-200 bg-emerald-50/70 px-4 py-4">
                        <div class="space-y-3">
                            <div>
                                <h3 class="text-sm font-semibold uppercase tracking-[0.14em] text-emerald-800">Data Privacy Consent</h3>
                                <p class="mt-2 text-sm leading-6 text-slate-700">
                                    I hereby consent and authorize the CMU Office of the Alumni Relations and Linkages (OARL) to collect, use, share, and disclose my personal data contained in this form and in other supporting documents for the purpose of alumni tracing and employability. I understand that, in compliance with the Data Privacy Act of 2012 (R.A. No. 10173), the Office of the Alumni Relations and Linkages (OARL) will strive to keep my information private and confidential and will retain my information solely for the fulfillment of the aforementioned purposes.
                                </p>
                            </div>
                            <label class="flex items-start gap-3 text-sm text-slate-700">
                                <input type="checkbox" name="data_privacy_consent" value="1" class="mt-0.5 h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500" {{ old('data_privacy_consent') ? 'checked' : '' }} required>
                                <span>I agree to the Privacy Policy and confirm that I have read and agree to the Terms.</span>
                            </label>
                        </div>
                    </div>
                @else
                    <div class="md:col-span-2 rounded-2xl border border-emerald-100 bg-emerald-50/60 px-4 py-3 text-sm text-emerald-900">
                        Data Privacy Consent on file
                        @if(auth()->user()?->data_privacy_consented_at)
                            as of {{ auth()->user()->data_privacy_consented_at->format('M d, Y') }}.
                        @else
                            for your alumni records and tracer submissions.
                        @endif
                    </div>
                @endif

                <div
                    id="tracer-information-attestation"
                    class="fixed inset-0 z-[70] {{ $showTracerAttestation ? 'flex' : 'hidden' }} items-center justify-center bg-slate-950/60 px-4 py-6 backdrop-blur-sm"
                    data-tracer-attestation
                    role="dialog"
                    aria-modal="true"
                    aria-labelledby="tracer-information-attestation-title"
                    @if(!$showTracerAttestation) hidden @endif
                >
                    <button
                        type="button"
                        class="absolute inset-0 cursor-default"
                        data-tracer-attestation-close
                        aria-label="Close tracer information confirmation"
                    ></button>
                    <div class="relative w-full max-w-lg rounded-lg border border-slate-200 bg-white p-5 shadow-2xl">
                        <div class="flex items-start gap-3">
                            <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-emerald-100 text-emerald-700">
                                <i class='bx bx-check-shield text-xl'></i>
                            </span>
                            <div class="min-w-0">
                                <h3 id="tracer-information-attestation-title" class="text-sm font-semibold uppercase tracking-[0.14em] text-slate-800">Information Attestation</h3>
                                <p class="mt-2 text-sm leading-6 text-slate-700">Please confirm that the tracer details you are submitting are truthful and current.</p>
                            </div>
                        </div>
                        <label class="mt-5 flex items-start gap-3 rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                            <input type="checkbox" name="information_attestation" value="1" class="mt-0.5 h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500" {{ old('information_attestation') ? 'checked' : '' }} {{ $showTracerAttestation ? 'required' : '' }} data-tracer-attestation-checkbox>
                            <span>I confirm this information is true and accurate.</span>
                        </label>
                        @error('information_attestation')
                            <p class="mt-3 text-sm font-medium text-rose-600">{{ $message }}</p>
                        @enderror
                        <div class="mt-5 flex flex-wrap items-center justify-end gap-3">
                            <button type="button" class="inline-flex items-center justify-center rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 transition-colors hover:bg-slate-100" data-tracer-attestation-close>
                                Cancel
                            </button>
                            <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-emerald-700">
                                <i class='bx bx-save text-lg'></i>
                                Confirm and Save
                            </button>
                        </div>
                    </div>
                </div>

                <div class="md:col-span-2 flex flex-wrap items-start gap-3 pt-2">
                    <button type="button" class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition-all duration-300 hover:-translate-y-0.5 hover:border-slate-400 hover:bg-slate-50 hover:shadow-sm" data-tracer-reset-button>
                        <i class='bx bx-reset text-lg'></i>
                        <span>Reset Changes</span>
                    </button>
                    <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-5 py-3 text-sm font-semibold text-white transition-all duration-300 hover:-translate-y-0.5 hover:bg-emerald-700 hover:shadow-md" data-tracer-submit-button>
                        <i class='bx {{ $editingTracer ? 'bx-save' : 'bx-plus-circle' }} text-lg'></i>
                        <span data-tracer-submit-label>{{ $editingTracer ? 'Save as New History Entry' : 'Save Tracer Record' }}</span>
                    </button>
                    <div class="min-w-[240px] flex-1">
                        <div
                            id="tracer-no-changes-message"
                            class="mt-3 hidden rounded-xl border border-amber-200 bg-amber-50/90 px-4 py-3 text-sm text-amber-900 shadow-sm ring-1 ring-amber-100/70 focus:outline-none focus:ring-2 focus:ring-amber-300"
                            data-tracer-no-changes-message
                            role="alert"
                            aria-hidden="true"
                            tabindex="-1"
                        >
                            <div class="flex items-start gap-3">
                                <span class="mt-0.5 inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-amber-100 text-amber-700">
                                    <i class='bx bx-info-circle text-lg'></i>
                                </span>
                                <div>
                                    <p class="font-semibold text-amber-950">No tracer changes found.</p>
                                    <p class="mt-1 leading-6">Please edit at least one tracer field before saving your information.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <div class="space-y-6">
            <div
                id="scholarship-record"
                class="surface-card scroll-mt-24 p-6"
                data-scholarship-card
                data-editor-open="{{ $scholarshipEditorOpen ? 'true' : 'false' }}"
            >
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 class="text-xl font-semibold text-slate-900">Scholarship Record</h2>
                        <p class="mt-1 text-sm text-slate-600">Saved on your alumni account. If you missed this during registration, you can update it here.</p>
                    </div>
                    <button
                        type="button"
                        class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition-all duration-300 hover:-translate-y-0.5 hover:border-emerald-300 hover:text-emerald-700"
                        data-scholarship-toggle
                        data-open-label="{{ $scholarshipUser?->has_scholarship && $scholarshipUser?->scholarship_name ? 'Edit Scholarship Record' : 'Add Scholarship Record' }}"
                        data-close-label="Close Editor"
                    >
                        <i class='bx bx-edit text-base'></i>
                        <span data-scholarship-toggle-label>{{ $scholarshipUser?->has_scholarship && $scholarshipUser?->scholarship_name ? 'Edit Scholarship Record' : 'Add Scholarship Record' }}</span>
                    </button>
                </div>

                @if($scholarshipUser?->has_scholarship && $scholarshipUser?->scholarship_name)
                    <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div class="rounded-2xl border border-emerald-100 bg-emerald-50/70 px-4 py-3">
                            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-emerald-700">Scholarship Name</p>
                            <p class="mt-1 text-base font-semibold text-slate-900">{{ $scholarshipUser->scholarship_name }}</p>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3">
                            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Year Awarded</p>
                            <p class="mt-1 text-sm font-medium text-slate-900">{{ $scholarshipUser->scholarship_year_awarded ?? 'N/A' }}</p>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3 sm:col-span-2">
                            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Helped You Get a Job After Graduation</p>
                            <p class="mt-1 text-sm font-medium text-slate-900">
                                {{ $scholarshipUser->scholarship_helped_get_job === null ? 'N/A' : ($scholarshipUser->scholarship_helped_get_job ? 'Yes' : 'No') }}
                            </p>
                        </div>
                    </div>
                @else
                    <div class="empty-state-card mt-4">
                        <span class="empty-state-card__icon">
                            <i class='bx bx-medal'></i>
                        </span>
                        <p class="empty-state-card__title">No scholarship recorded</p>
                        <p class="empty-state-card__body">Add your scholarship here if you forgot to include it during registration.</p>
                    </div>
                @endif

                <form
                    action="{{ route('tracer.scholarship.update') }}"
                    method="POST"
                    class="mt-5 rounded-3xl border border-slate-200 bg-slate-50/80 p-5 {{ $scholarshipEditorOpen ? '' : 'hidden' }}"
                    data-scholarship-form
                    data-scholarship-editor
                    {{ $scholarshipEditorOpen ? '' : 'hidden' }}
                >
                    @csrf
                    <input type="hidden" name="_scholarship_form" value="1">

                    <div class="space-y-4">
                        <div>
                            <p class="mb-2 text-sm font-medium text-slate-700">Do you have a scholarship record?</p>
                            <div class="flex flex-wrap gap-5">
                                <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                                    <input type="radio" name="has_scholarship" value="1" {{ (string) $scholarshipHasValue === '1' ? 'checked' : '' }}>
                                    <span>Yes</span>
                                </label>
                                <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                                    <input type="radio" name="has_scholarship" value="0" {{ (string) $scholarshipHasValue === '0' ? 'checked' : '' }}>
                                    <span>No</span>
                                </label>
                            </div>
                        </div>

                        <div class="space-y-4" data-scholarship-details>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-slate-700">Scholarship Name</label>
                                <span class="tracer-select-wrap">
                                    <select
                                        name="scholarship_name"
                                        class="tracer-select w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-100"
                                        data-scholarship-name
                                    >
                                        <option value="">Select scholarship name</option>
                                        @foreach($scholarshipNameOptions as $scholarshipNameOption)
                                            <option value="{{ $scholarshipNameOption }}" {{ $scholarshipNameValue === $scholarshipNameOption ? 'selected' : '' }}>
                                                {{ $scholarshipNameOption }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <span class="tracer-select-wrap__icon" aria-hidden="true">
                                        <i class='bx bx-chevron-down text-lg'></i>
                                    </span>
                                </span>
                            </div>

                            <div data-scholarship-other-group>
                                <label class="mb-1 block text-sm font-medium text-slate-700">If Other, specify scholarship name</label>
                                <input
                                    name="scholarship_name_other"
                                    type="text"
                                    value="{{ $scholarshipNameOtherValue }}"
                                    placeholder="Enter scholarship name"
                                    class="w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-100"
                                >
                            </div>

                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-slate-700">Year Awarded</label>
                                    <input
                                        name="scholarship_year_awarded"
                                        type="number"
                                        min="1900"
                                        max="{{ now()->year }}"
                                        value="{{ $scholarshipYearAwardedValue }}"
                                        placeholder="e.g. 2021"
                                        class="w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-100"
                                    >
                                </div>

                                <div>
                                    <p class="mb-2 text-sm font-medium text-slate-700">Did it help you get a job after graduation?</p>
                                    <div class="flex flex-wrap gap-5 pt-2">
                                        <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                                            <input type="radio" name="scholarship_helped_get_job" value="1" {{ (string) $scholarshipHelpedValue === '1' ? 'checked' : '' }}>
                                            <span>Yes</span>
                                        </label>
                                        <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                                            <input type="radio" name="scholarship_helped_get_job" value="0" {{ (string) $scholarshipHelpedValue === '0' ? 'checked' : '' }}>
                                            <span>No</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <p class="text-sm text-slate-500">Select <span class="font-semibold text-slate-700">No</span> if you need to remove an incorrect scholarship record from your account.</p>

                        <div
                            id="scholarship-information-attestation"
                            class="fixed inset-0 z-[70] {{ $showScholarshipAttestation ? 'flex' : 'hidden' }} items-center justify-center bg-slate-950/60 px-4 py-6 backdrop-blur-sm"
                            data-scholarship-attestation
                            role="dialog"
                            aria-modal="true"
                            aria-labelledby="scholarship-information-attestation-title"
                            @if(!$showScholarshipAttestation) hidden @endif
                        >
                            <button
                                type="button"
                                class="absolute inset-0 cursor-default"
                                data-scholarship-attestation-close
                                aria-label="Close scholarship information confirmation"
                            ></button>
                            <div class="relative w-full max-w-lg rounded-lg border border-slate-200 bg-white p-5 shadow-2xl">
                                <div class="flex items-start gap-3">
                                    <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-emerald-100 text-emerald-700">
                                        <i class='bx bx-check-shield text-xl'></i>
                                    </span>
                                    <div class="min-w-0">
                                        <h3 id="scholarship-information-attestation-title" class="text-sm font-semibold uppercase tracking-[0.14em] text-slate-800">Information Attestation</h3>
                                        <p class="mt-2 text-sm leading-6 text-slate-700">Please confirm that the scholarship details you are updating are truthful and accurate.</p>
                                    </div>
                                </div>
                                <label class="mt-5 flex items-start gap-3 rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                                    <input type="checkbox" name="scholarship_attestation" value="1" class="mt-0.5 h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500" {{ old('scholarship_attestation') ? 'checked' : '' }} {{ $showScholarshipAttestation ? 'required' : '' }} data-scholarship-attestation-checkbox>
                                    <span>I confirm this information is true and accurate.</span>
                                </label>
                                @error('scholarship_attestation')
                                    <p class="mt-3 text-sm font-medium text-rose-600">{{ $message }}</p>
                                @enderror
                                <div class="mt-5 flex flex-wrap items-center justify-end gap-3">
                                    <button type="button" class="inline-flex items-center justify-center rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 transition-colors hover:bg-slate-100" data-scholarship-attestation-close>
                                        Cancel
                                    </button>
                                    <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-emerald-700">
                                        <i class='bx bx-save text-lg'></i>
                                        Confirm and Save
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-start gap-3">
                            <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-5 py-3 text-sm font-semibold text-white transition-all duration-300 hover:-translate-y-0.5 hover:bg-emerald-700 hover:shadow-md" data-scholarship-submit-button>
                                <i class='bx bx-save text-lg'></i>
                                <span>Save Scholarship Record</span>
                            </button>
                            <button
                                type="button"
                                class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition-all duration-300 hover:-translate-y-0.5 hover:border-slate-400 hover:bg-slate-50 hover:shadow-sm"
                                data-scholarship-cancel-button
                            >
                                <i class='bx bx-x text-lg'></i>
                                <span>Cancel</span>
                            </button>
                            <div
                                id="scholarship-no-changes-message"
                                class="hidden min-w-[240px] flex-1 rounded-xl border border-amber-200 bg-amber-50/90 px-4 py-3 text-sm text-amber-900 shadow-sm ring-1 ring-amber-100/70 focus:outline-none focus:ring-2 focus:ring-amber-300"
                                data-scholarship-no-changes-message
                                role="alert"
                                aria-hidden="true"
                                tabindex="-1"
                            >
                                <div class="flex items-start gap-3">
                                    <span class="mt-0.5 inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-amber-100 text-amber-700">
                                        <i class='bx bx-info-circle text-lg'></i>
                                    </span>
                                    <div>
                                        <p class="font-semibold text-amber-950">No scholarship changes found.</p>
                                        <p class="mt-1 leading-6">Please edit at least one scholarship field before saving this record.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <div class="surface-card p-6">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 class="text-xl font-semibold text-slate-900">Latest Snapshot</h2>
                        <p class="mt-1 text-sm text-slate-600">Your most recent saved tracer record.</p>
                    </div>
                </div>
                @if($latestTracer)
                    @php
                        $latestSnapshotCards = [];
                        $addLatestSnapshotCard = function (string $label, $value, string $target, bool $primary = false) use (&$latestSnapshotCards): void {
                            if (filled($value)) {
                                $latestSnapshotCards[] = [
                                    'label' => $label,
                                    'value' => $value,
                                    'target' => $target,
                                    'primary' => $primary,
                                ];
                            }
                        };
                        $latestEmploymentType = trim((string) ($latestTracer->employment_type ?? ''));
                        $latestEmploymentKey = mb_strtolower($latestEmploymentType);
                        $latestTracerHasWorkDetails = filled($latestEmploymentType)
                            && !in_array($latestEmploymentKey, ['unemployed', 'never employed', 'no'], true);
                        $latestPresentlyEmployed = $latestTracer->presently_employed ?? null;
                        $latestProofStatusClasses = $latestTracer->employmentProofIsVerified()
                            ? 'bg-emerald-100 text-emerald-700'
                            : ($latestTracer->employmentProofIsRejected() ? 'bg-rose-100 text-rose-700' : 'bg-amber-100 text-amber-700');

                        $addLatestSnapshotCard('Employment Type', $latestTracer->employment_type, 'employment_type', true);

                        if ($latestTracerHasWorkDetails) {
                            $addLatestSnapshotCard('Position', $latestTracer->current_position, 'current_position');
                            $addLatestSnapshotCard('Company / Business', $latestTracer->current_company, 'current_company');
                            $addLatestSnapshotCard('Monthly Income Range', $latestTracer->monthly_income, 'monthly_income');
                            $addLatestSnapshotCard('Industry', $latestTracer->industry, 'industry');
                            $addLatestSnapshotCard('Present Employment Status', $latestTracer->present_employment_status, 'present_employment_status');
                            $addLatestSnapshotCard('Job Satisfaction', $latestTracer->job_satisfaction ? $latestTracer->job_satisfaction . ' / 5' : null, 'job_satisfaction');
                            $addLatestSnapshotCard('Skills Used', $latestTracer->skills_used, 'skills_used');
                        } elseif (filled($latestPresentlyEmployed) && mb_strtolower((string) $latestPresentlyEmployed) !== $latestEmploymentKey) {
                            $addLatestSnapshotCard('Presently Employed', $latestPresentlyEmployed, 'presently_employed');
                        }

                        $latestFirstJobTime = $latestTracer->time_to_first_job === 'Other'
                            ? ($latestTracer->time_to_first_job_other ?? 'Other')
                            : $latestTracer->time_to_first_job;
                        $latestCompetencySummary = collect((array) ($latestTracer->first_job_useful_competencies ?? []))->join(', ');

                        $addLatestSnapshotCard('Time to First Job', $latestFirstJobTime, 'time_to_first_job');
                        $addLatestSnapshotCard('Facilities Satisfaction', $latestTracer->facilities_satisfaction, 'facilities_satisfaction');
                        $addLatestSnapshotCard('Useful Competencies', $latestCompetencySummary, 'first_job_useful_competencies');

                        if ($latestTracer->further_education) {
                            $addLatestSnapshotCard('Further Education', $latestTracer->education_level ?? 'Yes', 'education_level');
                        }
                    @endphp

                    <div class="mt-4 space-y-3 text-sm">
                        @foreach($latestSnapshotCards as $snapshotCard)
                            <button
                                type="button"
                                class="{{ $snapshotCard['primary'] ? $snapshotPrimaryJumpClass : $snapshotJumpClass }}"
                                data-tracer-jump-target="{{ $snapshotCard['target'] }}"
                                aria-label="Go to {{ $snapshotCard['label'] }} field"
                            >
                                <span class="block text-xs font-semibold uppercase tracking-[0.14em] {{ $snapshotCard['primary'] ? 'text-emerald-700' : 'text-slate-500' }}">{{ $snapshotCard['label'] }}</span>
                                <span class="mt-1 block {{ $snapshotCard['primary'] ? 'text-base' : 'text-sm' }} font-semibold text-slate-900">{{ $snapshotCard['value'] }}</span>
                            </button>
                        @endforeach

                        @if($latestTracer->employment_proof_path)
                            <div class="rounded-2xl border border-slate-200 bg-white px-4 py-4">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Employment Proof</p>
                                    <span class="rounded-full px-2.5 py-1 text-[11px] font-semibold uppercase tracking-[0.12em] {{ $latestProofStatusClasses }}">
                                        {{ $latestTracer->employmentProofStatusLabel() }}
                                    </span>
                                </div>
                                @if($latestTracer->employment_proof_uploaded_at)
                                    <p class="mt-2 text-xs text-slate-500">Uploaded {{ $latestTracer->employment_proof_uploaded_at->format('M d, Y g:i A') }}</p>
                                @endif
                                @if($latestTracer->employmentProofIsRejected() && $latestTracer->employment_proof_rejection_note)
                                    <p class="mt-2 text-xs text-rose-600">{{ $latestTracer->employment_proof_rejection_note }}</p>
                                @endif
                                @if($latestTracer->employmentProofIsVerified())
                                    <a
                                        href="{{ route('tracer.proof.download', $latestTracer) }}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="mt-2 inline-flex items-center gap-2 text-sm font-medium text-emerald-700 hover:text-emerald-800"
                                    >
                                        <i class='bx bxs-file-pdf text-base'></i>
                                        View uploaded proof PDF
                                    </a>
                                @else
                                    <p class="mt-2 text-sm text-slate-500">Verified proof will appear here after admin review.</p>
                                @endif
                            </div>
                        @endif
                    </div>
                @else
                    <div class="empty-state-card mt-4">
                        <span class="empty-state-card__icon">
                            <i class='bx bx-notepad'></i>
                        </span>
                        <p class="empty-state-card__title">No tracer record yet</p>
                        <p class="empty-state-card__body">Submit a tracer record to get started.</p>
                    </div>
                @endif
            </div>

            <div class="surface-card p-6">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 class="text-xl font-semibold text-slate-900">Tracer History</h2>
                        <p class="mt-1 text-sm text-slate-600">A running timeline of the records you have submitted.</p>
                    </div>
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">{{ $tracerHistoryCountLabel }}</span>
                </div>

                <div class="mt-4 space-y-3">
                    @forelse($tracerHistory as $historyEntry)
                        @php
                            $record = $historyEntry['record'];
                            $historyBadges = $historyEntry['badges'] ?? [];
                            $historyNote = $historyEntry['note'] ?? null;
                        @endphp
                        <article class="rounded-2xl border border-slate-200 bg-white px-4 py-4 shadow-sm transition-all duration-300">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <p class="text-sm font-semibold text-slate-900">{{ $record->tracer_date?->format('M j, Y') }}</p>
                                    <p class="mt-1 text-xs font-semibold uppercase tracking-[0.14em] text-emerald-700">{{ $record->employment_type ?? 'Unknown' }}</p>
                                </div>
                                <div class="flex flex-wrap items-center gap-2">
                                    @if($record->job_satisfaction)
                                        <span class="rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700">{{ $record->job_satisfaction }}/5 satisfaction</span>
                                    @endif
                                    <a
                                        href="{{ route('tracer.index', ['edit' => $record->id]) }}#tracer-form"
                                        class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold uppercase tracking-[0.12em] text-slate-700 transition-all duration-300 hover:-translate-y-0.5 hover:border-emerald-300 hover:text-emerald-700"
                                    >
                                        <i class='bx bx-edit text-base'></i>
                                        Edit Entry
                                    </a>
                                </div>
                            </div>
                            @if($historyBadges)
                                <div class="mt-3 flex flex-wrap gap-2">
                                    @foreach($historyBadges as $badge)
                                        <span class="rounded-full px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.12em] {{ $badge['classes'] ?? 'bg-slate-100 text-slate-700 ring-1 ring-slate-200' }}">
                                            {{ $badge['label'] }}
                                        </span>
                                    @endforeach
                                </div>
                            @endif
                            @if($historyNote)
                                <p class="mt-3 text-xs text-slate-500">{{ $historyNote }}</p>
                            @endif
                            @php
                                $historyDetailRows = [];
                                $addHistoryDetailRow = function (string $label, $value) use (&$historyDetailRows): void {
                                    if (filled($value)) {
                                        $historyDetailRows[] = [
                                            'label' => $label,
                                            'value' => $value,
                                        ];
                                    }
                                };
                                $addHistoryDetailRow('Company / Business', $record->current_company);
                                $addHistoryDetailRow('Position', $record->current_position);
                                $addHistoryDetailRow('Industry', $record->industry);
                                $addHistoryDetailRow('Monthly Income Range', $record->monthly_income);

                                if ($record->further_education) {
                                    $addHistoryDetailRow('Further Education', $record->education_level ?? 'Yes');
                                }
                            @endphp
                            <div class="mt-3 grid grid-cols-1 gap-2 text-sm text-slate-600">
                                @forelse($historyDetailRows as $historyDetailRow)
                                    <p><span class="font-medium text-slate-800">{{ $historyDetailRow['label'] }}:</span> {{ $historyDetailRow['value'] }}</p>
                                @empty
                                    <p class="text-slate-500">No additional employment details recorded for this entry.</p>
                                @endforelse
                            </div>
                        </article>
                    @empty
                        <div class="empty-state-card">
                            <span class="empty-state-card__icon">
                                <i class='bx bx-history'></i>
                            </span>
                            <p class="empty-state-card__title">History is empty</p>
                            <p class="empty-state-card__body">No submissions yet.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    (function () {
        const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        const tracerIndexUrl = @json(route('tracer.index'));
        const tracerJumpTargets = {
            employment_type: {
                selector: '[name="employment_type"]',
            },
            current_position: {
                selector: '[name="current_position"]',
                fallback: '[name="employment_type"]',
            },
            current_company: {
                selector: '[name="current_company"]',
                fallback: '[name="employment_type"]',
            },
            monthly_income: {
                selector: '[name="monthly_income"]',
                fallback: '[name="employment_type"]',
            },
            industry: {
                selector: '[name="industry"]',
                fallback: '[name="employment_type"]',
            },
            job_satisfaction: {
                selector: '[name="job_satisfaction"]',
            },
            skills_used: {
                selector: '[name="skills_used"]',
            },
            education_level: {
                selector: '[name="education_level"]',
                fallback: '[name="further_education"]',
            },
            challenges_faced: {
                selector: '[name="challenges_faced"]',
            },
            recommendations: {
                selector: '[name="recommendations"]',
            },
            presently_employed: {
                selector: '[name="presently_employed"]',
            },
            present_employment_status: {
                selector: '[name="present_employment_status"]',
                fallback: '[name="presently_employed"]',
            },
            present_job_monthly_earnings: {
                selector: '[name="present_job_monthly_earnings"]',
                fallback: '[name="presently_employed"]',
            },
            is_first_job_after_college: {
                selector: '[name="is_first_job_after_college"]',
                fallback: '[name="presently_employed"]',
            },
            job_level: {
                selector: '[name="job_level"]',
                fallback: '[name="presently_employed"]',
            },
            present_job_acceptance_reasons: {
                selector: '[name="present_job_acceptance_reasons[]"]',
                fallback: '[name="presently_employed"]',
            },
            has_employee_awards: {
                selector: '[name="has_employee_awards"]',
                fallback: '[name="presently_employed"]',
            },
            employee_awards: {
                selector: '[name="employee_awards"]',
                fallback: '[name="has_employee_awards"]',
            },
            curriculum_relevant_to_first_job: {
                selector: '[name="curriculum_relevant_to_first_job"]',
            },
            first_job_useful_competencies: {
                selector: '[name="first_job_useful_competencies[]"]',
                fallback: '[name="curriculum_relevant_to_first_job"]',
            },
            curriculum_not_relevant_reason: {
                selector: '[name="curriculum_not_relevant_reason"]',
                fallback: '[name="curriculum_relevant_to_first_job"]',
            },
            employed_after_graduation: {
                selector: '[name="employed_after_graduation"]',
            },
            time_to_first_job: {
                selector: '[name="time_to_first_job"]',
                fallback: '[name="employed_after_graduation"]',
            },
            first_job_related_to_course: {
                selector: '[name="first_job_related_to_course"]',
                fallback: '[name="employed_after_graduation"]',
            },
            first_job_finding_method: {
                selector: '[name="first_job_finding_method"]',
                fallback: '[name="employed_after_graduation"]',
            },
            first_job_monthly_earnings: {
                selector: '[name="first_job_monthly_earnings"]',
                fallback: '[name="employed_after_graduation"]',
            },
            first_job_duration: {
                selector: '[name="first_job_duration"]',
                fallback: '[name="employed_after_graduation"]',
            },
            facilities_satisfaction: {
                selector: '[name="facilities_satisfaction"]',
            },
            teaching_faculty_enthusiastic: {
                selector: '[name="teaching_faculty_enthusiastic"]',
            },
            teaching_concepts_clear: {
                selector: '[name="teaching_concepts_clear"]',
            },
            teaching_faculty_knowledgeable: {
                selector: '[name="teaching_faculty_knowledgeable"]',
            },
            teaching_commitment: {
                selector: '[name="teaching_commitment"]',
            },
            degree_program_quality: {
                selector: '[name="degree_program_quality"]',
            },
            has_curriculum_suggestions: {
                selector: '[name="has_curriculum_suggestions"]',
            },
            curriculum_suggestions: {
                selector: '[name="curriculum_suggestions"]',
                fallback: '[name="has_curriculum_suggestions"]',
            },
            has_employability_suggestions: {
                selector: '[name="has_employability_suggestions"]',
            },
            employability_suggestions: {
                selector: '[name="employability_suggestions"]',
                fallback: '[name="has_employability_suggestions"]',
            },
            has_facility_suggestions: {
                selector: '[name="has_facility_suggestions"]',
            },
            facility_suggestions: {
                selector: '[name="facility_suggestions"]',
                fallback: '[name="has_facility_suggestions"]',
            },
            overall_recommend_institution: {
                selector: '[name="overall_recommend_institution"]',
            },
            overall_objectives_met: {
                selector: '[name="overall_objectives_met"]',
            },
            overall_university_experience: {
                selector: '[name="overall_university_experience"]',
            },
            employment_proof: {
                selector: '[name="employment_proof"]',
                fallback: '[name="presently_employed"]',
            },
        };
        const tracerValidationErrorTargets = {
            employment_type: 'employment_type',
            current_position: 'current_position',
            current_company: 'current_company',
            monthly_income: 'monthly_income',
            industry: 'industry',
            job_satisfaction: 'job_satisfaction',
            skills_used: 'skills_used',
            challenges_faced: 'challenges_faced',
            recommendations: 'recommendations',
            curriculum_relevant_to_first_job: 'curriculum_relevant_to_first_job',
            first_job_useful_competencies: 'first_job_useful_competencies',
            first_job_useful_competencies_other: 'first_job_useful_competencies',
            curriculum_not_relevant_reason: 'curriculum_not_relevant_reason',
            curriculum_not_relevant_reason_other: 'curriculum_not_relevant_reason',
            employed_after_graduation: 'employed_after_graduation',
            time_to_first_job: 'time_to_first_job',
            time_to_first_job_other: 'time_to_first_job',
            first_job_related_to_course: 'first_job_related_to_course',
            first_job_finding_method: 'first_job_finding_method',
            first_job_finding_method_other: 'first_job_finding_method',
            first_job_monthly_earnings: 'first_job_monthly_earnings',
            first_job_duration: 'first_job_duration',
            first_job_duration_other: 'first_job_duration',
            presently_employed: 'presently_employed',
            present_employment_status: 'present_employment_status',
            is_first_job_after_college: 'is_first_job_after_college',
            job_level: 'job_level',
            job_level_other: 'job_level',
            present_job_acceptance_reasons: 'present_job_acceptance_reasons',
            present_job_acceptance_other: 'present_job_acceptance_reasons',
            present_job_monthly_earnings: 'present_job_monthly_earnings',
            has_employee_awards: 'has_employee_awards',
            employee_awards: 'employee_awards',
            teaching_faculty_enthusiastic: 'teaching_faculty_enthusiastic',
            teaching_concepts_clear: 'teaching_concepts_clear',
            teaching_faculty_knowledgeable: 'teaching_faculty_knowledgeable',
            teaching_commitment: 'teaching_commitment',
            degree_program_quality: 'degree_program_quality',
            has_curriculum_suggestions: 'has_curriculum_suggestions',
            curriculum_suggestions: 'curriculum_suggestions',
            has_employability_suggestions: 'has_employability_suggestions',
            employability_suggestions: 'employability_suggestions',
            facilities_satisfaction: 'facilities_satisfaction',
            has_facility_suggestions: 'has_facility_suggestions',
            facility_suggestions: 'facility_suggestions',
            overall_recommend_institution: 'overall_recommend_institution',
            overall_objectives_met: 'overall_objectives_met',
            overall_university_experience: 'overall_university_experience',
            employment_proof: 'employment_proof',
            data_privacy_consent: null,
            information_attestation: null,
        };

        function normalizeTracerValue(value) {
            if (value instanceof File) {
                return value.name || value.size
                    ? [value.name, value.size, value.type, value.lastModified].join(':')
                    : '';
            }

            return typeof value === 'string' ? value.trim() : String(value ?? '');
        }

        function serializeTracerForm(form) {
            const state = {};
            const ignoredNames = new Set([
                '_token',
                'information_attestation',
                'scholarship_attestation',
            ]);

            for (const [name, value] of new FormData(form).entries()) {
                if (ignoredNames.has(name)) {
                    continue;
                }

                const normalizedValue = normalizeTracerValue(value);

                if (name.endsWith('[]')) {
                    if (!Array.isArray(state[name])) {
                        state[name] = [];
                    }

                    state[name].push(normalizedValue);
                    continue;
                }

                if (Object.prototype.hasOwnProperty.call(state, name)) {
                    state[name] = Array.isArray(state[name])
                        ? [...state[name], normalizedValue]
                        : [state[name], normalizedValue];
                    continue;
                }

                state[name] = normalizedValue;
            }

            return JSON.stringify(state);
        }

        function getFirstInvalidField(form) {
            return Array.from(form.elements).find((field) => (
                field instanceof HTMLElement
                && typeof field.checkValidity === 'function'
                && !field.disabled
                && !field.checkValidity()
            ));
        }

        function isVisibleDialog(dialog) {
            return dialog instanceof HTMLElement && !dialog.hidden && !dialog.classList.contains('hidden');
        }

        function setInlineReminderVisible(reminder, visible, shouldFocus = false) {
            if (!(reminder instanceof HTMLElement)) {
                return;
            }

            reminder.classList.toggle('hidden', !visible);
            reminder.setAttribute('aria-hidden', visible ? 'false' : 'true');

            if (visible && shouldFocus) {
                window.requestAnimationFrame(() => {
                    reminder.scrollIntoView({
                        behavior: prefersReducedMotion ? 'auto' : 'smooth',
                        block: 'center',
                    });
                    reminder.focus({ preventScroll: true });
                });
            }
        }

        function showAttestationDialog(dialog, checkbox, reminder = null) {
            if (!(dialog instanceof HTMLElement) || !(checkbox instanceof HTMLInputElement)) {
                return;
            }

            setInlineReminderVisible(reminder, false);
            dialog.hidden = false;
            dialog.classList.remove('hidden');
            dialog.classList.add('flex');
            checkbox.required = true;

            window.requestAnimationFrame(() => {
                checkbox.focus({ preventScroll: true });
            });
        }

        function hideAttestationDialog(dialog, checkbox, restoreFocusTarget = null) {
            if (!(dialog instanceof HTMLElement) || !(checkbox instanceof HTMLInputElement)) {
                return;
            }

            dialog.hidden = true;
            dialog.classList.add('hidden');
            dialog.classList.remove('flex');
            checkbox.required = false;

            if (restoreFocusTarget instanceof HTMLElement) {
                restoreFocusTarget.focus({ preventScroll: true });
            }
        }

        function setTracerSubmitState(form, submitting) {
            const submitButton = form.querySelector('[data-tracer-submit-button]');
            const submitLabel = form.querySelector('[data-tracer-submit-label]');

            if (!submitButton || !submitLabel) {
                return;
            }

            if (!submitButton.dataset.defaultLabel) {
                submitButton.dataset.defaultLabel = submitLabel.textContent.trim();
            }

            submitButton.disabled = submitting;
            submitButton.classList.toggle('opacity-80', submitting);
            submitButton.classList.toggle('cursor-wait', submitting);
            submitLabel.textContent = submitting ? 'Saving...' : submitButton.dataset.defaultLabel;
        }

        function toggleEmploymentFields(form) {
            const employmentTypeSelect = form.querySelector('#tracer_employment_type');
            const employedFields = form.querySelectorAll('[data-tracer-employed-field]');

            if (!employmentTypeSelect) {
                return;
            }

            const unemployed = employmentTypeSelect.value === 'Unemployed';

            employedFields.forEach((field) => {
                field.classList.toggle('opacity-60', unemployed);
                field.querySelectorAll('input, select, textarea').forEach((input) => {
                    input.disabled = unemployed;
                    if (unemployed) {
                        input.value = '';
                    }
                });
            });
        }

        function toggleEducationLevel(form) {
            const furtherEducationToggle = form.querySelector('#tracer_further_education');
            const educationLevelGroup = form.querySelector('#tracer_education_level_group');
            const educationLevelInput = form.querySelector('#tracer_education_level');

            if (!furtherEducationToggle || !educationLevelGroup || !educationLevelInput) {
                return;
            }

            const enabled = furtherEducationToggle.checked;
            educationLevelGroup.classList.toggle('opacity-60', !enabled);
            educationLevelInput.disabled = !enabled;
            educationLevelInput.required = enabled;

            if (!enabled) {
                educationLevelInput.value = '';
            }
        }

        function toggleFieldGroup(group, enabled) {
            if (!group) {
                return;
            }

            group.hidden = !enabled;
            group.classList.toggle('hidden', !enabled);
            group.classList.toggle('opacity-60', !enabled);

            group.querySelectorAll('input, select, textarea').forEach((input) => {
                input.disabled = !enabled;
            });
        }

        function setTracerRequiredByName(form, name, required) {
            form.querySelectorAll(`[name="${name}"]`).forEach((input) => {
                if (!(input instanceof HTMLElement)) {
                    return;
                }

                const shouldRequire = Boolean(required) && !input.matches(':disabled');
                input.required = shouldRequire;
                input.setAttribute('aria-required', shouldRequire ? 'true' : 'false');
            });
        }

        function selectedTracerValue(form, name) {
            const field = form.querySelector(`[name="${name}"]`);

            return field instanceof HTMLInputElement || field instanceof HTMLSelectElement || field instanceof HTMLTextAreaElement
                ? field.value
                : '';
        }

        function hasAnyCheckedTracerValue(form, name) {
            return Array.from(form.querySelectorAll(`input[name="${name}"]:checked`)).length > 0;
        }

        function isBlankTracerValue(value) {
            return normalizeTracerValue(value) === '';
        }

        function isTracerAnalyticsTargetMissing(form, targetKey) {
            const employmentType = selectedTracerValue(form, 'employment_type');
            const requiresCurrentWorkDetails = employmentType !== '' && employmentType !== 'Unemployed';
            const curriculumRelevant = checkedRadioValue(form, 'curriculum_relevant_to_first_job');
            const employedAfterGraduation = checkedRadioValue(form, 'employed_after_graduation');
            const presentlyEmployed = selectedTracerValue(form, 'presently_employed') === 'Yes';
            const hasEmployeeAwards = checkedRadioValue(form, 'has_employee_awards') === '1';
            const hasCurriculumSuggestions = checkedRadioValue(form, 'has_curriculum_suggestions') === '1';
            const hasEmployabilitySuggestions = checkedRadioValue(form, 'has_employability_suggestions') === '1';
            const hasFacilitySuggestions = checkedRadioValue(form, 'has_facility_suggestions') === '1';

            switch (targetKey) {
                case 'employment_type':
                    return isBlankTracerValue(employmentType);
                case 'current_position':
                case 'current_company':
                case 'monthly_income':
                case 'industry':
                    return requiresCurrentWorkDetails && isBlankTracerValue(selectedTracerValue(form, targetKey));
                case 'job_satisfaction':
                case 'skills_used':
                case 'presently_employed':
                case 'teaching_faculty_enthusiastic':
                case 'teaching_concepts_clear':
                case 'teaching_faculty_knowledgeable':
                case 'teaching_commitment':
                case 'degree_program_quality':
                case 'facilities_satisfaction':
                case 'overall_recommend_institution':
                case 'overall_objectives_met':
                case 'overall_university_experience':
                case 'challenges_faced':
                case 'recommendations':
                    return isBlankTracerValue(selectedTracerValue(form, targetKey));
                case 'curriculum_relevant_to_first_job':
                case 'employed_after_graduation':
                case 'has_curriculum_suggestions':
                case 'has_employability_suggestions':
                case 'has_facility_suggestions':
                    return isBlankTracerValue(checkedRadioValue(form, targetKey));
                case 'first_job_useful_competencies':
                    return curriculumRelevant === '1' && !hasAnyCheckedTracerValue(form, 'first_job_useful_competencies[]');
                case 'curriculum_not_relevant_reason':
                    return curriculumRelevant === '0' && isBlankTracerValue(selectedTracerValue(form, targetKey));
                case 'time_to_first_job':
                case 'first_job_related_to_course':
                case 'first_job_finding_method':
                case 'first_job_monthly_earnings':
                case 'first_job_duration':
                    return employedAfterGraduation === '1' && isBlankTracerValue(selectedTracerValue(form, targetKey));
                case 'present_employment_status':
                case 'job_level':
                case 'present_job_monthly_earnings':
                    return presentlyEmployed && isBlankTracerValue(selectedTracerValue(form, targetKey));
                case 'is_first_job_after_college':
                case 'has_employee_awards':
                    return presentlyEmployed && isBlankTracerValue(checkedRadioValue(form, targetKey));
                case 'present_job_acceptance_reasons':
                    return presentlyEmployed && !hasAnyCheckedTracerValue(form, 'present_job_acceptance_reasons[]');
                case 'employee_awards':
                    return presentlyEmployed && hasEmployeeAwards && isBlankTracerValue(selectedTracerValue(form, targetKey));
                case 'curriculum_suggestions':
                    return hasCurriculumSuggestions && isBlankTracerValue(selectedTracerValue(form, targetKey));
                case 'employability_suggestions':
                    return hasEmployabilitySuggestions && isBlankTracerValue(selectedTracerValue(form, targetKey));
                case 'facility_suggestions':
                    return hasFacilitySuggestions && isBlankTracerValue(selectedTracerValue(form, targetKey));
                default:
                    return false;
            }
        }

        function syncTracerAnalyticsReminder(form) {
            const reminder = document.querySelector('[data-tracer-analytics-reminder]');

            if (!(reminder instanceof HTMLElement) || !(form instanceof HTMLFormElement)) {
                return;
            }

            const chips = Array.from(reminder.querySelectorAll('[data-tracer-analytics-chip]'));
            const unresolvedChips = chips.filter((chip) => {
                if (!(chip instanceof HTMLElement)) {
                    return false;
                }

                const targetKey = chip.dataset.tracerAnalyticsTarget;
                const isMissing = targetKey
                    ? isTracerAnalyticsTargetMissing(form, targetKey)
                    : false;

                chip.dataset.tracerAnalyticsMissing = isMissing ? 'true' : 'false';
                chip.hidden = !isMissing;
                chip.classList.toggle('hidden', !isMissing);

                return isMissing;
            });
            const hasMissingItems = unresolvedChips.length > 0;
            reminder.hidden = !hasMissingItems;
            reminder.classList.toggle('hidden', !hasMissingItems);
            reminder.setAttribute('aria-hidden', hasMissingItems ? 'false' : 'true');
        }

        function syncTracerRequiredFields(form) {
            const employmentType = selectedTracerValue(form, 'employment_type');
            const requiresCurrentWorkDetails = employmentType !== '' && employmentType !== 'Unemployed';
            const curriculumRelevant = checkedRadioValue(form, 'curriculum_relevant_to_first_job');
            const employedAfterGraduation = checkedRadioValue(form, 'employed_after_graduation');
            const presentlyEmployed = selectedTracerValue(form, 'presently_employed') === 'Yes';
            const hasEmployeeAwards = checkedRadioValue(form, 'has_employee_awards') === '1';
            const hasCurriculumSuggestions = checkedRadioValue(form, 'has_curriculum_suggestions') === '1';
            const hasEmployabilitySuggestions = checkedRadioValue(form, 'has_employability_suggestions') === '1';
            const hasFacilitySuggestions = checkedRadioValue(form, 'has_facility_suggestions') === '1';

            ['current_position', 'current_company', 'monthly_income', 'industry'].forEach((name) => {
                setTracerRequiredByName(form, name, requiresCurrentWorkDetails);
            });

            [
                'job_satisfaction',
                'skills_used',
                'presently_employed',
                'teaching_faculty_enthusiastic',
                'teaching_concepts_clear',
                'teaching_faculty_knowledgeable',
                'teaching_commitment',
                'degree_program_quality',
                'facilities_satisfaction',
                'overall_recommend_institution',
                'overall_objectives_met',
                'overall_university_experience',
                'challenges_faced',
                'recommendations',
            ].forEach((name) => setTracerRequiredByName(form, name, true));

            setTracerRequiredByName(form, 'curriculum_relevant_to_first_job', true);
            setTracerRequiredByName(form, 'curriculum_not_relevant_reason', curriculumRelevant === '0');
            setTracerRequiredByName(form, 'employed_after_graduation', true);
            ['time_to_first_job', 'first_job_related_to_course', 'first_job_finding_method', 'first_job_monthly_earnings', 'first_job_duration'].forEach((name) => {
                setTracerRequiredByName(form, name, employedAfterGraduation === '1');
            });
            setTracerRequiredByName(form, 'time_to_first_job_other', employedAfterGraduation === '1' && selectedTracerValue(form, 'time_to_first_job') === 'Other');
            setTracerRequiredByName(form, 'first_job_finding_method_other', employedAfterGraduation === '1' && selectedTracerValue(form, 'first_job_finding_method') === 'Other');
            setTracerRequiredByName(form, 'first_job_duration_other', employedAfterGraduation === '1' && selectedTracerValue(form, 'first_job_duration') === 'Other');

            ['present_employment_status', 'is_first_job_after_college', 'job_level', 'present_job_monthly_earnings', 'has_employee_awards'].forEach((name) => {
                setTracerRequiredByName(form, name, presentlyEmployed);
            });
            setTracerRequiredByName(form, 'job_level_other', presentlyEmployed && selectedTracerValue(form, 'job_level') === 'Other');
            setTracerRequiredByName(form, 'employee_awards', presentlyEmployed && hasEmployeeAwards);

            setTracerRequiredByName(form, 'has_curriculum_suggestions', true);
            setTracerRequiredByName(form, 'curriculum_suggestions', hasCurriculumSuggestions);
            setTracerRequiredByName(form, 'has_employability_suggestions', true);
            setTracerRequiredByName(form, 'employability_suggestions', hasEmployabilitySuggestions);
            setTracerRequiredByName(form, 'has_facility_suggestions', true);
            setTracerRequiredByName(form, 'facility_suggestions', hasFacilitySuggestions);
        }

        function getMissingRequiredTracerSelection(form) {
            if (
                checkedRadioValue(form, 'curriculum_relevant_to_first_job') === '1'
                && !hasAnyCheckedTracerValue(form, 'first_job_useful_competencies[]')
            ) {
                return {
                    message: 'Please choose at least one useful competency for alumni analytics.',
                    selector: '#tracer_curriculum_yes_group',
                };
            }

            if (
                selectedTracerValue(form, 'presently_employed') === 'Yes'
                && !hasAnyCheckedTracerValue(form, 'present_job_acceptance_reasons[]')
            ) {
                return {
                    message: 'Please choose at least one reason for accepting the present job.',
                    selector: 'input[name="present_job_acceptance_reasons[]"]',
                };
            }

            return null;
        }

        function isVisibleForTracerJump(element) {
            return element instanceof HTMLElement
                && !element.closest('[hidden]')
                && element.getClientRects().length > 0;
        }

        function getTracerJumpAnchor(element) {
            return element?.closest?.('[data-tracer-field-anchor]') ?? element;
        }

        function resolveTracerJumpElement(form, targetKey) {
            const target = tracerJumpTargets[targetKey];
            if (!target) {
                return null;
            }

            const primary = form.querySelector(target.selector);
            const primaryAnchor = getTracerJumpAnchor(primary);
            const primaryAvailable = primary instanceof HTMLElement
                && isVisibleForTracerJump(primaryAnchor)
                && !primary.matches(':disabled');

            if (primaryAvailable) {
                return primary;
            }

            if (target.fallback) {
                const fallback = form.querySelector(target.fallback);
                if (fallback instanceof HTMLElement) {
                    return fallback;
                }
            }

            return primary instanceof HTMLElement ? primary : null;
        }

        function tracerTargetForValidationField(fieldName) {
            const normalized = String(fieldName || '')
                .replace(/\[\]$/, '')
                .replace(/\.\d+$/, '')
                .replace(/\.\*$/, '');

            if (Object.prototype.hasOwnProperty.call(tracerValidationErrorTargets, normalized)) {
                return tracerValidationErrorTargets[normalized];
            }

            return tracerJumpTargets[normalized] ? normalized : null;
        }

        function spotlightTracerField(element) {
            if (!(element instanceof HTMLElement)) {
                return;
            }

            const anchor = getTracerJumpAnchor(element);
            if (!(anchor instanceof HTMLElement)) {
                return;
            }

            const wizardStep = anchor.closest('[data-tracer-wizard-step]');
            const form = anchor.closest('form[data-tracer-entry-form]');
            if (wizardStep instanceof HTMLElement && form instanceof HTMLFormElement) {
                setTracerWizardStep(form, Number(wizardStep.dataset.tracerWizardStep), false);
            }

            anchor.scrollIntoView({
                behavior: prefersReducedMotion ? 'auto' : 'smooth',
                block: 'center',
            });

            anchor.classList.remove('tracer-field-jump-focus');
            void anchor.offsetWidth;
            anchor.classList.add('tracer-field-jump-focus');

            window.setTimeout(() => {
                anchor.classList.remove('tracer-field-jump-focus');
            }, prefersReducedMotion ? 200 : 1400);

            if (!element.matches(':disabled')) {
                window.setTimeout(() => {
                    element.focus({ preventScroll: true });
                }, prefersReducedMotion ? 0 : 180);
            }
        }

        function handleTracerSnapshotJump(event) {
            const trigger = event.currentTarget;
            const form = document.querySelector('form[data-tracer-entry-form]');
            const targetKey = trigger?.dataset?.tracerJumpTarget;

            if (!(form instanceof HTMLFormElement) || !targetKey) {
                return;
            }

            const targetElement = resolveTracerJumpElement(form, targetKey);
            spotlightTracerField(targetElement);
        }

        function setScholarshipEditorState(card, open, shouldFocus = true) {
            if (!(card instanceof HTMLElement)) {
                return;
            }

            const editor = card.querySelector('[data-scholarship-editor]');
            const toggleButton = card.querySelector('[data-scholarship-toggle]');
            const toggleLabel = card.querySelector('[data-scholarship-toggle-label]');

            if (!(editor instanceof HTMLElement) || !(toggleButton instanceof HTMLElement) || !(toggleLabel instanceof HTMLElement)) {
                return;
            }

            card.dataset.editorOpen = open ? 'true' : 'false';
            editor.hidden = !open;
            editor.classList.toggle('hidden', !open);
            toggleLabel.textContent = open
                ? (toggleButton.dataset.closeLabel || 'Close Editor')
                : (toggleButton.dataset.openLabel || 'Edit Scholarship Record');

            if (!open || !shouldFocus) {
                return;
            }

            const firstFocusableField = editor.querySelector('input:not([type="hidden"]):not([disabled]), select:not([disabled]), textarea:not([disabled])');
            if (firstFocusableField instanceof HTMLElement) {
                firstFocusableField.focus();
            }
        }

        function checkedRadioValue(form, name) {
            return form.querySelector(`input[name="${name}"]:checked`)?.value ?? '';
        }

        function hasCheckedCheckboxValue(form, name, value) {
            return Array.from(form.querySelectorAll(`input[name="${name}"]:checked`))
                .some((input) => input.value === value);
        }

        function syncCurriculumSection(form) {
            const selectedValue = checkedRadioValue(form, 'curriculum_relevant_to_first_job');
            const yesGroup = form.querySelector('#tracer_curriculum_yes_group');
            const noGroup = form.querySelector('#tracer_curriculum_no_group');
            const otherCompetencyGroup = form.querySelector('#tracer_first_job_competencies_other_group');
            const reasonSelect = form.querySelector('#tracer_curriculum_not_relevant_reason');
            const otherReasonGroup = form.querySelector('#tracer_curriculum_reason_other_group');

            toggleFieldGroup(yesGroup, selectedValue === '1');
            toggleFieldGroup(noGroup, selectedValue === '0');
            toggleFieldGroup(
                otherCompetencyGroup,
                selectedValue === '1' && hasCheckedCheckboxValue(form, 'first_job_useful_competencies[]', 'Other')
            );
            toggleFieldGroup(otherReasonGroup, selectedValue === '0' && reasonSelect?.value === 'Other');
        }

        function syncFirstJobSection(form) {
            const selectedValue = checkedRadioValue(form, 'employed_after_graduation');
            const detailsGroup = form.querySelector('#tracer_first_job_details_group');
            const timeSelect = form.querySelector('#tracer_time_to_first_job');
            const timeOtherGroup = form.querySelector('#tracer_time_to_first_job_other_group');
            const findingSelect = form.querySelector('#tracer_first_job_finding_method');
            const findingOtherGroup = form.querySelector('#tracer_first_job_finding_other_group');
            const durationSelect = form.querySelector('#tracer_first_job_duration');
            const durationOtherGroup = form.querySelector('#tracer_first_job_duration_other_group');
            const showDetails = selectedValue === '1';

            toggleFieldGroup(detailsGroup, showDetails);
            toggleFieldGroup(timeOtherGroup, showDetails && timeSelect?.value === 'Other');
            toggleFieldGroup(findingOtherGroup, showDetails && findingSelect?.value === 'Other');
            toggleFieldGroup(durationOtherGroup, showDetails && durationSelect?.value === 'Other');
        }

        function syncPresentEmploymentSection(form) {
            const presentlyEmployedSelect = form.querySelector('#tracer_presently_employed');
            const detailsGroup = form.querySelector('#tracer_present_employment_details_group');
            const jobLevelSelect = form.querySelector('#tracer_job_level');
            const jobLevelOtherGroup = form.querySelector('#tracer_job_level_other_group');
            const presentJobReasonOtherGroup = form.querySelector('#tracer_present_job_reason_other_group');
            const employeeAwardsGroup = form.querySelector('#tracer_employee_awards_group');
            const showDetails = presentlyEmployedSelect?.value === 'Yes';

            toggleFieldGroup(detailsGroup, showDetails);
            toggleFieldGroup(jobLevelOtherGroup, showDetails && jobLevelSelect?.value === 'Other');
            toggleFieldGroup(
                presentJobReasonOtherGroup,
                showDetails && hasCheckedCheckboxValue(form, 'present_job_acceptance_reasons[]', 'Other')
            );
            toggleFieldGroup(employeeAwardsGroup, showDetails && checkedRadioValue(form, 'has_employee_awards') === '1');
        }

        function syncSuggestionToggle(form, radioName, groupSelector) {
            toggleFieldGroup(
                form.querySelector(groupSelector),
                checkedRadioValue(form, radioName) === '1'
            );
        }

        function syncScholarshipForm(form) {
            const hasScholarship = checkedRadioValue(form, 'has_scholarship') === '1';
            const detailsGroup = form.querySelector('[data-scholarship-details]');
            const scholarshipNameSelect = form.querySelector('[data-scholarship-name]');
            const scholarshipOtherGroup = form.querySelector('[data-scholarship-other-group]');
            const scholarshipOtherInput = form.querySelector('input[name="scholarship_name_other"]');
            const scholarshipYearInput = form.querySelector('input[name="scholarship_year_awarded"]');
            const scholarshipHelpedInputs = form.querySelectorAll('input[name="scholarship_helped_get_job"]');

            toggleFieldGroup(detailsGroup, hasScholarship);
            toggleFieldGroup(scholarshipOtherGroup, hasScholarship && scholarshipNameSelect?.value === 'Other');

            if (scholarshipNameSelect instanceof HTMLSelectElement) {
                scholarshipNameSelect.required = hasScholarship;
            }

            if (scholarshipOtherInput instanceof HTMLInputElement) {
                scholarshipOtherInput.required = hasScholarship && scholarshipNameSelect?.value === 'Other';
            }

            if (scholarshipYearInput instanceof HTMLInputElement) {
                scholarshipYearInput.required = hasScholarship;
            }

            scholarshipHelpedInputs.forEach((input) => {
                input.required = hasScholarship;
            });
        }

        function spotlightEditMode(formShell) {
            if (!formShell || formShell.dataset.editing !== 'true') {
                return;
            }

            if (formShell.dataset.skipAutoscroll === 'true') {
                return;
            }

            window.setTimeout(() => {
                formShell.scrollIntoView({
                    behavior: prefersReducedMotion ? 'auto' : 'smooth',
                    block: 'start',
                });

                formShell.classList.remove('tracer-form-shell--focus');
                void formShell.offsetWidth;
                formShell.classList.add('tracer-form-shell--focus');

                window.setTimeout(() => {
                    formShell.classList.remove('tracer-form-shell--focus');
                }, prefersReducedMotion ? 200 : 1800);
                }, prefersReducedMotion ? 0 : 120);
        }

        function spotlightGuidedMode(formShell) {
            if (!formShell || formShell.dataset.guidedActive !== 'true') {
                return;
            }

            const firstFocusableField = formShell.querySelector('input:not([type="hidden"]):not([disabled]), select:not([disabled]), textarea:not([disabled])');

            window.setTimeout(() => {
                formShell.scrollIntoView({
                    behavior: prefersReducedMotion ? 'auto' : 'smooth',
                    block: 'start',
                });

                formShell.classList.remove('tracer-form-shell--focus');
                void formShell.offsetWidth;
                formShell.classList.add('tracer-form-shell--focus');

                if (firstFocusableField instanceof HTMLElement) {
                    window.setTimeout(() => {
                        firstFocusableField.focus({ preventScroll: true });
                    }, prefersReducedMotion ? 0 : 180);
                }

                window.setTimeout(() => {
                    formShell.classList.remove('tracer-form-shell--focus');
                }, prefersReducedMotion ? 200 : 1800);
            }, prefersReducedMotion ? 0 : 120);
        }

        async function handleTracerSubmit(event) {
            event.preventDefault();

            const form = event.currentTarget;
            const attestationDialog = form.querySelector('[data-tracer-attestation]');
            const attestationCheckbox = form.querySelector('[data-tracer-attestation-checkbox]');
            const noChangesReminder = form.querySelector('[data-tracer-no-changes-message]');
            if (attestationCheckbox instanceof HTMLInputElement) {
                attestationCheckbox.checked = true;
                attestationCheckbox.required = false;
            }
            hideAttestationDialog(attestationDialog, attestationCheckbox);
            syncTracerRequiredFields(form);
            syncTracerAnalyticsReminder(form);
            const invalidField = getFirstInvalidField(form);

            if (invalidField) {
                spotlightTracerField(invalidField);
                window.alert(invalidField.validity?.valueMissing
                    ? 'Please complete all required fields.'
                    : (invalidField.validationMessage || 'Please complete all required fields.'));
                return;
            }

            const missingRequiredSelection = getMissingRequiredTracerSelection(form);
            if (missingRequiredSelection) {
                const target = form.querySelector(missingRequiredSelection.selector);
                window.alert(missingRequiredSelection.message);

                if (target instanceof HTMLElement) {
                    spotlightTracerField(target);
                }

                return;
            }

            if (serializeTracerForm(form) === form.dataset.initialSnapshot) {
                hideAttestationDialog(attestationDialog, attestationCheckbox);
                setInlineReminderVisible(noChangesReminder, true, true);
                return;
            }

            setTracerSubmitState(form, true);

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: new FormData(form),
                    credentials: 'same-origin',
                });
                const data = await response.json().catch(() => ({}));

                if (!response.ok) {
                    const errorEntries = data?.errors && typeof data.errors === 'object'
                        ? Object.entries(data.errors)
                        : [];
                    const [firstErrorField, firstErrorMessages] = errorEntries[0] ?? [];
                    const message = Array.isArray(firstErrorMessages)
                        ? firstErrorMessages[0]
                        : (typeof firstErrorMessages === 'string' ? firstErrorMessages : data?.message);
                    const errorTarget = tracerTargetForValidationField(firstErrorField);

                    if (errorTarget) {
                        const targetElement = resolveTracerJumpElement(form, errorTarget);
                        spotlightTracerField(targetElement);
                    }

                    syncTracerRequiredFields(form);
                    syncTracerAnalyticsReminder(form);
                    window.alert(message || 'Unable to save tracer record right now.');
                    return;
                }

                if (typeof data?.page === 'string') {
                    const parsedPage = new DOMParser().parseFromString(data.page, 'text/html');
                    const nextRoot = parsedPage.querySelector('[data-tracer-page-root]');
                    const currentRoot = document.querySelector('[data-tracer-page-root]');

                    if (nextRoot && currentRoot) {
                        currentRoot.innerHTML = nextRoot.innerHTML;
                        window.history.replaceState({}, '', `${tracerIndexUrl}#tracer-form`);
                        initTracerPage();
                    }
                }

                window.alert(data?.message || 'Tracer record saved successfully.');
            } catch (error) {
                window.alert('Unable to save tracer record right now.');
            } finally {
                setTracerSubmitState(form, false);
            }
        }

        function handleScholarshipSubmit(event) {
            const form = event.currentTarget;
            const attestationDialog = form.querySelector('[data-scholarship-attestation]');
            const attestationCheckbox = form.querySelector('[data-scholarship-attestation-checkbox]');
            const noChangesReminder = form.querySelector('[data-scholarship-no-changes-message]');
            const submitButton = form.querySelector('[data-scholarship-submit-button]');
            const invalidField = getFirstInvalidField(form);

            if (invalidField) {
                event.preventDefault();
                invalidField.focus();
                window.alert(invalidField.validity?.valueMissing
                    ? 'Please complete all required fields.'
                    : (invalidField.validationMessage || 'Please complete all required fields.'));
                return;
            }

            if (serializeTracerForm(form) === form.dataset.initialSnapshot) {
                event.preventDefault();
                hideAttestationDialog(attestationDialog, attestationCheckbox, submitButton);
                setInlineReminderVisible(noChangesReminder, true, true);
                return;
            }

            if (!isVisibleDialog(attestationDialog)) {
                event.preventDefault();
                showAttestationDialog(attestationDialog, attestationCheckbox, noChangesReminder);
            }
        }

        async function confirmPortalAction(message, options = {}) {
            if (typeof window.portalConfirm === 'function') {
                return window.portalConfirm({
                    message,
                    ...options,
                });
            }

            return window.confirm(message);
        }

        async function handleTracerReset(event) {
            const trigger = event.currentTarget;
            const form = trigger?.closest('form[data-tracer-entry-form]');
            if (!(form instanceof HTMLFormElement)) {
                return;
            }

            if (serializeTracerForm(form) === form.dataset.initialSnapshot) {
                window.alert('The tracer form is already using the last loaded values.');
                return;
            }

            const confirmed = await confirmPortalAction('Reset all unsaved tracer changes and restore the last loaded values?', {
                title: 'Reset tracer form',
                confirmLabel: 'Reset Form',
                tone: 'danger',
            });
            if (!confirmed) {
                return;
            }

            form.reset();
            toggleEmploymentFields(form);
            toggleEducationLevel(form);
            syncCurriculumSection(form);
            syncFirstJobSection(form);
            syncPresentEmploymentSection(form);
            syncSuggestionToggle(form, 'has_curriculum_suggestions', '#tracer_curriculum_suggestions_group');
            syncSuggestionToggle(form, 'has_employability_suggestions', '#tracer_employability_suggestions_group');
            syncSuggestionToggle(form, 'has_facility_suggestions', '#tracer_facility_suggestions_group');
            syncTracerRequiredFields(form);
            setTracerWizardStep(form, 0, false);

            const firstFocusableField = form.querySelector('input:not([type="hidden"]):not([disabled]), select:not([disabled]), textarea:not([disabled])');
            if (firstFocusableField instanceof HTMLElement) {
                firstFocusableField.focus();
            }
        }

        async function handleScholarshipEditorToggle(event) {
            const trigger = event.currentTarget;
            const card = trigger?.closest('[data-scholarship-card]');
            const form = card?.querySelector('form[data-scholarship-form]');

            if (!(card instanceof HTMLElement) || !(form instanceof HTMLFormElement)) {
                return;
            }

            const shouldOpen = card.dataset.editorOpen !== 'true';

            if (!shouldOpen && serializeTracerForm(form) !== form.dataset.initialSnapshot) {
                const confirmed = await confirmPortalAction('Close the scholarship editor and discard unsaved changes?', {
                    title: 'Discard scholarship changes',
                    confirmLabel: 'Discard Changes',
                    tone: 'danger',
                });
                if (!confirmed) {
                    return;
                }

                form.reset();
                syncScholarshipForm(form);
            }

            setScholarshipEditorState(card, shouldOpen, shouldOpen);
        }

        async function handleScholarshipEditorCancel(event) {
            const trigger = event.currentTarget;
            const card = trigger?.closest('[data-scholarship-card]');
            const form = trigger?.closest('form[data-scholarship-form]');

            if (!(card instanceof HTMLElement) || !(form instanceof HTMLFormElement)) {
                return;
            }

            if (serializeTracerForm(form) !== form.dataset.initialSnapshot) {
                const confirmed = await confirmPortalAction('Discard unsaved scholarship changes?', {
                    title: 'Discard scholarship changes',
                    confirmLabel: 'Discard Changes',
                    tone: 'danger',
                });
                if (!confirmed) {
                    return;
                }
            }

            form.reset();
            syncScholarshipForm(form);
            setScholarshipEditorState(card, false, false);
        }

        const tracerWizardSteps = [
            { title: 'Education & first job', shortTitle: 'First job', icon: 'bx-book-open' },
            { title: 'Employment & career', shortTitle: 'Career', icon: 'bx-briefcase' },
            { title: 'Employment details', shortTitle: 'Details', icon: 'bx-building' },
            { title: 'University feedback', shortTitle: 'Feedback', icon: 'bx-message-square-detail' },
            { title: 'Review & submit', shortTitle: 'Review', icon: 'bx-check-shield' },
        ];

        function getTracerDirectCard(form, title) {
            return Array.from(form.children).find((child) => (
                child instanceof HTMLElement
                && child.querySelector('h3')?.textContent.trim() === title
            )) ?? null;
        }

        function createTracerWizard(form) {
            if (form._tracerWizard) {
                return form._tracerWizard;
            }

            const directChildren = Array.from(form.children);
            const firstJobCard = getTracerDirectCard(form, 'Employment Data');
            const employmentDetailsCard = getTracerDirectCard(form, 'Employment Status Details');
            const feedbackCard = getTracerDirectCard(form, 'Comments and Suggestions');

            if (!firstJobCard || !employmentDetailsCard || !feedbackCard) {
                return null;
            }

            const firstJobIndex = directChildren.indexOf(firstJobCard);
            const detailsIndex = directChildren.indexOf(employmentDetailsCard);
            const feedbackIndex = directChildren.indexOf(feedbackCard);
            const educationSummary = directChildren.find((child) => child.matches?.('[data-tracer-education-summary]')) ?? null;
            const isHiddenInput = (node) => node instanceof HTMLInputElement && node.type === 'hidden';
            const isWizardContent = (node) => !isHiddenInput(node);
            const educationAndFirstJob = [educationSummary, firstJobCard].filter(Boolean);
            const careerFields = directChildren
                .slice(0, firstJobIndex)
                .filter((node) => isWizardContent(node) && node !== educationSummary);
            const employmentDetails = [employmentDetailsCard];
            const universityFeedback = [feedbackCard];
            const reviewAndSubmit = directChildren
                .slice(feedbackIndex + 1)
                .filter(isWizardContent);

            const header = document.createElement('section');
            header.className = 'md:col-span-2 rounded-3xl border border-emerald-100 bg-gradient-to-br from-white via-emerald-50/50 to-white p-5 shadow-sm';
            header.dataset.tracerWizardHeader = '';

            const intro = document.createElement('div');
            intro.className = 'flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between';
            const introCopy = document.createElement('div');
            const heading = document.createElement('h3');
            heading.className = 'text-lg font-bold text-slate-900';
            heading.textContent = 'Graduate Tracer Survey';
            const description = document.createElement('p');
            description.className = 'mt-1 text-sm leading-6 text-slate-600';
            description.textContent = 'Tell us about your journey after graduating from CMU. Your answers are grouped into short sections, and you can review everything before submitting.';
            introCopy.append(heading, description);

            const progressLabel = document.createElement('span');
            progressLabel.className = 'shrink-0 rounded-full bg-white px-3 py-1.5 text-xs font-semibold text-emerald-800 ring-1 ring-emerald-100';
            progressLabel.dataset.tracerWizardProgressLabel = '';
            intro.append(introCopy, progressLabel);

            const progressTrack = document.createElement('div');
            progressTrack.className = 'mt-4 h-2 overflow-hidden rounded-full bg-slate-200';
            const progressFill = document.createElement('div');
            progressFill.className = 'h-full rounded-full bg-emerald-600 transition-all duration-300';
            progressFill.dataset.tracerWizardProgressFill = '';
            progressFill.style.width = '20%';
            progressTrack.append(progressFill);

            const stepNav = document.createElement('nav');
            stepNav.className = 'mt-4 grid grid-cols-2 gap-2 sm:grid-cols-5';
            stepNav.setAttribute('aria-label', 'Tracer survey sections');
            const stepPanels = [];
            const stepButtons = [];

            tracerWizardSteps.forEach((step, index) => {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'flex min-h-11 items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-left text-xs font-semibold text-slate-600 transition hover:border-emerald-300 hover:text-emerald-800 focus:outline-none focus:ring-2 focus:ring-emerald-200';
                button.dataset.tracerWizardJump = String(index);
                button.setAttribute('aria-label', `Go to section ${index + 1}: ${step.title}`);

                const number = document.createElement('span');
                number.className = 'inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-slate-100 text-slate-600';
                number.dataset.tracerWizardNumber = '';
                number.textContent = String(index + 1);
                const label = document.createElement('span');
                label.className = 'min-w-0 leading-tight';
                label.textContent = step.shortTitle;
                button.append(number, label);
                button.addEventListener('click', () => setTracerWizardStep(form, index, true));
                stepNav.append(button);
                stepButtons.push(button);

                const panel = document.createElement('section');
                panel.className = 'md:col-span-2 grid grid-cols-1 gap-4 md:grid-cols-2';
                panel.dataset.tracerWizardStep = String(index);
                panel.setAttribute('aria-label', `Section ${index + 1}: ${step.title}`);
                stepPanels.push(panel);
            });

            const draftArea = document.createElement('div');
            draftArea.className = 'mt-4 flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white/90 p-4 sm:flex-row sm:items-center sm:justify-between';
            draftArea.dataset.tracerDraftArea = '';
            const draftCopy = document.createElement('div');
            draftCopy.className = 'min-w-0';
            const draftConsentLabel = document.createElement('label');
            draftConsentLabel.className = 'flex items-start gap-2 text-sm font-medium text-slate-800';
            const draftConsent = document.createElement('input');
            draftConsent.type = 'checkbox';
            draftConsent.className = 'mt-0.5 h-4 w-4 shrink-0 rounded border-slate-300 text-emerald-700 focus:ring-emerald-500';
            draftConsent.dataset.tracerDraftConsent = '';
            const consentCopy = document.createElement('span');
            consentCopy.textContent = 'Allow this survey to be saved as an encrypted draft in my account.';
            draftConsentLabel.append(draftConsent, consentCopy);
            const draftStatus = document.createElement('p');
            draftStatus.className = 'mt-1 pl-6 text-xs leading-5 text-slate-500';
            draftStatus.dataset.tracerDraftStatus = '';
            draftStatus.setAttribute('role', 'status');
            draftStatus.textContent = 'Drafts are private to your account and are removed when you submit or delete them.';
            draftCopy.append(draftConsentLabel, draftStatus);

            const draftActions = document.createElement('div');
            draftActions.className = 'flex shrink-0 flex-wrap gap-2';
            const saveDraftButton = document.createElement('button');
            saveDraftButton.type = 'button';
            saveDraftButton.className = 'inline-flex items-center justify-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm font-semibold text-emerald-800 transition hover:bg-emerald-100';
            saveDraftButton.dataset.tracerDraftSave = '';
            saveDraftButton.innerHTML = '<i class="bx bx-save" aria-hidden="true"></i><span>Save draft & exit</span>';
            const deleteDraftButton = document.createElement('button');
            deleteDraftButton.type = 'button';
            deleteDraftButton.className = 'inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-600 transition hover:border-rose-200 hover:bg-rose-50 hover:text-rose-700';
            deleteDraftButton.dataset.tracerDraftDelete = '';
            deleteDraftButton.hidden = true;
            deleteDraftButton.textContent = 'Delete saved draft';
            draftActions.append(saveDraftButton, deleteDraftButton);
            draftArea.append(draftCopy, draftActions);
            if (form.dataset.tracerDraftEnabled !== 'true') {
                draftArea.hidden = true;
            }
            header.append(intro, progressTrack, stepNav, draftArea);

            const footer = document.createElement('div');
            footer.className = 'md:col-span-2 flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 pt-4';
            footer.dataset.tracerWizardFooter = '';
            const previousButton = document.createElement('button');
            previousButton.type = 'button';
            previousButton.className = 'inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:border-slate-400 hover:bg-slate-50';
            previousButton.dataset.tracerWizardPrevious = '';
            previousButton.innerHTML = '<i class="bx bx-left-arrow-alt text-lg" aria-hidden="true"></i><span>Previous section</span>';
            previousButton.addEventListener('click', () => setTracerWizardStep(form, Number(form.dataset.tracerWizardCurrent || 0) - 1, true));
            const nextButton = document.createElement('button');
            nextButton.type = 'button';
            nextButton.className = 'inline-flex items-center gap-2 rounded-xl bg-emerald-700 px-5 py-3 text-sm font-semibold text-white transition hover:bg-emerald-800 focus:outline-none focus:ring-2 focus:ring-emerald-300';
            nextButton.dataset.tracerWizardNext = '';
            nextButton.innerHTML = '<span>Continue</span><i class="bx bx-right-arrow-alt text-lg" aria-hidden="true"></i>';
            nextButton.addEventListener('click', () => setTracerWizardStep(form, Number(form.dataset.tracerWizardCurrent || 0) + 1, true));
            footer.append(previousButton, nextButton);

            const reviewPanel = document.createElement('div');
            reviewPanel.className = 'md:col-span-2 rounded-2xl border border-emerald-100 bg-white p-5';
            reviewPanel.dataset.tracerReviewSummary = '';
            reviewPanel.innerHTML = '<h3 class="text-base font-semibold text-slate-900">Review your answers</h3><p class="mt-1 text-sm text-slate-600">Check your responses before submitting. Use the section buttons above to make changes.</p><div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2" data-tracer-review-items></div>';
            stepPanels[4].prepend(reviewPanel);

            const groups = [educationAndFirstJob, careerFields, employmentDetails, universityFeedback, reviewAndSubmit];
            groups.forEach((nodes, index) => {
                nodes.forEach((node) => stepPanels[index].append(node));
            });

            form.append(header, ...stepPanels, footer);
            const wizard = {
                header,
                panels: stepPanels,
                buttons: stepButtons,
                title: introCopy.querySelector('h3'),
                progressLabel,
                progressFill,
                previousButton,
                nextButton,
                draftConsent,
                draftStatus,
                saveDraftButton,
                deleteDraftButton,
                reviewItems: reviewPanel.querySelector('[data-tracer-review-items]'),
                draftSaved: false,
                saveTimer: null,
                saving: false,
            };
            form._tracerWizard = wizard;

            setTracerWizardStep(form, 0, false);
            saveDraftButton.addEventListener('click', () => saveTracerDraft(form, true));
            deleteDraftButton.addEventListener('click', () => deleteTracerDraft(form));
            draftConsent.addEventListener('change', () => {
                if (draftConsent.checked) {
                    queueTracerDraftSave(form);
                }
            });

            return wizard;
        }

        function setTracerWizardStep(form, index, shouldFocus = false) {
            const wizard = form?._tracerWizard;
            if (!wizard) {
                return;
            }

            const boundedIndex = Math.max(0, Math.min(tracerWizardSteps.length - 1, Number(index) || 0));
            form.dataset.tracerWizardCurrent = String(boundedIndex);
            wizard.panels.forEach((panel, panelIndex) => {
                const active = panelIndex === boundedIndex;
                panel.hidden = !active;
                panel.classList.toggle('hidden', !active);
            });
            wizard.buttons.forEach((button, buttonIndex) => {
                const active = buttonIndex === boundedIndex;
                button.setAttribute('aria-current', active ? 'step' : 'false');
                button.classList.toggle('border-emerald-300', active);
                button.classList.toggle('bg-emerald-50', active);
                button.classList.toggle('text-emerald-900', active);
                const number = button.querySelector('[data-tracer-wizard-number]');
                number?.classList.toggle('bg-emerald-700', active);
                number?.classList.toggle('text-white', active);
                number?.classList.toggle('bg-slate-100', !active);
                number?.classList.toggle('text-slate-600', !active);
            });
            wizard.title.textContent = tracerWizardSteps[boundedIndex].title;
            wizard.progressLabel.textContent = `Step ${boundedIndex + 1} of ${tracerWizardSteps.length}`;
            wizard.progressFill.style.width = `${((boundedIndex + 1) / tracerWizardSteps.length) * 100}%`;
            wizard.previousButton.hidden = boundedIndex === 0;
            wizard.nextButton.hidden = boundedIndex === tracerWizardSteps.length - 1;
            if (boundedIndex === tracerWizardSteps.length - 1) {
                updateTracerReviewSummary(form);
            }
            updateTracerWizardCompletion(form);
            queueTracerDraftSave(form);

            if (shouldFocus) {
                wizard.header.scrollIntoView({ behavior: prefersReducedMotion ? 'auto' : 'smooth', block: 'start' });
            }
        }

        function updateTracerWizardCompletion(form) {
            const wizard = form?._tracerWizard;
            if (!wizard) {
                return;
            }

            wizard.panels.forEach((panel, index) => {
                const requiredFields = Array.from(panel.querySelectorAll('input:required, select:required, textarea:required'))
                    .filter((field) => !field.disabled && !field.closest('[hidden]'));
                const hasAnswers = Array.from(panel.querySelectorAll('input:not([type="hidden"]):not([type="file"]), select, textarea'))
                    .some((field) => field instanceof HTMLInputElement && ['checkbox', 'radio'].includes(field.type)
                        ? field.checked
                        : String(field.value ?? '').trim() !== '');
                const complete = requiredFields.length > 0 && requiredFields.every((field) => field.checkValidity());
                const status = complete ? 'Ready' : (hasAnswers ? 'In progress' : 'Not started');
                const button = wizard.buttons[index];
                const label = button?.querySelector('span:last-child');
                if (label) {
                    label.textContent = `${tracerWizardSteps[index].shortTitle} · ${status}`;
                }
                button?.setAttribute('data-tracer-step-status', status.toLowerCase().replaceAll(' ', '-'));
            });
        }

        function updateTracerReviewSummary(form) {
            const wizard = form?._tracerWizard;
            if (!wizard?.reviewItems) {
                return;
            }

            wizard.reviewItems.replaceChildren();
            const ignored = new Set(['_token', 'record_id', 'contact_permission', 'information_attestation', 'data_privacy_consent', 'employment_proof']);
            const controls = Array.from(form.elements).filter((field) => (
                field instanceof HTMLElement
                && field.name
                && !ignored.has(field.name.replace(/\[\]$/, ''))
                && !(field instanceof HTMLInputElement && ['hidden', 'file', 'submit', 'button'].includes(field.type))
                && !field.closest('[hidden]')
            ));
            const visited = new Set();

            controls.forEach((field) => {
                const key = field.name;
                if (visited.has(key)) {
                    return;
                }
                visited.add(key);
                const sameName = controls.filter((candidate) => candidate.name === key);
                let answer = '';
                if (sameName.every((candidate) => candidate instanceof HTMLInputElement && candidate.type === 'radio')) {
                    const selected = sameName.find((candidate) => candidate.checked);
                    answer = selected?.closest('label')?.textContent.trim() || selected?.value || '';
                } else if (sameName.every((candidate) => candidate instanceof HTMLInputElement && candidate.type === 'checkbox')) {
                    answer = sameName.filter((candidate) => candidate.checked)
                        .map((candidate) => candidate.closest('label')?.textContent.trim() || candidate.value)
                        .join(', ');
                } else if (field instanceof HTMLSelectElement) {
                    answer = field.value ? (field.selectedOptions[0]?.textContent.trim() ?? '') : '';
                } else if (field instanceof HTMLInputElement && field.type === 'checkbox') {
                    answer = field.checked ? (field.closest('label')?.textContent.trim() || 'Yes') : '';
                } else {
                    answer = String(field.value ?? '').trim();
                }

                if (!answer) {
                    return;
                }

                const anchor = field.closest('[data-tracer-field-anchor]');
                const label = anchor?.querySelector('label, p')?.textContent.trim()
                    || field.closest('label')?.textContent.trim()
                    || key.replace(/\[\]$/, '').replaceAll('_', ' ');
                const item = document.createElement('div');
                item.className = 'rounded-xl border border-slate-200 bg-slate-50 px-3 py-3';
                const labelNode = document.createElement('p');
                labelNode.className = 'text-xs font-semibold uppercase tracking-wide text-slate-500';
                labelNode.textContent = label;
                const answerNode = document.createElement('p');
                answerNode.className = 'mt-1 whitespace-pre-wrap break-words text-sm text-slate-800';
                answerNode.textContent = answer;
                item.append(labelNode, answerNode);
                wizard.reviewItems.append(item);
            });

            if (!wizard.reviewItems.children.length) {
                const empty = document.createElement('p');
                empty.className = 'text-sm text-slate-500';
                empty.textContent = 'Your answers will appear here as you complete the survey.';
                wizard.reviewItems.append(empty);
            }
        }

        function collectTracerDraftData(form) {
            const ignored = new Set(['_token', 'record_id', 'contact_permission', 'information_attestation', 'data_privacy_consent', 'employment_proof']);
            const grouped = new Map();
            Array.from(form.elements).forEach((field) => {
                if (!(field instanceof HTMLElement) || !field.name || ignored.has(field.name.replace(/\[\]$/, ''))) {
                    return;
                }
                if (field instanceof HTMLInputElement && ['hidden', 'file', 'submit', 'button'].includes(field.type)) {
                    return;
                }
                const group = grouped.get(field.name) ?? [];
                group.push(field);
                grouped.set(field.name, group);
            });

            const data = {};
            grouped.forEach((fields, name) => {
                if (fields.every((field) => field instanceof HTMLInputElement && field.type === 'radio')) {
                    data[name] = fields.find((field) => field.checked)?.value ?? '';
                } else if (fields.every((field) => field instanceof HTMLInputElement && field.type === 'checkbox')) {
                    data[name] = fields.filter((field) => field.checked).map((field) => field.value);
                } else if (fields[0] instanceof HTMLSelectElement && fields[0].multiple) {
                    data[name] = Array.from(fields[0].selectedOptions).map((option) => option.value);
                } else {
                    data[name] = fields[0].value ?? '';
                }
            });

            return data;
        }

        function restoreTracerDraftData(form, data) {
            if (!data || typeof data !== 'object') {
                return;
            }

            Object.entries(data).forEach(([name, savedValue]) => {
                const fields = Array.from(form.elements).filter((field) => field instanceof HTMLElement && field.name === name);
                if (!fields.length) {
                    return;
                }
                const values = Array.isArray(savedValue) ? savedValue.map(String) : null;

                if (fields.every((field) => field instanceof HTMLInputElement && field.type === 'radio')) {
                    fields.forEach((field) => {
                        field.checked = String(field.value) === String(savedValue);
                        field.defaultChecked = field.checked;
                    });
                } else if (fields.every((field) => field instanceof HTMLInputElement && field.type === 'checkbox')) {
                    const selectedValues = values ?? (savedValue ? [String(savedValue)] : []);
                    fields.forEach((field) => {
                        field.checked = selectedValues.includes(String(field.value));
                        field.defaultChecked = field.checked;
                    });
                } else if (fields[0] instanceof HTMLSelectElement && fields[0].multiple) {
                    const selectedValues = values ?? [String(savedValue)];
                    Array.from(fields[0].options).forEach((option) => {
                        option.selected = selectedValues.includes(option.value);
                        option.defaultSelected = option.selected;
                    });
                } else if (fields[0] instanceof HTMLInputElement || fields[0] instanceof HTMLTextAreaElement || fields[0] instanceof HTMLSelectElement) {
                    const value = Array.isArray(savedValue) ? (savedValue[0] ?? '') : String(savedValue ?? '');
                    fields[0].value = value;
                    if (fields[0] instanceof HTMLSelectElement) {
                        Array.from(fields[0].options).forEach((option) => { option.defaultSelected = option.selected; });
                    } else {
                        fields[0].defaultValue = value;
                    }
                }
            });
        }

        function tracerDraftHeaders(form) {
            return {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': form.querySelector('input[name="_token"]')?.value ?? '',
            };
        }

        async function loadTracerDraft(form) {
            const wizard = form?._tracerWizard;
            if (!wizard || form.dataset.tracerDraftEnabled !== 'true') {
                return;
            }

            try {
                const response = await fetch(form.dataset.tracerDraftUrl, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });
                const result = await response.json().catch(() => ({}));
                if (!response.ok) {
                    if (response.status === 503) {
                        wizard.draftStatus.textContent = result?.message || 'Secure draft saving is waiting for the database update.';
                    }
                    return;
                }

                const draft = result?.draft;
                if (!draft || !draft.consented) {
                    return;
                }

                restoreTracerDraftData(form, draft.data);
                wizard.draftConsent.checked = true;
                wizard.draftConsent.defaultChecked = true;
                wizard.draftSaved = true;
                wizard.deleteDraftButton.hidden = false;
                toggleEmploymentFields(form);
                toggleEducationLevel(form);
                syncCurriculumSection(form);
                syncFirstJobSection(form);
                syncPresentEmploymentSection(form);
                syncSuggestionToggle(form, 'has_curriculum_suggestions', '#tracer_curriculum_suggestions_group');
                syncSuggestionToggle(form, 'has_employability_suggestions', '#tracer_employability_suggestions_group');
                syncSuggestionToggle(form, 'has_facility_suggestions', '#tracer_facility_suggestions_group');
                syncTracerRequiredFields(form);
                form.dataset.initialSnapshot = serializeTracerForm(form);
                setTracerWizardStep(form, Number(draft.step) - 1, false);
                const savedLabel = draft.saved_at ? new Date(draft.saved_at).toLocaleString() : 'recently';
                wizard.draftStatus.textContent = `Draft restored from ${savedLabel}. Your selected employment PDF must be attached again before submitting.`;
                updateTracerReviewSummary(form);
            } catch (error) {
                wizard.draftStatus.textContent = 'Could not load your saved draft. Your existing tracer answers are still available.';
            }
        }

        function queueTracerDraftSave(form) {
            const wizard = form?._tracerWizard;
            if (!wizard || form.dataset.tracerDraftEnabled !== 'true' || !wizard.draftConsent.checked) {
                return;
            }

            window.clearTimeout(wizard.saveTimer);
            wizard.draftStatus.textContent = 'Saving your draft…';
            wizard.saveTimer = window.setTimeout(() => saveTracerDraft(form, false), 900);
        }

        async function saveTracerDraft(form, andExit) {
            const wizard = form?._tracerWizard;
            if (!wizard || !wizard.draftConsent.checked) {
                if (wizard) {
                    wizard.draftStatus.textContent = 'Allow encrypted draft saving above before saving or leaving this draft.';
                }
                return;
            }
            if (wizard.saving) {
                return;
            }

            window.clearTimeout(wizard.saveTimer);
            wizard.saving = true;
            wizard.saveDraftButton.disabled = true;
            wizard.saveDraftButton.classList.add('opacity-60');

            try {
                const response = await fetch(form.dataset.tracerDraftSaveUrl, {
                    method: 'PUT',
                    headers: tracerDraftHeaders(form),
                    credentials: 'same-origin',
                    body: JSON.stringify({
                        consent: true,
                        step: Number(form.dataset.tracerWizardCurrent || 0) + 1,
                        data: collectTracerDraftData(form),
                    }),
                });
                const result = await response.json().catch(() => ({}));
                if (!response.ok) {
                    throw new Error(result?.message || 'Could not save your draft. Please try again.');
                }

                wizard.draftSaved = true;
                wizard.deleteDraftButton.hidden = false;
                wizard.draftStatus.textContent = `Draft saved at ${new Date(result.saved_at || Date.now()).toLocaleTimeString()}. Your employment PDF is not stored in the draft.`;
                if (andExit) {
                    window.location.assign(form.dataset.tracerDashboardUrl);
                }
            } catch (error) {
                wizard.draftStatus.textContent = error instanceof Error ? error.message : 'Could not save your draft. Please try again.';
            } finally {
                wizard.saving = false;
                wizard.saveDraftButton.disabled = false;
                wizard.saveDraftButton.classList.remove('opacity-60');
            }
        }

        async function deleteTracerDraft(form) {
            const wizard = form?._tracerWizard;
            if (!wizard || !wizard.draftSaved) {
                return;
            }

            try {
                const response = await fetch(form.dataset.tracerDraftUrl, {
                    method: 'DELETE',
                    headers: tracerDraftHeaders(form),
                    credentials: 'same-origin',
                });
                const result = await response.json().catch(() => ({}));
                if (!response.ok) {
                    throw new Error(result?.message || 'Could not delete the saved draft.');
                }
                wizard.draftSaved = false;
                wizard.deleteDraftButton.hidden = true;
                wizard.draftConsent.checked = false;
                wizard.draftStatus.textContent = result?.message || 'Saved draft deleted.';
            } catch (error) {
                wizard.draftStatus.textContent = error instanceof Error ? error.message : 'Could not delete the saved draft.';
            }
        }

        function initTracerPage() {
            const formShell = document.querySelector('[data-tracer-form]');
            const form = document.querySelector('[data-tracer-entry-form]');
            const scholarshipCard = document.querySelector('[data-scholarship-card]');
            const scholarshipForm = scholarshipCard?.querySelector('form[data-scholarship-form]') ?? null;

            if (!formShell || !form) {
                return;
            }

            createTracerWizard(form);

            document.querySelectorAll('[data-tracer-jump-target]').forEach((trigger) => {
                if (!(trigger instanceof HTMLElement) || trigger.dataset.tracerJumpBound === 'true') {
                    return;
                }

                trigger.addEventListener('click', handleTracerSnapshotJump);
                trigger.dataset.tracerJumpBound = 'true';
            });

            if (!form.dataset.tracerJsBound) {
                const employmentTypeSelect = form.querySelector('#tracer_employment_type');
                const furtherEducationToggle = form.querySelector('#tracer_further_education');
                const curriculumRadios = form.querySelectorAll('input[name="curriculum_relevant_to_first_job"]');
                const curriculumCompetencies = form.querySelectorAll('input[name="first_job_useful_competencies[]"]');
                const curriculumReasonSelect = form.querySelector('#tracer_curriculum_not_relevant_reason');
                const firstJobRadios = form.querySelectorAll('input[name="employed_after_graduation"]');
                const firstJobTimeSelect = form.querySelector('#tracer_time_to_first_job');
                const firstJobFindingSelect = form.querySelector('#tracer_first_job_finding_method');
                const firstJobDurationSelect = form.querySelector('#tracer_first_job_duration');
                const presentlyEmployedSelect = form.querySelector('#tracer_presently_employed');
                const jobLevelSelect = form.querySelector('#tracer_job_level');
                const presentJobReasonInputs = form.querySelectorAll('input[name="present_job_acceptance_reasons[]"]');
                const employeeAwardsRadios = form.querySelectorAll('input[name="has_employee_awards"]');
                const curriculumSuggestionRadios = form.querySelectorAll('input[name="has_curriculum_suggestions"]');
                const employabilitySuggestionRadios = form.querySelectorAll('input[name="has_employability_suggestions"]');
                const facilitySuggestionRadios = form.querySelectorAll('input[name="has_facility_suggestions"]');
                const resetButton = form.querySelector('[data-tracer-reset-button]');
                const attestationDialog = form.querySelector('[data-tracer-attestation]');
                const attestationCheckbox = form.querySelector('[data-tracer-attestation-checkbox]');
                const attestationCloseControls = form.querySelectorAll('[data-tracer-attestation-close]');
                const noChangesReminder = form.querySelector('[data-tracer-no-changes-message]');
                const submitButton = form.querySelector('[data-tracer-submit-button]');

                if (employmentTypeSelect) {
                    employmentTypeSelect.addEventListener('change', () => {
                        toggleEmploymentFields(form);
                    });
                }

                if (furtherEducationToggle) {
                    furtherEducationToggle.addEventListener('change', () => {
                        toggleEducationLevel(form);
                    });
                }

                curriculumRadios.forEach((input) => input.addEventListener('change', () => syncCurriculumSection(form)));
                curriculumCompetencies.forEach((input) => input.addEventListener('change', () => syncCurriculumSection(form)));
                curriculumReasonSelect?.addEventListener('change', () => syncCurriculumSection(form));

                firstJobRadios.forEach((input) => input.addEventListener('change', () => syncFirstJobSection(form)));
                firstJobTimeSelect?.addEventListener('change', () => syncFirstJobSection(form));
                firstJobFindingSelect?.addEventListener('change', () => syncFirstJobSection(form));
                firstJobDurationSelect?.addEventListener('change', () => syncFirstJobSection(form));

                presentlyEmployedSelect?.addEventListener('change', () => syncPresentEmploymentSection(form));
                jobLevelSelect?.addEventListener('change', () => syncPresentEmploymentSection(form));
                presentJobReasonInputs.forEach((input) => input.addEventListener('change', () => syncPresentEmploymentSection(form)));
                employeeAwardsRadios.forEach((input) => input.addEventListener('change', () => syncPresentEmploymentSection(form)));

                curriculumSuggestionRadios.forEach((input) => input.addEventListener('change', () => syncSuggestionToggle(form, 'has_curriculum_suggestions', '#tracer_curriculum_suggestions_group')));
                employabilitySuggestionRadios.forEach((input) => input.addEventListener('change', () => syncSuggestionToggle(form, 'has_employability_suggestions', '#tracer_employability_suggestions_group')));
                facilitySuggestionRadios.forEach((input) => input.addEventListener('change', () => syncSuggestionToggle(form, 'has_facility_suggestions', '#tracer_facility_suggestions_group')));

                form.addEventListener('submit', handleTracerSubmit);
                form.addEventListener('input', () => {
                    syncTracerAnalyticsReminder(form);
                    queueTracerDraftSave(form);
                    updateTracerReviewSummary(form);
                    updateTracerWizardCompletion(form);
                    if (serializeTracerForm(form) !== form.dataset.initialSnapshot) {
                        setInlineReminderVisible(noChangesReminder, false);
                    }
                });
                form.addEventListener('change', () => {
                    syncTracerRequiredFields(form);
                    syncTracerAnalyticsReminder(form);
                    queueTracerDraftSave(form);
                    updateTracerReviewSummary(form);
                    updateTracerWizardCompletion(form);
                    if (serializeTracerForm(form) !== form.dataset.initialSnapshot) {
                        setInlineReminderVisible(noChangesReminder, false);
                    }
                });
                attestationCloseControls.forEach((control) => {
                    control.addEventListener('click', () => hideAttestationDialog(attestationDialog, attestationCheckbox, submitButton));
                });
                resetButton?.addEventListener('click', handleTracerReset);
                form.addEventListener('reset', () => window.setTimeout(() => {
                    queueTracerDraftSave(form);
                    updateTracerReviewSummary(form);
                    updateTracerWizardCompletion(form);
                }, 0));
                form.dataset.tracerJsBound = 'true';
            }

            if (scholarshipForm && !scholarshipForm.dataset.scholarshipJsBound) {
                const scholarshipToggleButton = scholarshipCard?.querySelector('[data-scholarship-toggle]');
                const scholarshipCancelButton = scholarshipForm.querySelector('[data-scholarship-cancel-button]');
                const scholarshipChoiceInputs = scholarshipForm.querySelectorAll('input[name="has_scholarship"]');
                const scholarshipNameSelect = scholarshipForm.querySelector('[data-scholarship-name]');
                const scholarshipAttestationDialog = scholarshipForm.querySelector('[data-scholarship-attestation]');
                const scholarshipAttestationCheckbox = scholarshipForm.querySelector('[data-scholarship-attestation-checkbox]');
                const scholarshipAttestationCloseControls = scholarshipForm.querySelectorAll('[data-scholarship-attestation-close]');
                const scholarshipNoChangesReminder = scholarshipForm.querySelector('[data-scholarship-no-changes-message]');
                const scholarshipSubmitButton = scholarshipForm.querySelector('[data-scholarship-submit-button]');

                scholarshipToggleButton?.addEventListener('click', handleScholarshipEditorToggle);
                scholarshipCancelButton?.addEventListener('click', handleScholarshipEditorCancel);
                scholarshipChoiceInputs.forEach((input) => input.addEventListener('change', () => syncScholarshipForm(scholarshipForm)));
                scholarshipNameSelect?.addEventListener('change', () => syncScholarshipForm(scholarshipForm));
                scholarshipForm.addEventListener('submit', handleScholarshipSubmit);
                scholarshipForm.addEventListener('input', () => {
                    if (serializeTracerForm(scholarshipForm) !== scholarshipForm.dataset.initialSnapshot) {
                        setInlineReminderVisible(scholarshipNoChangesReminder, false);
                    }
                });
                scholarshipForm.addEventListener('change', () => {
                    if (serializeTracerForm(scholarshipForm) !== scholarshipForm.dataset.initialSnapshot) {
                        setInlineReminderVisible(scholarshipNoChangesReminder, false);
                    }
                });
                scholarshipAttestationCloseControls.forEach((control) => {
                    control.addEventListener('click', () => hideAttestationDialog(scholarshipAttestationDialog, scholarshipAttestationCheckbox, scholarshipSubmitButton));
                });

                scholarshipForm.dataset.scholarshipJsBound = 'true';
            }

            toggleEmploymentFields(form);
            toggleEducationLevel(form);
            syncCurriculumSection(form);
            syncFirstJobSection(form);
            syncPresentEmploymentSection(form);
            syncSuggestionToggle(form, 'has_curriculum_suggestions', '#tracer_curriculum_suggestions_group');
            syncSuggestionToggle(form, 'has_employability_suggestions', '#tracer_employability_suggestions_group');
            syncSuggestionToggle(form, 'has_facility_suggestions', '#tracer_facility_suggestions_group');
            syncTracerRequiredFields(form);
            syncTracerAnalyticsReminder(form);
            form.dataset.initialSnapshot = serializeTracerForm(form);
            updateTracerReviewSummary(form);
            updateTracerWizardCompletion(form);
            if (!form.dataset.tracerDraftLoadStarted) {
                form.dataset.tracerDraftLoadStarted = 'true';
                loadTracerDraft(form);
            }
            if (isVisibleDialog(form.querySelector('[data-tracer-attestation]'))) {
                showAttestationDialog(
                    form.querySelector('[data-tracer-attestation]'),
                    form.querySelector('[data-tracer-attestation-checkbox]'),
                    form.querySelector('[data-tracer-no-changes-message]')
                );
            }
            if (scholarshipCard && scholarshipForm) {
                syncScholarshipForm(scholarshipForm);
                scholarshipForm.dataset.initialSnapshot = serializeTracerForm(scholarshipForm);
                setScholarshipEditorState(scholarshipCard, scholarshipCard.dataset.editorOpen === 'true', false);
                if (isVisibleDialog(scholarshipForm.querySelector('[data-scholarship-attestation]'))) {
                    showAttestationDialog(
                        scholarshipForm.querySelector('[data-scholarship-attestation]'),
                        scholarshipForm.querySelector('[data-scholarship-attestation-checkbox]'),
                        scholarshipForm.querySelector('[data-scholarship-no-changes-message]')
                    );
                }
            }
            spotlightEditMode(formShell);
            spotlightGuidedMode(formShell);
        }

        document.addEventListener('keydown', (event) => {
            if (event.key !== 'Escape') {
                return;
            }

            const tracerForm = document.querySelector('form[data-tracer-entry-form]');
            const scholarshipForm = document.querySelector('form[data-scholarship-form]');

            if (tracerForm instanceof HTMLFormElement) {
                const tracerDialog = tracerForm.querySelector('[data-tracer-attestation]');
                if (isVisibleDialog(tracerDialog)) {
                    hideAttestationDialog(
                        tracerDialog,
                        tracerForm.querySelector('[data-tracer-attestation-checkbox]'),
                        tracerForm.querySelector('[data-tracer-submit-button]')
                    );
                }
            }

            if (scholarshipForm instanceof HTMLFormElement) {
                const scholarshipDialog = scholarshipForm.querySelector('[data-scholarship-attestation]');
                if (isVisibleDialog(scholarshipDialog)) {
                    hideAttestationDialog(
                        scholarshipDialog,
                        scholarshipForm.querySelector('[data-scholarship-attestation-checkbox]'),
                        scholarshipForm.querySelector('[data-scholarship-submit-button]')
                    );
                }
            }
        });

        initTracerPage();
    })();
</script>
@endsection
