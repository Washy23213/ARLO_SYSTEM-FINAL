@php
    $auditLogPageTitle = $auditLogPageTitle ?? 'Audit Logs';
    $auditLogHeading = $auditLogHeading ?? 'System Activity Log';
    $auditLogRoute = $auditLogRoute ?? route('admin.audit-logs');
    $auditLogResetRoute = $auditLogResetRoute ?? $auditLogRoute;
    $latestActionWhen = !empty($summary['latestEntryAt'] ?? null)
        ? \Carbon\Carbon::parse($summary['latestEntryAt'])->diffForHumans()
        : null;
    $latestActionMeta = !empty($summary['latestActionLabel'] ?? null)
        ? trim($summary['latestActionLabel'] . ($latestActionWhen ? ' - ' . $latestActionWhen : ''))
        : 'No recent action';
    $activeAdminActors = collect($summary['activeAdminActors'] ?? []);
@endphp

@extends('admin.layout')

@section('title', $auditLogPageTitle)

@section('content')
<div class="space-y-6">
    <div class="portal-stat-grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4">
        <div data-tooltip="All saved admin activity records" data-portal-card-link="{{ $auditLogRoute }}#audit-log-records" role="link" tabindex="0" aria-label="Open all audit log records" class="portal-stat-card portal-stat-card--blue">
            <div class="portal-stat-card__layout">
                <span class="portal-stat-card__icon">
                    <i class='bx bx-history text-2xl'></i>
                </span>
                <div class="portal-stat-card__content">
                    <p class="portal-stat-card__kicker">All Actions</p>
                    <p class="portal-stat-card__value">{{ number_format((int) ($summary['totalLogs'] ?? 0)) }}</p>
                    <p class="portal-stat-card__meta">Saved records</p>
                </div>
            </div>
        </div>
        <div data-tooltip="Admin actions recorded since the start of today" data-portal-card-link="{{ $auditLogRoute }}#audit-log-records" role="link" tabindex="0" aria-label="Open today's audit log records" class="portal-stat-card portal-stat-card--green">
            <div class="portal-stat-card__layout">
                <span class="portal-stat-card__icon">
                    <i class='bx bx-calendar-check text-2xl'></i>
                </span>
                <div class="portal-stat-card__content">
                    <p class="portal-stat-card__kicker">Actions Today</p>
                    <p class="portal-stat-card__value">{{ number_format((int) ($summary['todayLogs'] ?? 0)) }}</p>
                    <p class="portal-stat-card__meta">Done today</p>
                </div>
            </div>
        </div>
        <div data-tooltip="Admins who made recorded actions in the last 7 days. This is activity, not availability." data-portal-card-link="#audit-active-admins" role="link" tabindex="0" aria-label="Jump to active admins list" class="portal-stat-card portal-stat-card--purple">
            <div class="portal-stat-card__layout">
                <span class="portal-stat-card__icon">
                    <i class='bx bx-shield-quarter text-2xl'></i>
                </span>
                <div class="portal-stat-card__content">
                    <p class="portal-stat-card__kicker">Admins Who Made Changes</p>
                    <p class="portal-stat-card__value">{{ number_format((int) ($summary['activeAdmins'] ?? 0)) }}</p>
                    <a href="#audit-active-admins" class="portal-stat-card__meta inline-flex items-center gap-1 font-semibold text-emerald-800 hover:text-emerald-950">
                        View names below
                        <i class='bx bx-chevron-down text-base'></i>
                    </a>
                </div>
            </div>
        </div>
        <div data-tooltip="Newest saved log: who did it, what action, and when." data-portal-card-link="{{ $auditLogRoute }}#audit-log-records" role="link" tabindex="0" aria-label="Open latest audit log records" class="portal-stat-card portal-stat-card--orange">
            <div class="portal-stat-card__layout">
                <span class="portal-stat-card__icon">
                    <i class='bx bx-time-five text-2xl'></i>
                </span>
                <div class="portal-stat-card__content">
                    <p class="portal-stat-card__kicker">Latest Action By</p>
                    <p class="portal-stat-card__value portal-stat-card__value--compact break-words">{{ $summary['latestActor'] ?? 'N/A' }}</p>
                    <p class="portal-stat-card__meta">{{ $latestActionMeta }}</p>
                </div>
            </div>
        </div>
    </div>

    <section id="audit-active-admins" class="surface-card scroll-mt-24 p-5">
        <div class="flex flex-wrap items-start justify-between gap-3 border-b border-gray-200 pb-4">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-emerald-700">Admin Activity</p>
                <h2 class="mt-1 text-lg font-semibold text-gray-900">Admins Who Made Changes</h2>
                <p class="mt-1 text-sm text-gray-600">Names listed here made at least one recorded action in the last 7 days.</p>
            </div>
            <span class="inline-flex items-center gap-2 rounded-full bg-emerald-50 px-3 py-1.5 text-sm font-semibold text-emerald-700 ring-1 ring-emerald-100">
                <i class='bx bx-shield-quarter'></i>
                {{ number_format($activeAdminActors->count()) }} {{ $activeAdminActors->count() === 1 ? 'admin' : 'admins' }}
            </span>
        </div>

        @if($activeAdminActors->isNotEmpty())
            <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                @foreach($activeAdminActors as $actor)
                    @php
                        $actorName = trim((string) ($actor['name'] ?? 'Unknown Admin'));
                        $actorInitials = collect(explode(' ', $actorName))
                            ->filter()
                            ->map(fn ($part) => \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($part, 0, 1)))
                            ->take(2)
                            ->implode('') ?: 'AD';
                        $actorLastSeenAt = !empty($actor['lastSeenAt'] ?? null)
                            ? \Carbon\Carbon::parse($actor['lastSeenAt'])
                            : null;
                    @endphp
                    <article class="rounded-2xl border border-emerald-100 bg-white/80 p-4 shadow-sm">
                        <div class="flex items-start gap-3">
                            <span class="inline-flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-2xl bg-emerald-700 text-sm font-bold text-white shadow-sm">
                                {{ $actorInitials }}
                            </span>
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3 class="truncate text-sm font-bold text-gray-950">{{ $actorName }}</h3>
                                    <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-[0.68rem] font-bold uppercase tracking-[0.12em] text-emerald-700 ring-1 ring-emerald-100">
                                        {{ $actor['role'] ?? 'Administrator' }}
                                    </span>
                                </div>
                                <p class="mt-1 break-all text-xs text-gray-500">{{ $actor['email'] ?? 'No linked account' }}</p>
                            </div>
                        </div>
                        <div class="mt-4 rounded-xl border border-gray-100 bg-gray-50/80 px-3 py-2">
                            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Latest recorded action</p>
                            <p class="mt-1 text-sm font-semibold text-gray-900">{{ $actor['latestAction'] ?? 'Recorded action' }}</p>
                            <p class="mt-1 text-xs text-gray-500">{{ $actorLastSeenAt ? $actorLastSeenAt->diffForHumans() : 'No recent time recorded' }}</p>
                        </div>
                    </article>
                @endforeach
            </div>
        @else
            <div class="empty-state-card mt-4">
                <span class="empty-state-card__icon">
                    <i class='bx bx-shield-quarter'></i>
                </span>
                <p class="empty-state-card__title">No admin changes in the last 7 days</p>
                <p class="empty-state-card__body">When an admin or super admin makes a recorded change, their name will appear here.</p>
            </div>
        @endif
    </section>

    <div class="surface-card p-5">
        <form method="GET" action="{{ $auditLogRoute }}" data-portal-preserve-scroll="true" data-portal-skip-animation="true" class="flex flex-wrap gap-4">
            <input
                type="text"
                name="search"
                value="{{ $search ?? '' }}"
                placeholder="Search action, description, admin, or IP..."
                class="min-w-64 flex-1 rounded-xl border border-gray-300 px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
            <select name="action" class="rounded-xl border border-gray-300 px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">All Actions</option>
                @foreach(($actionOptions ?? collect()) as $actionOption)
                    <option value="{{ $actionOption }}" {{ ($selectedAction ?? '') === $actionOption ? 'selected' : '' }}>{{ $actionOption }}</option>
                @endforeach
            </select>
            <button type="submit" class="rounded-xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white transition-colors hover:bg-blue-700">
                Apply Filters
            </button>
            <a href="{{ $auditLogResetRoute }}" data-portal-preserve-scroll="true" data-portal-skip-animation="true" class="rounded-xl bg-gray-100 px-5 py-3 text-sm font-semibold text-gray-700 transition-colors hover:bg-gray-200">
                Reset
            </a>
        </form>
    </div>

    <div id="audit-log-records" class="surface-card scroll-mt-6 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">{{ $auditLogHeading }}</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">When</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Actor</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Action</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Description</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">IP</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    @forelse($logs as $log)
                        @php
                            $actorName = trim((string) (($log->firstname ?? '') . ' ' . ($log->lastname ?? '')));
                            $actorLabel = $actorName !== '' ? $actorName : ($log->email ?? 'System');
                        @endphp
                        <tr>
                            <td class="px-6 py-4 align-top text-sm text-gray-600">
                                <p class="font-medium text-gray-900">{{ $log->created_at ? \Carbon\Carbon::parse($log->created_at)->format('M d, Y h:i A') : 'N/A' }}</p>
                                <p class="mt-1 text-xs text-gray-500">{{ $log->created_at ? \Carbon\Carbon::parse($log->created_at)->diffForHumans() : '' }}</p>
                            </td>
                            <td class="px-6 py-4 align-top text-sm text-gray-700">
                                <p class="font-medium text-gray-900">{{ $actorLabel }}</p>
                                <p class="mt-1 break-all text-xs text-gray-500">{{ $log->email ?? 'No linked account' }}</p>
                            </td>
                            <td class="px-6 py-4 align-top">
                                <span class="inline-flex rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-100">{{ $log->action }}</span>
                            </td>
                            <td class="px-6 py-4 align-top text-sm text-gray-700">
                                <p>{{ $log->description ?? 'No description provided.' }}</p>
                                @if(!empty($log->user_agent))
                                    <p class="mt-2 line-clamp-2 text-xs text-gray-500">{{ $log->user_agent }}</p>
                                @endif
                            </td>
                            <td class="px-6 py-4 align-top text-sm text-gray-600">{{ $log->ip_address ?? 'N/A' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-10 text-center">
                                <div class="empty-state-card mx-auto max-w-lg">
                                    <span class="empty-state-card__icon">
                                        <i class='bx bx-history'></i>
                                    </span>
                                    <p class="empty-state-card__title">No audit logs found</p>
                                    <p class="empty-state-card__body">Try different filters.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <x-pagination-footer :paginator="$logs" class="border-gray-200">
            {{ $logs->links() }}
        </x-pagination-footer>
    </div>
</div>
@endsection
