@extends('admin.layout')

@section('title', 'Notifications')

@section('content')
@php
    $audienceOptions = ['All', 'Students', 'Alumni', 'Faculty', 'Staff', 'Employers'];
    $locationOptions = ['Main Campus', 'Online', 'Bukidnon', 'Cagayan de Oro', 'Davao City', 'Manila', 'Others'];
    $targetDepartmentOptions = $targetDepartmentOptions ?? [];
    $targetGraduationYearOptions = $targetGraduationYearOptions ?? [];
    $announcementCreateModalOpen = request()->boolean('create') || ($errors->any() && old('title') !== null);
    $filterFrom = $from ? \Carbon\Carbon::parse($from)->format('Y-m-d\\TH:i') : '';
    $filterTo = $to ? \Carbon\Carbon::parse($to)->format('Y-m-d\\TH:i') : '';
@endphp
<div class="space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:justify-end">
        <form method="POST" action="{{ route('admin.announcements.sync-facebook') }}">
            @csrf
            <input type="hidden" name="limit" value="100">
            <button
                type="submit"
                class="inline-flex w-full items-center justify-center gap-2 rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-3.5 text-base font-semibold text-emerald-800 shadow-sm transition-all duration-300 hover:-translate-y-0.5 hover:bg-emerald-100 hover:shadow-md sm:w-auto"
            >
                <i class='bx bx-refresh text-2xl'></i>
                Sync Facebook Posts
            </button>
        </form>
        <button
            type="button"
            id="openAnnouncementCreateModal"
            class="inline-flex items-center justify-center gap-2 rounded-2xl bg-blue-600 px-5 py-3.5 text-base font-semibold text-white shadow-sm transition-all duration-300 hover:-translate-y-0.5 hover:bg-blue-700 hover:shadow-md"
            aria-haspopup="dialog"
            aria-controls="announcementCreateModal"
            aria-expanded="{{ $announcementCreateModalOpen ? 'true' : 'false' }}"
        >
            <i class='bx bx-plus text-2xl'></i>
            Add Announcement
        </button>
    </div>

    {{-- Filters and Search --}}
    <div class="mb-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <form method="GET" action="{{ route('admin.announcements') }}" data-portal-preserve-scroll="true" data-portal-skip-animation="true" class="space-y-4 p-4">
            <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-[minmax(220px,1.5fr)_minmax(175px,1fr)_minmax(175px,1fr)_minmax(140px,0.8fr)_minmax(140px,0.8fr)_minmax(140px,0.8fr)]">
                <div class="space-y-2">
                    <label for="announcementFilterSearch" class="sr-only">Filter notifications by keyword</label>
                    <input
                        id="announcementFilterSearch"
                        type="search"
                        name="search"
                        value="{{ $search ?? '' }}"
                        placeholder="Filter notifications"
                        class="h-10 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-700 shadow-sm placeholder:text-slate-400 focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-100"
                    >
                </div>
                <div>
                    <label for="announcementFilterFrom" class="sr-only">From date and time</label>
                    <input id="announcementFilterFrom" type="datetime-local" name="from" value="{{ $filterFrom }}" aria-label="From date and time" class="h-10 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-700 focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                </div>
                <div>
                    <label for="announcementFilterTo" class="sr-only">To date and time</label>
                    <input id="announcementFilterTo" type="datetime-local" name="to" value="{{ $filterTo }}" aria-label="To date and time" class="h-10 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-700 focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                </div>
                <div>
                    <label for="announcementFilterType" class="sr-only">Notification type</label>
                    <select id="announcementFilterType" name="type" aria-label="Notification type" class="h-10 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-700 focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                        <option value="">All Types</option>
                        @foreach(($typeOptions ?? collect()) as $type)
                            <option value="{{ $type }}" {{ ($selectedType ?? '') === $type ? 'selected' : '' }}>{{ $type }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="announcementFilterStatus" class="sr-only">Notification status</label>
                    <select id="announcementFilterStatus" name="status" aria-label="Notification status" class="h-10 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-700 focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                        <option value="">All Status</option>
                        <option value="published" {{ ($selectedStatus ?? '') === 'published' ? 'selected' : '' }}>Published</option>
                        <option value="draft" {{ ($selectedStatus ?? '') === 'draft' ? 'selected' : '' }}>Draft</option>
                    </select>
                </div>
                <div>
                    <label for="announcementFilterPriority" class="sr-only">Notification priority</label>
                    <select id="announcementFilterPriority" name="priority" aria-label="Notification priority" class="h-10 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-700 focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                        <option value="">All Priority</option>
                        @foreach(($priorityOptions ?? collect()) as $priority)
                            <option value="{{ $priority }}" {{ ($selectedPriority ?? '') === $priority ? 'selected' : '' }}>{{ $priority }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="flex flex-col gap-3 border-t border-slate-200 pt-3 sm:flex-row sm:items-center sm:justify-between">
                <span class="text-sm text-slate-500">Filter notifications</span>
                <div class="flex flex-col gap-3 sm:flex-row">
                    <button type="submit" class="inline-flex h-10 items-center justify-center gap-2 rounded-lg bg-emerald-800 px-4 text-sm font-semibold text-white transition-colors hover:bg-emerald-900">
                        <i class='bx bx-filter-alt text-base'></i>Filter
                    </button>
                    <a href="{{ route('admin.announcements') }}" data-portal-preserve-scroll="true" data-portal-skip-animation="true" class="inline-flex h-10 items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700 transition-colors hover:bg-slate-50">
                        <i class='bx bx-reset text-base'></i>Reset
                    </a>
                </div>
            </div>
        </form>
    </div>

    {{-- Notifications Table --}}
    <div id="notification-records" class="scroll-mt-6 overflow-hidden rounded-[1.75rem] border border-emerald-950/10 bg-white/95 shadow-[0_24px_60px_rgba(7,24,14,0.13)]">
        <div class="flex flex-col gap-4 border-b border-slate-200 bg-white px-5 py-4 sm:px-6 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h2 class="text-lg font-bold text-slate-950">Notifications <span class="font-semibold text-slate-500">({{ number_format($announcements->total()) }})</span></h2>
                <p class="mt-0.5 text-sm text-slate-500">{{ number_format($activeAnnouncements) }} published</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('admin.announcements', request()->query()) }}" class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-slate-300 bg-white text-slate-700 hover:bg-slate-50" aria-label="Refresh notifications" title="Refresh"><i class='bx bx-refresh text-xl'></i></a>
                <details class="relative">
                    <summary class="flex h-10 w-10 cursor-pointer list-none items-center justify-center rounded-lg border border-slate-300 bg-white text-slate-700 hover:bg-slate-50" aria-label="Notification table settings" title="Table settings"><i class='bx bx-cog text-xl'></i></summary>
                    <div class="absolute right-0 top-12 z-20 w-52 rounded-xl border border-slate-200 bg-white p-3 shadow-xl">
                        <p class="px-2 pb-2 text-xs font-bold uppercase tracking-wide text-slate-500">Visible columns</p>
                        @foreach (['title' => 'Title', 'message' => 'Message', 'region' => 'Region', 'service' => 'Service name', 'timestamp' => 'Timestamp', 'manage' => 'Actions'] as $column => $label)
                            <label class="flex cursor-pointer items-center gap-2 rounded-lg px-2 py-2 text-sm text-slate-700 hover:bg-slate-50"><input type="checkbox" checked data-notification-column-toggle="{{ $column }}" class="rounded border-slate-300 text-emerald-700 focus:ring-emerald-600">{{ $label }}</label>
                        @endforeach
                    </div>
                </details>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-[980px] w-full border-collapse text-left text-sm">
                <thead class="bg-slate-50 text-[0.68rem] font-bold uppercase tracking-[0.12em] text-slate-600">
                    <tr class="border-b border-slate-200">
                        <th scope="col" data-notification-column="title" class="w-[23%] px-5 py-3">Title</th>
                        <th scope="col" data-notification-column="message" class="w-[32%] px-5 py-3">Message</th>
                        <th scope="col" data-notification-column="region" class="w-[15%] px-5 py-3">Region</th>
                        <th scope="col" data-notification-column="service" class="w-[12%] px-5 py-3">Service name</th>
                        <th scope="col" data-notification-column="timestamp" class="w-[14%] px-5 py-3">Timestamp</th>
                        <th scope="col" data-notification-column="manage" class="px-5 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-emerald-950/10 bg-white/80">
                    @forelse($announcements as $announcement)
                    @php
                        $detailRoute = route('admin.announcements.show', $announcement->id);
                        $announcementPriority = trim((string) ($announcement->priority ?? 'Medium')) ?: 'Medium';
                        $priorityBadgeClasses = match ($announcementPriority) {
                            'Urgent' => 'bg-rose-50 text-rose-700 ring-1 ring-rose-200',
                            'High' => 'bg-orange-50 text-orange-700 ring-1 ring-orange-200',
                            'Medium' => 'bg-amber-50 text-amber-700 ring-1 ring-amber-200',
                            default => 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200',
                        };
                        $storedSourceUrl = trim((string) ($announcement->facebook_post_url ?? ''));
                        $sourceUrl = trim((string) ($announcement->facebook_open_url ?? $storedSourceUrl));
                        $isFacebookPost = $sourceUrl !== '' || !empty($announcement->facebook_post_id ?? null);
                        $rawContent = (string) ($announcement->content ?? 'No announcement message provided.');
                        if ($storedSourceUrl !== '') {
                            $rawContent = str_replace('Original Facebook post: '.$storedSourceUrl, '', $rawContent);
                        }
                        if ($sourceUrl !== '' && $sourceUrl !== $storedSourceUrl) {
                            $rawContent = str_replace('Original Facebook post: '.$sourceUrl, '', $rawContent);
                        }
                        $previewText = trim((string) preg_replace('/Original Facebook post:\s*https?:\/\/\S+/i', '', $rawContent));
                        $previewText = $previewText !== '' ? \Illuminate\Support\Str::limit($previewText, 170) : 'No message provided.';
                        $publishedAt = $announcement->published_at ?? $announcement->created_at ?? null;
                        $regionLabel = trim((string) ($announcement->event_location ?? ''));
                        if ($regionLabel === '' && filled($announcement->target_department ?? null)) {
                            $regionLabel = 'Department: '.(string) $announcement->target_department;
                        }
                        if ($regionLabel === '' && filled($announcement->target_audience ?? null) && $announcement->target_audience !== 'All') {
                            $regionLabel = 'Audience: '.(string) $announcement->target_audience;
                        }
                        $regionLabel = $regionLabel !== '' ? $regionLabel : '—';
                        $serviceLabel = $isFacebookPost ? 'Facebook' : 'CMU OARL';
                        $statusText = ($announcement->is_published ?? true) ? 'Published' : 'Draft';
                        $statusBadgeClasses = match ($statusText) {
                            'Published' => 'bg-emerald-50 text-emerald-800 ring-1 ring-emerald-200',
                            default => 'bg-slate-100 text-slate-700 ring-1 ring-slate-200',
                        };
                        $isRead = (bool) ($announcement->is_read ?? false);
                        $rowTone = $isRead ? 'bg-white hover:bg-slate-50' : 'bg-emerald-50/50 hover:bg-emerald-50';
                        $titleTone = $isRead ? 'font-bold text-slate-800' : 'font-black text-slate-950';
                        $previewTone = $isRead ? 'font-medium text-slate-500' : 'font-semibold text-slate-800';
                        $metaTone = $isRead ? 'font-medium text-slate-600' : 'font-semibold text-slate-800';
                        $iconTone = $isFacebookPost
                            ? 'border-blue-100 bg-white text-[#1877F2] ring-1 ring-blue-100 shadow-sm'
                            : ($isRead
                            ? 'border-emerald-950/10 bg-gradient-to-br from-emerald-50 to-white text-emerald-700'
                            : 'border-emerald-200 bg-emerald-700 text-white shadow-emerald-900/15');
                    @endphp
                    <tr
                        class="portal-clickable-row group {{ $rowTone }}"
                        tabindex="0"
                        role="link"
                        aria-label="{{ $isRead ? 'Read' : 'Unread' }} announcement record for {{ $announcement->title ?? 'this announcement' }}"
                        onclick="window.location='{{ $detailRoute }}'"
                        onkeydown="if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); window.location='{{ $detailRoute }}'; }"
                    >
                        <td data-notification-column="title" class="px-5 py-4 align-top">
                            <div class="flex min-w-0 items-start gap-3">
                                <span class="mt-0.5 inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border text-lg {{ $iconTone }}"><i class='bx {{ $isFacebookPost ? 'bxl-facebook-circle' : 'bxs-megaphone' }}'></i></span>
                                <div class="min-w-0">
                                    <h3 class="text-sm leading-5 {{ $titleTone }}">{{ $announcement->title ?? 'N/A' }}</h3>
                                    <div class="mt-1.5 flex flex-wrap items-center gap-1.5">
                                        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[0.67rem] font-semibold text-slate-600">{{ $announcement->type ?? 'Update' }}</span>
                                        <span class="rounded-full px-2 py-0.5 text-[0.67rem] font-semibold {{ $priorityBadgeClasses }}">{{ $announcementPriority }}</span>
                                        <span class="rounded-full px-2 py-0.5 text-[0.67rem] font-semibold {{ $statusBadgeClasses }}">{{ $statusText }}</span>
                                        @unless($isRead)<span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[0.67rem] font-semibold text-emerald-800">Unread</span>@endunless
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td data-notification-column="message" class="px-5 py-4 align-top text-sm leading-5 {{ $previewTone }}">
                            {{ $previewText }}
                            @if($sourceUrl !== '')
                                <a href="{{ $sourceUrl }}" target="_blank" rel="noopener noreferrer" onclick="event.stopPropagation()" onkeydown="event.stopPropagation()" class="mt-2 inline-flex items-center gap-1 text-xs font-semibold text-blue-700 hover:underline"><i class='bx bx-link-external'></i>Original post</a>
                            @endif
                        </td>
                        <td data-notification-column="region" class="px-5 py-4 align-top text-sm {{ $metaTone }}">
                            {{ $regionLabel }}
                        </td>
                        <td data-notification-column="service" class="px-5 py-4 align-top text-sm {{ $metaTone }}">
                            {{ $serviceLabel }}
                        </td>
                        <td data-notification-column="timestamp" class="whitespace-nowrap px-5 py-4 align-top text-sm {{ $metaTone }}">
                            <time datetime="{{ $publishedAt ? \Carbon\Carbon::parse($publishedAt)->toIso8601String() : '' }}">{{ $publishedAt ? \Carbon\Carbon::parse($publishedAt)->format('M d, Y') : 'N/A' }}</time>
                            @if($publishedAt)<span class="mt-1 block text-xs font-medium text-slate-500">{{ \Carbon\Carbon::parse($publishedAt)->format('g:i A') }}</span>@endif
                        </td>
                        <td data-notification-column="manage" class="portal-row-actions px-5 py-4 align-top" onclick="event.stopPropagation()" onkeydown="event.stopPropagation()">
                            <div class="flex items-center justify-end gap-2">
                                <a
                                    href="{{ route('admin.announcements.edit', $announcement->id) }}"
                                    data-tooltip="Edit announcement"
                                    class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-emerald-200 bg-emerald-50 text-emerald-800 shadow-sm transition-all hover:-translate-y-0.5 hover:bg-emerald-100 hover:shadow-md"
                                >
                                    <i class='bx bx-edit text-lg'></i>
                                    <span class="sr-only">Edit announcement</span>
                                </a>
                                <form
                                    action="{{ route('admin.announcements.delete', $announcement->id) }}"
                                    method="POST"
                                    class="inline"
                                    data-confirm-submit="Delete this announcement? This will permanently remove the record from the database."
                                    data-confirm-title="Delete announcement"
                                    data-confirm-label="Delete"
                                    data-confirm-tone="danger"
                                >
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" data-tooltip="Delete announcement" class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-rose-200 bg-rose-50 text-rose-700 shadow-sm transition-all hover:-translate-y-0.5 hover:bg-rose-100 hover:shadow-md">
                                        <i class='bx bx-trash text-lg'></i>
                                        <span class="sr-only">Delete announcement</span>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-10">
                            <div class="empty-state-card mx-auto max-w-lg">
                                <span class="empty-state-card__icon">
                                    <i class='bx bxs-megaphone'></i>
                                </span>
                                <p class="empty-state-card__title">No notifications found</p>
                                <p class="empty-state-card__body">Try different filters or add an announcement.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <x-pagination-footer :paginator="$announcements" class="border-gray-200">
            {{ $announcements->links() }}
        </x-pagination-footer>
    </div>
