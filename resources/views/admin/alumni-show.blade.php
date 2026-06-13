@extends('admin.layout')

@section('title', 'Alumni Details')

@section('content')
@php
    $adminSnapshotJumpClass = 'w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-left transition-all duration-200 hover:-translate-y-0.5 hover:border-emerald-300 hover:bg-emerald-50/60 hover:shadow-sm focus:outline-none focus:ring-2 focus:ring-emerald-200';
    $employmentProofStatusClasses = static function ($record): string {
        return match ($record?->employmentProofStatus()) {
            \App\Models\TracerRecord::EMPLOYMENT_PROOF_STATUS_VERIFIED => 'bg-emerald-100 text-emerald-700 ring-1 ring-emerald-200',
            \App\Models\TracerRecord::EMPLOYMENT_PROOF_STATUS_REJECTED => 'bg-rose-100 text-rose-700 ring-1 ring-rose-200',
            \App\Models\TracerRecord::EMPLOYMENT_PROOF_STATUS_PENDING => 'bg-amber-100 text-amber-700 ring-1 ring-amber-200',
            default => 'bg-slate-100 text-slate-700 ring-1 ring-slate-200',
        };
    };
    $employmentProofReviewActionLabel = static function ($record, string $action): string {
        $isPending = $record?->employmentProofIsPending();

        return match ($action) {
            'verify' => $isPending ? 'Verify' : 'Change to Verified',
            'reject' => $isPending ? 'Reject' : 'Change to Rejected',
            'verify_proof' => $isPending ? 'Verify Proof' : 'Change to Verified',
            'reject_proof' => $isPending ? 'Reject Proof' : 'Change to Rejected',
            default => 'Change Review',
        };
    };
    $employmentProofReviewActionIcon = static function ($record, string $action): string {
        if (! $record?->employmentProofIsPending()) {
            return 'bx-edit-alt';
        }

        return $action === 'verify' ? 'bx-check-shield' : 'bx-x-circle';
    };
