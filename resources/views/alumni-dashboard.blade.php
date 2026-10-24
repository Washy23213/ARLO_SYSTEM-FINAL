@extends('components.layouts.dashboard')

@section('title', 'Dashboard')

@section('content')
@php
    $user = $user ?? auth()->user();
    $latestTracer = $latestTracer ?? null;
    $profileChecklist = $profileChecklist ?? [];
    $profilePercent = $profilePercent ?? 0;
    $profileCompleted = $profileCompleted ?? collect($profileChecklist)->filter()->count();
    $profileTotal = count($profileChecklist);
    $missingProfileItems = collect($profileChecklist)
        ->reject(fn ($done) => (bool) $done)
        ->keys()
        ->values();
    $firstMissingItem = $missingProfileItems->first();
    $employmentStatus = $employmentStatus ?? 'Unknown';
    $tracerStatus = $tracerStatus ?? ['label' => 'Not submitted', 'tone' => 'amber', 'detail' => '', 'date_label' => 'No submission yet'];
    $tracerCardCopy = ($tracerStatus['tone'] ?? 'amber') === 'emerald'
        ? 'Used for graduate tracer reports and employment insights.'
        : ($tracerStatus['detail'] ?? 'Your tracer details appear here.');
    $tracerDateLabel = $tracerStatus['date_label'] ?? 'No tracer date yet';
    $tracerPillLabel = $tracerDateLabel === 'No submission yet'
        ? $tracerDateLabel
        : 'Last submitted: ' . $tracerDateLabel;
    $jobOpportunitySummary = $jobOpportunitySummary ?? ['total' => 0, 'pending' => 0, 'reviewed' => 0, 'latest_status' => 'No submissions yet', 'latest_job_title' => 'You have not posted a job opportunity yet.', 'latest_company' => null, 'latest_submitted_label' => 'Open the jobs module to post an opportunity.'];
    $jobPostsTotal = (int) ($jobOpportunitySummary['total'] ?? 0);
    $jobPostsPending = (int) ($jobOpportunitySummary['pending'] ?? 0);
    $jobPostsReviewed = (int) ($jobOpportunitySummary['reviewed'] ?? 0);
    $latestJobPostTitle = trim((string) ($jobOpportunitySummary['latest_job_title'] ?? ''));
    $latestJobPostStatus = trim((string) ($jobOpportunitySummary['latest_status'] ?? ''));
    $jobPostsCopy = $jobPostsTotal > 0
        ? 'Submitted opportunities for OARL review.'
        : 'Share relevant job openings with fellow alumni.';
    $jobPostsMeta = $jobPostsTotal > 0
        ? 'Latest: ' . \Illuminate\Support\Str::limit($latestJobPostTitle !== '' ? $latestJobPostTitle : 'Job opportunity', 34) . ($latestJobPostStatus !== '' ? ' - ' . $latestJobPostStatus : '')
        : 'No submitted job opportunities yet.';
    $jobOpportunityListings = collect($jobOpportunityListings ?? []);
    $recentUpdates = collect($recentUpdates ?? []);
    $statusBadgeClasses = match ($employmentStatus) {
        'Employed' => 'bg-emerald-100 text-emerald-700 ring-1 ring-emerald-200',
        'Self-Employed' => 'bg-amber-100 text-amber-700 ring-1 ring-amber-200',
        'Unemployed' => 'bg-rose-100 text-rose-700 ring-1 ring-rose-200',
        default => 'bg-slate-100 text-slate-600 ring-1 ring-slate-200',
    };
    $tracerBadgeClasses = ($tracerStatus['tone'] ?? 'amber') === 'emerald'
        ? 'bg-emerald-100 text-emerald-700 ring-1 ring-emerald-200'
        : 'bg-amber-100 text-amber-700 ring-1 ring-amber-200';
    $profileCardState = $profilePercent >= 100 ? 'is-complete' : 'is-pending';
    $tracerCardState = ($tracerStatus['tone'] ?? 'amber') === 'emerald' ? 'is-complete' : 'is-pending';
    $jobPostsCardState = $jobPostsTotal > 0 ? 'has-posts' : 'is-empty';
    $checklistTargets = [
        'Phone number' => [
            'url' => route('profile', ['from' => 'overview', 'focus_field' => 'phone']) . '#profile-phone-field',
            'page' => 'Profile',
        ],
        'College' => [
            'url' => route('profile', ['from' => 'overview', 'focus_field' => 'department']) . '#profile-department-field',
            'page' => 'Profile',
        ],
        'Degree' => [
            'url' => route('profile', ['from' => 'overview', 'focus_field' => 'degree']) . '#profile-degree-field',
            'page' => 'Profile',
        ],
        'Batch' => [
            'url' => route('profile', ['from' => 'overview', 'focus_field' => 'year_graduated']) . '#profile-year-graduated-field',
            'page' => 'Profile',
        ],
        'Location' => [
            'url' => route('profile', ['from' => 'overview', 'focus_field' => 'location']) . '#profile-location-field',
            'page' => 'Profile',
        ],
        'Skills' => [
            'url' => route('profile', ['from' => 'overview', 'focus_field' => 'skills']) . '#profile-skills-field',
            'page' => 'Profile',
        ],
        'Employment summary' => [
            'url' => route('tracer.index', ['from' => 'overview', 'focus_field' => 'employment_summary']) . '#tracer-form',
            'page' => 'Tracer',
        ],
        'Latest tracer record' => [
            'url' => route('tracer.index', ['from' => 'overview', 'focus_field' => 'latest_tracer_record']) . '#tracer-form',
            'page' => 'Tracer',
        ],
    ];
    $firstMissingTarget = $firstMissingItem ? ($checklistTargets[$firstMissingItem] ?? null) : null;

