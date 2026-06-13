@extends('admin.layout')

@section('title', 'Job Management')

@section('content')
@php
    $locationOptions = ['Bukidnon', 'Cagayan de Oro', 'Davao City', 'Manila', 'Cebu', 'Remote', 'Abroad', 'Others'];
    $salaryOptions = array_values(array_diff(\App\Support\SystemOptions::salaryRanges(), ['Prefer not to say']));
    $jobCreateModalOpen = request()->boolean('create') || ($errors->any() && old('title') !== null);
@endphp
<div class="space-y-6">
    <div class="flex justify-end">
        <button
            type="button"
            id="openJobCreateModal"
            class="inline-flex items-center justify-center gap-2 rounded-2xl bg-blue-600 px-5 py-3.5 text-base font-semibold text-white shadow-sm transition-all duration-300 hover:-translate-y-0.5 hover:bg-blue-700 hover:shadow-md"
            aria-haspopup="dialog"
            aria-controls="jobCreateModal"
            aria-expanded="{{ $jobCreateModalOpen ? 'true' : 'false' }}"
        >
            <i class='bx bx-plus text-2xl'></i>
            Post New Job
        </button>
    </div>

    {{-- Statistics --}}
    <div class="portal-stat-grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4">
        <div data-tooltip="Current job posts stored for alumni browsing, excluding archived records" data-portal-card-link="{{ route('admin.jobs') }}#job-records" role="link" tabindex="0" aria-label="Open job posting records" class="portal-stat-card portal-stat-card--blue">
            <div class="portal-stat-card__layout">
                <span class="portal-stat-card__icon"><i class='bx bx-briefcase text-2xl'></i></span>
                <div class="portal-stat-card__content">
                    <p class="portal-stat-card__kicker">Job Posts Listed</p>
                    <p class="portal-stat-card__value">{{ $jobPostsCount }}</p>
                    <p class="portal-stat-card__meta">Current records</p>
                </div>
            </div>
        </div>
        <div data-tooltip="Active job posts that alumni can currently view" data-portal-card-link="{{ route('admin.jobs', ['status' => 'active']) }}#job-records" role="link" tabindex="0" aria-label="Show active job posts" class="portal-stat-card portal-stat-card--green">
            <div class="portal-stat-card__layout">
                <span class="portal-stat-card__icon"><i class='bx bx-check-circle text-2xl'></i></span>
                <div class="portal-stat-card__content">
                    <p class="portal-stat-card__kicker">Open Job Posts</p>
                    <p class="portal-stat-card__value">{{ $activeJobsCount }}</p>
                    <p class="portal-stat-card__meta">Visible to alumni</p>
                </div>
            </div>
        </div>
        <div data-tooltip="Alumni-submitted job opportunities waiting for admin approval or rejection" data-portal-card-link="{{ route('admin.jobs') }}#pending-job-submissions" role="link" tabindex="0" aria-label="Jump to pending alumni job submissions" class="portal-stat-card portal-stat-card--purple">
            <div class="portal-stat-card__layout">
                <span class="portal-stat-card__icon"><i class='bx bx-message-square-add text-2xl'></i></span>
                <div class="portal-stat-card__content">
                    <p class="portal-stat-card__kicker">Pending Alumni Requests</p>
                    <p class="portal-stat-card__value">{{ $pendingSubmissionCount }}</p>
                    <p class="portal-stat-card__meta">Needs admin review</p>
                </div>
            </div>
        </div>
        <div data-tooltip="Alumni job requests already approved or rejected by an admin" data-portal-card-link="{{ route('admin.jobs') }}#job-records" role="link" tabindex="0" aria-label="Open reviewed job records" class="portal-stat-card portal-stat-card--orange">
            <div class="portal-stat-card__layout">
                <span class="portal-stat-card__icon"><i class='bx bx-check-double text-2xl'></i></span>
                <div class="portal-stat-card__content">
                    <p class="portal-stat-card__kicker">Approved or Rejected</p>
                    <p class="portal-stat-card__value">{{ $reviewedSubmissionCount }}</p>
                    <p class="portal-stat-card__meta">Finished requests</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters and Search --}}
    <div data-tooltip="Filter job postings by search, company, type, or status" class="mb-6 overflow-hidden rounded-[1.5rem] border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 bg-gradient-to-r from-white via-slate-50 to-white px-5 py-4">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Filter Jobs</p>
            <p class="mt-1 text-sm text-slate-600">Search and narrow the current postings list.</p>
        </div>
        <form method="GET" action="{{ route('admin.jobs') }}" data-portal-preserve-scroll="true" data-portal-skip-animation="true" class="space-y-5 px-5 py-5">
            <div class="grid grid-cols-1 gap-4 xl:grid-cols-[minmax(0,1.4fr)_minmax(180px,0.8fr)_minmax(180px,0.8fr)_minmax(180px,0.8fr)]">
                <div class="space-y-2">
                    <label for="jobFilterSearch" class="block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Keyword</label>
                    <input
                        id="jobFilterSearch"
                        type="text"
                        name="search"
                        value="{{ $search ?? '' }}"
                        placeholder="Search jobs, companies, or locations"
                        class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700 shadow-sm transition-all duration-200 placeholder:text-slate-400 focus:border-blue-500 focus:outline-none focus:ring-4 focus:ring-blue-100"
                    >
                </div>
                <div class="space-y-2">
                    <label for="jobFilterCompany" class="block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Company</label>
                    <select id="jobFilterCompany" name="company" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700 shadow-sm transition-all duration-200 focus:border-blue-500 focus:outline-none focus:ring-4 focus:ring-blue-100">
                        <option value="">All Companies</option>
                        @foreach(($companyOptions ?? collect()) as $company)
                            <option value="{{ $company }}" {{ ($selectedCompany ?? '') === $company ? 'selected' : '' }}>{{ $company }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="space-y-2">
                    <label for="jobFilterType" class="block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Job Type</label>
                    <select id="jobFilterType" name="type" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700 shadow-sm transition-all duration-200 focus:border-blue-500 focus:outline-none focus:ring-4 focus:ring-blue-100">
                        <option value="">All Types</option>
                        @foreach(($typeOptions ?? collect()) as $type)
                            <option value="{{ $type }}" {{ ($selectedType ?? '') === $type ? 'selected' : '' }}>{{ $type }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="space-y-2">
                    <label for="jobFilterStatus" class="block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Status</label>
                    <select id="jobFilterStatus" name="status" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700 shadow-sm transition-all duration-200 focus:border-blue-500 focus:outline-none focus:ring-4 focus:ring-blue-100">
                        <option value="">All Status</option>
                        <option value="active" {{ ($selectedStatus ?? '') === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ ($selectedStatus ?? '') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                        <option value="archived" {{ ($selectedStatus ?? '') === 'archived' ? 'selected' : '' }}>Archived</option>
                    </select>
                </div>
            </div>
            <div class="flex flex-col gap-3 border-t border-slate-200 pt-4 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-sm text-slate-500">Use one or more filters to refine the job postings table.</p>
                <div class="flex flex-col gap-3 sm:flex-row">
                    <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white transition-colors hover:bg-blue-700">
                        <i class='bx bx-filter-alt text-base'></i>
                        Apply Filters
                    </button>
                    <a href="{{ route('admin.jobs') }}" data-portal-preserve-scroll="true" data-portal-skip-animation="true" class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition-colors hover:bg-slate-50">
                        <i class='bx bx-reset text-base'></i>
                        Reset
                    </a>
                </div>
            </div>
        </form>
    </div>

    {{-- Pending Alumni Job Submissions --}}
    <div id="pending-job-submissions" class="scroll-mt-6 mb-6 overflow-hidden rounded-[1.75rem] border border-emerald-950/10 bg-white/95 shadow-[0_24px_60px_rgba(7,24,14,0.13)]">
        <div class="flex flex-col gap-4 border-b border-emerald-950/10 bg-gradient-to-r from-white via-emerald-50/80 to-amber-50/50 px-6 py-5 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-emerald-700/80">Job Requests</p>
                <h2 class="mt-1 text-2xl font-extrabold tracking-normal text-slate-950">Pending Alumni Submissions</h2>
            </div>
            <span class="inline-flex items-center gap-2 rounded-full border border-amber-200 bg-amber-50 px-3.5 py-2 text-sm font-semibold text-amber-800 shadow-sm">
                <i class='bx bx-time-five text-lg'></i>
                {{ number_format(collect($pendingSubmissions ?? [])->count()) }} pending
            </span>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full border-separate border-spacing-0">
                <thead class="bg-[#f3f7f3]">
                    <tr>
                        <th class="w-[34%] px-7 py-4 text-left text-[0.68rem] font-bold uppercase tracking-[0.22em] text-emerald-950/70">Request</th>
                        <th class="px-5 py-4 text-left text-[0.68rem] font-bold uppercase tracking-[0.22em] text-emerald-950/70">Company</th>
                        <th class="px-5 py-4 text-left text-[0.68rem] font-bold uppercase tracking-[0.22em] text-emerald-950/70">Submitted By</th>
                        <th class="px-5 py-4 text-left text-[0.68rem] font-bold uppercase tracking-[0.22em] text-emerald-950/70">Contact</th>
                        <th class="px-5 py-4 text-left text-[0.68rem] font-bold uppercase tracking-[0.22em] text-emerald-950/70">Submitted</th>
                        <th class="px-7 py-4 text-right text-[0.68rem] font-bold uppercase tracking-[0.22em] text-emerald-950/70">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-emerald-950/10 bg-white/80">
                    @forelse(($pendingSubmissions ?? collect()) as $submission)
                    <tr class="group transition-colors duration-200 hover:bg-emerald-50/40">
                        <td class="px-7 py-5 align-top">
                            <div class="flex min-w-[22rem] items-start gap-4">
                                <span class="mt-0.5 hidden h-11 w-11 flex-shrink-0 items-center justify-center rounded-2xl border border-emerald-950/10 bg-gradient-to-br from-emerald-50 to-white text-xl text-emerald-800 shadow-sm sm:inline-flex">
                                    <i class='bx bx-briefcase-alt-2'></i>
                                </span>
                                <div class="min-w-0">
                                    <h3 class="text-base font-extrabold leading-snug tracking-normal text-slate-950">{{ $submission->title }}</h3>
                                    <p class="mt-2 text-sm leading-relaxed text-slate-600">Submitted for OARL approval.</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-5 py-5 align-top text-sm font-semibold text-slate-700">{{ $submission->company }}</td>
                        <td class="px-5 py-5 align-top text-sm font-semibold text-slate-600">{{ $submission->firstname }} {{ $submission->lastname }}</td>
                        <td class="px-5 py-5 align-top text-sm font-medium text-slate-600">
                            <span class="break-all">{{ $submission->contact_email }}</span>
                        </td>
                        <td class="px-5 py-5 align-top text-sm font-semibold text-slate-600">{{ \Carbon\Carbon::parse($submission->created_at)->format('M d, Y') }}</td>
                        <td class="px-7 py-5 align-top text-sm text-slate-500">
                            <div class="flex items-center justify-end gap-2">
                                <form
                                    action="{{ route('admin.job-submissions.approve', $submission->id) }}"
                                    method="POST"
                                    data-confirm-submit="Approve and publish this submitted job posting?"
                                    data-confirm-title="Approve job submission"
                                    data-confirm-label="Approve"
                                >
                                    @csrf
                                    <button type="submit" class="inline-flex items-center gap-1.5 rounded-full border border-emerald-200 bg-emerald-50 px-3.5 py-2 text-xs font-bold text-emerald-800 shadow-sm transition-all hover:-translate-y-0.5 hover:bg-emerald-100 hover:shadow-md">
                                        <i class='bx bx-check text-base'></i>
                                        Approve
                                    </button>
                                </form>
                                <button
                                    type="button"
                                    class="inline-flex items-center gap-1.5 rounded-full border border-rose-200 bg-rose-50 px-3.5 py-2 text-xs font-bold text-rose-700 shadow-sm transition-all hover:-translate-y-0.5 hover:bg-rose-100 hover:shadow-md"
                                    data-open-job-rejection
                                    data-reject-action="{{ route('admin.job-submissions.reject', $submission->id) }}"
                                    data-reject-title="{{ $submission->title }}"
                                    data-reject-company="{{ $submission->company }}"
                                    data-reject-submitter="{{ trim(($submission->firstname ?? '') . ' ' . ($submission->lastname ?? '')) }}"
                                >
                                    <i class='bx bx-x text-base'></i>
                                    Reject
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-10">
                            <div class="empty-state-card mx-auto max-w-lg">
                                <span class="empty-state-card__icon">
                                    <i class='bx bx-check-circle'></i>
                                </span>
                                <p class="empty-state-card__title">No pending job submissions</p>
                                <p class="empty-state-card__body">New alumni requests will appear here for review.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Jobs List --}}
    <section id="job-records" class="alumni-job-board admin-job-board scroll-mt-6">
        <div class="alumni-job-board__header">
            <div>
                <p class="alumni-job-board__kicker">Job Records</p>
                <h2 class="alumni-job-board__title">Job Postings</h2>
                <p class="alumni-job-board__copy">Published job records shown to alumni.</p>
            </div>
            <span class="alumni-job-board__count">
                <i class='bx bx-list-ul text-lg'></i>
                {{ number_format($jobs->total()) }} {{ $jobs->total() === 1 ? 'record' : 'records' }}
            </span>
        </div>

        @if($jobs->count() > 0)
            <div class="alumni-job-rows admin-job-rows">
                @foreach($jobs as $job)
                    @php
                        $detailRoute = route('admin.jobs.show', $job->id);
                        $postedAt = !empty($job->created_at ?? null) ? \Carbon\Carbon::parse($job->created_at) : null;
                        $deadlineAt = !empty($job->application_deadline ?? null) ? \Carbon\Carbon::parse($job->application_deadline) : null;
                        $deadlineLabel = $deadlineAt ? 'Apply by '.$deadlineAt->format('M d, Y') : 'Open until filled';
                        $deadlineClasses = $deadlineAt
                            ? ($deadlineAt->isPast()
                                ? 'alumni-job-chip--expired'
                                : 'alumni-job-chip--deadline')
                            : 'alumni-job-chip--neutral';
                        $statusText = !empty($job->archived_at ?? null)
                            ? 'Archived'
                            : (($job->is_active ?? true) ? 'Active' : 'Inactive');
                        $statusChipClass = match ($statusText) {
                            'Archived' => 'admin-job-chip--archived',
                            'Active' => 'admin-job-chip--active',
                            default => 'admin-job-chip--inactive',
                        };
                        $descriptionPreview = \Illuminate\Support\Str::limit((string) ($job->description ?? 'No description provided.'), 155);
                    @endphp
                    <article class="alumni-job-row admin-job-row">
                        <div class="alumni-job-row__main">
                            <span class="alumni-job-row__marker">
                                <i class='bx bx-briefcase-alt-2'></i>
                            </span>
                            <div class="alumni-job-row__content">
                                <div class="alumni-job-row__heading">
                                    <div class="min-w-0">
                                        <h3 class="alumni-job-row__title">{{ $job->title ?? 'Untitled job posting' }}</h3>
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
                                    <span class="alumni-job-chip {{ $statusChipClass }}">
                                        <i class='bx bx-check-shield text-sm'></i>
                                        {{ $statusText }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <aside class="alumni-job-row__aside admin-job-row__aside">
                            <p class="alumni-job-row__meta">
                                <span>{{ $postedAt ? 'Posted '.$postedAt->format('M d, Y') : 'Recently posted' }}</span>
                                @if(isset($job->views_count))
                                    <span>{{ number_format((int) $job->views_count) }} {{ (int) $job->views_count === 1 ? 'view' : 'views' }}</span>
                                @endif
                            </p>
                            <div class="admin-job-row__controls">
                                <a href="{{ $detailRoute }}" class="alumni-job-row__action">
                                    View Details
                                    <i class='bx bx-right-arrow-alt text-lg'></i>
                                </a>
                                <div class="admin-job-row__manage" aria-label="Manage {{ $job->title ?? 'job posting' }}">
                                    <a
                                        href="{{ route('admin.jobs.edit', $job->id) }}"
                                        data-tooltip="Edit job posting"
                                        class="admin-job-row__icon-action admin-job-row__icon-action--edit"
                                    >
                                        <i class='bx bx-edit text-lg'></i>
                                        <span class="sr-only">Edit job posting</span>
                                    </a>
                                    <form
                                        action="{{ route('admin.jobs.delete', $job->id) }}"
                                        method="POST"
                                        class="inline"
                                        data-confirm-submit="Delete this job posting? This will permanently remove the record from the database."
                                        data-confirm-title="Delete job posting"
                                        data-confirm-label="Delete"
                                        data-confirm-tone="danger"
                                    >
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" data-tooltip="Delete job posting" class="admin-job-row__icon-action admin-job-row__icon-action--delete">
                                            <i class='bx bx-trash text-lg'></i>
                                            <span class="sr-only">Delete job posting</span>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </aside>
                    </article>
                @endforeach
            </div>
        @else
            <div class="px-6 py-10">
                <div class="empty-state-card mx-auto max-w-lg">
                    <span class="empty-state-card__icon">
                        <i class='bx bx-briefcase-alt-2'></i>
                    </span>
                    <p class="empty-state-card__title">No job postings found</p>
                    <p class="empty-state-card__body">Try different filters or add a posting.</p>
                </div>
            </div>
        @endif

        <x-pagination-footer :paginator="$jobs">
            {{ $jobs->links() }}
        </x-pagination-footer>
    </section>
</div>

<div
    id="jobRejectionModal"
    class="fixed inset-0 z-50 hidden overflow-hidden"
    aria-hidden="true"
>
    <div id="jobRejectionModalBackdrop" class="absolute inset-0 bg-slate-950/50 backdrop-blur-sm"></div>
    <div class="relative flex min-h-full items-center justify-center overflow-y-auto px-4 py-6">
        <div class="w-full max-w-xl overflow-hidden rounded-[1.25rem] border border-white/70 bg-white shadow-[0_28px_80px_rgba(15,23,42,0.28)]">
            <div class="flex items-start justify-between gap-4 border-b border-rose-100 bg-gradient-to-r from-white via-rose-50/80 to-white px-6 py-5">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-rose-700">Reject Job Submission</p>
                    <h2 id="jobRejectionModalTitle" class="mt-2 text-xl font-extrabold text-slate-950">Send rejection reason</h2>
                    <p id="jobRejectionModalMeta" class="mt-1 text-sm leading-6 text-slate-600">Tell the alumni why this job cannot be approved yet.</p>
                </div>
                <button
                    type="button"
                    id="closeJobRejectionModal"
                    class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500 hover:bg-slate-100 hover:text-slate-700"
                    aria-label="Close rejection reason form"
                >
                    <i class='bx bx-x text-2xl'></i>
                </button>
            </div>

            <form
                id="jobRejectionForm"
                method="POST"
                class="space-y-5 px-6 py-5"
                data-confirm-submit="Reject this job submission and notify the alumni with this reason?"
                data-confirm-title="Reject job submission"
                data-confirm-label="Reject and Notify"
                data-confirm-tone="danger"
            >
                @csrf
                <div>
                    <label for="jobRejectionReason" class="block text-sm font-bold text-slate-800">Reason for rejection</label>
                    <textarea
                        id="jobRejectionReason"
                        name="admin_notes"
                        rows="5"
                        maxlength="1000"
                        required
                        class="mt-2 w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm leading-6 text-slate-700 shadow-sm focus:border-rose-300 focus:outline-none focus:ring-4 focus:ring-rose-100"
                        placeholder="Example: The contact email is invalid, or the job description is missing the application instructions."
                    ></textarea>
                    <p class="mt-2 text-xs leading-5 text-slate-500">This reason will be emailed to the alumni and shown in their portal notifications and job submission history.</p>
                </div>

                <div class="flex flex-col-reverse justify-end gap-3 border-t border-slate-200 pt-4 sm:flex-row">
                    <button type="button" id="cancelJobRejectionModal" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                        Cancel
                    </button>
                    <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl bg-rose-600 px-4 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-rose-700">
                        <i class='bx bx-send'></i>
                        Reject and Notify
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div
    id="jobCreateModal"
    class="fixed inset-0 z-50 overflow-hidden {{ $jobCreateModalOpen ? '' : 'hidden' }}"
    aria-hidden="{{ $jobCreateModalOpen ? 'false' : 'true' }}"
>
    <div id="jobCreateModalBackdrop" class="absolute inset-0 bg-slate-950/50 backdrop-blur-sm"></div>
    <div class="relative h-full overflow-y-auto overflow-x-hidden overscroll-contain">
    <div class="flex min-h-full items-start justify-center px-4 py-4 sm:px-6 sm:py-6 lg:items-center">
        <div class="flex w-full max-w-4xl max-h-[calc(100vh-2rem)] flex-col overflow-hidden rounded-[1.75rem] border border-white/70 bg-white shadow-[0_28px_80px_rgba(15,23,42,0.28)] sm:max-h-[calc(100vh-3rem)]">
            <div class="flex items-start justify-between gap-4 border-b border-slate-200 bg-gradient-to-r from-white via-emerald-50/70 to-white px-6 py-5">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700">Job Posting</p>
                    <h2 class="mt-2 text-2xl font-bold text-slate-900">Create New Job</h2>
                    <p class="mt-1 text-sm text-slate-600">Add a job posting without leaving the management page.</p>
                </div>
                <button
                    type="button"
                    id="closeJobCreateModal"
                    class="inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-slate-200 bg-white text-slate-500 transition-all duration-200 hover:bg-slate-100 hover:text-slate-700"
                    aria-label="Close job posting form"
                >
                    <i class='bx bx-x text-2xl'></i>
                </button>
            </div>

            <div class="flex-1 overflow-y-auto px-6 pb-6 pt-4">
                @if($errors->any() && old('title') !== null)
                    <div class="mb-5 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                        <ul class="list-disc pl-5">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form
                    action="{{ route('admin.jobs.store') }}"
                    method="POST"
                    class="space-y-6"
                    data-confirm-submit="Post this job now? Alumni will be able to view it according to its active status."
                    data-confirm-title="Post job"
                    data-confirm-label="Post Job"
                >
                    @csrf

                    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h3 class="text-lg font-semibold text-slate-900">Job Details</h3>
                        <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700">Job Title</label>
                                <input type="text" name="title" value="{{ old('title') }}" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700">Company</label>
                                <input type="text" name="company" value="{{ old('company') }}" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700">Job Type</label>
                                <select name="type" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                                    <option value="">Select Type</option>
                                    @foreach(['Full-time', 'Part-time', 'Contract', 'Internship'] as $type)
                                        <option value="{{ $type }}" {{ old('type') === $type ? 'selected' : '' }}>{{ $type }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700">Location</label>
                                <select id="job_create_location_select" name="location" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                                    <option value="">Select Location</option>
                                    @foreach($locationOptions as $option)
                                        <option value="{{ $option }}" {{ old('location') === $option ? 'selected' : '' }}>{{ $option }}</option>
                                    @endforeach
                                </select>
                                <div id="job_create_specific_location_group" class="mt-3 {{ old('location') === 'Others' ? '' : 'hidden' }}">
                                    <label class="mb-1 block text-sm font-medium text-gray-700">Specific Location</label>
                                    <input id="job_create_specific_location_input" type="text" name="specific_location" value="{{ old('specific_location') }}" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter specific location">
                                </div>
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700">Salary Range</label>
                                <select name="salary_range" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="">Select Salary Range</option>
                                    @foreach($salaryOptions as $range)
                                        <option value="{{ $range }}" {{ \App\Support\SystemOptions::salaryRangeLabel(old('salary_range')) === $range ? 'selected' : '' }}>{{ $range }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700">Application Deadline</label>
                                <input type="date" name="application_deadline" value="{{ old('application_deadline') }}" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div class="flex items-end md:col-span-2">
                                <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', '1') ? 'checked' : '' }} class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    Active job posting
                                </label>
                            </div>
                            <div class="md:col-span-2">
                                <label class="mb-1 block text-sm font-medium text-gray-700">Description</label>
                                <textarea name="description" rows="5" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" required>{{ old('description') }}</textarea>
                            </div>
                            <div class="md:col-span-2">
                                <label class="mb-1 block text-sm font-medium text-gray-700">Requirements</label>
                                <textarea name="requirements" rows="4" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">{{ old('requirements') }}</textarea>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h3 class="text-lg font-semibold text-slate-900">Contact Information</h3>
                        <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700">Contact Email</label>
                                <input type="email" name="contact_email" value="{{ old('contact_email') }}" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700">Contact Phone (Optional)</label>
                                <input type="text" name="contact_phone" value="{{ old('contact_phone') }}" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col-reverse justify-end gap-3 border-t border-slate-200 pt-4 sm:flex-row">
                        <button type="button" id="cancelJobCreateModal" class="rounded-xl bg-gray-100 px-4 py-2 text-sm font-semibold text-gray-700 transition-colors hover:bg-gray-200">
                            Cancel
                        </button>
                        <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-blue-700">
                            <i class='bx bx-send'></i>
                            Post Job
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    </div>
</div>

<script>
    function initializeJobRejectionModal() {
        const modal = document.getElementById('jobRejectionModal');
        const form = document.getElementById('jobRejectionForm');
        const reason = document.getElementById('jobRejectionReason');
        const title = document.getElementById('jobRejectionModalTitle');
        const meta = document.getElementById('jobRejectionModalMeta');
        const openButtons = document.querySelectorAll('[data-open-job-rejection]');
        const closeButtons = [
            document.getElementById('closeJobRejectionModal'),
            document.getElementById('cancelJobRejectionModal'),
            document.getElementById('jobRejectionModalBackdrop'),
        ];

        if (!modal || !form || !reason || !openButtons.length) {
            return;
        }

        const lockPageScroll = () => {
            document.documentElement.classList.add('overflow-hidden');
            document.body.classList.add('overflow-hidden');
        };

        const unlockPageScroll = () => {
            document.documentElement.classList.remove('overflow-hidden');
            document.body.classList.remove('overflow-hidden');
        };

        const closeModal = () => {
            modal.classList.add('hidden');
            modal.setAttribute('aria-hidden', 'true');
            form.removeAttribute('action');
            reason.value = '';
            unlockPageScroll();
        };

        const openModal = (button) => {
            form.action = button.dataset.rejectAction || '';
            const jobTitle = button.dataset.rejectTitle || 'this job submission';
            const company = button.dataset.rejectCompany || 'the submitted company';
            const submitter = button.dataset.rejectSubmitter || 'the alumni';

            if (title) {
                title.textContent = `Reject ${jobTitle}`;
            }

            if (meta) {
                meta.textContent = `Write the reason ${submitter} will receive for ${company}.`;
            }

            modal.classList.remove('hidden');
            modal.setAttribute('aria-hidden', 'false');
            lockPageScroll();
            window.setTimeout(() => reason.focus({ preventScroll: true }), 60);
        };

        openButtons.forEach((button) => {
            button.addEventListener('click', () => openModal(button));
        });

        closeButtons.forEach((button) => {
            button?.addEventListener('click', closeModal);
        });

        modal.addEventListener('click', (event) => {
            if (event.target === modal) {
                closeModal();
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
                closeModal();
            }
        });
    }

    function toggleJobCreateSpecificLocation() {
        const locationSelect = document.getElementById('job_create_location_select');
        const specificGroup = document.getElementById('job_create_specific_location_group');
        const specificInput = document.getElementById('job_create_specific_location_input');

        if (!locationSelect || !specificGroup || !specificInput) {
            return;
        }

        if (locationSelect.value === 'Others') {
            specificGroup.classList.remove('hidden');
            specificInput.setAttribute('required', 'required');
            return;
        }

        specificGroup.classList.add('hidden');
        specificInput.removeAttribute('required');
        specificInput.value = '';
    }

    function initializeJobCreateModal() {
        const modal = document.getElementById('jobCreateModal');
        const openButton = document.getElementById('openJobCreateModal');
        const closeButton = document.getElementById('closeJobCreateModal');
        const cancelButton = document.getElementById('cancelJobCreateModal');
        const backdrop = document.getElementById('jobCreateModalBackdrop');
        const locationSelect = document.getElementById('job_create_location_select');

        if (!modal || !openButton || !closeButton || !cancelButton || !backdrop) {
            return;
        }

        const lockPageScroll = () => {
            document.documentElement.classList.add('overflow-hidden');
            document.body.classList.add('overflow-hidden');
        };

        const unlockPageScroll = () => {
            document.documentElement.classList.remove('overflow-hidden');
            document.body.classList.remove('overflow-hidden');
        };

        const clearCreateQueryParam = () => {
            const url = new URL(window.location.href);

            if (!url.searchParams.has('create')) {
                return;
            }

            url.searchParams.delete('create');
            window.history.replaceState(window.history.state, '', `${url.pathname}${url.search}${url.hash}`);
        };

        const showModal = () => {
            modal.classList.remove('hidden');
            modal.setAttribute('aria-hidden', 'false');
            openButton.setAttribute('aria-expanded', 'true');
            lockPageScroll();
            toggleJobCreateSpecificLocation();
        };

        const hideModal = () => {
            modal.classList.add('hidden');
            modal.setAttribute('aria-hidden', 'true');
            openButton.setAttribute('aria-expanded', 'false');
            unlockPageScroll();
            clearCreateQueryParam();
        };

        openButton.addEventListener('click', showModal);
        closeButton.addEventListener('click', hideModal);
        cancelButton.addEventListener('click', hideModal);
        backdrop.addEventListener('click', hideModal);
        locationSelect?.addEventListener('change', toggleJobCreateSpecificLocation);

        modal.addEventListener('click', (event) => {
            if (event.target === modal) {
                hideModal();
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
                hideModal();
            }
        });

        toggleJobCreateSpecificLocation();

        if (modal.getAttribute('aria-hidden') === 'false') {
            lockPageScroll();
            clearCreateQueryParam();
        }
    }

    document.addEventListener('admin:page-ready', initializeJobRejectionModal, { once: true });
    document.addEventListener('admin:page-ready', initializeJobCreateModal, { once: true });
</script>
@endsection