@endphp
<div class="p-6">
    <div class="admin-record-hero mb-6 p-6">
        <div class="admin-record-hero__content flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Alumni Details</h1>
                <p class="text-gray-600 mt-1">View alumni profile information</p>
                <div class="mt-3 inline-flex items-center gap-2 rounded-full bg-white px-3 py-1 text-sm text-gray-700 border border-gray-200">
                    <i class='bx bx-user text-blue-600'></i>
                    {{ $alumni->firstname ?? 'N/A' }} {{ $alumni->lastname ?? '' }}
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-3 md:justify-end">
                <a
                    href="{{ route('admin.alumni.edit', $alumni->id) }}"
                    class="inline-flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm font-semibold text-emerald-700 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:border-emerald-300 hover:bg-emerald-100 focus:outline-none focus:ring-2 focus:ring-emerald-200"
                >
                    <i class='bx bx-edit text-lg'></i>
                    Edit Record
                </a>
                <form
                    action="{{ route('admin.alumni.delete', $alumni->id) }}"
                    method="POST"
                    data-confirm-submit="Delete this alumni record? This action cannot be undone."
                    data-confirm-title="Delete alumni record"
                    data-confirm-label="Delete Record"
                    data-confirm-tone="danger"
                >
                    @csrf
                    @method('DELETE')
                    <button
                        type="submit"
                        class="inline-flex items-center gap-2 rounded-xl border border-rose-200 bg-white px-4 py-2.5 text-sm font-semibold text-rose-700 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:border-rose-300 hover:bg-rose-50 focus:outline-none focus:ring-2 focus:ring-rose-200"
                    >
                        <i class='bx bx-trash text-lg'></i>
                        Delete
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 lg:col-span-2">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Profile Information</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <p class="text-xs uppercase tracking-wide text-gray-500">First Name</p>
                    <p class="text-gray-900 font-medium mt-1">{{ $alumni->firstname ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wide text-gray-500">Middle Name</p>
                    <p class="text-gray-900 font-medium mt-1">{{ $alumni->middlename ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wide text-gray-500">Last Name</p>
                    <p class="text-gray-900 font-medium mt-1">{{ $alumni->lastname ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wide text-gray-500">Alumni ID No.</p>
                    <p class="text-gray-900 font-medium mt-1">{{ $alumni->alumni_idnum ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wide text-gray-500">Student ID No.</p>
                    <p class="text-gray-900 font-medium mt-1">{{ $alumni->student_idnum ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wide text-gray-500">Email</p>
                    <p class="text-gray-900 font-medium mt-1 break-all">{{ $alumni->email ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wide text-gray-500">Phone</p>
                    <p class="text-gray-900 font-medium mt-1">{{ $alumni->phone ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wide text-gray-500">College</p>
                    <p class="text-gray-900 font-medium mt-1">{{ $alumni->department ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wide text-gray-500">Degree</p>
                    <p class="text-gray-900 font-medium mt-1">{{ $alumni->degree ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wide text-gray-500">Course</p>
                    <p class="text-gray-900 font-medium mt-1">{{ $alumni->course ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wide text-gray-500">Major</p>
                    <p class="text-gray-900 font-medium mt-1">{{ $alumni->major ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wide text-gray-500">Batch</p>
                    <p class="text-gray-900 font-medium mt-1">{{ $alumni->year_graduated ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wide text-gray-500">Graduation Date</p>
                    <p class="text-gray-900 font-medium mt-1">{{ $alumni->dt_grad ? \Carbon\Carbon::parse($alumni->dt_grad)->format('M d, Y') : 'N/A' }}</p>
                </div>
                <div data-scroll-jump-destination="admin_employment_status">
                    <p class="text-xs uppercase tracking-wide text-gray-500">Employment Status</p>
                    <p class="text-gray-900 font-medium mt-1">
                        @if(($alumni->employed_after_grad ?? null) === 'Yes')
                            Employed
                        @elseif(($alumni->employed_after_grad ?? null) === 'No')
                            Unemployed
                        @else
                            Unknown
                        @endif
                    </p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Career Snapshot</h2>
            <div class="space-y-4">
                <div data-scroll-jump-destination="admin_industry">
                    <p class="text-xs uppercase tracking-wide text-gray-500">Industry</p>
                    <p class="text-gray-900 font-medium mt-1">{{ $alumni->industry ?? 'N/A' }}</p>
                </div>
                <div data-scroll-jump-destination="admin_current_position">
                    <p class="text-xs uppercase tracking-wide text-gray-500">Current Position</p>
                    <p class="text-gray-900 font-medium mt-1">{{ $alumni->current_position ?? 'N/A' }}</p>
                </div>
                <div data-scroll-jump-destination="admin_current_company">
                    <p class="text-xs uppercase tracking-wide text-gray-500">Current Company / Business</p>
                    <p class="text-gray-900 font-medium mt-1">{{ $alumni->company ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wide text-gray-500">Location</p>
                    <p class="text-gray-900 font-medium mt-1">{{ $alumni->location ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wide text-gray-500">Salary Range</p>
                    <p class="text-gray-900 font-medium mt-1">{{ \App\Support\SystemOptions::salaryRangeLabel($alumni->salary_range) ?? 'N/A' }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Profile Notes</h2>
            <div class="space-y-4">
                <div data-scroll-jump-destination="admin_skills">
                    <p class="text-xs uppercase tracking-wide text-gray-500">Skills</p>
                    <p class="text-gray-900 font-medium mt-1 whitespace-pre-line">{{ $alumni->skills ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wide text-gray-500">Achievements</p>
                    <p class="text-gray-900 font-medium mt-1 whitespace-pre-line">{{ $alumni->achievements ?? 'N/A' }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Scholarship Summary</h2>
            <div class="space-y-4">
                <div>
                    <p class="text-xs uppercase tracking-wide text-gray-500">Scholarship</p>
                    <p class="text-gray-900 font-medium mt-1">
                        @if($alumni->has_scholarship && $alumni->scholarship_name)
                            {{ $alumni->scholarship_name }}{{ $alumni->scholarship_year_awarded ? ' (' . $alumni->scholarship_year_awarded . ')' : '' }}
                        @else
                            None declared
                        @endif
                    </p>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wide text-gray-500">Scholarship Helped with Job</p>
                    <p class="text-gray-900 font-medium mt-1">
                        @if($alumni->has_scholarship)
                            {{ $alumni->scholarship_helped_get_job === null ? 'N/A' : ($alumni->scholarship_helped_get_job ? 'Yes' : 'No') }}
                        @else
                            N/A
                        @endif
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,1fr)_minmax(340px,0.95fr)]">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Tracer History</h2>
                    <p class="mt-1 text-sm text-gray-600">A running timeline of the tracer records on file for this alumni.</p>
                </div>
                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">{{ $tracerHistory->count() }} entries</span>
            </div>

            <div class="mt-4 space-y-3">
                @forelse($tracerHistory as $historyEntry)
                    @php
                        $record = $historyEntry['record'];
                        $historyBadges = $historyEntry['badges'] ?? [];
                        $historyNote = $historyEntry['note'] ?? null;
                        $isLatestHistoryEntry = $latestTracer && (string) $record->id === (string) $latestTracer->id;
                    @endphp
                    <article
                        class="rounded-2xl border border-slate-200 bg-white px-4 py-4 shadow-sm transition-all duration-300"
                        @if($isLatestHistoryEntry) data-scroll-jump-destination="admin_latest_tracer_entry" @endif
                    >
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <p class="text-sm font-semibold text-slate-900">{{ $record->tracer_date?->format('M j, Y') ?? 'N/A' }}</p>
                                <p class="mt-1 text-xs font-semibold uppercase tracking-[0.14em] text-emerald-700">{{ $record->employment_type ?? 'Unknown' }}</p>
                            </div>
                            <div class="flex flex-wrap items-center gap-2">
                                @if($record->job_satisfaction)
                                    <span class="rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700">{{ $record->job_satisfaction }}/5 satisfaction</span>
                                @endif
                                @if($record->employment_proof_path)
                                    <span class="rounded-full px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.12em] {{ $employmentProofStatusClasses($record) }}">
                                        {{ $record->employmentProofStatusLabel() }}
                                    </span>
                                    <a
                                        href="{{ route('admin.alumni.proof.download', ['alumni' => $alumni, 'record' => $record]) }}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold uppercase tracking-[0.12em] text-slate-700 transition-all duration-300 hover:-translate-y-0.5 hover:border-emerald-300 hover:text-emerald-700"
                                    >
                                        <i class='bx bxs-file-pdf text-base'></i>
                                        View PDF
                                    </a>
                                    @if(! $record->employmentProofIsVerified())
                                        <form method="POST" action="{{ route('admin.alumni.proof.verify', ['alumni' => $alumni, 'record' => $record]) }}">
                                            @csrf
                                            <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-3 py-2 text-xs font-semibold uppercase tracking-[0.12em] text-white transition-all duration-300 hover:-translate-y-0.5 hover:bg-emerald-700">
                                                <i class='bx {{ $employmentProofReviewActionIcon($record, 'verify') }} text-base'></i>
                                                {{ $employmentProofReviewActionLabel($record, 'verify') }}
                                            </button>
                                        </form>
                                    @endif
                                    @if(! $record->employmentProofIsRejected())
                                        <form method="POST" action="{{ route('admin.alumni.proof.reject', ['alumni' => $alumni, 'record' => $record]) }}">
                                            @csrf
                                            <button type="submit" class="inline-flex items-center gap-2 rounded-xl border border-rose-200 bg-white px-3 py-2 text-xs font-semibold uppercase tracking-[0.12em] text-rose-700 transition-all duration-300 hover:-translate-y-0.5 hover:bg-rose-50">
                                                <i class='bx {{ $employmentProofReviewActionIcon($record, 'reject') }} text-base'></i>
                                                {{ $employmentProofReviewActionLabel($record, 'reject') }}
                                            </button>
                                        </form>
                                    @endif
                                @endif
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
                        @if($record->employmentProofIsRejected() && $record->employment_proof_rejection_note)
                            <p class="mt-3 rounded-lg border border-rose-100 bg-rose-50 px-3 py-2 text-sm text-rose-700">
                                <span class="font-semibold">Rejection note:</span> {{ $record->employment_proof_rejection_note }}
                            </p>
                        @endif
                        @php
                            $adminHistoryDetailRows = [];
                            $addAdminHistoryDetailRow = function (string $label, $value, ?string $destination = null) use (&$adminHistoryDetailRows): void {
                                if (filled($value)) {
                                    $adminHistoryDetailRows[] = [
                                        'label' => $label,
                                        'value' => $value,
                                        'destination' => $destination,
                                    ];
                                }
                            };
                            $addAdminHistoryDetailRow('Company / Business', $record->current_company);
                            $addAdminHistoryDetailRow('Position', $record->current_position);
                            $addAdminHistoryDetailRow('Industry', $record->industry);
                            $addAdminHistoryDetailRow('Monthly Income Range', $record->monthly_income);

                            if ($record->further_education) {
                                $addAdminHistoryDetailRow(
                                    'Further Education',
                                    $record->education_level ?? 'Yes',
                                    $isLatestHistoryEntry ? 'admin_further_education' : null
                                );
                            }
                        @endphp
                        <div class="mt-3 grid grid-cols-1 gap-2 text-sm text-slate-600">
                            @forelse($adminHistoryDetailRows as $adminHistoryDetailRow)
                                <p @if($adminHistoryDetailRow['destination']) data-scroll-jump-destination="{{ $adminHistoryDetailRow['destination'] }}" @endif>
                                    <span class="font-medium text-slate-800">{{ $adminHistoryDetailRow['label'] }}:</span> {{ $adminHistoryDetailRow['value'] }}
                                </p>
                            @empty
                                <p class="text-slate-500">No additional employment details recorded for this entry.</p>
                            @endforelse
                        </div>
                    </article>
                @empty
                    <div class="rounded-2xl border border-dashed border-gray-200 bg-gray-50 px-4 py-6 text-center text-sm text-gray-500">
                        No tracer history available yet.
                    </div>
                @endforelse
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Latest Tracer Snapshot</h2>
            @if($latestTracer)
                @php
                    $adminLatestSnapshotCards = [];
                    $addAdminLatestSnapshotCard = function (string $label, $value, string $target = 'admin_latest_tracer_entry') use (&$adminLatestSnapshotCards): void {
                        if (filled($value)) {
                            $adminLatestSnapshotCards[] = [
                                'label' => $label,
                                'value' => $value,
                                'target' => $target,
                            ];
                        }
                    };
                    $adminLatestEmploymentType = trim((string) ($latestTracer->employment_type ?? ''));
                    $adminLatestEmploymentKey = mb_strtolower($adminLatestEmploymentType);
                    $adminLatestHasWorkDetails = filled($adminLatestEmploymentType)
                        && !in_array($adminLatestEmploymentKey, ['unemployed', 'never employed', 'no'], true);

                    $addAdminLatestSnapshotCard('Tracer Date', optional($latestTracer->tracer_date)->format('M j, Y'));
                    $addAdminLatestSnapshotCard('Employment Type', $latestTracer->employment_type);

                    if ($adminLatestHasWorkDetails) {
                        $addAdminLatestSnapshotCard('Current Position', $latestTracer->current_position, 'admin_current_position');
                        $addAdminLatestSnapshotCard('Current Company / Business', $latestTracer->current_company, 'admin_current_company');
                        $addAdminLatestSnapshotCard('Monthly Income Range', $latestTracer->monthly_income);
                        $addAdminLatestSnapshotCard('Industry', $latestTracer->industry, 'admin_industry');
                        $addAdminLatestSnapshotCard('Job Satisfaction', $latestTracer->job_satisfaction ? $latestTracer->job_satisfaction . ' / 5' : null);
                        $addAdminLatestSnapshotCard('Skills Used', $latestTracer->skills_used);
                    }

                    $adminLatestFirstJobTime = $latestTracer->time_to_first_job === 'Other'
                        ? ($latestTracer->time_to_first_job_other ?? 'Other')
                        : $latestTracer->time_to_first_job;
                    $adminLatestCompetencies = collect((array) ($latestTracer->first_job_useful_competencies ?? []))->join(', ');

                    $addAdminLatestSnapshotCard('Time to First Job', $adminLatestFirstJobTime);
                    $addAdminLatestSnapshotCard('Facilities Satisfaction', $latestTracer->facilities_satisfaction);
                    $addAdminLatestSnapshotCard('Useful Competencies', $adminLatestCompetencies);

                    if ($latestTracer->further_education) {
                        $addAdminLatestSnapshotCard('Further Education', $latestTracer->education_level ?? 'Yes', 'admin_further_education');
                    }
                @endphp
                <div class="space-y-4 text-sm">
                    @foreach($adminLatestSnapshotCards as $adminLatestSnapshotCard)
                        <button type="button" class="{{ $adminSnapshotJumpClass }}" data-scroll-jump-target="{{ $adminLatestSnapshotCard['target'] }}" aria-label="Go to {{ $adminLatestSnapshotCard['label'] }}">
                            <span class="block text-xs uppercase tracking-wide text-gray-500">{{ $adminLatestSnapshotCard['label'] }}</span>
                            <span class="mt-1 block text-gray-900 font-medium">{{ $adminLatestSnapshotCard['value'] }}</span>
                        </button>
                    @endforeach
                    @if($latestTracer->employment_proof_path)
                        <div class="rounded-xl border border-slate-200 bg-white px-4 py-4" data-scroll-jump-destination="admin_employment_proof">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <p class="text-xs uppercase tracking-wide text-gray-500">Employment Proof</p>
                                <span class="rounded-full px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.12em] {{ $employmentProofStatusClasses($latestTracer) }}">
                                    {{ $latestTracer->employmentProofStatusLabel() }}
                                </span>
                            </div>
                            <p class="text-gray-900 font-medium mt-1">
                                {{ $latestTracer->employment_proof_original_name ?: basename($latestTracer->employment_proof_path) }}
                            </p>
                            @if($latestTracer->employment_proof_uploaded_at)
                                <p class="mt-2 text-xs text-gray-500">Uploaded {{ $latestTracer->employment_proof_uploaded_at->format('M d, Y g:i A') }}</p>
                            @endif
                            <a
                                href="{{ route('admin.alumni.proof.download', ['alumni' => $alumni, 'record' => $latestTracer]) }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="mt-3 inline-flex items-center gap-2 rounded-lg bg-blue-600 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-700"
                            >
                                <i class='bx bxs-file-pdf text-base'></i>
                                View Employment Proof PDF
                            </a>
                            @if($latestTracer->employmentProofIsRejected() && $latestTracer->employment_proof_rejection_note)
                                <p class="mt-3 rounded-lg border border-rose-100 bg-rose-50 px-3 py-2 text-sm text-rose-700">{{ $latestTracer->employment_proof_rejection_note }}</p>
                            @endif
                            @if(! $latestTracer->employmentProofIsVerified())
                                <form method="POST" action="{{ route('admin.alumni.proof.verify', ['alumni' => $alumni, 'record' => $latestTracer]) }}" class="mt-3 inline-block">
                                    @csrf
                                    <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
                                        <i class='bx {{ $employmentProofReviewActionIcon($latestTracer, 'verify') }} text-base'></i>
                                        {{ $employmentProofReviewActionLabel($latestTracer, 'verify_proof') }}
                                    </button>
                                </form>
                            @endif
                            @if(! $latestTracer->employmentProofIsRejected())
                                <form method="POST" action="{{ route('admin.alumni.proof.reject', ['alumni' => $alumni, 'record' => $latestTracer]) }}" class="mt-3 space-y-2">
                                    @csrf
                                    <label class="block text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Rejection Note</label>
                                    <textarea name="rejection_note" rows="2" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-rose-300 focus:outline-none focus:ring-2 focus:ring-rose-100" placeholder="Optional note for why this proof is not accepted"></textarea>
                                    <button type="submit" class="inline-flex items-center gap-2 rounded-lg border border-rose-200 bg-white px-3 py-2 text-sm font-semibold text-rose-700 hover:bg-rose-50">
                                        <i class='bx {{ $employmentProofReviewActionIcon($latestTracer, 'reject') }} text-base'></i>
                                        {{ $employmentProofReviewActionLabel($latestTracer, 'reject_proof') }}
                                    </button>
                                </form>
                            @endif
                        </div>
                    @endif
                </div>
            @else
                <div class="rounded-2xl border border-dashed border-gray-200 bg-gray-50 px-4 py-6 text-center text-sm text-gray-500">
                    This alumni account has no tracer submissions yet.
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
