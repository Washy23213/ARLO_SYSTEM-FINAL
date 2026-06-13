@extends('components.layouts.dashboard')

@section('title', 'My Profile')

@section('content')
@php
    $user = $user ?? auth()->user();
    $latestTracer = $latestTracer ?? null;
    $profileChecklist = $profileChecklist ?? [];
    $profilePercent = $profilePercent ?? 0;
    $employmentStatus = $employmentStatus ?? 'Unknown';
    $departmentOptions = \App\Support\SystemOptions::departments();
    $programCatalog = \App\Support\SystemOptions::programCatalogByDepartment();
    $locationOptions = \App\Support\SystemOptions::locations();
    $statusColors = match ($employmentStatus) {
        'Employed' => 'bg-emerald-100 text-emerald-700',
        'Self-Employed' => 'bg-amber-100 text-amber-700',
        'Unemployed' => 'bg-rose-100 text-rose-700',
        default => 'bg-slate-100 text-slate-600',
    };
    $yesNoLabel = static fn ($value) => $value === null ? 'N/A' : ($value ? 'Yes' : 'No');
    $latestCompetencies = collect((array) ($latestTracer?->first_job_useful_competencies ?? []))->join(', ');
    $latestEmploymentProofUrl = $latestTracer?->employment_proof_path && $latestTracer?->employmentProofIsVerified()
        ? route('tracer.proof.download', $latestTracer)
        : null;
    $latestEmploymentProofName = $latestTracer?->employment_proof_path
        ? ($latestTracer->employment_proof_original_name ?: basename($latestTracer->employment_proof_path))
        : null;
    $registrationMissingNote = 'You did not answer this in the registration. You can update it now.';
    $skillsValue = old('skills', $user->skills ?? '');
    $achievementsValue = old('achievements', $user->achievements ?? '');
    $knownLocation = old('location', in_array($user->location, $locationOptions, true) ? $user->location : (!empty($user->location) ? 'Others' : ''));
    $specificLocation = old('specific_location', in_array($user->location, $locationOptions, true) ? '' : ($user->location ?? ''));
    $newsletterOptInChecked = filter_var(
        old('newsletter_opt_in', ($user->newsletter_opt_in ?? true) ? '1' : '0'),
        FILTER_VALIDATE_BOOLEAN
    );
    $showProfileAttestation = old('profile_information_attestation') || $errors->has('profile_information_attestation');
    $profileInitials = strtoupper(
        substr(trim((string) ($user->firstname ?? 'U')), 0, 1)
        . substr(trim((string) ($user->lastname ?? 'S')), 0, 1)
    );
    $profileInitials = trim($profileInitials) !== '' ? $profileInitials : 'US';
    $profileChecklistFieldByLabel = [
        'Phone number' => 'phone',
        'College' => 'department',
        'Degree' => 'degree',
        'Batch' => 'year_graduated',
        'Location' => 'location',
        'Skills' => 'skills',
    ];
    $profileMissingFieldSet = collect($profileChecklistFieldByLabel)
        ->filter(fn (string $field, string $label) => !($profileChecklist[$label] ?? true))
        ->flip()
        ->all();
    $profileChecklistTracerHrefByLabel = [
        'Employment summary' => route('tracer.index', ['from' => 'overview', 'focus_field' => 'employment_summary']) . '#tracer-form',
        'Latest tracer record' => route('tracer.index', ['from' => 'overview', 'focus_field' => 'latest_tracer_record']) . '#tracer-form',
    ];
    $profileTracerCompletionMissing = !($profileChecklist['Employment summary'] ?? true)
        || !($profileChecklist['Latest tracer record'] ?? true);
    $profileTracerCompletionHref = route('tracer.index');
    foreach ($profileChecklistTracerHrefByLabel as $label => $href) {
        if (!($profileChecklist[$label] ?? true)) {
            $profileTracerCompletionHref = $href;
            break;
        }
    }
    $profileMissingFieldPrompts = [
        'phone' => 'Phone number is missing for profile completeness.',
        'department' => 'College is missing for profile completeness.',
        'degree' => 'Degree is missing for profile completeness.',
        'year_graduated' => 'Batch is missing for profile completeness.',
        'location' => 'Location is missing for profile completeness.',
        'skills' => 'Skills are missing for profile completeness.',
    ];
    $profileMissingFields = array_keys($profileMissingFieldSet);
    $profileFieldIsMissing = static fn (string $field): bool => array_key_exists($field, $profileMissingFieldSet);
    $profileFocusFields = ['phone', 'department', 'degree', 'year_graduated', 'location', 'skills'];
    $profileFocusField = request()->query('focus_field');
    $openedFromOverview = request()->query('from') === 'overview' && in_array($profileFocusField, $profileFocusFields, true);
    $profileFieldShellClasses = static function (string $field) use ($profileFocusField, $openedFromOverview, $profileFieldIsMissing): string {
        $baseClasses = 'scroll-mt-28';

        if ($profileFieldIsMissing($field)) {
            return $baseClasses . ' rounded-2xl border border-amber-300 bg-amber-50/80 p-4 shadow-sm ring-2 ring-amber-100';
        }

        if (!$openedFromOverview || $profileFocusField !== $field) {
            return $baseClasses;
        }

        return $baseClasses . ' rounded-2xl border border-emerald-200 bg-emerald-50/80 p-4 shadow-sm ring-1 ring-emerald-100';
    };
    $profileControlClasses = static function (string $field) use ($profileFieldIsMissing): string {
        $classes = 'w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-100';

        if ($profileFieldIsMissing($field)) {
            $classes .= ' !border-amber-400 !bg-amber-50/80 !ring-2 !ring-amber-100 focus:!border-amber-500 focus:!ring-amber-200';
        }

        return $classes;
    };
