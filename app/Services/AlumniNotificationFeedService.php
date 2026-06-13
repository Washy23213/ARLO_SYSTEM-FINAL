<?php

namespace App\Services;

use App\Notifications\AnnouncementPublishedNotification;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AlumniNotificationFeedService
{
    public function __construct(
        protected AlumniFeedService $feedService,
        protected AnnouncementReadTracker $announcementReadTracker
    ) {
    }

    public function activityItemsFor(mixed $user): Collection
    {
        $notificationRecords = collect();

        if ($user !== null && Schema::hasTable('notifications')) {
            $notificationRecords = $user->notifications()->latest()->get();
        }

        $announcements = $this->feedService->latestAnnouncements(null, $user);
        $jobs = $this->feedService->latestJobs(null);

        return $this->buildActivityItems($user, $notificationRecords, $announcements, $jobs);
    }

    public function indicatorCountSince(mixed $user, CarbonInterface|string|null $since): int
    {
        $threshold = $this->normalizeTimestamp($since);

        return $this->activityItemsFor($user)
            ->filter(fn (array $item) => $threshold === null || ($item['sort_timestamp'] ?? 0) > $threshold)
            ->count();
    }

    protected function buildActivityItems(mixed $user, Collection $notificationRecords, Collection $announcements, Collection $jobs): Collection
    {
        $visibleAnnouncementIdList = $announcements
            ->pluck('id')
            ->map(fn ($value) => (string) $value)
            ->filter()
            ->unique()
            ->values();
        $visibleAnnouncementIds = $visibleAnnouncementIdList->flip();
        $announcementReadMap = $this->announcementReadTracker->readMapFor($user, $visibleAnnouncementIdList->all());

        $notificationRecords = $notificationRecords
            ->filter(function (DatabaseNotification $notification) use ($visibleAnnouncementIds) {
                if ($notification->type !== AnnouncementPublishedNotification::class) {
                    return true;
                }

                $announcementId = (string) data_get($notification->data, 'announcement_id', '');

                return $announcementId !== '' && $visibleAnnouncementIds->has($announcementId);
            })
            ->values();

        $announcementNotificationIds = $notificationRecords
            ->filter(fn (DatabaseNotification $notification) => $notification->type === AnnouncementPublishedNotification::class)
            ->pluck('data.announcement_id')
            ->filter()
            ->map(fn ($value) => (string) $value)
            ->unique()
            ->values();
        $facebookAnnouncementIds = $announcements
            ->filter(fn ($announcement) => filled($announcement->facebook_post_id ?? null) || filled($announcement->facebook_post_url ?? null))
            ->pluck('id')
            ->map(fn ($value) => (string) $value)
            ->flip();

        $notificationItems = $notificationRecords->map(function (DatabaseNotification $notification) use ($facebookAnnouncementIds, $announcementReadMap) {
            $createdAt = $notification->created_at;
            $category = (string) data_get($notification->data, 'category', 'Update');
            $type = (string) data_get($notification->data, 'type', '');
            $announcementId = (string) data_get($notification->data, 'announcement_id', '');
            $isFacebookAnnouncement = $announcementId !== '' && $facebookAnnouncementIds->has($announcementId);
            $isUnread = is_null($notification->read_at);

            if ($notification->type === AnnouncementPublishedNotification::class
                && $announcementId !== ''
                && isset($announcementReadMap[$announcementId])) {
                $isUnread = false;
            }

            return [
                'key' => 'notification:' . $notification->id,
                'is_unread' => $isUnread,
                'category' => $category,
                'title' => data_get($notification->data, 'title', 'System update'),
                'summary' => data_get($notification->data, 'summary', 'There is a new update waiting in your portal.'),
                'priority' => data_get($notification->data, 'priority'),
                'status' => data_get($notification->data, 'status'),
                'icon' => $this->resolveActivityIcon($category, $type, $isFacebookAnnouncement),
                'open_url' => route('notifications.open', $notification->id),
                'mark_read_url' => $isUnread ? route('notifications.read', $notification->id) : null,
                'created_at' => $createdAt,
                'date_label' => optional($createdAt)->format('d M') ?: 'Today',
                'time_label' => optional($createdAt)->diffForHumans() ?: 'Just now',
                'sort_timestamp' => optional($createdAt)->getTimestamp() ?? 0,
            ];
        });

        $announcementItems = $announcements
            ->reject(fn ($announcement) => $announcementNotificationIds->contains((string) $announcement->id))
            ->map(function ($announcement) use ($announcementReadMap) {
                $createdAt = $announcement->published_at ?? $announcement->created_at ?? $announcement->updated_at ?? null;
                $normalizedCreatedAt = $this->normalizeDateTime($createdAt);
                $isFacebookAnnouncement = filled($announcement->facebook_post_id ?? null) || filled($announcement->facebook_post_url ?? null);
                $announcementId = (string) ($announcement->id ?? '');

                return [
                    'key' => 'announcement:' . $announcement->id,
                    'is_unread' => ! isset($announcementReadMap[$announcementId]),
                    'category' => 'Announcement',
                    'title' => $announcement->title,
                    'summary' => Str::limit((string) ($announcement->content ?? 'A new alumni announcement has been published.'), 140),
                    'priority' => $announcement->priority ?? null,
                    'status' => null,
                    'icon' => $this->resolveActivityIcon('Announcement', (string) ($announcement->type ?? ''), $isFacebookAnnouncement),
                    'open_url' => route('announcement.detail', $announcement->id),
                    'mark_read_url' => null,
                    'created_at' => $normalizedCreatedAt,
                    'date_label' => $normalizedCreatedAt?->format('d M') ?? 'Today',
                    'time_label' => $normalizedCreatedAt?->diffForHumans() ?? 'Just now',
                    'sort_timestamp' => $normalizedCreatedAt?->getTimestamp() ?? 0,
                ];
            });

        $jobItems = $jobs->map(function ($job) {
            $createdAt = $job->created_at ?? $job->updated_at ?? null;
            $normalizedCreatedAt = $this->normalizeDateTime($createdAt);
            $summaryParts = array_filter([
                $job->company ?? null,
                $job->location ?? null,
                Str::limit((string) ($job->description ?? ''), 90),
            ]);

            return [
                'key' => 'job:' . $job->id,
                'is_unread' => false,
                'category' => 'Job Opportunity',
                'title' => $job->title,
                'summary' => implode(' | ', $summaryParts) ?: 'A new job opportunity is available in the portal.',
                'priority' => $job->type ?? null,
                'status' => null,
                'icon' => $this->resolveActivityIcon('Job Opportunity'),
                'open_url' => route('job.detail', $job->id),
                'mark_read_url' => null,
                'created_at' => $normalizedCreatedAt,
                'date_label' => $normalizedCreatedAt?->format('d M') ?? 'Today',
                'time_label' => $normalizedCreatedAt?->diffForHumans() ?? 'Just now',
                'sort_timestamp' => $normalizedCreatedAt?->getTimestamp() ?? 0,
            ];
        });

        return $notificationItems
            ->concat($announcementItems)
            ->concat($jobItems)
            ->sortByDesc('sort_timestamp')
            ->values();
    }

    protected function normalizeTimestamp(CarbonInterface|string|null $since): ?int
    {
        if ($since instanceof CarbonInterface) {
            return $since->getTimestamp();
        }

        if (!is_string($since) || trim($since) === '') {
            return null;
        }

        try {
            return Carbon::parse($since)->getTimestamp();
        } catch (\Throwable) {
            return null;
        }
    }

    protected function normalizeDateTime(mixed $value): ?Carbon
    {
        if ($value instanceof CarbonInterface) {
            return Carbon::instance($value);
        }

        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    protected function resolveActivityIcon(?string $category, ?string $type = null, bool $isFacebookAnnouncement = false): string
    {
        if ($isFacebookAnnouncement) {
            return 'bxl-facebook-circle';
        }

        $normalizedCategory = strtolower(trim((string) $category));
        $normalizedType = strtolower(trim((string) $type));

        if (str_contains($normalizedCategory, 'job')) {
            return 'bx-briefcase-alt-2';
        }

        return match ($normalizedType ?: $normalizedCategory) {
            'event' => 'bx-calendar-event',
            'news' => 'bx-news',
            'update' => 'bx-refresh',
            'alert' => 'bx-error-circle',
            'announcement' => 'bxs-megaphone',
            default => 'bx-bell',
        };
    }
}
