@extends('components.layouts.dashboard')

@section('title', 'Notifications')

@section('content')
<div class="space-y-6">
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <div class="surface-card overflow-hidden p-6">
            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">Total Updates</p>
            <p class="mt-4 text-5xl font-black leading-none text-slate-900">{{ $totalNotifications }}</p>
            <p class="mt-4 text-sm text-slate-600">Admin announcements, Facebook updates, jobs, and account activity currently shown in your feed.</p>
        </div>
        <div class="surface-card overflow-hidden border border-emerald-100 bg-gradient-to-br from-emerald-50/60 via-white to-emerald-100/30 p-6">
            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-emerald-700">Needs Attention</p>
            <p class="mt-4 text-5xl font-black leading-none text-emerald-700">{{ $unreadNotifications }}</p>
            <p class="mt-4 text-sm text-slate-600">Items that still need your attention.</p>
        </div>
    </div>

    <div class="overflow-hidden rounded-[1.75rem] border border-emerald-950/10 bg-white/95 shadow-[0_24px_60px_rgba(7,24,14,0.13)]">
        <div class="border-b border-emerald-950/10 bg-gradient-to-r from-white via-emerald-50/80 to-amber-50/50 px-6 py-5">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.2em] text-emerald-700/80">Activity Records</p>
                    <h2 class="mt-1 text-2xl font-extrabold tracking-normal text-slate-950">Recent activity</h2>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <span class="inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-white px-3.5 py-2 text-sm font-semibold text-emerald-800 shadow-sm">
                        <i class='bx bx-list-ul text-lg'></i>
                        {{ number_format($totalNotifications) }} records
                    </span>
                    <span class="inline-flex items-center gap-2 rounded-full border border-sky-100 bg-sky-50 px-3.5 py-2 text-sm font-semibold text-sky-700 shadow-sm">
                        <i class='bx bx-bell text-lg'></i>
                        {{ number_format($unreadNotifications) }} unread
                    </span>
                    <a href="{{ route('notifications.index', ['status' => 'all']) }}" data-portal-preserve-scroll="true" data-portal-skip-animation="true" class="rounded-full px-4 py-2 text-sm font-semibold transition-all duration-300 {{ $status === 'all' ? 'bg-[#0B3B1F] text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">All</a>
                    <a href="{{ route('notifications.index', ['status' => 'unread']) }}" data-portal-preserve-scroll="true" data-portal-skip-animation="true" class="rounded-full px-4 py-2 text-sm font-semibold transition-all duration-300 {{ $status === 'unread' ? 'bg-emerald-600 text-white shadow-sm' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100' }}">Unread</a>
                    <a href="{{ route('notifications.index', ['status' => 'read']) }}" data-portal-preserve-scroll="true" data-portal-skip-animation="true" class="rounded-full px-4 py-2 text-sm font-semibold transition-all duration-300 {{ $status === 'read' ? 'bg-sky-600 text-white shadow-sm' : 'bg-sky-50 text-sky-700 hover:bg-sky-100' }}">Read</a>
                    @if ($unreadNotifications > 0)
                        <form method="POST" action="{{ route('notifications.read-all') }}">
                            @csrf
                            <button type="submit" class="inline-flex items-center rounded-full border border-[#0B3B1F]/15 bg-white px-4 py-2 text-sm font-semibold text-[#0B3B1F] transition-all duration-300 hover:bg-[#0B3B1F] hover:text-white">
                                <i class='bx bx-check-double mr-2 text-lg'></i> Mark All Read
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>

        @if ($activityItems->count() > 0)
            <div class="divide-y divide-emerald-950/10 bg-white/80">
                @foreach ($activityItems as $item)
                    @php
                        $isJobItem = str_contains(strtolower((string) $item['category']), 'job');
                        $isFacebookItem = ($item['icon'] ?? '') === 'bxl-facebook-circle';
                        $markerClasses = $item['is_unread']
                            ? ($isFacebookItem
                                ? 'bg-[#1877F2] shadow-[0_0_0_4px_rgba(24,119,242,0.14)]'
                                : ($isJobItem
                                ? 'bg-blue-500 shadow-[0_0_0_4px_rgba(59,130,246,0.14)]'
                                : 'bg-emerald-500 shadow-[0_0_0_4px_rgba(16,185,129,0.14)]'))
                            : ($isFacebookItem ? 'bg-blue-200' : ($isJobItem ? 'bg-blue-200' : 'bg-emerald-200'));
                        $iconClasses = $item['is_unread']
                            ? ($isFacebookItem
                                ? 'bg-white text-[#1877F2] ring-1 ring-blue-100 shadow-sm group-hover:scale-105'
                                : ($isJobItem
                                ? 'bg-blue-600 text-white shadow-sm group-hover:scale-105'
                                : 'bg-emerald-600 text-white shadow-sm group-hover:scale-105'))
                            : ($isFacebookItem
                                ? 'bg-white text-[#1877F2] ring-1 ring-blue-100 group-hover:bg-blue-50'
                                : ($isJobItem
                                ? 'bg-blue-50 text-blue-700 ring-1 ring-blue-100 group-hover:bg-blue-100'
                                : 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100 group-hover:bg-emerald-100'));
                        $metaBadgeClasses = 'rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-[0.12em] text-slate-700';
                        $priorityLabel = trim((string) ($item['priority'] ?? ''));
                        $priorityBadgeClasses = match ($priorityLabel) {
                            'Urgent' => 'rounded-full bg-rose-100 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-[0.12em] text-rose-700 ring-1 ring-rose-200',
                            'High' => 'rounded-full bg-orange-100 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-[0.12em] text-orange-700 ring-1 ring-orange-200',
                            'Medium' => 'rounded-full bg-amber-100 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-[0.12em] text-amber-700 ring-1 ring-amber-200',
                            'Low' => 'rounded-full bg-emerald-100 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-[0.12em] text-emerald-700 ring-1 ring-emerald-200',
                            default => $metaBadgeClasses,
                        };
                        $articleClasses = $item['is_unread']
                            ? ($isFacebookItem ? 'bg-blue-50/60 hover:bg-blue-50' : ($isJobItem ? 'bg-blue-50/75 hover:bg-blue-50' : 'bg-emerald-50/75 hover:bg-emerald-50'))
                            : 'bg-white/80 hover:bg-emerald-50/35';
                        $accentBarClasses = $isFacebookItem ? 'bg-[#1877F2]' : ($isJobItem ? 'bg-blue-500' : 'bg-emerald-500');
                        $newBadgeClasses = $isFacebookItem
                            ? 'inline-flex items-center gap-1 rounded-full bg-blue-100 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-[0.12em] text-blue-700 ring-1 ring-blue-200'
                            : ($isJobItem
                            ? 'inline-flex items-center gap-1 rounded-full bg-blue-100 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-[0.12em] text-blue-700 ring-1 ring-blue-200'
                            : 'inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-[0.12em] text-emerald-700 ring-1 ring-emerald-200');
                        $newBadgeDotClasses = $isFacebookItem ? 'bg-[#1877F2]' : ($isJobItem ? 'bg-blue-500' : 'bg-emerald-500');
                    @endphp
                    <article
                        class="group relative isolate overflow-hidden transition-all duration-300 {{ $articleClasses }} portal-clickable-item"
                        tabindex="0"
                        role="link"
                        aria-label="Open notification: {{ $item['title'] }}"
                        onclick="window.location='{{ $item['open_url'] }}'"
                        onkeydown="if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); window.location='{{ $item['open_url'] }}'; }"
                    >
                        @if ($item['is_unread'])
                            <span class="absolute inset-y-0 left-0 w-1 rounded-r-full {{ $accentBarClasses }}"></span>
                        @endif

                        <div class="grid gap-4 px-5 py-5 md:grid-cols-[auto_minmax(0,220px)_minmax(0,1fr)_auto] md:items-center md:px-7">
                            <div class="flex items-center gap-3">
                                <span class="inline-flex h-3 w-3 shrink-0 rounded-full {{ $markerClasses }}"></span>
                                <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl transition-all duration-300 {{ $iconClasses }}">
                                    <i class='bx {{ $item['icon'] }} text-xl'></i>
                                </span>
                            </div>

                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="truncate text-sm uppercase tracking-[0.16em] {{ $item['is_unread'] ? 'font-bold text-slate-900' : 'font-semibold text-slate-500' }}">{{ $item['category'] }}</p>
                                    @if ($item['priority'])
                                        <span class="{{ $priorityBadgeClasses }}">{{ $item['priority'] }}</span>
                                    @endif
                                    @if ($item['status'])
                                        <span class="{{ $metaBadgeClasses }}">{{ $item['status'] }}</span>
                                    @endif
                                    @if ($item['is_unread'])
                                        <span class="{{ $newBadgeClasses }}">
                                            <span class="h-2 w-2 rounded-full {{ $newBadgeDotClasses }}"></span>
                                            New
                                        </span>
                                    @endif
                                </div>
                                    <p class="mt-2 truncate text-base {{ $item['is_unread'] ? 'font-extrabold text-slate-950' : 'font-bold text-slate-800' }}">{{ $item['title'] }}</p>
                            </div>

                            <div class="min-w-0">
                                <div class="portal-message-preview {{ $item['is_unread'] ? 'portal-message-preview--active' : '' }}">
                                    <p class="line-clamp-2 text-sm leading-relaxed transition-colors duration-300 {{ $item['is_unread'] ? 'font-medium text-slate-700' : 'text-slate-500' }}">{{ $item['summary'] }}</p>
                                </div>
                            </div>

                            <div class="flex items-center justify-between gap-3 md:justify-end">
                                <div class="text-right">
                                    <p class="text-sm {{ $item['is_unread'] ? 'font-bold text-slate-900' : 'font-medium text-slate-500' }}">{{ $item['date_label'] }}</p>
                                    <p class="mt-1 text-xs text-slate-400">{{ $item['time_label'] }}</p>
                                </div>
                                <div class="flex items-center gap-2 transition-all duration-300 md:translate-x-2 md:opacity-0 md:group-hover:translate-x-0 md:group-hover:opacity-100">
                                    @if ($item['mark_read_url'])
                                        <form method="POST" action="{{ $item['mark_read_url'] }}" onclick="event.stopPropagation()" onkeydown="event.stopPropagation()">
                                            @csrf
                                            <button type="submit" class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-emerald-200 bg-emerald-100 text-emerald-700 transition-all duration-300 hover:bg-emerald-600 hover:text-white" aria-label="Mark item as read">
                                                <i class='bx bx-check text-xl'></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        @else
            <div class="px-6 py-10 text-center">
                <span class="inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400 shadow-sm">
                    <i class='bx bx-bell-off text-3xl'></i>
                </span>
                <p class="mt-4 text-lg font-semibold text-slate-800">No notifications here yet</p>
                <p class="mt-2 text-sm leading-relaxed text-slate-600">Admin announcements, Facebook posts, and account updates appear here.</p>
            </div>
        @endif

        <x-pagination-footer :paginator="$activityItems" class="py-5">
            {{ $activityItems->links() }}
        </x-pagination-footer>
    </div>
</div>
@endsection
