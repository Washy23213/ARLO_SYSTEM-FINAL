@extends('components.layouts.dashboard')

@section('title', 'Announcements')

@section('content')
@php
    $featuredAnnouncement = $featuredAnnouncement ?? null;
@endphp

<div class="space-y-6">
    <section class="surface-card overflow-hidden border border-emerald-100 bg-gradient-to-br from-white via-emerald-50/60 to-sky-50/70 p-0">
        <div class="grid gap-0 lg:grid-cols-[minmax(0,1.35fr)_minmax(280px,0.9fr)]">
            <div class="px-6 py-6">
                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-emerald-700">Announcement Center</p>
                <h2 class="mt-3 text-3xl font-black tracking-tight text-slate-900">OARL announcements and Facebook Page updates in one place.</h2>
                <p class="mt-3 max-w-2xl text-sm leading-relaxed text-slate-600">
                    This page combines direct OARL announcements with synchronized posts from the official Facebook Page so alumni can browse both past and future updates.
                </p>
                <div class="mt-5 flex flex-wrap gap-2">
                    <span class="rounded-full bg-white px-3 py-1.5 text-xs font-semibold uppercase tracking-[0.12em] text-slate-700 ring-1 ring-slate-200">
                        {{ number_format((int) ($announcementCount ?? 0)) }} visible announcements
                    </span>
                    <span class="rounded-full bg-blue-50 px-3 py-1.5 text-xs font-semibold uppercase tracking-[0.12em] text-blue-700 ring-1 ring-blue-100">
                        {{ number_format((int) ($facebookSyncedCount ?? 0)) }} Facebook-synced
                    </span>
                    <span class="rounded-full bg-rose-50 px-3 py-1.5 text-xs font-semibold uppercase tracking-[0.12em] text-rose-700 ring-1 ring-rose-100">
                        {{ number_format((int) ($highPriorityCount ?? 0)) }} high priority
                    </span>
                </div>
            </div>

            <div class="border-t border-slate-200/70 bg-slate-950 px-6 py-6 text-white lg:border-l lg:border-t-0">
                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-emerald-200">Latest highlight</p>
                @if ($featuredAnnouncement)
                    @php
                        $featuredPostedAt = $featuredAnnouncement->published_at ?? $featuredAnnouncement->created_at ?? $featuredAnnouncement->updated_at ?? null;
                        $featuredPostedLabel = $featuredPostedAt ? \Carbon\Carbon::parse($featuredPostedAt)->diffForHumans() : 'Recently posted';
                    @endphp
                    <h3 class="mt-3 text-2xl font-black leading-tight">{{ $featuredAnnouncement->title }}</h3>
                    <p class="mt-3 text-sm leading-relaxed text-white/75">{{ \Illuminate\Support\Str::limit((string) ($featuredAnnouncement->content ?? 'No content provided.'), 190) }}</p>
                    <div class="mt-4 flex flex-wrap gap-2 text-xs font-semibold uppercase tracking-[0.12em] text-white/70">
                        <span class="rounded-full bg-white/10 px-3 py-1.5 ring-1 ring-white/10">{{ $featuredAnnouncement->type ?? 'Announcement' }}</span>
                        <span class="rounded-full bg-white/10 px-3 py-1.5 ring-1 ring-white/10">{{ $featuredAnnouncement->priority ?? 'Medium' }}</span>
                        @if (!empty($featuredAnnouncement->facebook_post_url ?? null))
                            <span class="rounded-full bg-blue-400/15 px-3 py-1.5 text-blue-100 ring-1 ring-blue-200/20">Facebook Sync</span>
                        @endif
                    </div>
                    <div class="mt-5 flex items-center justify-between gap-3">
                        <p class="text-xs font-medium uppercase tracking-[0.14em] text-white/55">{{ $featuredPostedLabel }}</p>
                        <a href="{{ route('announcement.detail', $featuredAnnouncement->id) }}" class="inline-flex items-center gap-2 rounded-full bg-white px-4 py-2 text-sm font-semibold text-slate-900 transition-all duration-300 hover:-translate-y-0.5 hover:bg-emerald-50">
                            Open announcement
                            <i class='bx bx-right-arrow-alt text-lg'></i>
                        </a>
                    </div>
                @else
                    <h3 class="mt-3 text-2xl font-black leading-tight">No published announcements yet.</h3>
                    <p class="mt-3 text-sm leading-relaxed text-white/75">Once OARL or Facebook-synced announcements are available, the latest one will be highlighted here.</p>
                @endif
            </div>
        </div>
    </section>

    <section class="surface-card overflow-hidden p-0">
        <div class="border-b border-slate-200/80 px-6 py-5">
            <h2 class="text-lg font-semibold text-slate-900">Browse announcements</h2>
            <p class="mt-1 text-sm text-slate-600">Search by keyword, then narrow by type or priority.</p>
        </div>
        <form method="GET" action="{{ route('announcements.index') }}" data-portal-preserve-scroll="true" data-portal-skip-animation="true" class="space-y-4 px-6 py-5">
            <div class="grid gap-4 lg:grid-cols-[minmax(0,1.4fr)_minmax(180px,0.7fr)_minmax(180px,0.7fr)_auto]">
                <div class="space-y-2">
                    <label for="announcementsSearch" class="block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Keyword</label>
                    <input
                        id="announcementsSearch"
                        type="text"
                        name="search"
                        value="{{ $search ?? '' }}"
                        placeholder="Search title, content, or type"
                        class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700 shadow-sm transition-all duration-200 placeholder:text-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-4 focus:ring-emerald-100"
                    >
                </div>
                <div class="space-y-2">
                    <label for="announcementsType" class="block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Type</label>
                    <select id="announcementsType" name="type" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700 shadow-sm transition-all duration-200 focus:border-emerald-500 focus:outline-none focus:ring-4 focus:ring-emerald-100">
                        <option value="">All Types</option>
                        @foreach (($typeOptions ?? collect()) as $typeOption)
                            <option value="{{ $typeOption }}" {{ ($selectedType ?? '') === $typeOption ? 'selected' : '' }}>{{ $typeOption }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="space-y-2">
                    <label for="announcementsPriority" class="block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Priority</label>
                    <select id="announcementsPriority" name="priority" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700 shadow-sm transition-all duration-200 focus:border-emerald-500 focus:outline-none focus:ring-4 focus:ring-emerald-100">
                        <option value="">All Priorities</option>
                        @foreach (($priorityOptions ?? collect()) as $priorityOption)
                            <option value="{{ $priorityOption }}" {{ ($selectedPriority ?? '') === $priorityOption ? 'selected' : '' }}>{{ $priorityOption }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-end gap-3">
                    <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-emerald-600 px-5 py-3 text-sm font-semibold text-white transition-all duration-300 hover:bg-emerald-700">
                        <i class='bx bx-filter-alt text-base'></i>
                        Filter
                    </button>
                    <a href="{{ route('announcements.index') }}" data-portal-preserve-scroll="true" data-portal-skip-animation="true" class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition-all duration-300 hover:bg-slate-50">
                        <i class='bx bx-reset text-base'></i>
                        Reset
                    </a>
                </div>
            </div>
        </form>
    </section>

    @if ($announcements->count() > 0)
        <section class="overflow-hidden rounded-[1.75rem] border border-emerald-950/10 bg-white/95 shadow-[0_24px_60px_rgba(7,24,14,0.13)]">
            <div class="flex flex-col gap-4 border-b border-emerald-950/10 bg-gradient-to-r from-white via-emerald-50/80 to-amber-50/50 px-6 py-5 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.2em] text-emerald-700/80">Announcement Records</p>
                    <h2 class="mt-1 text-2xl font-extrabold tracking-normal text-slate-950">Latest Updates</h2>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <span class="inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-white px-3.5 py-2 text-sm font-semibold text-emerald-800 shadow-sm">
                        <i class='bx bx-list-ul text-lg'></i>
                        {{ number_format($announcements->total()) }} records
                    </span>
                    <span class="inline-flex items-center gap-2 rounded-full border border-blue-100 bg-blue-50 px-3.5 py-2 text-sm font-semibold text-blue-700 shadow-sm">
                        <i class='bx bxl-facebook-circle text-lg text-[#1877F2]'></i>
                        Facebook included
                    </span>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full border-separate border-spacing-0">
                    <thead class="bg-[#f3f7f3]">
                        <tr>
                            <th class="w-[48%] px-7 py-4 text-left text-[0.68rem] font-bold uppercase tracking-[0.22em] text-emerald-950/70">Announcement</th>
                            <th class="px-5 py-4 text-left text-[0.68rem] font-bold uppercase tracking-[0.22em] text-emerald-950/70">Type</th>
                            <th class="px-5 py-4 text-left text-[0.68rem] font-bold uppercase tracking-[0.22em] text-emerald-950/70">Priority</th>
                            <th class="px-5 py-4 text-left text-[0.68rem] font-bold uppercase tracking-[0.22em] text-emerald-950/70">Published</th>
                            <th class="px-5 py-4 text-left text-[0.68rem] font-bold uppercase tracking-[0.22em] text-emerald-950/70">Source</th>
                            <th class="px-7 py-4 text-right text-[0.68rem] font-bold uppercase tracking-[0.22em] text-emerald-950/70">Open</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-emerald-950/10 bg-white/80">
                        @foreach ($announcements as $announcement)
                            @php
                                $announcementType = trim((string) ($announcement->type ?? 'Announcement')) ?: 'Announcement';
                                $announcementPriority = trim((string) ($announcement->priority ?? 'Medium')) ?: 'Medium';
                                $announcementPostedAt = $announcement->published_at ?? $announcement->created_at ?? $announcement->updated_at ?? null;
                                $announcementDateLabel = $announcementPostedAt ? \Carbon\Carbon::parse($announcementPostedAt)->format('M d, Y') : 'N/A';
                                $priorityTone = match ($announcementPriority) {
                                    'Urgent' => 'bg-rose-50 text-rose-700 ring-1 ring-rose-200',
                                    'High' => 'bg-orange-50 text-orange-700 ring-1 ring-orange-200',
                                    'Medium' => 'bg-amber-50 text-amber-700 ring-1 ring-amber-200',
                                    default => 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200',
                                };
                                $storedSourceUrl = trim((string) ($announcement->facebook_post_url ?? ''));
                                $sourceUrl = trim((string) ($announcement->facebook_open_url ?? $storedSourceUrl));
                                $isFacebookAnnouncement = $sourceUrl !== '';
                                $rawContent = (string) ($announcement->content ?? 'No content provided.');
                                if ($storedSourceUrl !== '') {
                                    $rawContent = str_replace('Original Facebook post: '.$storedSourceUrl, '', $rawContent);
                                }
                                if ($sourceUrl !== '' && $sourceUrl !== $storedSourceUrl) {
                                    $rawContent = str_replace('Original Facebook post: '.$sourceUrl, '', $rawContent);
                                }
                                $previewText = trim((string) preg_replace('/Original Facebook post:\s*https?:\/\/\S+/i', '', $rawContent));
                                $previewText = $previewText !== '' ? \Illuminate\Support\Str::limit($previewText, 170) : 'No message provided.';
                                $detailRoute = route('announcement.detail', $announcement->id);
                                $isRead = (bool) ($announcement->is_read ?? false);
                                $rowTone = $isRead
                                    ? 'bg-white/80 hover:bg-slate-50/80'
                                    : 'bg-emerald-50/70 shadow-[inset_4px_0_0_rgba(5,150,105,0.75)] hover:bg-emerald-50';
                                $titleTone = $isRead ? 'font-bold text-slate-800' : 'font-black text-slate-950';
                                $previewTone = $isRead ? 'font-medium text-slate-500' : 'font-semibold text-slate-800';
                                $metaTone = $isRead ? 'font-semibold text-slate-600' : 'font-extrabold text-slate-900';
                                $iconTone = $isFacebookAnnouncement
                                    ? 'border-blue-100 bg-white text-[#1877F2] ring-1 ring-blue-100 shadow-sm'
                                    : ($isRead
                                    ? 'border-emerald-950/10 bg-gradient-to-br from-emerald-50 to-white text-emerald-700'
                                    : 'border-emerald-200 bg-emerald-700 text-white shadow-emerald-900/15');
                            @endphp
                            <tr
                                class="portal-clickable-row group {{ $rowTone }}"
                                tabindex="0"
                                role="link"
                                aria-label="{{ $isRead ? 'Read' : 'Unread' }} announcement: {{ $announcement->title }}"
                                onclick="window.location='{{ $detailRoute }}'"
                                onkeydown="if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); window.location='{{ $detailRoute }}'; }"
                            >
                                <td class="px-7 py-5 align-top">
                                    <div class="flex min-w-[28rem] items-start gap-4">
                                        <span class="mt-0.5 hidden h-11 w-11 flex-shrink-0 items-center justify-center rounded-2xl border text-xl transition-all sm:inline-flex {{ $iconTone }}">
                                            <i class='bx {{ $isFacebookAnnouncement ? 'bxl-facebook-circle' : 'bxs-megaphone' }}'></i>
                                        </span>
                                        <div class="min-w-0">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <h3 class="max-w-2xl text-base leading-snug tracking-normal {{ $titleTone }}">{{ $announcement->title }}</h3>
                                                @if($isFacebookAnnouncement)
                                                    <span class="inline-flex items-center gap-1.5 rounded-full border border-blue-100 bg-blue-50 px-2.5 py-1 text-[0.68rem] font-bold uppercase tracking-[0.14em] text-blue-700">
                                                        <i class='bx bxl-facebook-circle text-sm text-[#1877F2]'></i>
                                                        Facebook
                                                    </span>
                                                @endif
                                                @unless($isRead)
                                                    <span class="inline-flex items-center gap-1.5 rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-[0.68rem] font-bold uppercase tracking-[0.14em] text-emerald-800">
                                                        <i class='bx bxs-envelope text-sm'></i>
                                                        Unread
                                                    </span>
                                                @endunless
                                            </div>
                                            <p class="mt-2 max-w-3xl text-sm leading-relaxed {{ $previewTone }}">{{ $previewText }}</p>
                                            @if($sourceUrl !== '')
                                                <a href="{{ $sourceUrl }}" target="_blank" rel="noopener noreferrer" onclick="event.stopPropagation()" onkeydown="event.stopPropagation()" class="mt-3 inline-flex items-center gap-1.5 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-bold text-emerald-800 transition-colors hover:bg-emerald-100">
                                                    <i class='bx bx-link-external text-sm'></i>
                                                    Original post
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-5 align-top text-sm {{ $metaTone }}">{{ $announcementType }}</td>
                                <td class="px-5 py-5 align-top">
                                    <span class="inline-flex rounded-full px-3 py-1.5 text-[0.68rem] font-bold uppercase tracking-[0.14em] {{ $priorityTone }}">
                                        {{ $announcementPriority }}
                                    </span>
                                </td>
                                <td class="px-5 py-5 align-top text-sm {{ $metaTone }}">{{ $announcementDateLabel }}</td>
                                <td class="px-5 py-5 align-top">
                                    <span class="inline-flex rounded-full px-3 py-1.5 text-xs font-bold {{ $isFacebookAnnouncement ? 'bg-blue-50 text-blue-700 ring-1 ring-blue-100' : 'bg-emerald-50 text-emerald-800 ring-1 ring-emerald-200' }}">
                                        {{ $isFacebookAnnouncement ? 'Facebook' : 'Portal' }}
                                    </span>
                                </td>
                                <td class="px-7 py-5 align-top" onclick="event.stopPropagation()" onkeydown="event.stopPropagation()">
                                    <div class="flex justify-end">
                                        <a href="{{ $detailRoute }}" data-tooltip="Open announcement" class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-emerald-200 bg-emerald-50 text-emerald-800 shadow-sm transition-all hover:-translate-y-0.5 hover:bg-emerald-100 hover:shadow-md">
                                            <i class='bx bx-right-arrow-alt text-xl'></i>
                                            <span class="sr-only">Open announcement</span>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        <x-pagination-footer :paginator="$announcements" class="surface-card py-5">
            {{ $announcements->links() }}
        </x-pagination-footer>
    @else
        <div class="surface-card px-6 py-10 text-center">
            <span class="inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400 shadow-sm">
                <i class='bx bx-bell-off text-3xl'></i>
            </span>
            <p class="mt-4 text-lg font-semibold text-slate-800">No announcements found</p>
            <p class="mt-2 text-sm leading-relaxed text-slate-600">Try a different filter, or wait for the next OARL or Facebook Page update to sync in.</p>
        </div>
    @endif
</div>
@endsection
