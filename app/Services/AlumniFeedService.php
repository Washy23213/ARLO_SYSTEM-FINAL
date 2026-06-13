<?php

namespace App\Services;

use App\Notifications\AnnouncementPublishedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AlumniFeedService
{
    public function __construct(
        protected AnnouncementAudienceService $announcementAudienceService
    ) {
    }

    public function latestJobs(?int $limit = 3)
    {
        if (!Schema::hasTable('jobs')) {
            return collect();
        }

        $query = DB::table('jobs')->orderByDesc('created_at');

        if (Schema::hasColumn('jobs', 'is_active')) {
            $query->where('is_active', true);
        }

        if (Schema::hasColumn('jobs', 'archived_at')) {
            $query->whereNull('archived_at');
        }

        if ($limit !== null) {
            $query->limit($limit);
        }

        return $query->get();
    }

    public function latestAnnouncements(?int $limit = 3, mixed $user = null)
    {
        if (!Schema::hasTable('announcements')) {
            return collect();
        }

        $query = DB::table('announcements')->orderByDesc('created_at');

        if (Schema::hasColumn('announcements', 'is_published')) {
            $query->where('is_published', true);
        }

        if (Schema::hasColumn('announcements', 'archived_at')) {
            $query->whereNull('archived_at');
        }

        if (Schema::hasColumn('announcements', 'expires_at')) {
            $query->where(function ($builder) {
                $builder->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
        }

        if ($user !== null
            && Schema::hasColumn('announcements', 'target_audience')
            && Schema::hasColumn('announcements', 'target_department')
            && Schema::hasColumn('announcements', 'target_graduation_year')) {
            $this->announcementAudienceService->applyVisibilityScope($query, $user);
        }

        if ($limit !== null) {
            $query->limit($limit);
        }

        return $query->get();
    }

    public function unreadAnnouncementIdsFor(mixed $user): array
    {
        if ($user === null || !Schema::hasTable('notifications')) {
            return [];
        }

        return $user->unreadNotifications()
            ->where('type', AnnouncementPublishedNotification::class)
            ->get()
            ->pluck('data.announcement_id')
            ->filter()
            ->map(fn ($value) => (string) $value)
            ->unique()
            ->values()
            ->all();
    }
}