</div>

<div
    id="announcementCreateModal"
    class="fixed inset-0 z-50 overflow-hidden {{ $announcementCreateModalOpen ? '' : 'hidden' }}"
    aria-hidden="{{ $announcementCreateModalOpen ? 'false' : 'true' }}"
>
    <div id="announcementCreateModalBackdrop" class="absolute inset-0 bg-slate-950/50 backdrop-blur-sm"></div>
    <div class="relative h-full overflow-y-auto overflow-x-hidden overscroll-contain">
    <div class="flex min-h-full items-start justify-center px-4 py-4 sm:px-6 sm:py-6 lg:items-center">
        <div class="flex w-full max-w-5xl max-h-[calc(100vh-2rem)] flex-col overflow-hidden rounded-[1.75rem] border border-white/70 bg-white shadow-[0_28px_80px_rgba(15,23,42,0.28)] sm:max-h-[calc(100vh-3rem)]">
            <div class="flex items-start justify-between gap-4 border-b border-slate-200 bg-gradient-to-r from-white via-emerald-50/70 to-white px-6 py-5">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700">Announcement</p>
                    <h2 class="mt-2 text-2xl font-bold text-slate-900">Add Announcement</h2>
                    <p class="mt-1 text-sm text-slate-600">Publish a new notification announcement without leaving this page.</p>
                </div>
                <button
                    type="button"
                    id="closeAnnouncementCreateModal"
                    class="inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-slate-200 bg-white text-slate-500 transition-all duration-200 hover:bg-slate-100 hover:text-slate-700"
                    aria-label="Close announcement form"
                >
                    <i class='bx bx-x text-2xl'></i>
                </button>
            </div>

            <div class="flex-1 overflow-y-auto px-6 pb-6 pt-4">
                @if($announcementCreateModalOpen)
                    <div class="mb-5 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                        <ul class="list-disc pl-5">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('admin.announcements.store') }}" method="POST" class="space-y-6">
                    @csrf

                    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div class="md:col-span-2">
                                <label class="mb-1 block text-sm font-medium text-gray-700">Title</label>
                                <input type="text" name="title" value="{{ old('title') }}" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700">Type</label>
                                <select name="type" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                                    <option value="">Select Type</option>
                                    @foreach(['Event', 'News', 'Update', 'Alert', 'Job'] as $type)
                                        <option value="{{ $type }}" {{ old('type') === $type ? 'selected' : '' }}>{{ $type }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700">Priority</label>
                                <select name="priority" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                                    <option value="">Select Priority</option>
                                    @foreach(['Low', 'Medium', 'High', 'Urgent'] as $priority)
                                        <option value="{{ $priority }}" {{ old('priority', 'Medium') === $priority ? 'selected' : '' }}>{{ $priority }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700">Target Audience</label>
                                <select name="target_audience" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    @foreach($audienceOptions as $audience)
                                        <option value="{{ $audience }}" {{ old('target_audience', 'All') === $audience ? 'selected' : '' }}>{{ $audience }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700">Target College (Optional)</label>
                                <select name="target_department" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="">All Colleges</option>
                                    @foreach($targetDepartmentOptions as $department)
                                        <option value="{{ $department }}" {{ old('target_department') === $department ? 'selected' : '' }}>{{ $department }}</option>
                                    @endforeach
                                </select>
                                <p class="mt-2 text-xs text-gray-500">Leave blank for all colleges.</p>
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700">Target Graduation Year (Optional)</label>
                                <select name="target_graduation_year" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="">All Years</option>
                                    @foreach($targetGraduationYearOptions as $year)
                                        <option value="{{ $year }}" {{ (string) old('target_graduation_year') === (string) $year ? 'selected' : '' }}>{{ $year }}</option>
                                    @endforeach
                                </select>
                                <p class="mt-2 text-xs text-gray-500">Leave blank for all years.</p>
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700">Event Date (Optional)</label>
                                <input type="datetime-local" name="event_date" value="{{ old('event_date') }}" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700">Event Location (Optional)</label>
                                <select id="announcement_event_location_select" name="event_location" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="">Select Event Location</option>
                                    @foreach($locationOptions as $location)
                                        <option value="{{ $location }}" {{ old('event_location') === $location ? 'selected' : '' }}>{{ $location }}</option>
                                    @endforeach
                                </select>
                                <div id="announcement_specific_event_location_group" class="mt-3 {{ old('event_location') === 'Others' ? '' : 'hidden' }}">
                                    <label class="mb-1 block text-sm font-medium text-gray-700">Specific Event Location</label>
                                    <input id="announcement_specific_event_location_input" type="text" name="specific_event_location" value="{{ old('specific_event_location') }}" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter specific event location">
                                </div>
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700">Expires At (Optional)</label>
                                <input type="datetime-local" name="expires_at" value="{{ old('expires_at') }}" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div class="flex items-end">
                                <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                    <input type="checkbox" name="is_published" value="1" {{ old('is_published', '1') ? 'checked' : '' }} class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    Publish now
                                </label>
                            </div>
                            <div class="md:col-span-2">
                                <label class="mb-1 block text-sm font-medium text-gray-700">Content</label>
                                <textarea name="content" rows="8" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" required>{{ old('content') }}</textarea>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col-reverse justify-end gap-3 border-t border-slate-200 pt-4 sm:flex-row">
                        <button type="button" id="cancelAnnouncementCreateModal" class="rounded-xl bg-gray-100 px-4 py-2 text-sm font-semibold text-gray-700 transition-colors hover:bg-gray-200">
                            Cancel
                        </button>
                        <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-blue-700">
                            <i class='bx bx-send'></i>
                            Add Announcement
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    </div>
</div>

<script>
    function toggleAnnouncementSpecificEventLocation() {
        const eventLocationSelect = document.getElementById('announcement_event_location_select');
        const specificGroup = document.getElementById('announcement_specific_event_location_group');
        const specificInput = document.getElementById('announcement_specific_event_location_input');

        if (!eventLocationSelect || !specificGroup || !specificInput) {
            return;
        }

        if (eventLocationSelect.value === 'Others') {
            specificGroup.classList.remove('hidden');
            specificInput.setAttribute('required', 'required');
            return;
        }

        specificGroup.classList.add('hidden');
        specificInput.removeAttribute('required');
        specificInput.value = '';
    }

    function initializeAnnouncementCreateModal() {
        const modal = document.getElementById('announcementCreateModal');
        const openButton = document.getElementById('openAnnouncementCreateModal');
        const closeButton = document.getElementById('closeAnnouncementCreateModal');
        const cancelButton = document.getElementById('cancelAnnouncementCreateModal');
        const backdrop = document.getElementById('announcementCreateModalBackdrop');
        const eventLocationSelect = document.getElementById('announcement_event_location_select');

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
            toggleAnnouncementSpecificEventLocation();
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
        eventLocationSelect?.addEventListener('change', toggleAnnouncementSpecificEventLocation);

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
                hideModal();
            }
        });

        toggleAnnouncementSpecificEventLocation();

        if (modal.getAttribute('aria-hidden') === 'false') {
            lockPageScroll();
            clearCreateQueryParam();
        }
    }

    document.addEventListener('admin:page-ready', initializeAnnouncementCreateModal, { once: true });
</script>
@endsection
