@extends('components.layouts.dashboard')

@section('title', 'Job Opportunities')

@section('content')
@php
    $jobTypeOptions = \App\Support\SystemOptions::jobTypes();
    $locationOptions = \App\Support\SystemOptions::locations();
    $jobSubmissionModalOpen = $errors->any() && old('title') !== null;
    $availableJobCount = isset($jobOpportunities)
        ? (method_exists($jobOpportunities, 'total') ? $jobOpportunities->total() : $jobOpportunities->count())
        : 0;
@endphp
<div class="alumni-jobs-page space-y-6">
    <section class="alumni-jobs-summary">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="alumni-jobs-summary__kicker">Approved Opportunities</p>
                <h2 class="alumni-jobs-summary__title">Browse roles shared for CMU alumni</h2>
                <p class="alumni-jobs-summary__copy">Explore reviewed openings from employers and alumni. New postings still appear in notifications, while this page keeps the full job board in one place.</p>
            </div>
            <div class="alumni-jobs-summary__metric">
                <span class="alumni-jobs-summary__metric-icon">
                    <i class='bx bx-briefcase-alt-2'></i>
                </span>
                <div>
                    <span class="alumni-jobs-summary__metric-value">{{ number_format($availableJobCount) }}</span>
                    <span class="alumni-jobs-summary__metric-label">open {{ $availableJobCount === 1 ? 'role' : 'roles' }}</span>
                </div>
            </div>
        </div>
    </section>

    <section class="alumni-job-board">
        <div class="alumni-job-board__header">
            <div>
                <p class="alumni-job-board__kicker">Job Board</p>
                <h2 data-tooltip="Approved job opportunities available to alumni" class="alumni-job-board__title">Current Job Opportunities</h2>
                <p class="alumni-job-board__copy">Reviewed job postings available to alumni.</p>
            </div>
            <span class="alumni-job-board__count">
                <i class='bx bx-list-ul text-lg'></i>
                {{ number_format($availableJobCount) }} {{ $availableJobCount === 1 ? 'record' : 'records' }}
            </span>
        </div>

        <form method="GET" action="{{ route('jobs') }}" class="grid grid-cols-1 gap-3 border-b border-slate-200 bg-slate-50/70 p-4 sm:grid-cols-2 lg:grid-cols-[minmax(0,1fr)_minmax(180px,0.45fr)_minmax(180px,0.45fr)_auto_auto] lg:items-end" role="search" aria-label="Search job opportunities">
            <div>
                <label for="job-board-search" class="mb-1 block text-xs font-semibold text-slate-700">Search jobs</label>
                <input id="job-board-search" type="search" name="q" value="{{ $jobSearch ?? '' }}" maxlength="120" placeholder="Job title, company, location, or keywords" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-100">
            </div>
            <div>
                <label for="job-board-type" class="mb-1 block text-xs font-semibold text-slate-700">Employment type</label>
                <select id="job-board-type" name="type" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                    <option value="">All types</option>
                    @foreach(($jobTypeOptions ?? []) as $option)
                        <option value="{{ $option }}" @selected(($jobTypeFilter ?? '') === $option)>{{ $option }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="job-board-location" class="mb-1 block text-xs font-semibold text-slate-700">Location</label>
                <select id="job-board-location" name="location" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                    <option value="">All locations</option>
                    @foreach(($jobLocationOptions ?? []) as $option)
                        <option value="{{ $option }}" @selected(($jobLocationFilter ?? '') === $option)>{{ $option }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-800 px-4 py-2.5 text-sm font-bold text-white hover:bg-emerald-900"><i class='bx bx-search'></i> Search</button>
            @if(($jobSearch ?? '') !== '' || ($jobTypeFilter ?? '') !== '' || ($jobLocationFilter ?? '') !== '')
                <a href="{{ route('jobs') }}" class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-100">Clear filters</a>
            @endif
        </form>

        @if(isset($jobOpportunities) && $jobOpportunities->count() > 0)
            <div class="alumni-job-rows">
                @foreach($jobOpportunities as $job)
                    @php
                        $postedAt = !empty($job->created_at ?? null) ? \Carbon\Carbon::parse($job->created_at) : null;
                        $deadlineAt = !empty($job->application_deadline ?? null) ? \Carbon\Carbon::parse($job->application_deadline) : null;
                        $deadlineLabel = $deadlineAt ? 'Apply by '.$deadlineAt->format('M d, Y') : 'Open until filled';
                        $deadlineClasses = $deadlineAt
                            ? ($deadlineAt->isPast()
                                ? 'alumni-job-chip--expired'
                                : 'alumni-job-chip--deadline')
                            : 'alumni-job-chip--neutral';
                        $descriptionPreview = \Illuminate\Support\Str::limit((string) ($job->description ?? 'No description provided.'), 155);
                    @endphp
                    <article class="alumni-job-row group">
                        <div class="alumni-job-row__main">
                            <span class="alumni-job-row__marker">
                                <i class='bx bx-briefcase-alt-2'></i>
                            </span>
                            <div class="alumni-job-row__content">
                                <div class="alumni-job-row__heading">
                                    <div class="min-w-0">
                                        <h3 class="alumni-job-row__title">{{ $job->title ?? 'Untitled job opportunity' }}</h3>
                                        <p class="alumni-job-row__company">{{ $job->company ?? 'Company not specified' }}</p>
                                    </div>
                                </div>
                                <p class="alumni-job-row__description">{{ $descriptionPreview }}</p>
                                <div class="alumni-job-row__chips">
                                    <span class="alumni-job-chip alumni-job-chip--type">
                                        <i class='bx bx-briefcase text-sm'></i>
                                        {{ $job->type ?? 'Type not specified' }}
                                    </span>
                                    <span class="alumni-job-chip alumni-job-chip--location">
                                        <i class='bx bx-map text-sm'></i>
                                        {{ $job->location ?? 'Location not specified' }}
                                    </span>
                                    <span class="alumni-job-chip {{ $deadlineClasses }}">
                                        <i class='bx bx-calendar text-sm'></i>
                                        {{ $deadlineLabel }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <aside class="alumni-job-row__aside">
                            <p class="alumni-job-row__meta">
                                <span>{{ $postedAt ? 'Posted '.$postedAt->diffForHumans() : 'Recently posted' }}</span>
                                @if(isset($job->views_count))
                                    <span>{{ number_format((int) $job->views_count) }} views</span>
                                @endif
                            </p>
                            <a href="{{ route('job.detail', $job->id) }}" class="alumni-job-row__action">
                                View Details
                                <i class='bx bx-right-arrow-alt text-lg'></i>
                            </a>
                        </aside>
                    </article>
                @endforeach
            </div>

            <x-pagination-footer :paginator="$jobOpportunities">
                {{ $jobOpportunities->links() }}
            </x-pagination-footer>
        @else
            <div class="px-6 py-10">
                <div class="empty-state-card mx-auto max-w-lg">
                    <span class="empty-state-card__icon">
                        <i class='bx bx-briefcase-alt-2'></i>
                    </span>
                    <p class="empty-state-card__title">No job opportunities available</p>
                    <p class="empty-state-card__body">
                        @if(($jobSearch ?? '') !== '' || ($jobTypeFilter ?? '') !== '' || ($jobLocationFilter ?? '') !== '')
                            No openings match these filters. Try another keyword or clear your filters.
                        @else
                            Approved job opportunities will appear here once OARL publishes them.
                        @endif
                    </p>
                    @if(($jobSearch ?? '') !== '' || ($jobTypeFilter ?? '') !== '' || ($jobLocationFilter ?? '') !== '')
                        <a href="{{ route('jobs') }}" class="mt-3 inline-flex items-center gap-1 text-sm font-semibold text-emerald-800">Clear filters <i class='bx bx-right-arrow-alt'></i></a>
                    @endif
                </div>
            </div>
        @endif
    </section>

    @if(auth()->check())
    <section class="alumni-job-submit-panel">
        <div class="alumni-job-submit-panel__head">
            <div>
                <p class="alumni-job-submit-panel__kicker">Alumni Contribution</p>
                <h2 data-tooltip="Alumni can post a job opportunity for OARL review" class="alumni-job-submit-panel__title">Share a Job Opportunity</h2>
                <p class="alumni-job-submit-panel__copy">Submit openings from your company or network. OARL reviews each post before it becomes visible to alumni.</p>
            </div>
            <span class="alumni-job-submit-panel__badge">OARL Review Required</span>
        </div>

        <div class="alumni-job-submit-panel__steps">
            <div class="alumni-job-submit-step">
                <span>1</span>
                <p>Job Details</p>
            </div>
            <div class="alumni-job-submit-step">
                <span>2</span>
                <p>Review Queue</p>
            </div>
            <div class="alumni-job-submit-step">
                <span>3</span>
                <p>Publication Decision</p>
            </div>
        </div>

        <button
            data-tooltip="Open the form to post a job opportunity"
            type="button"
            id="openJobFormBtn"
            class="alumni-job-submit-panel__button"
            aria-haspopup="dialog"
            aria-controls="jobSubmissionModal"
            aria-expanded="{{ $jobSubmissionModalOpen ? 'true' : 'false' }}"
        >
            <i class='bx bx-plus-circle'></i>
            Post Job Opportunity
        </button>
    </section>

    <div class="overflow-hidden rounded-[1.75rem] border border-emerald-950/10 bg-white/95 shadow-[0_24px_60px_rgba(7,24,14,0.13)]">
        <div class="flex flex-col gap-4 border-b border-emerald-950/10 bg-gradient-to-r from-white via-emerald-50/80 to-amber-50/50 px-6 py-5 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-emerald-700/80">Job Records</p>
                <h2 data-tooltip="Status of your posted job opportunities" class="mt-1 text-2xl font-extrabold tracking-normal text-slate-950">My Posted Opportunities</h2>
            </div>
            <span class="inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-white px-3.5 py-2 text-sm font-semibold text-emerald-800 shadow-sm">
                <i class='bx bx-list-ul text-lg'></i>
                {{ number_format(isset($mySubmissions) ? $mySubmissions->count() : 0) }} records
            </span>
        </div>
        @if(isset($mySubmissions) && $mySubmissions->isNotEmpty())
            <div class="overflow-x-auto">
                <table class="min-w-full border-separate border-spacing-0">
                    <thead class="bg-[#f3f7f3]">
                        <tr>
                            <th class="w-[46%] px-7 py-4 text-left text-[0.68rem] font-bold uppercase tracking-[0.22em] text-emerald-950/70">Job</th>
                            <th class="px-5 py-4 text-left text-[0.68rem] font-bold uppercase tracking-[0.22em] text-emerald-950/70">Company</th>
                            <th class="px-5 py-4 text-left text-[0.68rem] font-bold uppercase tracking-[0.22em] text-emerald-950/70">Posted</th>
                            <th class="px-7 py-4 text-right text-[0.68rem] font-bold uppercase tracking-[0.22em] text-emerald-950/70">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-emerald-950/10 bg-white/80">
                        @foreach($mySubmissions as $submission)
                            @php
                                $statusClass = $submission->status === 'approved'
                                    ? 'bg-emerald-50 text-emerald-800 ring-1 ring-emerald-200'
                                    : ($submission->status === 'rejected' ? 'bg-rose-50 text-rose-700 ring-1 ring-rose-200' : 'bg-amber-50 text-amber-800 ring-1 ring-amber-200');
                            @endphp
                            <tr class="group transition-colors duration-200 hover:bg-emerald-50/40">
                                <td class="px-7 py-5 align-top">
                                    <div class="flex min-w-[24rem] items-start gap-4">
                                        <span class="mt-0.5 hidden h-11 w-11 flex-shrink-0 items-center justify-center rounded-2xl border border-emerald-950/10 bg-gradient-to-br from-emerald-50 to-white text-xl text-emerald-800 shadow-sm sm:inline-flex">
                                            <i class='bx bx-briefcase-alt-2'></i>
                                        </span>
                                        <div class="min-w-0">
                                            <h3 class="text-base font-extrabold leading-snug tracking-normal text-slate-950">{{ $submission->title }}</h3>
                                            <p class="mt-2 text-sm leading-relaxed text-slate-600">Posted for OARL review.</p>
                                            @if($submission->status === 'rejected' && filled($submission->admin_notes ?? null))
                                                <div class="mt-3 rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-sm leading-6 text-rose-800">
                                                    <span class="font-bold">Reason rejected:</span>
                                                    {{ $submission->admin_notes }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-5 align-top text-sm font-semibold text-slate-700">{{ $submission->company }}</td>
                                <td class="px-5 py-5 align-top text-sm font-semibold text-slate-600">{{ \Carbon\Carbon::parse($submission->created_at)->format('M d, Y h:i A') }}</td>
                                <td class="px-7 py-5 align-top text-right">
                                    <span class="inline-flex rounded-full px-3 py-1.5 text-xs font-bold {{ $statusClass }}">
                                        {{ ucfirst($submission->status) }}
                                    </span>
                                    @if($submission->status === 'rejected' && filled($submission->admin_notes ?? null))
                                        <p class="mt-2 text-xs font-semibold text-rose-700">Reason included</p>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="px-6 py-10">
            <div class="empty-state-card mx-auto max-w-lg">
                <span class="empty-state-card__icon">
                    <i class='bx bx-message-square-detail'></i>
                </span>
                <p class="empty-state-card__title">No job opportunities posted yet</p>
                <p class="empty-state-card__body">Post an opportunity to see it here.</p>
            </div>
            </div>
        @endif
    </div>
    @else
    <div class="surface-card border-amber-200 bg-amber-50 p-5">
        <h2 class="mb-2 text-lg font-semibold text-amber-900">Post a Job Opportunity</h2>
        <p class="text-sm text-amber-800 mb-3">Please log in as an alumni user to post a job opportunity for OARL review.</p>
        <a href="{{ route('login') }}" class="inline-flex rounded-md bg-amber-600 px-4 py-2 text-white hover:bg-amber-700">Go to Login</a>
    </div>
    @endif
</div>

@if(auth()->check())
<div
    id="jobSubmissionModal"
    class="fixed inset-0 z-50 overflow-hidden {{ $jobSubmissionModalOpen ? '' : 'hidden' }}"
    aria-hidden="{{ $jobSubmissionModalOpen ? 'false' : 'true' }}"
>
    <div id="jobSubmissionModalBackdrop" class="absolute inset-0 bg-slate-950/45 backdrop-blur-sm"></div>
    <div class="relative h-full overflow-y-auto overflow-x-hidden overscroll-contain">
    <div class="flex min-h-full items-start justify-center px-4 py-4 sm:px-6 sm:py-6 lg:items-center">
        <div class="w-full max-w-3xl overflow-hidden rounded-[1.75rem] border border-white/70 bg-white shadow-[0_28px_80px_rgba(15,23,42,0.28)]">
            <div class="flex items-start justify-between gap-4 border-b border-slate-200 bg-gradient-to-r from-white via-emerald-50/70 to-white px-6 py-5">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700">Alumni Job Opportunity</p>
                    <h2 class="mt-2 text-2xl font-bold text-slate-900">Post a Job Opportunity</h2>
                    <p class="mt-1 text-sm text-slate-600">Provide the job details below. OARL will review the post before publication.</p>
                </div>
                <button
                    type="button"
                    id="closeJobSubmissionModal"
                    class="inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-slate-200 bg-white text-slate-500 transition-all duration-200 hover:bg-slate-100 hover:text-slate-700"
                    aria-label="Close job submission form"
                >
                    <i class='bx bx-x text-2xl'></i>
                </button>
            </div>

            <div class="max-h-[calc(100vh-5rem)] overflow-y-auto px-6 py-6">
                @if($jobSubmissionModalOpen)
                    <div class="mb-5 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                        <ul class="list-disc pl-5">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form
                    action="{{ route('jobs.submit') }}"
                    method="POST"
                    id="jobPostingForm"
                    class="grid grid-cols-1 gap-4 md:grid-cols-2"
                    data-confirm-submit="Post this job opportunity for OARL review?"
                    data-confirm-title="Post job opportunity"
                    data-confirm-label="Post for Review"
                >
                    @csrf
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Job Title</label>
                        <input name="title" type="text" placeholder="e.g. Junior Accountant" class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-100" value="{{ old('title') }}" required>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Company</label>
                        <input name="company" type="text" placeholder="Company name" class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-100" value="{{ old('company') }}" required>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Type</label>
                        <select name="type" class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-100" required>
                            <option value="">Select type</option>
                            @foreach($jobTypeOptions as $jobTypeOption)
                                <option value="{{ $jobTypeOption }}" {{ old('type') === $jobTypeOption ? 'selected' : '' }}>{{ $jobTypeOption }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Location</label>
                        <select id="job_location_select" name="location" class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-100" required>
                            <option value="">Select location</option>
                            @foreach($locationOptions as $locationOption)
                                <option value="{{ $locationOption }}" {{ old('location') === $locationOption ? 'selected' : '' }}>{{ $locationOption }}</option>
                            @endforeach
                        </select>
                        <div id="job_specific_location_group" class="mt-3 {{ old('location') === 'Others' ? '' : 'hidden' }}">
                            <input id="job_specific_location_input" name="specific_location" type="text" value="{{ old('specific_location') }}" placeholder="Enter specific location" class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                        </div>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Contact Email</label>
                        <input name="contact_email" type="email" placeholder="hr@company.com" class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-100" value="{{ old('contact_email') }}" required>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Application Deadline</label>
                        <input name="application_deadline" type="date" class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-100" value="{{ old('application_deadline') }}">
                    </div>
                    <div class="md:col-span-2">
                        <label class="mb-1 block text-sm font-medium text-gray-700">Job Description</label>
                        <textarea name="description" placeholder="Write key responsibilities and overview..." class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-100" rows="4" required>{{ old('description') }}</textarea>
                    </div>
                    <div class="md:col-span-2">
                        <label class="mb-1 block text-sm font-medium text-gray-700">Requirements</label>
                        <textarea name="requirements" placeholder="One requirement per line" class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-100" rows="3">{{ old('requirements') }}</textarea>
                    </div>
                    <div class="md:col-span-2 flex flex-col-reverse gap-3 border-t border-slate-200 pt-4 sm:flex-row sm:justify-end">
                        <button data-tooltip="Close the job posting form" type="button" id="cancelJobFormBtn" class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-gray-700 hover:bg-gray-50">
                            Cancel
                        </button>
                        <button data-tooltip="Post this job opportunity for OARL review" type="submit" class="inline-flex items-center justify-center rounded-md bg-emerald-600 px-4 py-2 text-white hover:bg-emerald-700">
                            <i class='bx bx-send mr-2'></i> Post for Review
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    </div>
</div>
@endif

<script>
    (function () {
        const locationSelect = document.getElementById('job_location_select');
        const specificLocationGroup = document.getElementById('job_specific_location_group');
        const specificLocationInput = document.getElementById('job_specific_location_input');
        const toggleSpecificLocation = function () {
            if (!locationSelect || !specificLocationGroup || !specificLocationInput) return;

            if (locationSelect.value === 'Others') {
                specificLocationGroup.classList.remove('hidden');
                specificLocationInput.setAttribute('required', 'required');
            } else {
                specificLocationGroup.classList.add('hidden');
                specificLocationInput.removeAttribute('required');
                specificLocationInput.value = '';
            }
        };

        if (locationSelect) {
            locationSelect.addEventListener('change', toggleSpecificLocation);
            toggleSpecificLocation();
        }

        const openBtn = document.getElementById('openJobFormBtn');
        const cancelBtn = document.getElementById('cancelJobFormBtn');
        const closeBtn = document.getElementById('closeJobSubmissionModal');
        const modal = document.getElementById('jobSubmissionModal');
        const backdrop = document.getElementById('jobSubmissionModalBackdrop');
        const form = document.getElementById('jobPostingForm');
        if (!openBtn || !form || !modal || !backdrop || !closeBtn) return;

        const lockPageScroll = function () {
            document.documentElement.classList.add('overflow-hidden');
            document.body.classList.add('overflow-hidden');
        };

        const unlockPageScroll = function () {
            document.documentElement.classList.remove('overflow-hidden');
            document.body.classList.remove('overflow-hidden');
        };

        const openModal = function () {
            modal.classList.remove('hidden');
            modal.setAttribute('aria-hidden', 'false');
            openBtn.setAttribute('aria-expanded', 'true');
            lockPageScroll();
            const firstInput = form.querySelector('input[name="title"]');
            if (firstInput) firstInput.focus();
        };

        const closeModal = function () {
            modal.classList.add('hidden');
            modal.setAttribute('aria-hidden', 'true');
            openBtn.setAttribute('aria-expanded', 'false');
            unlockPageScroll();
        };

        openBtn.addEventListener('click', openModal);
        closeBtn.addEventListener('click', closeModal);
        backdrop.addEventListener('click', closeModal);

        if (cancelBtn) {
            cancelBtn.addEventListener('click', closeModal);
        }

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
                closeModal();
            }
        });

        if (modal.getAttribute('aria-hidden') === 'false') {
            lockPageScroll();
        }
    })();
</script>
@endsection
