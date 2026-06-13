@extends('components.layouts.dashboard')

@section('title', 'Announcement Details')

@section('content')
<div class="mx-auto max-w-5xl space-y-6">
    @php
        $metaType = $announcement['type'] ?? null;
        $isFacebookPostHeader = !empty($announcement['is_facebook_post']);

        if (isset($announcement['details']) && is_array($announcement['details'])) {
            foreach ($announcement['details'] as $detailLine) {
                if (!is_string($detailLine) || strpos($detailLine, ':') === false) {
                    continue;
                }
                [$rawLabel, $rawValue] = explode(':', $detailLine, 2);
                $label = strtolower(trim($rawLabel));
                $value = trim($rawValue);
                if ($value === '') {
                    continue;
                }

                if ($metaType === null && $label === 'type') {
                    $metaType = $value;
                }
            }
        }

        $typeText = $isFacebookPostHeader ? 'Facebook Update' : match (strtolower((string) $metaType)) {
            'job' => 'Job Opening',
            'event' => 'Upcoming Event',
            'news' => 'Latest News',
            'update' => 'Important Notice',
            'alert' => 'Important Alert',
            '' => 'N/A',
            default => ucwords((string) $metaType),
        };

        $typeIcon = $isFacebookPostHeader ? 'bxl-facebook-circle' : match (strtolower((string) $metaType)) {
            'job' => 'bx-briefcase-alt-2',
            'event' => 'bx-calendar-event',
            'news' => 'bx-news',
            'update' => 'bx-refresh',
            'alert' => 'bx-error-circle',
            default => 'bx-bell',
        };

    @endphp

    <div class="space-y-4">
        <section class="page-section rounded-[1.75rem] px-6 py-8 text-center">
            <div class="relative mx-auto max-w-3xl">
                <div class="absolute left-1/2 top-2 h-24 w-24 -translate-x-1/2 rounded-full bg-emerald-100/60 blur-3xl"></div>
                <span class="dashboard-hero-icon mx-auto relative {{ $isFacebookPostHeader ? '!bg-white !text-[#1877F2] ring-1 ring-blue-100' : '' }}">
                    <i class='bx {{ $typeIcon }} text-2xl'></i>
                </span>
                <p class="section-kicker mt-4 text-emerald-700/80">Announcement</p>
                <h1 class="section-heading mt-2">{{ $announcement['title'] }}</h1>
                <p class="section-subcopy mt-3">{{ $announcement['company'] }}</p>
            </div>
        </section>
    </div>

    <div class="surface-card p-6">
        <div class="flex flex-wrap items-center justify-center gap-3 text-center">
            <span class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm">
                <i class="bx bx-time text-lg text-emerald-700"></i>
                Posted {{ $announcement['posted'] }}
            </span>
            <span class="inline-flex items-center gap-2 rounded-full border border-blue-200 bg-blue-50 px-4 py-2 text-sm font-medium text-blue-700 shadow-sm">
                <i class="bx bx-show text-lg"></i>
                Viewed <span class="font-semibold">{{ number_format((int) ($announcement['views_count'] ?? 0)) }}</span> times
            </span>
            @if(!empty($announcement['is_read']))
            <span class="inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm font-medium text-emerald-800 shadow-sm">
                <i class="bx bx-envelope-open text-lg"></i>
                Read
                @if(!empty($announcement['read_at']))
                    <span class="font-semibold">{{ \Carbon\Carbon::parse($announcement['read_at'])->diffForHumans() }}</span>
                @endif
            </span>
            @endif
            @if($announcement['deadline'])
            <span class="inline-flex items-center gap-2 rounded-full border border-amber-200 bg-amber-50 px-4 py-2 text-sm font-medium text-amber-800 shadow-sm">
                <i class="bx bx-calendar text-lg text-emerald-700"></i>
                Deadline {{ $announcement['deadline'] }}
            </span>
            @endif
            <span class="inline-flex rounded-full border border-emerald-200 bg-gradient-to-r from-emerald-50 to-teal-50 px-4 py-2 text-sm font-semibold text-emerald-800 shadow-sm">
                {{ $typeText }}
            </span>
        </div>
    </div>

    <div class="surface-card p-6 text-center">
        <h2 class="inline-flex items-center gap-2 text-2xl font-semibold text-gray-900 mb-4">
            <span class="inline-flex h-8 w-8 items-center justify-center rounded-md bg-emerald-100 text-emerald-700">
                <i class='bx bx-detail text-lg'></i>
            </span>
            Details
        </h2>
        <div class="mb-6 rounded-2xl border border-emerald-100 bg-gradient-to-r from-emerald-50/80 via-white to-teal-50/70 px-6 py-6 shadow-sm ring-1 ring-emerald-100/70">
            @php
                $referenceUrl = $announcement['reference_url'] ?? null;
                $storedReferenceUrl = $announcement['stored_reference_url'] ?? $referenceUrl;
                $isFacebookPost = !empty($announcement['is_facebook_post']);
                $contentText = (string) ($announcement['content'] ?? '');

                if ($isFacebookPost && !empty($storedReferenceUrl)) {
                    $contentText = trim(str_replace('Original Facebook post: '.$storedReferenceUrl, '', $contentText));
                }

                if ($isFacebookPost && !empty($referenceUrl) && $referenceUrl !== $storedReferenceUrl) {
                    $contentText = trim(str_replace('Original Facebook post: '.$referenceUrl, '', $contentText));
                }

                if ($isFacebookPost) {
                    $contentText = trim((string) preg_replace('/(?:^|\R)\s*Original Facebook post:\s*https?:\/\/\S+\s*/iu', "\n", $contentText));
                }

                $contentParts = preg_split('/(https?:\/\/[^\s<]+)/u', $contentText, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [];
                $linkedContent = collect($contentParts)->map(function ($part) {
                    if (preg_match('/^https?:\/\/[^\s<]+$/u', $part)) {
                        $url = rtrim($part, '.,;:)');
                        $suffix = substr($part, strlen($url));

                        return '<a href="'.e($url).'" target="_blank" rel="noopener noreferrer" class="font-semibold text-emerald-700 underline decoration-emerald-300 underline-offset-4 transition-colors hover:text-emerald-900">'.e($url).'</a>'.e($suffix);
                    }

                    return e($part);
                })->implode('');
            @endphp
            @if(trim($contentText) !== '')
                <p class="text-gray-800 leading-relaxed font-medium">{!! nl2br($linkedContent) !!}</p>
            @endif
            @if($isFacebookPost && !empty($referenceUrl))
                <p class="mt-5 inline-flex flex-wrap items-center justify-center gap-2 rounded-xl border border-emerald-100 bg-white/80 px-4 py-3 text-sm font-medium text-gray-800 shadow-sm">
                    <span>Original Facebook post:</span>
                    <a href="{{ $referenceUrl }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1.5 font-semibold text-emerald-700 underline decoration-emerald-300 underline-offset-4 transition-colors hover:text-emerald-900">
                        <i class='bx bx-link-external text-base'></i>
                        Open original post
                    </a>
                </p>
            @endif
        </div>

        @if(isset($announcement['details']) && is_array($announcement['details']))
            <div class="space-y-3">
                @foreach($announcement['details'] as $detail)
                    @if(strpos($detail, ':') !== false)
                        @php
                            $parts = explode(':', $detail, 2);
                            $label = trim($parts[0]);
                            $value = trim($parts[1]);
                            $normalizedLabel = strtolower($label);
                            $hiddenDetailLabels = ['priority', 'audience', 'target audience', 'event date', 'location', 'type'];
                            $displayValue = $normalizedLabel === 'type' ? $typeText : $value;
                        @endphp
                        @if(in_array($normalizedLabel, $hiddenDetailLabels, true))
                            @continue
                        @endif
                        <div class="rounded-2xl border border-emerald-100 bg-gradient-to-r from-emerald-50/75 to-white px-4 py-3 shadow-sm">
                            <div class="flex items-start justify-between gap-4">
                                <span class="inline-flex items-center text-sm font-semibold text-emerald-800 flex-shrink-0">
                                    <i class='bx bx-chevron-right mr-1 text-emerald-700'></i>
                                    {{ $label }}:
                                </span>
                                <span class="text-sm font-medium text-gray-800 text-right">{{ $displayValue }}</span>
                            </div>
                        </div>
                    @else
                        <div class="rounded-2xl border-l-4 border-emerald-400 bg-gradient-to-r from-emerald-50/70 to-white p-3 shadow-sm">
                            <p class="text-gray-800 font-medium">{{ $detail }}</p>
                        </div>
                    @endif
                @endforeach
            </div>
        @endif
    </div>

    <div class="surface-card p-6">
        <h2 class="inline-flex items-center gap-2 text-2xl font-semibold text-gray-900 mb-4">
            <span class="inline-flex h-8 w-8 items-center justify-center rounded-md bg-emerald-100 text-emerald-700">
                <i class='bx bx-envelope text-lg'></i>
            </span>
            Contact Information
        </h2>
        <div class="rounded-2xl border border-emerald-100 bg-gradient-to-r from-emerald-50/80 via-white to-teal-50/70 p-5 shadow-sm ring-1 ring-emerald-100/70">
            <p class="text-gray-700 mb-3">{{ $announcement['contact_info'] }}</p>
            @if(!empty($announcement['reference_url']) && empty($announcement['is_facebook_post']))
                <a
                    href="{{ $announcement['reference_url'] }}"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-white px-4 py-2 text-sm font-semibold text-emerald-800 shadow-sm transition-colors hover:bg-emerald-50"
                >
                    <i class='bx bx-link-external text-base'></i>
                    Open Original Facebook Post
                </a>
            @endif
            @if($announcement['type'] === 'job')
                <p class="text-sm text-gray-600">
                    Interested candidates should send their resume and cover letter with the subject line: "Application - {{ $announcement['title'] }}"
                </p>
            @elseif($announcement['type'] === 'event')
                <p class="text-sm text-gray-600">
                    Don't miss this opportunity to connect with fellow alumni and industry professionals!
                </p>
            @else
                <p class="text-sm text-gray-600">
                    For more information, please contact the organization directly.
                </p>
            @endif
        </div>
    </div>
</div>
@endsection
