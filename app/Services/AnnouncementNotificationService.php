<?php

namespace App\Services;

use App\Notifications\AnnouncementPublishedNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class AnnouncementNotificationService
{
    public function __construct(
        protected AnnouncementAudienceService $announcementAudienceService
    ) {
    }

    public function shouldNotifyOnPublication(?object $existing, array $data): bool
    {
        $isPublished = (bool) ($data['is_published'] ?? false);

        if (! $isPublished) {
            return false;
        }

        if ($existing === null) {
            return true;
        }

        return ! (bool) ($existing->is_published ?? false);
    }

    public function shouldBackfillNotification(object|array $announcement): bool
    {
        $announcementId = trim((string) data_get($announcement, 'id', ''));

        if ($announcementId === '') {
            return false;
        }

        if (! $this->isPublished($announcement) || $this->isArchived($announcement) || $this->isExpired($announcement)) {
            return false;
        }

        if (! Schema::hasTable('notifications')) {
            return false;
        }

        return ! DB::table('notifications')
            ->where('type', AnnouncementPublishedNotification::class)
            ->get(['data'])
            ->contains(function (object $row) use ($announcementId) {
                $payload = json_decode((string) ($row->data ?? '{}'), true);

                return (string) data_get($payload, 'announcement_id', '') === $announcementId;
            });
    }

    public function notifyRecipients(object|array $announcement): int
    {
        $recipients = $this->announcementAudienceService
            ->recipientsQuery($announcement)
            ->get();

        $count = 0;

        foreach ($recipients as $recipient) {
            try {
                $recipient->notify(new AnnouncementPublishedNotification(
                    (string) data_get($announcement, 'id', ''),
                    (string) data_get($announcement, 'title', 'New Announcement'),
                    (string) data_get($announcement, 'content', ''),
                    (string) data_get($announcement, 'type', 'Announcement'),
                    (string) data_get($announcement, 'priority', 'Medium')
                ));
                $count++;
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        return $count;
    }

    protected function isPublished(object|array $announcement): bool
    {
        return (bool) data_get($announcement, 'is_published', false);
    }

    protected function isArchived(object|array $announcement): bool
    {
        return ! empty(data_get($announcement, 'archived_at'));
    }

    protected function isExpired(object|array $announcement): bool
    {
        $expiresAt = data_get($announcement, 'expires_at');

        if ($expiresAt === null || $expiresAt === '') {
            return false;
        }

        try {
            return Carbon::parse($expiresAt)->isPast();
        } catch (Throwable) {
            return false;
        }
    }
}