@endphp

<div class="alumni-overview -mt-2 space-y-4">
    <section class="surface-card overflow-hidden border border-emerald-950/10 bg-gradient-to-r from-white via-emerald-50/60 to-amber-50/50 p-0">
        <div class="grid gap-4 p-4 sm:p-5 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-center">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-amber-700">CMU OARL Alumni Portal</p>
                <h2 class="mt-2 text-3xl font-black tracking-tight text-emerald-950">Welcome back, {{ $user->firstname }}.</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">
                    {{ $user->degree ?: 'Your CMU alumni profile' }}
                    @if($user->year_graduated)
                        <span class="px-1 text-slate-400">·</span> Class of {{ $user->year_graduated }}
                    @endif
                    @if($user->department)
                        <span class="px-1 text-slate-400">·</span> {{ $user->department }}
                    @endif
                </p>
                <div class="mt-4 flex flex-wrap gap-2">
                    <span class="inline-flex items-center gap-2 rounded-full bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 ring-1 ring-emerald-100">
                        <i class='bx bx-id-card text-emerald-700'></i> Profile {{ $profilePercent }}% complete
                    </span>
                    <span class="inline-flex items-center gap-2 rounded-full bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 ring-1 ring-emerald-100">
                        <i class='bx bx-spreadsheet text-emerald-700'></i> Tracer {{ $tracerStatus['label'] ?? 'Not submitted' }}
                    </span>
                </div>
            </div>
            <a href="{{ route('tracer.index') }}#tracer-form" class="inline-flex min-h-12 items-center justify-center gap-2 rounded-xl bg-emerald-800 px-5 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-emerald-900 focus:outline-none focus:ring-2 focus:ring-emerald-300">
                {{ ($tracerStatus['tone'] ?? 'amber') === 'emerald' ? 'Update Tracer Survey' : 'Continue Tracer Survey' }}
                <i class='bx bx-right-arrow-alt text-lg'></i>
            </a>
        </div>
    </section>

    <div class="alumni-overview-stats grid grid-cols-1 gap-3 md:grid-cols-3">
        <section class="alumni-overview-stat alumni-overview-stat--profile surface-card overflow-hidden border p-4 {{ $profileCardState }}">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="alumni-overview-stat__eyebrow text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Profile Completion</p>
                    <p class="alumni-overview-stat__value mt-4 text-4xl font-black leading-none text-slate-900">{{ $profilePercent }}%</p>
                </div>
                <span class="alumni-overview-stat__icon inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-white text-emerald-700 shadow-sm ring-1 ring-emerald-100">
                    <i class='bx bx-id-card text-2xl'></i>
                </span>
            </div>
            <p class="alumni-overview-stat__copy mt-3 text-sm text-slate-600">{{ $profileCompleted }} of {{ $profileTotal }} profile items complete.</p>
            <div class="alumni-overview-stat__progress mt-4 h-2.5 overflow-hidden rounded-full bg-white/80 ring-1 ring-black/5">
                <div class="alumni-overview-stat__progress-fill h-full rounded-full bg-gradient-to-r {{ $profilePercent >= 100 ? 'from-emerald-500 to-teal-500' : 'from-amber-400 to-amber-500' }}" role="progressbar" aria-label="Profile completion" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $profilePercent }}" style="width: {{ $profilePercent }}%"></div>
            </div>
            @if($firstMissingItem && $firstMissingTarget)
                <div class="mt-3 flex flex-wrap items-center justify-between gap-2">
                    <p class="text-xs font-medium text-slate-600">First up: <span class="font-bold text-slate-800">{{ $firstMissingItem }}</span></p>
                    <a href="{{ $firstMissingTarget['url'] }}" class="inline-flex min-h-9 items-center gap-1 rounded-lg bg-emerald-800 px-3 py-2 text-xs font-bold text-white transition hover:bg-emerald-900 focus:outline-none focus:ring-2 focus:ring-emerald-300">
                        {{ $firstMissingTarget['page'] === 'Profile' ? 'Complete profile' : 'Complete tracer' }} <i class='bx bx-right-arrow-alt text-base' aria-hidden="true"></i>
                    </a>
                </div>
            @else
                <p class="alumni-overview-stat__meta mt-3 text-xs font-medium text-emerald-800">All profile items are complete.</p>
            @endif
        </section>

        <section class="alumni-overview-stat alumni-overview-stat--tracer surface-card overflow-hidden border p-4 {{ $tracerCardState }}">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="alumni-overview-stat__eyebrow text-xs font-semibold uppercase tracking-[0.18em] {{ ($tracerStatus['tone'] ?? 'amber') === 'emerald' ? 'text-emerald-700' : 'text-amber-700' }}">Tracer Status</p>
                    <p class="alumni-overview-stat__value alumni-overview-stat__value--compact mt-4 text-2xl font-black leading-tight text-slate-900">{{ $tracerStatus['label'] ?? 'Not submitted' }}</p>
                </div>
                <span class="alumni-overview-stat__icon inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-white {{ ($tracerStatus['tone'] ?? 'amber') === 'emerald' ? 'text-emerald-700 ring-emerald-100' : 'text-amber-700 ring-amber-100' }} shadow-sm ring-1">
                    <i class='bx bx-spreadsheet text-2xl'></i>
                </span>
            </div>
            <p class="alumni-overview-stat__copy mt-3 text-sm text-slate-600">{{ $tracerCardCopy }}</p>
            <div class="alumni-overview-stat__pill mt-4 inline-flex rounded-full bg-white/85 px-3 py-1 text-xs font-semibold text-slate-700 ring-1 ring-black/5">
                {{ $tracerPillLabel }}
            </div>
        </section>

        <section class="alumni-overview-stat alumni-overview-stat--jobs surface-card overflow-hidden border border-slate-200 bg-gradient-to-br from-white via-slate-50/80 to-slate-100/60 p-4 {{ $jobPostsCardState }}">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="alumni-overview-stat__eyebrow text-xs font-semibold uppercase tracking-[0.18em] text-slate-700">My Job Posts</p>
                    <p class="alumni-overview-stat__value mt-4 text-4xl font-black leading-none text-slate-900">{{ $jobPostsTotal }}</p>
                </div>
                <span class="alumni-overview-stat__icon inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-white text-slate-700 shadow-sm ring-1 ring-slate-200">
                    <i class='bx bx-message-square-add text-2xl'></i>
                </span>
            </div>
            <p class="alumni-overview-stat__copy mt-3 text-sm text-slate-600">{{ $jobPostsCopy }}</p>
            @if($jobPostsTotal > 0)
                <div class="mt-5 grid grid-cols-2 gap-3 border-y border-slate-200/70 py-3">
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500">Pending</p>
                        <p class="mt-1 text-2xl font-black leading-none text-slate-900">{{ $jobPostsPending }}</p>
                    </div>
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500">Reviewed</p>
                        <p class="mt-1 text-2xl font-black leading-none text-slate-900">{{ $jobPostsReviewed }}</p>
                    </div>
                </div>
            @else
                <a href="{{ route('jobs') }}" class="alumni-overview-stat__pill mt-5 inline-flex rounded-full bg-white/85 px-3 py-1 text-xs font-semibold text-slate-700 ring-1 ring-slate-100">
                    Post Opportunity
                </a>
            @endif
            <p class="alumni-overview-stat__meta mt-4 text-sm font-medium text-slate-600">{{ $jobPostsMeta }}</p>
        </section>

    </div>

    <div class="grid grid-cols-1 gap-4 xl:grid-cols-[minmax(0,1.12fr)_minmax(320px,0.88fr)]">
        <div class="space-y-4">
            <section class="alumni-overview-panel alumni-overview-panel--employment surface-card overflow-hidden p-0">
                <div class="alumni-overview-panel__mast border-b border-slate-200/80 bg-gradient-to-r from-slate-900 via-[#133824] to-[#184d32] px-5 py-4 text-white">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-white/65">Employment Snapshot</p>
                        <h2 class="mt-2 text-2xl font-black">My Latest Employment Record</h2>
                        <p class="mt-2 max-w-2xl text-sm leading-relaxed text-white/78">Your latest tracer details.</p>
                    </div>
                </div>

                <div class="p-5">
                    @if ($latestTracer)
                        <div class="alumni-overview-highlight rounded-[1.7rem] border border-emerald-100 bg-gradient-to-r from-emerald-50/90 via-white to-sky-50/70 p-5 shadow-[0_18px_34px_rgba(15,23,42,0.06)]">
                            <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700">Current Role</p>
                                    <h3 class="mt-2 text-2xl font-black text-slate-900">{{ $latestTracer->current_position ?? ($user->current_position ?? 'Position not set') }}</h3>
                                    <p class="mt-1 text-base text-slate-600">{{ $latestTracer->current_company ?? ($user->company ?? 'Company not set') }}</p>
                                </div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $statusBadgeClasses }}">{{ $employmentStatus }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="mt-5 grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div class="alumni-overview-detail rounded-[1.5rem] border border-slate-200 bg-white px-4 py-4 shadow-sm">
                                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Tracer Date</p>
                                <p class="mt-2 text-sm font-medium text-slate-900">{{ optional($latestTracer->tracer_date)->format('M d, Y') ?? ($tracerStatus['date_label'] ?? 'N/A') }}</p>
                            </div>
                            <div class="alumni-overview-detail rounded-[1.5rem] border border-slate-200 bg-white px-4 py-4 shadow-sm">
                                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Employment Type</p>
                                <p class="mt-2 text-sm font-medium text-slate-900">{{ $latestTracer->employment_type ?? 'N/A' }}</p>
                            </div>
                            <div class="alumni-overview-detail rounded-[1.5rem] border border-slate-200 bg-white px-4 py-4 shadow-sm">
                                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Industry</p>
                                <p class="mt-2 text-sm font-medium text-slate-900">{{ $latestTracer->industry ?? ($user->industry ?? 'N/A') }}</p>
                            </div>
                            <div class="alumni-overview-detail rounded-[1.5rem] border border-slate-200 bg-white px-4 py-4 shadow-sm">
                                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Job Satisfaction</p>
                                <p class="mt-2 text-sm font-medium text-slate-900">{{ $latestTracer->job_satisfaction ? $latestTracer->job_satisfaction . ' / 5' : 'N/A' }}</p>
                            </div>
                            <div class="alumni-overview-detail rounded-[1.5rem] border border-slate-200 bg-white px-4 py-4 shadow-sm">
                                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Monthly Income Range</p>
                                <p class="mt-2 text-sm font-medium text-slate-900">{{ $latestTracer->monthly_income ?: 'N/A' }}</p>
                            </div>
                        </div>

                        <div class="mt-4 grid grid-cols-1 gap-4">
                            <div class="alumni-overview-detail rounded-[1.5rem] border border-slate-200 bg-white px-4 py-4 shadow-sm">
                                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Skills Used</p>
                                <p class="mt-2 text-sm leading-relaxed text-slate-700">{{ $latestTracer->skills_used ?? 'N/A' }}</p>
                            </div>
                            <div class="alumni-overview-detail rounded-[1.5rem] border border-slate-200 bg-white px-4 py-4 shadow-sm">
                                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Recommendations</p>
                                <p class="mt-2 text-sm leading-relaxed text-slate-700">{{ $latestTracer->recommendations ?? 'N/A' }}</p>
                            </div>
                        </div>
                    @else
                        <div class="empty-state-card mt-1">
                            <span class="empty-state-card__icon">
                                <i class='bx bx-notepad'></i>
                            </span>
                            <p class="empty-state-card__title">No tracer record yet</p>
                            <p class="empty-state-card__body">Add a tracer entry to see it here.</p>
                        </div>
                    @endif
                </div>
            </section>

        </div>

        <div class="space-y-4">
            <section class="alumni-overview-panel alumni-overview-panel--checklist surface-card overflow-hidden p-0">
                <div class="alumni-overview-panel__mast border-b border-slate-200/80 bg-gradient-to-r from-white via-emerald-50/70 to-white px-5 py-4">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Readiness</p>
                            <h2 class="mt-2 text-lg font-semibold text-slate-900">Checklist</h2>
                            <p class="mt-1 text-sm text-slate-600">Details that still need attention.</p>
                        </div>
                        <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-100">{{ $profilePercent }}%</span>
                    </div>
                </div>

                <div class="p-4">
                    <div class="h-3 overflow-hidden rounded-full bg-slate-100">
                        <div class="h-full rounded-full bg-gradient-to-r from-emerald-500 via-teal-500 to-sky-500" role="progressbar" aria-label="Profile completion" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $profilePercent }}" style="width: {{ $profilePercent }}%"></div>
                    </div>

                    <div class="mt-5 space-y-2">
                        @foreach($profileChecklist as $item => $done)
                            @php
                                $checklistTarget = !$done ? ($checklistTargets[$item] ?? null) : null;
                            @endphp
                            @if($checklistTarget)
                                <a
                                    href="{{ $checklistTarget['url'] }}"
                                    class="alumni-overview-checklist-item group flex items-center justify-between gap-4 rounded-[1.35rem] border border-amber-100 bg-amber-50/75 px-4 py-3 transition-all duration-300 hover:-translate-y-0.5 hover:border-amber-200 hover:bg-amber-50 hover:shadow-sm"
                                >
                                    <div class="flex items-center gap-3">
                                        <span class="inline-flex h-9 w-9 items-center justify-center rounded-2xl bg-white text-amber-700 ring-1 ring-amber-200">
                                            <i class='bx bx-time-five text-lg'></i>
                                        </span>
                                        <div>
                                            <span class="block text-sm text-amber-900">{{ $item }}</span>
                                            <span class="mt-1 block text-[11px] font-medium uppercase tracking-[0.14em] text-slate-500 group-hover:text-slate-700">Open {{ $checklistTarget['page'] }}</span>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <span class="text-xs font-semibold text-amber-700">Needs update</span>
                                        <span class="mt-1 inline-flex items-center gap-1 text-xs font-medium text-slate-500 group-hover:text-slate-700">
                                            Go now
                                            <i class='bx bx-right-arrow-alt text-sm'></i>
                                        </span>
                                    </div>
                                </a>
                            @else
                                <div class="alumni-overview-checklist-item flex items-center justify-between gap-4 rounded-[1.35rem] border px-4 py-3 {{ $done ? 'border-emerald-100 bg-emerald-50/60' : 'border-amber-100 bg-amber-50/75' }}">
                                    <div class="flex items-center gap-3">
                                        <span class="inline-flex h-9 w-9 items-center justify-center rounded-2xl {{ $done ? 'bg-white text-emerald-700 ring-1 ring-emerald-200' : 'bg-white text-amber-700 ring-1 ring-amber-200' }}">
                                            <i class='bx {{ $done ? 'bx-check' : 'bx-time-five' }} text-lg'></i>
                                        </span>
                                        <span class="text-sm {{ $done ? 'text-emerald-900' : 'text-amber-900' }}">{{ $item }}</span>
                                    </div>
                                    <span class="text-xs font-semibold {{ $done ? 'text-emerald-700' : 'text-amber-700' }}">{{ $done ? 'Complete' : 'Needs update' }}</span>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            </section>

        </div>
    </div>

    <section id="dashboard-job-opportunities" class="alumni-job-opportunities-section surface-card overflow-hidden border border-emerald-100 bg-gradient-to-br from-white via-emerald-50/70 to-amber-50/45 p-0">
        <div class="flex flex-col gap-4 border-b border-emerald-950/10 px-6 py-5 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.22em] text-[#9a6800]">Job Opportunity</p>
                <h2 class="mt-2 text-2xl font-black leading-tight text-[#072714]">Current openings for alumni</h2>
                <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">Approved job posts and alumni-submitted opportunities are available from your portal after login.</p>
            </div>
            <div class="flex flex-col gap-2 sm:flex-row">
                <a href="{{ route('jobs') }}" class="alumni-job-action alumni-job-action--view inline-flex items-center justify-center rounded-xl border border-[#0B3B1F]/15 bg-white px-4 py-2.5 text-sm font-bold text-[#0B3B1F] shadow-sm hover:bg-[#EEF4EF]">
                    <i class='bx bx-briefcase-alt-2 mr-2 text-lg'></i>
                    View Opportunities
                </a>
                <a href="{{ route('jobs') }}" class="alumni-job-action alumni-job-action--post inline-flex items-center justify-center rounded-xl bg-[#862633] px-4 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-[#701f2a]">
                    <i class='bx bx-plus-circle mr-2 text-lg'></i>
                    Post Opportunity
                </a>
            </div>
        </div>

        <div class="p-5">
            @if($jobOpportunityListings->isNotEmpty())
                <div class="grid gap-3 lg:grid-cols-3">
                    @foreach($jobOpportunityListings as $jobOpportunity)
                        <a href="{{ route('job.detail', $jobOpportunity->id) }}" class="alumni-job-opening-card block rounded-2xl border border-emerald-950/10 bg-white/90 px-4 py-4 shadow-sm hover:border-emerald-200 hover:bg-white">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <h3 class="line-clamp-2 text-base font-extrabold leading-snug text-slate-950">{{ $jobOpportunity->title }}</h3>
                                    <p class="mt-1 truncate text-sm font-semibold text-slate-600">{{ $jobOpportunity->company ?? 'Company not specified' }}</p>
                                </div>
                                <span class="alumni-job-opening-card__arrow inline-flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100">
                                    <i class='bx bx-right-arrow-alt text-xl'></i>
                                </span>
                            </div>
                            <div class="mt-4 flex flex-wrap gap-2">
                                <span class="alumni-job-opening-card__type rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700 ring-1 ring-emerald-100">{{ $jobOpportunity->type ?? 'Job opportunity' }}</span>
                                <span class="alumni-job-opening-card__location rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-700 ring-1 ring-slate-200">{{ $jobOpportunity->location ?? 'Location not set' }}</span>
                            </div>
                            <p class="mt-3 text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">
                                Posted {{ !empty($jobOpportunity->created_at) ? \Carbon\Carbon::parse($jobOpportunity->created_at)->diffForHumans() : 'recently' }}
                            </p>
                        </a>
                    @endforeach
                </div>
            @else
                <div class="flex flex-col gap-4 rounded-2xl border border-dashed border-emerald-200 bg-white/75 px-5 py-5 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-start gap-3">
                        <span class="inline-flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100">
                            <i class='bx bx-briefcase-alt-2 text-2xl'></i>
                        </span>
                        <div>
                            <h3 class="text-base font-bold text-slate-900">No active job openings yet</h3>
                            <p class="mt-1 text-sm leading-6 text-slate-600">You can still post a job opportunity for OARL review.</p>
                        </div>
                    </div>
                    <a href="{{ route('jobs') }}" class="inline-flex items-center justify-center rounded-xl bg-[#862633] px-4 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-[#701f2a]">
                        Post Opportunity
                    </a>
                </div>
            @endif
        </div>
    </section>

    <section class="surface-card overflow-hidden border border-slate-200 p-0" aria-labelledby="dashboard-recent-updates-title">
        <div class="flex flex-col gap-3 border-b border-slate-200/80 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-emerald-700">For you</p>
                <h2 id="dashboard-recent-updates-title" class="mt-1 text-xl font-bold text-slate-900">Recent updates</h2>
            </div>
            <a href="{{ route('notifications.index') }}" class="inline-flex items-center gap-1 text-sm font-semibold text-emerald-800 hover:text-emerald-950">View all updates <i class='bx bx-right-arrow-alt text-lg'></i></a>
        </div>
        <div class="grid gap-3 p-4 sm:grid-cols-2 sm:p-5 lg:grid-cols-3">
            @forelse($recentUpdates as $update)
                <a href="{{ $update['open_url'] ?? route('notifications.index') }}" class="group rounded-2xl border border-slate-200 bg-white p-4 transition hover:border-emerald-200 hover:bg-emerald-50/40 focus:outline-none focus:ring-2 focus:ring-emerald-200">
                    <div class="flex items-start gap-3">
                        <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-800 ring-1 ring-emerald-100"><i class='bx {{ $update['icon'] ?? 'bx-bell' }} text-xl'></i></span>
                        <div class="min-w-0">
                            <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-emerald-700">{{ $update['category'] ?? 'Update' }} <span class="px-1 text-slate-400">·</span> {{ $update['time_label'] ?? 'Recently' }}</p>
                            <h3 class="mt-1 line-clamp-2 text-sm font-bold text-slate-900 group-hover:text-emerald-900">{{ $update['title'] ?? 'Portal update' }}</h3>
                            <p class="mt-1 line-clamp-2 text-xs leading-5 text-slate-600">{{ $update['summary'] ?? 'Open this update to see more.' }}</p>
                        </div>
                    </div>
                </a>
            @empty
                <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50/70 px-4 py-5 text-sm text-slate-600 sm:col-span-2 lg:col-span-3">
                    You’re all caught up. New announcements and job opportunities will appear here.
                </div>
            @endforelse
        </div>
    </section>
</div>
@endsection