@endphp
<div class="space-y-6">
    <div class="grid grid-cols-1 gap-6 xl:grid-cols-[minmax(320px,0.9fr)_minmax(0,1.35fr)]">
        <div class="space-y-6">
            <div class="surface-card p-6">
                <div class="flex items-start gap-4">
                    <span
                        class="inline-flex h-16 w-16 items-center justify-center rounded-2xl border border-emerald-200 text-xl font-black tracking-[0.08em] shadow-sm"
                        style="background: linear-gradient(135deg, #00491e, #02681e); color: #ffffff;"
                        aria-hidden="true"
                    >
                        {{ $profileInitials }}
                    </span>
                    <div class="min-w-0">
                        <h2 class="truncate text-2xl font-bold text-slate-900">{{ $user->firstname ?? 'User' }} {{ $user->lastname ?? '' }}</h2>
                        <p class="mt-1 break-words text-sm text-slate-600">{{ $user->email ?? 'No email set' }}</p>
                        <div class="mt-3 flex flex-wrap items-center gap-2">
                            <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $statusColors }}">{{ $employmentStatus }}</span>
                        </div>
                    </div>
                    </div>

                <div class="mt-6 space-y-3 text-sm">
                    <div class="flex items-center justify-between rounded-xl border border-slate-200 bg-white px-4 py-3">
                        <span class="text-slate-500">College</span>
                        <span class="text-right font-medium text-slate-900">{{ $user->department ?? 'N/A' }}</span>
                    </div>
                    <div class="flex items-center justify-between rounded-xl border border-slate-200 bg-white px-4 py-3">
                        <span class="text-slate-500">Degree</span>
                        <span class="text-right font-medium text-slate-900">{{ $user->degree ?? 'N/A' }}</span>
                    </div>
                    <div class="flex items-center justify-between rounded-xl border border-slate-200 bg-white px-4 py-3">
                        <span class="text-slate-500">Batch</span>
                        <span class="font-medium text-slate-900">{{ $user->year_graduated ?? 'N/A' }}</span>
                    </div>
                    <div class="flex items-center justify-between rounded-xl border border-slate-200 bg-white px-4 py-3">
                        <span class="text-slate-500">Scholarship</span>
                        <span class="text-right font-medium text-slate-900">
                            @if($user->has_scholarship && $user->scholarship_name)
                                {{ $user->scholarship_name }}{{ $user->scholarship_year_awarded ? ' (' . $user->scholarship_year_awarded . ')' : '' }}
                            @else
                                None declared
                            @endif
                        </span>
                    </div>
                    <div class="flex items-center justify-between rounded-xl border border-slate-200 bg-white px-4 py-3">
                        <span class="text-slate-500">Scholarship Helped with Job</span>
                        <span class="text-right font-medium text-slate-900">
                            @if($user->has_scholarship)
                                {{ $user->scholarship_helped_get_job === null ? 'N/A' : ($user->scholarship_helped_get_job ? 'Yes' : 'No') }}
                            @else
                                N/A
                            @endif
                        </span>
                    </div>
                    <div class="flex items-center justify-between rounded-xl border border-slate-200 bg-white px-4 py-3">
                        <span class="text-slate-500">Location</span>
                        <span class="text-right font-medium text-slate-900">{{ $user->location ?? 'N/A' }}</span>
                    </div>
                    <div class="flex items-center justify-between rounded-xl border border-slate-200 bg-white px-4 py-3">
                        <span class="text-slate-500">Phone</span>
                        <span class="font-medium text-slate-900">{{ $user->phone ?? 'N/A' }}</span>
                    </div>
                </div>

                <div class="mt-6 rounded-2xl border border-emerald-100 bg-emerald-50/80 p-4">
                    <div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-emerald-700">Profile Completeness</p>
                            <p class="mt-2 text-3xl font-black text-slate-900">{{ $profilePercent }}%</p>
                            <p class="mt-1 text-sm text-slate-600">Complete more details to improve tracer quality and reporting accuracy.</p>
                        </div>
                    </div>
                    <div class="mt-4 h-3 overflow-hidden rounded-full bg-white/90">
                        <div class="h-full rounded-full bg-gradient-to-r from-emerald-500 to-teal-500" style="width: {{ $profilePercent }}%"></div>
                    </div>
                </div>
            </div>

            <div class="surface-card p-6">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900">Completion Checklist</h2>
                        <p class="mt-1 text-sm text-slate-600">A quick view of the profile items that affect analytics and tracer reports.</p>
                    </div>
                </div>

                <div class="mt-4 space-y-2">
                    @foreach($profileChecklist as $item => $done)
                        @php
                            $checklistField = $profileChecklistFieldByLabel[$item] ?? null;
                            $checklistHref = $checklistField
                                ? '#profile-' . str_replace('_', '-', $checklistField) . '-field'
                                : ($profileChecklistTracerHrefByLabel[$item] ?? null);
                        @endphp
                        <div class="flex items-center justify-between gap-3 rounded-xl border px-4 py-3 {{ $done ? 'border-emerald-100 bg-emerald-50/60' : 'border-amber-100 bg-amber-50/70' }}">
                            <span class="text-sm {{ $done ? 'text-emerald-900' : 'text-amber-900' }}">{{ $item }}</span>
                            <div class="flex shrink-0 items-center gap-2">
                                @if(!$done && $checklistHref)
                                    <a href="{{ $checklistHref }}" class="rounded-full bg-white/80 px-2.5 py-1 text-xs font-semibold text-amber-800 ring-1 ring-amber-200 transition hover:bg-amber-100">
                                        {{ $checklistField ? 'Show field' : 'Open Tracer' }}
                                    </a>
                                @endif
                                <span class="text-xs font-semibold {{ $done ? 'text-emerald-700' : 'text-amber-700' }}">{{ $done ? 'Complete' : 'Needs update' }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="space-y-6">
            <div id="profile-edit-form" class="surface-card p-6 scroll-mt-24">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-xl font-semibold text-slate-900">Update Profile Details</h2>
                        <p class="mt-1 text-sm text-slate-600">Edit your core alumni record here. Employment status, company, salary, and job history stay in Tracer.</p>
                    </div>
                </div>

                <form action="{{ route('settings.profile.update') }}" method="POST" class="mt-5 grid grid-cols-1 gap-4 md:grid-cols-2" data-profile-update-form>
                    @csrf
                    @if(count($profileMissingFields) > 0)
                        <div class="md:col-span-2 rounded-2xl border border-amber-200 bg-amber-50/90 px-4 py-3 text-sm text-amber-900 ring-1 ring-amber-100">
                            <div class="flex items-start gap-3">
                                <span class="mt-0.5 inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-amber-100 text-amber-700">
                                    <i class='bx bx-error-circle text-lg'></i>
                                </span>
                                <div>
                                    <p class="font-semibold text-amber-950">Missing profile fields are highlighted below.</p>
                                    <p class="mt-1 leading-6">Update the amber fields to raise your profile completeness from {{ $profilePercent }}%.</p>
                                </div>
                            </div>
                        </div>
                    @endif
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">First Name</label>
                        <input name="firstname" type="text" value="{{ old('firstname', $user->firstname ?? '') }}" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-100" required>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Last Name</label>
                        <input name="lastname" type="text" value="{{ old('lastname', $user->lastname ?? '') }}" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-100" required>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Email</label>
                        <input name="email" type="email" value="{{ old('email', $user->email ?? '') }}" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-100" required>
                    </div>
                    <div class="md:col-span-2 rounded-xl border border-emerald-100 bg-emerald-50/60 px-4 py-3 ring-1 ring-emerald-100/70">
                        <input type="hidden" name="newsletter_opt_in" value="0">
                        <label for="profile_newsletter_opt_in" class="flex items-start gap-3">
                            <input
                                id="profile_newsletter_opt_in"
                                type="checkbox"
                                name="newsletter_opt_in"
                                value="1"
                                class="mt-1 h-4 w-4 rounded border-emerald-300 text-emerald-700 focus:ring-emerald-500"
                                {{ $newsletterOptInChecked ? 'checked' : '' }}
                            >
                            <span class="min-w-0">
                                <span class="block text-sm font-semibold text-slate-900">Receive OARL newsletter and alumni updates</span>
                                <span class="mt-1 block text-sm leading-6 text-slate-600">
                                    Official announcements, job opportunities, events, and newsletter updates will be sent to your account email.
                                </span>
                            </span>
                        </label>
                    </div>
                    <div
                        id="profile-phone-field"
                        class="{{ $profileFieldShellClasses('phone') }}"
                        data-profile-field="phone"
                        data-profile-completion-missing="{{ $profileFieldIsMissing('phone') ? 'true' : 'false' }}"
                        data-guided-active="{{ $openedFromOverview && $profileFocusField === 'phone' ? 'true' : 'false' }}"
                    >
                        @if($openedFromOverview && $profileFocusField === 'phone')
                            <p class="mb-3 inline-flex items-center gap-2 rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.14em] text-emerald-800">
                                <i class='bx bx-navigation text-sm'></i>
                                Start here
                            </p>
                        @endif
                        @if($profileFieldIsMissing('phone'))
                            <p class="mb-3 inline-flex items-center gap-2 rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.14em] text-amber-800">
                                <i class='bx bx-error-circle text-sm'></i>
                                Missing info
                            </p>
                        @endif
                        <label class="mb-1 block text-sm font-medium text-slate-700">Phone</label>
                        <input name="phone" type="tel" value="{{ old('phone', $user->phone ?? '') }}" class="{{ $profileControlClasses('phone') }}" {{ $openedFromOverview && $profileFocusField === 'phone' ? 'autofocus' : '' }}>
                        @if($profileFieldIsMissing('phone'))
                            <p class="mt-2 text-xs font-medium text-amber-800">{{ $profileMissingFieldPrompts['phone'] }}</p>
                        @endif
                    </div>
                    <div
                        id="profile-department-field"
                        class="{{ $profileFieldShellClasses('department') }}"
                        data-profile-field="department"
                        data-profile-completion-missing="{{ $profileFieldIsMissing('department') ? 'true' : 'false' }}"
                        data-guided-active="{{ $openedFromOverview && $profileFocusField === 'department' ? 'true' : 'false' }}"
                    >
                        @if($openedFromOverview && $profileFocusField === 'department')
                            <p class="mb-3 inline-flex items-center gap-2 rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.14em] text-emerald-800">
                                <i class='bx bx-navigation text-sm'></i>
                                Start here
                            </p>
                        @endif
                        @if($profileFieldIsMissing('department'))
                            <p class="mb-3 inline-flex items-center gap-2 rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.14em] text-amber-800">
                                <i class='bx bx-error-circle text-sm'></i>
                                Missing info
                            </p>
                        @endif
                        <label class="mb-1 block text-sm font-medium text-slate-700">College</label>
                        @php
                            $selectedDepartment = old('department', $user->department ?? '');
                            $selectedDegree = old('degree', $user->degree ?? '');
                            $degreeOptions = \App\Support\SystemOptions::programsForDepartment($selectedDepartment);
                        @endphp
                        <span class="portal-select-wrap">
                            <select id="profile_department_select" name="department" class="portal-select {{ $profileControlClasses('department') }}" {{ $openedFromOverview && $profileFocusField === 'department' ? 'autofocus' : '' }}>
                                <option value="">Select College</option>
                                @foreach($departmentOptions as $departmentOption)
                                    <option value="{{ $departmentOption }}" {{ $selectedDepartment === $departmentOption ? 'selected' : '' }}>
                                        {{ $departmentOption }}
                                    </option>
                                @endforeach
                                @if($selectedDepartment !== '' && !in_array($selectedDepartment, $departmentOptions, true))
                                    <option value="{{ $selectedDepartment }}" selected>{{ $selectedDepartment }}</option>
                                @endif
                            </select>
                            <span class="portal-select-wrap__icon" aria-hidden="true">
                                <i class='bx bx-chevron-down text-lg'></i>
                            </span>
                        </span>
                        @if($profileFieldIsMissing('department'))
                            <p class="mt-2 text-xs font-medium text-amber-800">{{ $profileMissingFieldPrompts['department'] }}</p>
                        @endif
                    </div>
                    <div
                        id="profile-degree-field"
                        class="{{ $profileFieldShellClasses('degree') }}"
                        data-profile-field="degree"
                        data-profile-completion-missing="{{ $profileFieldIsMissing('degree') ? 'true' : 'false' }}"
                        data-guided-active="{{ $openedFromOverview && $profileFocusField === 'degree' ? 'true' : 'false' }}"
                    >
                        @if($openedFromOverview && $profileFocusField === 'degree')
                            <p class="mb-3 inline-flex items-center gap-2 rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.14em] text-emerald-800">
                                <i class='bx bx-navigation text-sm'></i>
                                Start here
                            </p>
                        @endif
                        @if($profileFieldIsMissing('degree'))
                            <p class="mb-3 inline-flex items-center gap-2 rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.14em] text-amber-800">
                                <i class='bx bx-error-circle text-sm'></i>
                                Missing info
                            </p>
                        @endif
                        <label class="mb-1 block text-sm font-medium text-slate-700">Degree</label>
                        <span class="portal-select-wrap">
                            <select id="profile_degree_select" name="degree" class="portal-select {{ $profileControlClasses('degree') }}" {{ $selectedDepartment === '' ? 'disabled' : '' }} {{ $openedFromOverview && $profileFocusField === 'degree' ? 'autofocus' : '' }}>
                                <option value="">{{ $selectedDepartment === '' ? 'Select college first' : 'Select Degree / Program' }}</option>
                                @foreach($degreeOptions as $degreeOption)
                                    <option value="{{ $degreeOption }}" {{ $selectedDegree === $degreeOption ? 'selected' : '' }}>
                                        {{ $degreeOption }}
                                    </option>
                                @endforeach
                                @if($selectedDegree !== '' && !in_array($selectedDegree, $degreeOptions, true))
                                    <option value="{{ $selectedDegree }}" selected>{{ $selectedDegree }}</option>
                                @endif
                            </select>
                            <span class="portal-select-wrap__icon" aria-hidden="true">
                                <i class='bx bx-chevron-down text-lg'></i>
                            </span>
                        </span>
                        @if($profileFieldIsMissing('degree'))
                            <p class="mt-2 text-xs font-medium text-amber-800">{{ $profileMissingFieldPrompts['degree'] }}</p>
                        @endif
                    </div>
                    <div
                        id="profile-year-graduated-field"
                        class="{{ $profileFieldShellClasses('year_graduated') }}"
                        data-profile-field="year_graduated"
                        data-profile-completion-missing="{{ $profileFieldIsMissing('year_graduated') ? 'true' : 'false' }}"
                        data-guided-active="{{ $openedFromOverview && $profileFocusField === 'year_graduated' ? 'true' : 'false' }}"
                    >
                        @if($openedFromOverview && $profileFocusField === 'year_graduated')
                            <p class="mb-3 inline-flex items-center gap-2 rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.14em] text-emerald-800">
                                <i class='bx bx-navigation text-sm'></i>
                                Start here
                            </p>
                        @endif
                        @if($profileFieldIsMissing('year_graduated'))
                            <p class="mb-3 inline-flex items-center gap-2 rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.14em] text-amber-800">
                                <i class='bx bx-error-circle text-sm'></i>
                                Missing info
                            </p>
                        @endif
                        <label class="mb-1 block text-sm font-medium text-slate-700">Batch</label>
                        <input name="year_graduated" type="number" min="1960" value="{{ old('year_graduated', $user->year_graduated ?? '') }}" class="{{ $profileControlClasses('year_graduated') }}" {{ $openedFromOverview && $profileFocusField === 'year_graduated' ? 'autofocus' : '' }}>
                        @if($profileFieldIsMissing('year_graduated'))
                            <p class="mt-2 text-xs font-medium text-amber-800">{{ $profileMissingFieldPrompts['year_graduated'] }}</p>
                        @endif
                    </div>
                    <div
                        id="profile-location-field"
                        class="{{ $profileFieldShellClasses('location') }}"
                        data-profile-field="location"
                        data-profile-completion-missing="{{ $profileFieldIsMissing('location') ? 'true' : 'false' }}"
                        data-guided-active="{{ $openedFromOverview && $profileFocusField === 'location' ? 'true' : 'false' }}"
                    >
                        @if($openedFromOverview && $profileFocusField === 'location')
                            <p class="mb-3 inline-flex items-center gap-2 rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.14em] text-emerald-800">
                                <i class='bx bx-navigation text-sm'></i>
                                Start here
                            </p>
                        @endif
                        @if($profileFieldIsMissing('location'))
                            <p class="mb-3 inline-flex items-center gap-2 rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.14em] text-amber-800">
                                <i class='bx bx-error-circle text-sm'></i>
                                Missing info
                            </p>
                        @endif
                        <label class="mb-1 block text-sm font-medium text-slate-700">Location</label>
                        <span class="portal-select-wrap">
                            <select id="profile_location_select" name="location" class="portal-select {{ $profileControlClasses('location') }}" {{ $openedFromOverview && $profileFocusField === 'location' ? 'autofocus' : '' }}>
                                <option value="">Select Location</option>
                                @foreach($locationOptions as $locationOption)
                                    <option value="{{ $locationOption }}" {{ $knownLocation === $locationOption ? 'selected' : '' }}>
                                        {{ $locationOption }}
                                    </option>
                                @endforeach
                            </select>
                            <span class="portal-select-wrap__icon" aria-hidden="true">
                                <i class='bx bx-chevron-down text-lg'></i>
                            </span>
                        </span>
                        <div id="profile_specific_location_group" class="mt-3 {{ $knownLocation === 'Others' ? '' : 'hidden' }}">
                            <label class="mb-1 block text-sm font-medium text-slate-700">Specific Location</label>
                            <input id="profile_specific_location_input" name="specific_location" type="text" value="{{ $specificLocation }}" placeholder="Enter specific location" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                        </div>
                        @if($profileFieldIsMissing('location'))
                            <p class="mt-2 text-xs font-medium text-amber-800">{{ $profileMissingFieldPrompts['location'] }}</p>
                        @endif
                    </div>
                    <div
                        id="profile-skills-field"
                        class="md:col-span-2 {{ $profileFieldShellClasses('skills') }}"
                        data-profile-field="skills"
                        data-profile-completion-missing="{{ $profileFieldIsMissing('skills') ? 'true' : 'false' }}"
                        data-guided-active="{{ $openedFromOverview && $profileFocusField === 'skills' ? 'true' : 'false' }}"
                    >
                        @if($openedFromOverview && $profileFocusField === 'skills')
                            <p class="mb-3 inline-flex items-center gap-2 rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.14em] text-emerald-800">
                                <i class='bx bx-navigation text-sm'></i>
                                Start here
                            </p>
                        @endif
                        @if($profileFieldIsMissing('skills'))
                            <p class="mb-3 inline-flex items-center gap-2 rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.14em] text-amber-800">
                                <i class='bx bx-error-circle text-sm'></i>
                                Missing info
                            </p>
                        @endif
                        <label class="mb-1 block text-sm font-medium text-slate-700">Skills</label>
                        <textarea name="skills" rows="4" class="{{ $profileControlClasses('skills') }}" {{ $openedFromOverview && $profileFocusField === 'skills' ? 'autofocus' : '' }}>{{ $skillsValue }}</textarea>
                        @if($profileFieldIsMissing('skills'))
                            <p class="mt-2 text-xs font-medium text-amber-800">{{ $profileMissingFieldPrompts['skills'] }}</p>
                        @elseif(blank($skillsValue))
                            <p class="mt-2 text-xs text-amber-700">{{ $registrationMissingNote }}</p>
                        @endif
                    </div>
                    <div class="md:col-span-2">
                        <label class="mb-1 block text-sm font-medium text-slate-700">Achievements</label>
                        <textarea name="achievements" rows="4" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-100">{{ $achievementsValue }}</textarea>
                        @if(blank($achievementsValue))
                            <p class="mt-2 text-xs text-amber-700">{{ $registrationMissingNote }}</p>
                        @endif
                    </div>
                    <div
                        id="profile-information-attestation"
                        class="fixed inset-0 z-[70] {{ $showProfileAttestation ? 'flex' : 'hidden' }} items-center justify-center bg-slate-950/60 px-4 py-6 backdrop-blur-sm"
                        data-profile-attestation
                        role="dialog"
                        aria-modal="true"
                        aria-labelledby="profile-information-attestation-title"
                        @if(!$showProfileAttestation) hidden @endif
                    >
                        <button
                            type="button"
                            class="absolute inset-0 cursor-default"
                            data-profile-attestation-close
                            aria-label="Close information confirmation"
                        ></button>
                        <div class="relative w-full max-w-lg rounded-lg border border-slate-200 bg-white p-5 shadow-2xl">
                            <div class="flex items-start gap-3">
                                <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-emerald-100 text-emerald-700">
                                    <i class='bx bx-check-shield text-xl'></i>
                                </span>
                                <div class="min-w-0">
                                    <h3 id="profile-information-attestation-title" class="text-sm font-semibold uppercase tracking-[0.14em] text-slate-800">Information Attestation</h3>
                                    <p class="mt-2 text-sm leading-6 text-slate-700">Please confirm that the profile details you are updating are truthful and accurate.</p>
                                </div>
                            </div>
                            <label class="mt-5 flex items-start gap-3 rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                                <input type="checkbox" name="profile_information_attestation" value="1" class="mt-0.5 h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500" {{ old('profile_information_attestation') ? 'checked' : '' }} {{ $showProfileAttestation ? 'required' : '' }} data-profile-attestation-checkbox>
                                <span>I confirm this information is true and accurate.</span>
                            </label>
                            @error('profile_information_attestation')
                                <p class="mt-3 text-sm font-medium text-rose-600">{{ $message }}</p>
                            @enderror
                            <div class="mt-5 flex flex-wrap items-center justify-end gap-3">
                                <button type="button" class="inline-flex items-center justify-center rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 transition-colors hover:bg-slate-100" data-profile-attestation-close>
                                    Cancel
                                </button>
                                <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-emerald-700">
                                    <i class='bx bx-save text-lg'></i>
                                    Confirm and Save
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="md:col-span-2 flex flex-wrap items-start justify-between gap-3 border-t border-slate-200 pt-4">
                        <div class="min-w-0 flex-1">
                            <p class="text-sm text-slate-600">Save your basic alumni record here. Use Tracer for employment updates.</p>
                            <div
                                id="profile-no-changes-message"
                                class="mt-3 hidden rounded-xl border border-amber-200 bg-amber-50/90 px-4 py-3 text-sm text-amber-900 shadow-sm ring-1 ring-amber-100/70 focus:outline-none focus:ring-2 focus:ring-amber-300"
                                data-profile-no-changes-message
                                role="alert"
                                aria-hidden="true"
                                tabindex="-1"
                            >
                                <div class="flex items-start gap-3">
                                    <span class="mt-0.5 inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-amber-100 text-amber-700">
                                        <i class='bx bx-info-circle text-lg'></i>
                                    </span>
                                    <div>
                                        <p class="font-semibold text-amber-950">No profile changes found.</p>
                                        <p class="mt-1 leading-6">Please edit at least one profile field before saving your information.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-5 py-3 text-sm font-semibold text-white transition-all duration-300 hover:-translate-y-0.5 hover:bg-emerald-700 hover:shadow-md" data-profile-save-button>
                            <i class='bx bx-save text-lg'></i>
                            Save Information
                        </button>
                    </div>
                </form>
            </div>

            <div
                class="surface-card p-6 {{ $profileTracerCompletionMissing ? '!border-amber-300 !bg-amber-50/70 !ring-2 !ring-amber-100' : '' }}"
                data-profile-tracer-completion-missing="{{ $profileTracerCompletionMissing ? 'true' : 'false' }}"
            >
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-xl font-semibold text-slate-900">Latest Tracer Snapshot</h2>
                        <p class="mt-1 text-sm text-slate-600">Your most recent tracer record is shown here so the profile and tracer module stay consistent.</p>
                    </div>
                    <a href="{{ $profileTracerCompletionHref }}" class="inline-flex items-center gap-2 rounded-xl px-4 py-2 text-sm font-semibold text-white transition-all duration-300 hover:-translate-y-0.5 hover:shadow-md {{ $profileTracerCompletionMissing ? 'bg-amber-600 hover:bg-amber-700' : 'bg-emerald-600 hover:bg-emerald-700' }}">
                        <i class='bx bx-spreadsheet text-base'></i>
                        Open Tracer
                    </a>
                </div>

                @if($profileTracerCompletionMissing)
                    <div class="mt-4 rounded-2xl border border-amber-200 bg-white/80 px-4 py-3 text-sm text-amber-900 ring-1 ring-amber-100">
                        <div class="flex items-start gap-3">
                            <span class="mt-0.5 inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-amber-100 text-amber-700">
                                <i class='bx bx-error-circle text-lg'></i>
                            </span>
                            <div>
                                <p class="font-semibold text-amber-950">Tracer information is still affecting your profile completeness.</p>
                                <p class="mt-1 leading-6">Open Tracer to complete your employment summary or submit your latest tracer record.</p>
                            </div>
                        </div>
                    </div>
                @endif

                @if($latestTracer)
                    @php
                        $profileSnapshotCards = [];
                        $addProfileSnapshotCard = function (string $label, $value) use (&$profileSnapshotCards): void {
                            if (filled($value)) {
                                $profileSnapshotCards[] = [
                                    'label' => $label,
                                    'value' => $value,
                                ];
                            }
                        };
                        $latestEmploymentType = trim((string) ($latestTracer->employment_type ?? ''));
                        $latestEmploymentKey = mb_strtolower($latestEmploymentType);
                        $latestTracerHasWorkDetails = filled($latestEmploymentType)
                            && !in_array($latestEmploymentKey, ['unemployed', 'never employed', 'no'], true);
                        $latestPresentlyEmployed = $latestTracer->presently_employed ?? null;
                        $latestIndustry = $latestTracer->industry ?? $user->industry ?? null;

                        $addProfileSnapshotCard('Tracer Date', optional($latestTracer->tracer_date)->format('M d, Y'));
                        $addProfileSnapshotCard('Employment Type', $latestTracer->employment_type);

                        if ($latestTracerHasWorkDetails) {
                            $addProfileSnapshotCard('Current Position', $latestTracer->current_position);
                            $addProfileSnapshotCard('Company / Business', $latestTracer->current_company);
                            $addProfileSnapshotCard('Industry', $latestIndustry);
                            $addProfileSnapshotCard('Monthly Income Range', $latestTracer->monthly_income);
                            $addProfileSnapshotCard('Present Employment Status', $latestTracer->present_employment_status);
                            $addProfileSnapshotCard('Job Satisfaction', $latestTracer->job_satisfaction ? $latestTracer->job_satisfaction . ' / 5' : null);
                        } elseif (filled($latestPresentlyEmployed) && mb_strtolower((string) $latestPresentlyEmployed) !== $latestEmploymentKey) {
                            $addProfileSnapshotCard('Presently Employed', $latestPresentlyEmployed);
                        }

                        $addProfileSnapshotCard(
                            'Time to First Job',
                            $latestTracer->time_to_first_job === 'Other'
                                ? ($latestTracer->time_to_first_job_other ?? 'Other')
                                : $latestTracer->time_to_first_job
                        );
                        $addProfileSnapshotCard('Facilities Satisfaction', $latestTracer->facilities_satisfaction);
                        $addProfileSnapshotCard('Useful Competencies', $latestCompetencies);
                        $addProfileSnapshotCard('Skills Used', $latestTracer->skills_used);

                        if ($latestTracer->further_education) {
                            $addProfileSnapshotCard('Further Education', $latestTracer->education_level ?? 'Yes');
                        }
                    @endphp

                    <div class="mt-5 grid grid-cols-1 gap-4 md:grid-cols-2">
                        @foreach($profileSnapshotCards as $snapshotCard)
                            <div class="rounded-2xl border border-slate-200 bg-white px-4 py-4">
                                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">{{ $snapshotCard['label'] }}</p>
                                <p class="mt-2 text-sm font-medium text-slate-900">{{ $snapshotCard['value'] }}</p>
                            </div>
                        @endforeach
                    </div>
                    @if($latestTracer->employment_proof_path)
                        <div class="mt-4 rounded-2xl border border-slate-200 bg-white px-4 py-4">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Employment Proof</p>
                                <span class="rounded-full px-2.5 py-1 text-[11px] font-semibold uppercase tracking-[0.12em] {{ $latestTracer->employmentProofIsVerified() ? 'bg-emerald-100 text-emerald-700' : ($latestTracer->employmentProofIsRejected() ? 'bg-rose-100 text-rose-700' : 'bg-amber-100 text-amber-700') }}">
                                    {{ $latestTracer->employmentProofStatusLabel() }}
                                </span>
                            </div>
                            <p class="mt-2 text-sm font-medium text-slate-900">{{ $latestEmploymentProofName }}</p>
                            @if($latestTracer?->employment_proof_uploaded_at)
                                <p class="mt-1 text-xs text-slate-500">Uploaded {{ $latestTracer->employment_proof_uploaded_at->format('M d, Y g:i A') }}</p>
                            @endif
                            @if($latestTracer->employmentProofIsRejected() && $latestTracer->employment_proof_rejection_note)
                                <p class="mt-2 text-xs text-rose-600">{{ $latestTracer->employment_proof_rejection_note }}</p>
                            @elseif($latestEmploymentProofUrl)
                                <a
                                    href="{{ $latestEmploymentProofUrl }}"
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
                @else
                    <div class="empty-state-card mt-5">
                        <span class="empty-state-card__icon">
                            <i class='bx bx-notepad'></i>
                        </span>
                        <p class="empty-state-card__title">No tracer record yet</p>
                        <p class="empty-state-card__body">Add a tracer record to populate this section.</p>
                    </div>
                @endif
            </div>

        </div>
    </div>
</div>

<script>
    (function () {
        const form = document.querySelector('[data-profile-update-form]');

        if (!(form instanceof HTMLFormElement)) {
            return;
        }

        const attestation = form.querySelector('[data-profile-attestation]');
        const checkbox = form.querySelector('[data-profile-attestation-checkbox]');
        const closeControls = form.querySelectorAll('[data-profile-attestation-close]');
        const saveButton = form.querySelector('[data-profile-save-button]');
        const noChangesMessage = form.querySelector('[data-profile-no-changes-message]');

        if (!(attestation instanceof HTMLElement) || !(checkbox instanceof HTMLInputElement)) {
            return;
        }

        const ignoredFieldNames = new Set(['_token', 'profile_information_attestation']);

        const getComparableFields = () => Array.from(form.elements).filter((field) => {
            if (!(field instanceof HTMLInputElement || field instanceof HTMLSelectElement || field instanceof HTMLTextAreaElement)) {
                return false;
            }

            if (!field.name || ignoredFieldNames.has(field.name)) {
                return false;
            }

            return !['button', 'submit', 'reset'].includes(field.type);
        });

        const getProfileSnapshot = () => JSON.stringify(getComparableFields().map((field) => {
            if (field instanceof HTMLInputElement && ['checkbox', 'radio'].includes(field.type)) {
                return [field.name, field.value, field.checked];
            }

            return [field.name, field.value];
        }));

        const initialProfileSnapshot = getProfileSnapshot();

        const profileHasChanges = () => getProfileSnapshot() !== initialProfileSnapshot;
        const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        const setNoChangesMessageVisible = (visible, shouldFocus = false) => {
            if (!(noChangesMessage instanceof HTMLElement)) {
                return;
            }

            noChangesMessage.classList.toggle('hidden', !visible);
            noChangesMessage.setAttribute('aria-hidden', visible ? 'false' : 'true');

            if (visible && shouldFocus) {
                window.requestAnimationFrame(() => {
                    noChangesMessage.scrollIntoView({
                        behavior: prefersReducedMotion ? 'auto' : 'smooth',
                        block: 'center',
                    });
                    noChangesMessage.focus({ preventScroll: true });
                });
            }
        };

        const showAttestation = () => {
            attestation.hidden = false;
            attestation.classList.remove('hidden');
            attestation.classList.add('flex');
            checkbox.required = true;
            setNoChangesMessageVisible(false);

            window.requestAnimationFrame(() => {
                checkbox.focus({ preventScroll: true });
            });
        };

        const hideAttestation = () => {
            attestation.hidden = true;
            attestation.classList.add('hidden');
            attestation.classList.remove('flex');
            checkbox.required = false;

            if (saveButton instanceof HTMLElement) {
                saveButton.focus({ preventScroll: true });
            }
        };

        closeControls.forEach((control) => {
            control.addEventListener('click', hideAttestation);
        });

        form.addEventListener('input', () => {
            if (profileHasChanges()) {
                setNoChangesMessageVisible(false);
            }
        });

        form.addEventListener('change', () => {
            if (profileHasChanges()) {
                setNoChangesMessageVisible(false);
            }
        });

        form.addEventListener('submit', (event) => {
            if (!attestation.hidden && !attestation.classList.contains('hidden')) {
                return;
            }

            event.preventDefault();

            if (!profileHasChanges()) {
                hideAttestation();
                setNoChangesMessageVisible(true, true);
                return;
            }

            showAttestation();
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && !attestation.hidden) {
                hideAttestation();
            }
        });

        if (!attestation.hidden) {
            showAttestation();
        }
    })();

    (function () {
        const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        const activeTarget = document.querySelector('[data-guided-active="true"]');

        if (!(activeTarget instanceof HTMLElement)) {
            return;
        }

        const firstFocusableField = activeTarget.querySelector('input:not([type="hidden"]):not([disabled]), select:not([disabled]), textarea:not([disabled])');

        window.setTimeout(() => {
            activeTarget.scrollIntoView({
                behavior: prefersReducedMotion ? 'auto' : 'smooth',
                block: 'center',
            });

            if (firstFocusableField instanceof HTMLElement) {
                window.setTimeout(() => {
                    firstFocusableField.focus({ preventScroll: true });
                }, prefersReducedMotion ? 0 : 180);
            }
        }, prefersReducedMotion ? 0 : 120);
    })();
</script>
@include('partials.department-program-form-script', [
    'programCatalog' => $programCatalog,
    'departmentProgramFieldConfig' => [
        'departmentSelectId' => 'profile_department_select',
        'degreeSelectId' => 'profile_degree_select',
        'selectedDegree' => $selectedDegree ?? '',
        'locationSelectId' => 'profile_location_select',
        'specificLocationGroupId' => 'profile_specific_location_group',
        'specificLocationInputId' => 'profile_specific_location_input',
    ],
])
@endsection
