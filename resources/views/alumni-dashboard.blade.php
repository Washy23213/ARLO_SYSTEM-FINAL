@extends('components.layouts.dashboard')

@section('title', 'Dashboard')

@section('content')
@php
    $user = $user ?? auth()->user();
    $profileChecklist = $profileChecklist ?? [];
    $profilePercent = $profilePercent ?? 0;
    $profileCompleted = $profileCompleted ?? collect($profileChecklist)->filter()->count();
    $profileTotal = count($profileChecklist);
    $missingProfileItems = collect($profileChecklist)
        ->reject(fn ($done) => (bool) $done)
        ->keys()
        ->values();
    $firstMissingItem = $missingProfileItems->first();
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
    $profileCardState = $profilePercent >= 100 ? 'is-complete' : 'is-pending';
    $tracerCardState = ($tracerStatus['tone'] ?? 'amber') === 'emerald' ? 'is-complete' : 'is-pending';
    $jobPostsCardState = $jobPostsTotal > 0 ? 'has-posts' : 'is-empty';
    $dashboardNews = collect($dashboardNews ?? []);
    $dashboardNewsCards = $dashboardNews->map(function ($announcement) {
        $publishedAt = $announcement->published_at ?? $announcement->created_at ?? null;
        $expiresAt = $announcement->expires_at ?? null;

        return [
            'category' => filled($announcement->type ?? null) ? $announcement->type : 'Announcement',
            'title' => $announcement->title ?? 'CMU OARL Update',
            'summary' => \Illuminate\Support\Str::limit(strip_tags((string) ($announcement->content ?? 'A new update from CMU OARL.')), 110),
            'image' => filled($announcement->image_url ?? null) ? $announcement->image_url : asset('images/features/notifications.jpg'),
            'date' => $publishedAt ? \Carbon\Carbon::parse($publishedAt)->format('d M Y') : 'Recently',
            'expires' => $expiresAt ? 'Until ' . \Carbon\Carbon::parse($expiresAt)->format('d M Y') : null,
            'url' => route('announcement.detail', $announcement->id),
            'sample' => false,
        ];
    });

    $recentUpdateCards = $recentUpdates
        ->reject(fn ($update) => $dashboardNews->isNotEmpty() && strcasecmp((string) ($update['category'] ?? ''), 'Announcement') === 0)
        ->map(fn ($update) => [
            'category' => $update['category'] ?? 'Update',
            'title' => $update['title'] ?? 'Portal update',
            'summary' => \Illuminate\Support\Str::limit((string) ($update['summary'] ?? 'Open this update to see more.'), 110),
            'image' => asset(str_contains(strtolower((string) ($update['category'] ?? '')), 'job') ? 'images/features/jobs.jpg' : 'images/features/notifications.jpg'),
            'date' => $update['date_label'] ?? ($update['time_label'] ?? 'Recently'),
            'expires' => null,
            'url' => $update['open_url'] ?? route('notifications.index'),
            'sample' => false,
        ]);
    $dashboardNewsCards = $dashboardNewsCards
        ->concat($recentUpdateCards)
        ->unique('title')
        ->take(3)
        ->values();

    if ($dashboardNewsCards->isEmpty()) {
        $dashboardNewsCards = collect([
            ['category' => 'Community', 'title' => 'Alumni community spotlight', 'summary' => 'Alumni stories and achievements from across CMU.', 'image' => asset('images/features/profile.jpg'), 'date' => null, 'expires' => null, 'url' => route('notifications.index'), 'sample' => true],
            ['category' => 'Career update', 'title' => 'Career connections and opportunities', 'summary' => 'Career resources and opportunities for CMU alumni.', 'image' => asset('images/features/jobs.jpg'), 'date' => null, 'expires' => null, 'url' => route('notifications.index'), 'sample' => true],
            ['category' => 'Campus news', 'title' => 'News from the CMU community', 'summary' => 'University announcements and events.', 'image' => asset('images/features/notifications.jpg'), 'date' => null, 'expires' => null, 'url' => route('notifications.index'), 'sample' => true],
        ]);
    }
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
            <a href="{{ route('tracer.index') }}#tracer-form" class="mt-3 inline-flex min-h-9 items-center gap-1 rounded-lg border border-amber-200 bg-white px-3 py-2 text-xs font-bold text-amber-800 transition hover:bg-amber-50 focus:outline-none focus:ring-2 focus:ring-amber-300">
                {{ ($tracerStatus['tone'] ?? 'amber') === 'emerald' ? 'Update Tracer Survey' : 'Continue Tracer Survey' }}
                <i class='bx bx-right-arrow-alt text-base' aria-hidden="true"></i>
            </a>
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

    <section class="alumni-overview-panel alumni-overview-panel--checklist surface-card overflow-hidden p-0" aria-labelledby="dashboard-checklist-title">
        <div class="alumni-overview-panel__mast border-b border-slate-200/80 bg-gradient-to-r from-white via-emerald-50/70 to-white px-5 py-4">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Readiness</p>
                    <h2 id="dashboard-checklist-title" class="mt-2 text-lg font-semibold text-slate-900">Profile checklist</h2>
                    <p class="mt-1 text-sm text-slate-600">See what is complete and go directly to anything that still needs attention.</p>
                </div>
                <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-100">{{ $profilePercent }}%</span>
            </div>
        </div>

        <div class="p-4 sm:p-5">
            <div class="h-3 overflow-hidden rounded-full bg-slate-100">
                <div class="h-full rounded-full bg-gradient-to-r from-emerald-500 via-teal-500 to-sky-500" role="progressbar" aria-label="Profile completion" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $profilePercent }}" style="width: {{ $profilePercent }}%"></div>
            </div>

            <div class="mt-4 grid grid-cols-1 gap-2 md:grid-cols-2">
                @foreach($profileChecklist as $item => $done)
                    @php($checklistTarget = !$done ? ($checklistTargets[$item] ?? null) : null)
                    @if($checklistTarget)
                        <a href="{{ $checklistTarget['url'] }}" class="alumni-overview-checklist-item is-incomplete group flex items-center justify-between gap-3 rounded-xl border border-slate-200 bg-white px-3 py-3 transition hover:border-amber-300 hover:bg-white focus:outline-none focus:ring-2 focus:ring-amber-300">
                            <span class="flex min-w-0 items-center gap-3">
                                <span class="alumni-overview-checklist-item__icon inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-white text-amber-700 ring-1 ring-amber-200"><i class='bx bx-time-five text-lg' aria-hidden="true"></i></span>
                                <span class="min-w-0">
                                    <span class="alumni-overview-checklist-item__title block text-sm font-semibold text-amber-950">{{ $item }}</span>
                                    <span class="mt-0.5 flex flex-wrap items-center gap-x-1.5 text-xs">
                                        <span class="alumni-overview-checklist-item__status font-bold text-amber-700">Incomplete</span>
                                        <span class="text-slate-500">· Open {{ $checklistTarget['page'] }}</span>
                                    </span>
                                </span>
                            </span>
                            <span class="alumni-overview-checklist-item__action inline-flex shrink-0 items-center gap-1 text-xs font-semibold text-amber-800">Update <i class='bx bx-right-arrow-alt text-base' aria-hidden="true"></i></span>
                        </a>
                    @else
                        <div class="alumni-overview-checklist-item {{ $done ? 'is-complete' : 'is-incomplete' }} flex items-center justify-between gap-3 rounded-xl border px-3 py-3 {{ $done ? 'border-emerald-100 bg-emerald-50/60' : 'border-slate-200 bg-white' }}">
                            <span class="flex min-w-0 items-center gap-3">
                                <span class="alumni-overview-checklist-item__icon inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-white {{ $done ? 'text-emerald-700 ring-1 ring-emerald-200' : 'text-amber-700 ring-1 ring-amber-200' }}"><i class='bx {{ $done ? 'bx-check' : 'bx-time-five' }} text-lg' aria-hidden="true"></i></span>
                                <span class="alumni-overview-checklist-item__title text-sm font-medium {{ $done ? 'text-emerald-900' : 'text-amber-950' }}">{{ $item }}</span>
                            </span>
                            <span class="alumni-overview-checklist-item__status shrink-0 text-xs font-semibold {{ $done ? 'text-emerald-700' : 'text-amber-700' }}">{{ $done ? 'Complete' : 'Incomplete' }}</span>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    </section>

    <section id="dashboard-job-opportunities" class="alumni-job-opportunities-section surface-card overflow-hidden border border-emerald-100 p-0" aria-labelledby="dashboard-jobs-title">
        <div class="flex flex-col gap-3 border-b border-emerald-950/10 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-emerald-800">Career board</p>
                <h2 id="dashboard-jobs-title" class="mt-1 text-xl font-black text-slate-950">Job opportunities</h2>
                <p class="mt-1 text-sm text-slate-600">Browse current openings or share an opportunity with fellow alumni.</p>
            </div>
            <a href="{{ route('jobs') }}" class="inline-flex min-h-10 items-center justify-center gap-2 rounded-xl bg-emerald-800 px-4 py-2 text-sm font-bold text-white shadow-sm transition hover:bg-emerald-900 focus:outline-none focus:ring-2 focus:ring-emerald-300">
                View all opportunities <i class='bx bx-right-arrow-alt text-lg' aria-hidden="true"></i>
            </a>
        </div>

        <div class="grid grid-cols-1 gap-4 p-4 sm:grid-cols-2 sm:p-5 lg:grid-cols-3">
            @if($jobOpportunityListings->isNotEmpty())
                @foreach($jobOpportunityListings as $jobOpportunity)
                    <a href="{{ route('job.detail', $jobOpportunity->id) }}" class="alumni-dashboard-feature-card group overflow-hidden rounded-2xl border border-slate-200 bg-white transition hover:-translate-y-0.5 hover:border-emerald-200 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-emerald-300">
                        <img src="{{ asset('images/features/jobs.jpg') }}" alt="" class="alumni-dashboard-feature-card__image">
                        <span class="block p-4">
                            <span class="inline-flex rounded-full bg-emerald-50 px-2.5 py-1 text-[11px] font-bold text-emerald-800 ring-1 ring-emerald-100">{{ $jobOpportunity->type ?: 'Job opening' }}</span>
                            <span class="mt-3 block line-clamp-2 text-base font-extrabold leading-snug text-slate-950 group-hover:text-emerald-900">{{ $jobOpportunity->title }}</span>
                            <span class="mt-1 block truncate text-sm font-semibold text-slate-600">{{ $jobOpportunity->company ?: 'CMU alumni employer' }}</span>
                            <span class="mt-3 flex flex-wrap items-center justify-between gap-2 text-xs text-slate-600">
                                <span><i class='bx bx-map mr-1 text-emerald-800' aria-hidden="true"></i>{{ $jobOpportunity->location ?: 'Location not specified' }}</span>
                                <span>{{ !empty($jobOpportunity->created_at) ? \Carbon\Carbon::parse($jobOpportunity->created_at)->diffForHumans() : 'Recently posted' }}</span>
                            </span>
                        </span>
                    </a>
                @endforeach
            @else
                <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50/70 px-5 py-6 text-center sm:col-span-2 lg:col-span-3">
                    <span class="mx-auto inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-800 ring-1 ring-emerald-100"><i class='bx bx-briefcase-alt-2 text-2xl' aria-hidden="true"></i></span>
                    <h3 class="mt-3 text-base font-bold text-slate-900">No job opportunities available</h3>
                    <p class="mx-auto mt-1 max-w-xl text-sm leading-6 text-slate-600">Approved job opportunities will appear here once OARL publishes them.</p>
                    <a href="{{ route('jobs') }}" class="mt-4 inline-flex min-h-10 items-center justify-center gap-2 rounded-xl border border-emerald-200 bg-white px-4 py-2 text-sm font-bold text-emerald-900 transition hover:bg-emerald-50 focus:outline-none focus:ring-2 focus:ring-emerald-300">Open job board <i class='bx bx-right-arrow-alt text-lg' aria-hidden="true"></i></a>
                </div>
            @endif
        </div>
    </section>

    <section class="surface-card overflow-hidden border border-slate-200 p-0" aria-labelledby="dashboard-news-title">
        <div class="flex flex-col gap-3 border-b border-slate-200/80 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-emerald-700">CMU OARL</p>
                <h2 id="dashboard-news-title" class="mt-1 text-xl font-bold text-slate-950">Latest News &amp; Updates</h2>
            </div>
            <a href="{{ route('notifications.index') }}" class="inline-flex items-center gap-1 text-sm font-semibold text-emerald-800 hover:text-emerald-950 focus:outline-none focus:ring-2 focus:ring-emerald-300">View all updates <i class='bx bx-right-arrow-alt text-lg' aria-hidden="true"></i></a>
        </div>

        <div class="alumni-dashboard-news-carousel p-4 sm:p-5" data-dashboard-news-carousel role="region" aria-roledescription="carousel" aria-label="Latest news and updates">
            <div class="alumni-dashboard-news-carousel__viewport">
                <div class="alumni-dashboard-news-carousel__track">
            @if($dashboardNewsCards->isNotEmpty())
                @foreach($dashboardNewsCards as $newsCard)
                    <a data-news-carousel-item href="{{ $newsCard['url'] }}" class="alumni-dashboard-feature-card alumni-dashboard-news-card group overflow-hidden rounded-2xl border border-slate-200 bg-white transition hover:-translate-y-0.5 hover:border-emerald-200 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-emerald-300">
                        <img src="{{ $newsCard['image'] }}" alt="" class="alumni-dashboard-feature-card__image alumni-dashboard-news-card__image">
                        <span class="block p-4">
                            <span class="flex items-center justify-between gap-2">
                                <span class="text-xs font-semibold text-slate-500">{{ $newsCard['category'] }}</span>
                                @if($newsCard['sample'])
                                    <span class="rounded-full bg-slate-100 px-2 py-1 text-[10px] font-bold uppercase tracking-wide text-slate-600">Sample</span>
                                @elseif($newsCard['expires'])
                                    <span class="rounded-full bg-amber-50 px-2 py-1 text-[10px] font-bold text-amber-800">{{ $newsCard['expires'] }}</span>
                                @endif
                            </span>
                            <span class="mt-2 block line-clamp-2 text-base font-extrabold leading-snug text-slate-950 group-hover:text-emerald-900">{{ $newsCard['title'] }}</span>
                            <span class="mt-2 block line-clamp-2 min-h-10 text-sm leading-5 text-slate-600">{{ $newsCard['summary'] }}</span>
                            @if($newsCard['date'])
                                <span class="mt-4 block border-t border-slate-100 pt-3 text-xs font-medium text-slate-500">{{ $newsCard['date'] }}</span>
                            @endif
                        </span>
                    </a>
                @endforeach
            @else
                <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50/70 px-4 py-5 text-sm text-slate-600">New CMU OARL news and announcements will appear here.</div>
            @endif
                </div>
            </div>
        </div>
        @if($dashboardNewsCards->contains(fn ($newsCard) => $newsCard['sample']))
            <p class="px-5 pb-5 text-xs text-slate-500">Sample previews. Official updates appear here when published.</p>
        @endif
    </section>

    @if(session('show_profile_completion_reminder', false) && $profilePercent < 100 && $firstMissingItem && $firstMissingTarget)
        <dialog class="alumni-profile-reminder" data-profile-reminder aria-labelledby="profile-reminder-title" aria-describedby="profile-reminder-copy">
            <div class="alumni-profile-reminder__card">
                <div class="flex items-start justify-between gap-4">
                    <span class="alumni-profile-reminder__icon" aria-hidden="true"><i class='bx bx-id-card'></i></span>
                    <button type="button" data-profile-reminder-close class="alumni-profile-reminder__close" aria-label="Dismiss profile completion reminder"><i class='bx bx-x text-xl' aria-hidden="true"></i></button>
                </div>
                <p class="mt-4 text-xs font-bold uppercase tracking-[0.18em] text-amber-700">Profile completion reminder</p>
                <h2 id="profile-reminder-title" class="mt-2 text-2xl font-black text-slate-950">Complete your alumni profile</h2>
                <p id="profile-reminder-copy" class="mt-2 text-sm leading-6 text-slate-600">Your profile is {{ $profilePercent }}% complete. The next item to update is <strong class="text-slate-900">{{ $firstMissingItem }}</strong>. Completing your profile helps CMU OARL keep your alumni record current.</p>
                <a href="{{ $firstMissingTarget['url'] }}" class="mt-5 inline-flex min-h-11 w-full items-center justify-center gap-2 rounded-xl bg-emerald-800 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-emerald-900 focus:outline-none focus:ring-2 focus:ring-emerald-300">
                    {{ $firstMissingTarget['page'] === 'Profile' ? 'Complete profile' : 'Update tracer details' }} <i class='bx bx-right-arrow-alt text-lg' aria-hidden="true"></i>
                </a>
                <button type="button" data-profile-reminder-close class="mt-3 min-h-10 w-full rounded-xl px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-emerald-300">Remind me later</button>
            </div>
        </dialog>
    @endif
</div>
@endsection
