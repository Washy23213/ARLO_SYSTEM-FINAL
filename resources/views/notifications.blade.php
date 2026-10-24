@extends('components.layouts.dashboard')

@section('title', 'Notifications')

@section('content')
@php
    $filterFrom = $from ? \Carbon\Carbon::parse($from)->format('Y-m-d\\TH:i') : '';
    $filterTo = $to ? \Carbon\Carbon::parse($to)->format('Y-m-d\\TH:i') : '';
@endphp
<section class="overflow-hidden rounded-2xl border border-emerald-950/10 bg-white shadow-[0_16px_42px_rgba(7,24,14,0.09)]" aria-labelledby="notificationsHeading">
    <header class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-200 px-5 py-4 sm:px-6">
        <div class="flex items-center gap-3">
            <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-800"><i class="bx bx-bell text-xl"></i></span>
            <div>
                <h1 id="notificationsHeading" class="text-lg font-bold text-slate-950">Notifications <span class="font-semibold text-slate-500">({{ number_format($totalNotifications) }})</span></h1>
                <p class="mt-0.5 text-sm text-slate-500">{{ number_format($unreadNotifications) }} unread</p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            @if ($unreadNotifications > 0)
                <form method="POST" action="{{ route('notifications.read-all') }}">
                    @csrf
                    <button type="submit" class="inline-flex min-h-10 items-center gap-2 rounded-lg border border-emerald-200 bg-white px-3 text-sm font-semibold text-emerald-900 transition hover:bg-emerald-50">
                        <i class="bx bx-check-double text-lg"></i><span class="hidden sm:inline">Mark all read</span>
                    </button>
                </form>
            @endif
            <a href="{{ route('notifications.index', request()->query()) }}" class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-slate-300 bg-white text-slate-700 transition hover:bg-slate-50" aria-label="Refresh notifications" title="Refresh">
                <i class="bx bx-refresh text-xl"></i>
            </a>
        </div>
    </header>

    <form method="GET" action="{{ route('notifications.index') }}" class="grid grid-cols-1 gap-3 border-b border-slate-200 bg-slate-50/70 p-4 md:grid-cols-2 xl:grid-cols-[minmax(220px,1.4fr)_minmax(160px,1fr)_minmax(160px,1fr)_150px_auto_auto] xl:items-end" role="search" aria-label="Filter notifications">
        <label class="block">
            <span class="sr-only">Filter notifications by keyword</span>
            <span class="flex h-10 items-center gap-2 rounded-lg border border-slate-300 bg-white px-3 focus-within:border-emerald-600 focus-within:ring-2 focus-within:ring-emerald-100">
                <i class="bx bx-search text-slate-500"></i>
                <input type="search" name="search" value="{{ $search }}" placeholder="Filter notifications" class="min-w-0 flex-1 border-0 bg-transparent p-0 text-sm outline-none focus:ring-0">
            </span>
        </label>
        <label class="block text-xs font-semibold text-slate-600">
            <span class="sr-only">From date and time</span>
            <input type="datetime-local" name="from" value="{{ $filterFrom }}" aria-label="From date and time" class="h-10 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm font-normal text-slate-700 focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-100">
        </label>
        <label class="block text-xs font-semibold text-slate-600">
            <span class="sr-only">To date and time</span>
            <input type="datetime-local" name="to" value="{{ $filterTo }}" aria-label="To date and time" class="h-10 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm font-normal text-slate-700 focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-100">
        </label>
        <label class="block">
            <span class="sr-only">Read status</span>
            <select name="status" aria-label="Read status" class="h-10 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-700 focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                <option value="all" {{ $status === 'all' ? 'selected' : '' }}>All statuses</option>
                <option value="unread" {{ $status === 'unread' ? 'selected' : '' }}>Unread</option>
                <option value="read" {{ $status === 'read' ? 'selected' : '' }}>Read</option>
            </select>
        </label>
        <button type="submit" class="inline-flex h-10 items-center justify-center gap-2 rounded-lg bg-emerald-800 px-4 text-sm font-semibold text-white transition hover:bg-emerald-900"><i class="bx bx-filter-alt"></i>Filter</button>
        <details class="relative justify-self-start xl:justify-self-end">
            <summary class="flex h-10 w-10 cursor-pointer list-none items-center justify-center rounded-lg border border-slate-300 bg-white text-slate-700 hover:bg-slate-100" aria-label="Notification table settings" title="Table settings"><i class="bx bx-cog text-xl"></i></summary>
            <div class="absolute right-0 top-12 z-20 w-56 rounded-xl border border-slate-200 bg-white p-3 shadow-xl">
                <p class="px-2 pb-2 text-xs font-bold uppercase tracking-wide text-slate-500">Visible columns</p>
                @foreach (['title' => 'Title', 'message' => 'Message', 'region' => 'Region', 'service' => 'Service name', 'timestamp' => 'Timestamp'] as $column => $label)
                    <label class="flex cursor-pointer items-center gap-2 rounded-lg px-2 py-2 text-sm text-slate-700 hover:bg-slate-50"><input type="checkbox" checked data-notification-column-toggle="{{ $column }}" class="rounded border-slate-300 text-emerald-700 focus:ring-emerald-600">{{ $label }}</label>
                @endforeach
            </div>
        </details>
    </form>

    <div class="overflow-x-auto">
        <table class="min-w-[850px] w-full border-collapse text-left text-sm">
            <thead class="bg-slate-50 text-[0.68rem] font-bold uppercase tracking-[0.12em] text-slate-600">
                <tr class="border-b border-slate-200">
                    <th scope="col" data-notification-column="title" class="w-[23%] px-5 py-3">Title</th>
                    <th scope="col" data-notification-column="message" class="w-[35%] px-5 py-3">Message</th>
                    <th scope="col" data-notification-column="region" class="w-[15%] px-5 py-3">Region</th>
                    <th scope="col" data-notification-column="service" class="w-[13%] px-5 py-3">Service name</th>
                    <th scope="col" data-notification-column="timestamp" class="w-[14%] px-5 py-3">Timestamp</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
                @forelse ($activityItems as $item)
                    <tr class="align-top transition-colors {{ $item['is_unread'] ? 'bg-emerald-50/50 hover:bg-emerald-50' : 'bg-white hover:bg-slate-50' }}">
                        <td data-notification-column="title" class="px-5 py-4">
                            <div class="flex items-start gap-2">
                                @if ($item['is_unread'])<span class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-emerald-600" aria-label="Unread"></span>@endif
                                <div class="min-w-0">
                                    <a href="{{ $item['open_url'] }}" class="font-semibold text-slate-900 hover:text-emerald-800 hover:underline">{{ $item['title'] }}</a>
                                    <div class="mt-1.5 flex flex-wrap items-center gap-1.5">
                                        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[0.67rem] font-semibold text-slate-600">{{ $item['category'] }}</span>
                                        @if ($item['priority'])<span class="rounded-full bg-amber-50 px-2 py-0.5 text-[0.67rem] font-semibold text-amber-800">{{ $item['priority'] }}</span>@endif
                                        @if ($item['status'])<span class="rounded-full bg-slate-100 px-2 py-0.5 text-[0.67rem] font-semibold text-slate-600">{{ $item['status'] }}</span>@endif
                                    </div>
                                    @if ($item['mark_read_url'])
                                        <form method="POST" action="{{ $item['mark_read_url'] }}" class="mt-2">
                                            @csrf
                                            <button type="submit" class="text-xs font-semibold text-emerald-800 hover:underline">Mark read</button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td data-notification-column="message" class="px-5 py-4 leading-5 text-slate-600">{{ $item['summary'] }}</td>
                        <td data-notification-column="region" class="px-5 py-4 text-slate-600">{{ filled($item['region'] ?? null) ? $item['region'] : '—' }}</td>
                        <td data-notification-column="service" class="px-5 py-4 text-slate-700">{{ $item['category'] }}</td>
                        <td data-notification-column="timestamp" class="whitespace-nowrap px-5 py-4 text-slate-600">
                            <time datetime="{{ optional($item['created_at'])->toIso8601String() }}">{{ optional($item['created_at'])->format('M d, Y') ?: $item['date_label'] }}</time>
                            <span class="mt-1 block text-xs text-slate-500">{{ optional($item['created_at'])->format('g:i A') ?: $item['time_label'] }}</span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-5 py-14 text-center">
                            <i class="bx bx-bell-off text-3xl text-slate-400"></i>
                            <p class="mt-2 font-semibold text-slate-800">No notifications found</p>
                            <p class="mt-1 text-sm text-slate-500">Try changing your filters.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <x-pagination-footer :paginator="$activityItems" class="border-t border-slate-200 py-4">
        {{ $activityItems->links() }}
    </x-pagination-footer>
</section>
@endsection
