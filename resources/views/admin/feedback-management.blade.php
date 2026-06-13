@extends('admin.layout')

@section('title', 'Feedback Monitoring')

@section('content')
<div class="space-y-6">
    <div class="portal-stat-grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4">
        <div data-tooltip="Feedback that still needs admin action" data-portal-card-link="{{ route('admin.feedback', ['status' => 'Open']) }}#feedback-queue" role="link" tabindex="0" aria-label="Show open feedback" class="portal-stat-card portal-stat-card--orange">
            <div class="portal-stat-card__layout">
                <span class="portal-stat-card__icon">
                    <i class='bx bx-bell text-2xl'></i>
                </span>
                <div class="portal-stat-card__content">
                    <p class="portal-stat-card__kicker">Open Feedback</p>
                    <p class="portal-stat-card__value">{{ number_format((int) ($summary['activeFeedback'] ?? 0)) }}</p>
                    <p class="portal-stat-card__meta">Needs action</p>
                </div>
            </div>
        </div>
        <div data-tooltip="Open feedback that has no admin assigned yet" data-portal-card-link="{{ route('admin.feedback', ['status' => 'Open', 'assignee' => 'unassigned']) }}#feedback-queue" role="link" tabindex="0" aria-label="Show unassigned open feedback" class="portal-stat-card portal-stat-card--blue">
            <div class="portal-stat-card__layout">
                <span class="portal-stat-card__icon">
                    <i class='bx bx-user-x text-2xl'></i>
                </span>
                <div class="portal-stat-card__content">
                    <p class="portal-stat-card__kicker">No Admin Assigned</p>
                    <p class="portal-stat-card__value">{{ number_format((int) ($summary['unassignedFeedback'] ?? 0)) }}</p>
                    <p class="portal-stat-card__meta">Needs owner</p>
                </div>
            </div>
        </div>
        <div data-tooltip="High-priority feedback that still needs work" data-portal-card-link="{{ route('admin.feedback', ['priority' => 'High']) }}#feedback-queue" role="link" tabindex="0" aria-label="Show urgent feedback" class="portal-stat-card portal-stat-card--purple">
            <div class="portal-stat-card__layout">
                <span class="portal-stat-card__icon">
                    <i class='bx bx-error-circle text-2xl'></i>
                </span>
                <div class="portal-stat-card__content">
                    <p class="portal-stat-card__kicker">Urgent Feedback</p>
                    <p class="portal-stat-card__value">{{ number_format((int) ($summary['urgentFeedback'] ?? 0)) }}</p>
                    <p class="portal-stat-card__meta">High priority</p>
                </div>
            </div>
        </div>
        <div data-tooltip="Feedback that is already resolved or closed" data-portal-card-link="{{ route('admin.feedback', ['status' => 'Resolved']) }}#feedback-queue" role="link" tabindex="0" aria-label="Show resolved feedback" class="portal-stat-card portal-stat-card--green">
            <div class="portal-stat-card__layout">
                <span class="portal-stat-card__icon">
                    <i class='bx bx-check-double text-2xl'></i>
                </span>
                <div class="portal-stat-card__content">
                    <p class="portal-stat-card__kicker">Solved Feedback</p>
                    <p class="portal-stat-card__value">{{ number_format((int) ($summary['solvedFeedback'] ?? 0)) }}</p>
                    <p class="portal-stat-card__meta">Done</p>
                </div>
            </div>
        </div>
    </div>

    <div class="surface-card p-5">
        <form method="GET" action="{{ route('admin.feedback') }}" data-portal-preserve-scroll="true" data-portal-skip-animation="true" class="flex flex-wrap gap-4">
            <input
                type="text"
                name="search"
                value="{{ $search ?? '' }}"
                placeholder="Search subject, message, sender, or assignee..."
                class="min-w-64 flex-1 rounded-xl border border-gray-300 px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
            <select name="status" class="rounded-xl border border-gray-300 px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">All Statuses</option>
                @foreach ($statusOptions as $statusOption)
                    <option value="{{ $statusOption }}" {{ ($selectedStatus ?? '') === $statusOption ? 'selected' : '' }}>{{ $statusOption }}</option>
                @endforeach
            </select>
            <select name="priority" class="rounded-xl border border-gray-300 px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">All Priorities</option>
                @foreach ($priorityOptions as $priorityOption)
                    <option value="{{ $priorityOption }}" {{ ($selectedPriority ?? '') === $priorityOption ? 'selected' : '' }}>{{ $priorityOption }}</option>
                @endforeach
            </select>
            <select name="type" class="rounded-xl border border-gray-300 px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">All Types</option>
                @foreach ($typeOptions as $typeOption)
                    <option value="{{ $typeOption }}" {{ ($selectedType ?? '') === $typeOption ? 'selected' : '' }}>{{ $typeOption }}</option>
                @endforeach
            </select>
            <select name="assignee" class="rounded-xl border border-gray-300 px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">All Assignees</option>
                <option value="unassigned" {{ ($selectedAssignee ?? '') === 'unassigned' ? 'selected' : '' }}>Unassigned</option>
                @foreach ($assigneeOptions as $assigneeOption)
                    <option value="{{ $assigneeOption['id'] }}" {{ ($selectedAssignee ?? '') === $assigneeOption['id'] ? 'selected' : '' }}>
                        {{ $assigneeOption['label'] }}
                    </option>
                @endforeach
            </select>
            <button type="submit" class="rounded-xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white transition-colors hover:bg-blue-700">
                Apply Filters
            </button>
            <a href="{{ route('admin.feedback') }}" data-portal-preserve-scroll="true" data-portal-skip-animation="true" class="rounded-xl bg-gray-100 px-5 py-3 text-sm font-semibold text-gray-700 transition-colors hover:bg-gray-200">
                Reset
            </a>
        </form>
    </div>

    <div id="feedback-queue" class="surface-card scroll-mt-6 overflow-hidden">
        <div class="border-b border-gray-200 px-6 py-4">
            <h2 class="text-lg font-semibold text-gray-900">Feedback Queue</h2>
            <p class="mt-1 text-sm text-gray-500">Open a record to manage it.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Submitted</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Sender</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Feedback</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Priority</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Assigned</th>
                        <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Manage</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    @forelse ($feedbackItems as $item)
                        @php
                            $senderName = trim((string) ($item->submitter_firstname ?? '') . ' ' . (string) ($item->submitter_lastname ?? ''));
                            $senderLabel = $senderName !== '' ? $senderName : ($item->submitter_email ?? 'Anonymous / Removed User');
                            $assigneeName = trim((string) ($item->assignee_firstname ?? '') . ' ' . (string) ($item->assignee_lastname ?? ''));
                            $assigneeLabel = $assigneeName !== '' ? $assigneeName : ($item->assignee_email ?? 'Unassigned');
                            $detailRoute = route('admin.feedback.show', $item->id);
                            $statusClasses = match ($item->status) {
                                'Resolved', 'Closed' => 'bg-green-100 text-green-800',
                                'In Progress' => 'bg-blue-100 text-blue-800',
                                default => 'bg-amber-100 text-amber-800',
                            };
                            $priorityClasses = match ($item->priority) {
                                'High' => 'bg-rose-100 text-rose-800',
                                'Low' => 'bg-slate-100 text-slate-700',
                                default => 'bg-orange-100 text-orange-800',
                            };
                        @endphp
                        <tr
                            class="portal-clickable-row"
                            tabindex="0"
                            role="link"
                            aria-label="Open feedback {{ $item->subject }}"
                            onclick="window.location='{{ $detailRoute }}'"
                            onkeydown="if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); window.location='{{ $detailRoute }}'; }"
                        >
                            <td class="px-6 py-4 align-top text-sm text-gray-600">
                                <p class="font-medium text-gray-900">{{ $item->created_at ? \Carbon\Carbon::parse($item->created_at)->format('M d, Y h:i A') : 'N/A' }}</p>
                                <p class="mt-1 text-xs text-gray-500">{{ $item->created_at ? \Carbon\Carbon::parse($item->created_at)->diffForHumans() : '' }}</p>
                            </td>
                            <td class="px-6 py-4 align-top text-sm text-gray-700">
                                <p class="font-medium text-gray-900">{{ $senderLabel }}</p>
                                <p class="mt-1 break-all text-xs text-gray-500">{{ $item->submitter_email ?? 'No linked email' }}</p>
                            </td>
                            <td class="px-6 py-4 align-top text-sm text-gray-700">
                                <p class="font-semibold text-gray-900">{{ $item->subject }}</p>
                                <p class="mt-1 line-clamp-2 text-gray-500">{{ $item->message }}</p>
                            </td>
                            <td class="px-6 py-4 align-top text-sm text-gray-700">{{ $item->type ?? 'General Feedback' }}</td>
                            <td class="px-6 py-4 align-top">
                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $priorityClasses }}">{{ $item->priority ?? 'Medium' }}</span>
                            </td>
                            <td class="px-6 py-4 align-top">
                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $statusClasses }}">{{ $item->status ?? 'Open' }}</span>
                            </td>
                            <td class="px-6 py-4 align-top text-sm text-gray-700">
                                <p class="font-medium text-gray-900">{{ $assigneeLabel }}</p>
                                @if (!empty($item->assignee_email))
                                    <p class="mt-1 break-all text-xs text-gray-500">{{ $item->assignee_email }}</p>
                                @endif
                            </td>
                            <td class="portal-row-actions px-6 py-4 align-top text-right" onclick="event.stopPropagation()" onkeydown="event.stopPropagation()">
                                <a href="{{ $detailRoute }}" class="inline-flex items-center gap-1 rounded-md border border-blue-200 bg-blue-50 px-3 py-1.5 text-xs font-medium text-blue-700 hover:bg-blue-100">
                                    <i class='bx bx-show text-sm'></i>
                                    Review
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-10 text-center">
                                <div class="empty-state-card mx-auto max-w-lg">
                                    <span class="empty-state-card__icon">
                                        <i class='bx bx-message-rounded-dots'></i>
                                    </span>
                                    <p class="empty-state-card__title">No feedback matched the current filters</p>
                                    <p class="empty-state-card__body">Try different filters or wait for new submissions.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <x-pagination-footer :paginator="$feedbackItems" class="border-gray-200">
            {{ $feedbackItems->links() }}
        </x-pagination-footer>
    </div>
</div>
@endsection
