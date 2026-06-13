@extends('admin.layout')

@section('title', 'Announcement Details')

@section('content')
@php
    $announcementPriority = trim((string) ($announcement->priority ?? 'Medium')) ?: 'Medium';
    $priorityBadgeClasses = match ($announcementPriority) {
        'Urgent' => 'bg-rose-100 text-rose-700 ring-1 ring-rose-200',
        'High' => 'bg-orange-100 text-orange-700 ring-1 ring-orange-200',
        'Medium' => 'bg-amber-100 text-amber-700 ring-1 ring-amber-200',
        default => 'bg-emerald-100 text-emerald-700 ring-1 ring-emerald-200',
    };
    $storedFacebookPostUrl = trim((string) ($announcement->facebook_post_url ?? ''));
    $facebookPostUrl = trim((string) ($announcement->facebook_open_url ?? $storedFacebookPostUrl));
    $referenceUrl = $facebookPostUrl !== '' ? $facebookPostUrl : ($announcement->attachment_url ?? null);
    $referenceLabel = $facebookPostUrl !== '' ? 'Original Facebook post:' : 'Reference link:';
    $contentText = (string) ($announcement->content ?? 'N/A');
    $isRead = (bool) ($announcement->is_read ?? false);
    $readAt = $announcement->read_at ?? null;

    if ($storedFacebookPostUrl !== '') {
        $contentText = trim(str_replace('Original Facebook post: '.$storedFacebookPostUrl, '', $contentText));
    }

    if ($facebookPostUrl !== '' && $facebookPostUrl !== $storedFacebookPostUrl) {
        $contentText = trim(str_replace('Original Facebook post: '.$facebookPostUrl, '', $contentText));
    }

    if ($facebookPostUrl !== '') {
        $contentText = trim((string) preg_replace('/(?:^|\R)\s*Original Facebook post:\s*https?:\/\/\S+\s*/iu', "\n", $contentText));
    }
@endphp
<div class="mx-auto max-w-6xl space-y-6">
    <div class="surface-card p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div class="md:col-span-2">
                <p class="text-xs uppercase tracking-wide text-gray-500">Title</p>
                <p class="text-gray-900 font-semibold mt-1">{{ $announcement->title ?? 'N/A' }}</p>
            </div>
            <div>
                <p class="text-xs uppercase tracking-wide text-gray-500">Type</p>
                <p class="text-gray-900 mt-1">{{ $announcement->type ?? 'N/A' }}</p>
            </div>
            <div>
                <p class="text-xs uppercase tracking-wide text-gray-500">Priority</p>
                <div class="mt-1">
                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold uppercase tracking-[0.12em] {{ $priorityBadgeClasses }}">
                        {{ $announcementPriority }}
                    </span>
                </div>
            </div>
            <div>
                <p class="text-xs uppercase tracking-wide text-gray-500">Target Audience</p>
                <p class="text-gray-900 mt-1">{{ $announcement->target_audience ?? 'All' }}</p>
            </div>
            <div>
                <p class="text-xs uppercase tracking-wide text-gray-500">Target College</p>
                <p class="text-gray-900 mt-1">{{ $announcement->target_department ?? 'All Colleges' }}</p>
            </div>
            <div>
                <p class="text-xs uppercase tracking-wide text-gray-500">Target Graduation Year</p>
                <p class="text-gray-900 mt-1">{{ $announcement->target_graduation_year ?? 'All Years' }}</p>
            </div>
            <div>
                <p class="text-xs uppercase tracking-wide text-gray-500">Status</p>
                <p class="text-gray-900 mt-1">{{ (isset($announcement->is_published) && $announcement->is_published) ? 'Published' : 'Draft' }}</p>
            </div>
            <div>
                <p class="text-xs uppercase tracking-wide text-gray-500">Read Status</p>
                <div class="mt-1">
                    <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold uppercase tracking-[0.12em] {{ $isRead ? 'bg-slate-100 text-slate-700 ring-1 ring-slate-200' : 'bg-emerald-100 text-emerald-800 ring-1 ring-emerald-200' }}">
                        <i class='bx {{ $isRead ? 'bx-envelope-open' : 'bxs-envelope' }}'></i>
                        {{ $isRead ? 'Read' : 'Unread' }}
                    </span>
                    @if($isRead && !empty($readAt))
                        <p class="mt-1 text-xs font-medium text-gray-500">{{ \Carbon\Carbon::parse($readAt)->diffForHumans() }}</p>
                    @endif
                </div>
            </div>
            <div>
                <p class="text-xs uppercase tracking-wide text-gray-500">Event Date</p>
                <p class="text-gray-900 mt-1">{{ !empty($announcement->event_date) ? \Carbon\Carbon::parse($announcement->event_date)->format('M d, Y h:i A') : 'N/A' }}</p>
            </div>
            <div>
                <p class="text-xs uppercase tracking-wide text-gray-500">Event Location</p>
                <p class="text-gray-900 mt-1">{{ $announcement->event_location ?? 'N/A' }}</p>
            </div>
            <div class="md:col-span-2">
                <p class="text-xs uppercase tracking-wide text-gray-500">Content</p>
                <div class="mt-1 space-y-4 text-gray-900">
                    @if($contentText !== '')
                        <p class="whitespace-pre-line">{{ $contentText }}</p>
                    @endif
                    @if(!empty($referenceUrl))
                        <p class="inline-flex flex-wrap items-center gap-2 rounded-xl border border-emerald-100 bg-emerald-50/70 px-4 py-3 text-sm font-medium text-gray-800">
                            <span>{{ $referenceLabel }}</span>
                            <a href="{{ $referenceUrl }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1.5 font-semibold text-emerald-700 hover:text-emerald-900">
                                <i class='bx bx-link-external'></i>
                                {{ $facebookPostUrl !== '' ? 'Open original post' : 'Open link' }}
                            </a>
                        </p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
