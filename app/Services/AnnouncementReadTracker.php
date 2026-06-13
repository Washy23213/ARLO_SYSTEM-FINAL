<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AnnouncementReadTracker
{
    public function readMapFor(mixed $user, iterable $announcementIds): array
    {
        $userId = $this->userId($user);
        $ids = $this->normalizeIds($announcementIds);

        if ($userId === null || $ids === [] || ! Schema::hasTable('announcement_reads')) {
            return [];
        }

        return DB::table('announcement_reads')
            ->where('user_id', $userId)
            ->whereIn('announcement_id', $ids)
            ->pluck('read_at', 'announcement_id')
            ->mapWithKeys(fn ($readAt, $announcementId) => [(string) $announcementId => $readAt])
            ->all();
    }

    public function markRead(mixed $user, string $announcementId): bool
    {
        $userId = $this->userId($user);
        $announcementId = trim($announcementId);

        if ($userId === null || $announcementId === '' || ! Schema::hasTable('announcement_reads')) {
            return false;
        }

        $now = now();
        $keys = [
            'announcement_id' => $announcementId,
            'user_id' => $userId,
        ];

        $exists = DB::table('announcement_reads')->where($keys)->exists();

        if ($exists) {
            DB::table('announcement_reads')
                ->where($keys)
                ->update([
                    'read_at' => $now,
                    'updated_at' => $now,
                ]);
        } else {
            DB::table('announcement_reads')->insert($keys + [
                'read_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        return true;
    }

    public function markManyRead(mixed $user, iterable $announcementIds): void
    {
        foreach ($this->normalizeIds($announcementIds) as $announcementId) {
            $this->markRead($user, $announcementId);
        }
    }

    public function decorate(object $announcement, array $readMap): object
    {
        $announcementId = (string) ($announcement->id ?? '');
        $readAt = $readMap[$announcementId] ?? null;

        $announcement->is_read = $readAt !== null;
        $announcement->read_at = $readAt;

        return $announcement;
    }

    protected function userId(mixed $user): ?string
    {
        if ($user === null) {
            return null;
        }

        $id = method_exists($user, 'getAuthIdentifier')
            ? $user->getAuthIdentifier()
            : ($user->id ?? null);

        return is_scalar($id) && trim((string) $id) !== '' ? (string) $id : null;
    }

    protected function normalizeIds(iterable $announcementIds): array
    {
        $ids = [];

        foreach ($announcementIds as $announcementId) {
            if (! is_scalar($announcementId)) {
                continue;
            }

            $announcementId = trim((string) $announcementId);

            if ($announcementId !== '') {
                $ids[$announcementId] = $announcementId;
            }
        }

        return array_values($ids);
    }
}
