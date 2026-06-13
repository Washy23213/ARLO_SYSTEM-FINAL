<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class FacebookPageAnnouncementSynchronizer
{
    public function __construct(
        protected AnnouncementNotificationService $announcementNotificationService,
        protected FacebookPostUrlResolver $facebookPostUrlResolver
    ) {
    }

    public function syncRecentPosts(int $limit = 100): array
    {
        if (! $this->isEnabled()) {
            return [
                'attempted' => false,
                'success' => false,
                'imported' => 0,
                'updated' => 0,
                'message' => 'Facebook Page sync is disabled.',
            ];
        }

        if ($this->isMisconfigured()) {
            return [
                'attempted' => true,
                'success' => false,
                'imported' => 0,
                'updated' => 0,
                'message' => 'Facebook Page sync is enabled but incomplete.',
            ];
        }

        if (! $this->hasColumn('announcements', 'facebook_post_id')) {
            return [
                'attempted' => true,
                'success' => false,
                'imported' => 0,
                'updated' => 0,
                'message' => 'Facebook announcement sync requires the latest database migration.',
            ];
        }

        try {
            $imported = 0;
            $updated = 0;
            $archived = 0;
            $processed = 0;
            $nextPageUrl = null;
            $syncedPostIds = [];

            while ($processed < $limit) {
                $remaining = max($limit - $processed, 1);
                $response = $this->requestFeedPage($nextPageUrl, $remaining);

                if (! $response->successful()) {
                    $this->reportFacebookFailure(sprintf(
                        'Facebook announcement import failed with status %s: %s',
                        $response->status(),
                        Str::limit((string) $response->body(), 500)
                    ));

                    return [
                        'attempted' => true,
                        'success' => false,
                        'imported' => 0,
                        'updated' => 0,
                        'message' => 'Facebook announcement import failed.',
                    ];
                }

                $posts = (array) $response->json('data', []);

                foreach ($posts as $post) {
                    $post = (array) $post;
                    $postId = trim((string) data_get($post, 'id', ''));

                    if ($postId !== '') {
                        $syncedPostIds[$postId] = true;
                    }

                    $result = $this->upsertAnnouncementFromPost($post);
                    $processed++;

                    if ($result === 'created') {
                        $imported++;
                    }

                    if ($result === 'updated') {
                        $updated++;
                    }

                    if ($processed >= $limit) {
                        break;
                    }
                }

                $nextPageUrl = $this->nextPageUrl((array) $response->json());

                if ($posts === [] || $nextPageUrl === null) {
                    break;
                }
            }

            $archived = $this->archiveUnavailableFacebookAnnouncements(array_keys($syncedPostIds));

            return [
                'attempted' => true,
                'success' => true,
                'imported' => $imported,
                'updated' => $updated,
                'archived' => $archived,
                'message' => $this->summaryMessage($imported, $updated, $archived),
            ];
        } catch (Throwable $exception) {
            $this->reportFacebookException('Facebook announcement import request failed.', $exception);

            return [
                'attempted' => true,
                'success' => false,
                'imported' => 0,
                'updated' => 0,
                'archived' => 0,
                'message' => 'Facebook announcement import failed.',
            ];
        }
    }

    protected function requestFeedPage(?string $pageUrl, int $pageLimit)
    {
        $request = Http::acceptJson()
            ->timeout(15);

        if (is_string($pageUrl) && trim($pageUrl) !== '') {
            return $request->get($pageUrl);
        }

        return $request->get($this->endpoint(), [
            'access_token' => (string) config('services.facebook.page_access_token'),
            'fields' => $this->requestedFields(),
            'limit' => min($pageLimit, 100),
        ]);
    }

    protected function upsertAnnouncementFromPost(array $post): ?string
    {
        $postId = trim((string) data_get($post, 'id', ''));

        if ($postId === '') {
            return null;
        }

        $title = $this->resolveTitle($post);
        $content = $this->resolveContent($post);

        if ($title === '' && $content === '') {
            return null;
        }

        $createdAt = $this->parseTimestamp(data_get($post, 'created_time')) ?? now();
        $updatedAt = $this->parseTimestamp(data_get($post, 'updated_time')) ?? $createdAt;
        $permalink = $this->facebookPostUrlResolver->resolve(
            (string) data_get($post, 'permalink_url', ''),
            $postId
        ) ?? '';
        $imageUrl = $this->cleanUrl((string) data_get($post, 'full_picture', ''));

        $existing = DB::table('announcements')
            ->where('facebook_post_id', $postId)
            ->first();

        if ($existing) {
            $updatePayload = [
                'title' => $title,
                'content' => $content,
                'is_published' => true,
                'facebook_last_synced_at' => now(),
            ];

            if ($this->hasColumn('announcements', 'published_at') && empty($existing->published_at)) {
                $updatePayload['published_at'] = $createdAt;
            }

            if ($this->hasColumn('announcements', 'updated_at')) {
                $updatePayload['updated_at'] = $updatedAt;
            }

            if ($this->hasColumn('announcements', 'image_url') && $imageUrl !== '') {
                $updatePayload['image_url'] = $imageUrl;
            }

            if ($this->hasColumn('announcements', 'attachment_url') && $permalink !== '') {
                $updatePayload['attachment_url'] = $permalink;
            }

            if ($this->hasColumn('announcements', 'facebook_post_url') && $permalink !== '') {
                $updatePayload['facebook_post_url'] = $permalink;
            }

            DB::table('announcements')
                ->where('id', $existing->id)
                ->update($updatePayload);

            $announcement = (object) array_merge((array) $existing, $updatePayload, [
                'id' => $existing->id,
            ]);

            if ($this->announcementNotificationService->shouldBackfillNotification($announcement)) {
                $this->announcementNotificationService->notifyRecipients($announcement);
            }

            return 'updated';
        }

        $insertPayload = [
            'id' => (string) Str::uuid(),
            'title' => $title,
            'content' => $content,
            'type' => 'Update',
            'priority' => 'Medium',
            'target_audience' => 'All',
            'event_date' => null,
            'event_location' => null,
            'is_published' => true,
            'published_at' => $createdAt,
            'expires_at' => null,
            'views_count' => 0,
            'created_by' => null,
            'created_at' => $createdAt,
            'updated_at' => $updatedAt,
            'facebook_post_id' => $postId,
            'facebook_post_url' => $permalink !== '' ? $permalink : null,
            'facebook_last_synced_at' => now(),
        ];

        if ($this->hasColumn('announcements', 'target_department')) {
            $insertPayload['target_department'] = null;
        }

        if ($this->hasColumn('announcements', 'target_graduation_year')) {
            $insertPayload['target_graduation_year'] = null;
        }

        if ($this->hasColumn('announcements', 'image_url')) {
            $insertPayload['image_url'] = $imageUrl !== '' ? $imageUrl : null;
        }

        if ($this->hasColumn('announcements', 'attachment_url')) {
            $insertPayload['attachment_url'] = $permalink !== '' ? $permalink : null;
        }

        DB::table('announcements')->insert($insertPayload);

        $this->announcementNotificationService->notifyRecipients((object) $insertPayload);

        return 'created';
    }

    protected function archiveUnavailableFacebookAnnouncements(array $syncedPostIds): int
    {
        if (! $this->hasColumn('announcements', 'facebook_post_id')) {
            return 0;
        }

        if (! $this->hasColumn('announcements', 'archived_at') && ! $this->hasColumn('announcements', 'is_published')) {
            return 0;
        }

        $syncedPostIdLookup = array_fill_keys($syncedPostIds, true);
        $announcements = DB::table('announcements')
            ->select(['id', 'facebook_post_id'])
            ->whereNotNull('facebook_post_id')
            ->whereRaw("TRIM(facebook_post_id) <> ''")
            ->when($this->hasColumn('announcements', 'archived_at'), fn ($query) => $query->whereNull('archived_at'))
            ->get();

        $archived = 0;

        foreach ($announcements as $announcement) {
            $postId = trim((string) ($announcement->facebook_post_id ?? ''));

            if ($postId === '' || isset($syncedPostIdLookup[$postId])) {
                continue;
            }

            if (! $this->facebookPostIsUnavailable($postId)) {
                continue;
            }

            $archived += $this->archiveFacebookAnnouncement((string) $announcement->id);
        }

        return $archived;
    }

    protected function facebookPostIsUnavailable(string $postId): bool
    {
        try {
            $version = (string) config('services.facebook.graph_version', 'v25.0');
            $response = Http::acceptJson()
                ->timeout(15)
                ->get("https://graph.facebook.com/{$version}/{$postId}", [
                    'fields' => 'id,is_published',
                    'access_token' => (string) config('services.facebook.page_access_token'),
                ]);
        } catch (Throwable $exception) {
            $this->reportFacebookException("Facebook source post check failed for {$postId}.", $exception);

            return false;
        }

        if ($response->successful()) {
            return $response->json('is_published') === false;
        }

        $errorCode = (string) $response->json('error.code', '');
        $errorSubcode = (string) $response->json('error.error_subcode', '');
        $errorMessage = Str::lower((string) $response->json('error.message', ''));

        return in_array($response->status(), [400, 404], true)
            && (
                $errorCode === '100'
                || $errorSubcode === '33'
                || Str::contains($errorMessage, [
                    'unsupported get request',
                    'does not exist',
                    'cannot be loaded',
                    'missing permissions',
                ])
            );
    }

    protected function archiveFacebookAnnouncement(string $announcementId): int
    {
        $payload = [];

        if ($this->hasColumn('announcements', 'is_published')) {
            $payload['is_published'] = false;
        }

        if ($this->hasColumn('announcements', 'archived_at')) {
            $payload['archived_at'] = now();
        }

        if ($this->hasColumn('announcements', 'updated_at')) {
            $payload['updated_at'] = now();
        }

        if ($this->hasColumn('announcements', 'facebook_last_synced_at')) {
            $payload['facebook_last_synced_at'] = now();
        }

        if ($payload === []) {
            return 0;
        }

        return DB::table('announcements')
            ->where('id', $announcementId)
            ->update($payload);
    }

    protected function resolveTitle(array $post): string
    {
        $message = $this->cleanText((string) data_get($post, 'message', ''));
        $attachmentTitle = $this->cleanText((string) data_get($this->firstAttachment($post), 'title', ''));

        $candidate = $message !== ''
            ? trim((string) preg_split('/\R+/', $message, 2)[0])
            : $attachmentTitle;

        if ($candidate === '') {
            $candidate = 'Facebook Page Update';
        }

        return Str::limit($this->cleanText($candidate), 255, '');
    }

    protected function resolveContent(array $post): string
    {
        $attachment = $this->firstAttachment($post);
        $message = $this->cleanText((string) data_get($post, 'message', ''));
        $attachmentTitle = $this->cleanText((string) data_get($attachment, 'title', ''));
        $attachmentDescription = $this->cleanText((string) data_get($attachment, 'description', ''));

        $parts = array_values(array_filter([
            $message,
            $attachmentTitle !== '' && $attachmentTitle !== $message ? $attachmentTitle : null,
            $attachmentDescription,
        ], fn ($value) => is_string($value) && trim($value) !== ''));

        if ($parts === []) {
            $parts[] = 'This post was imported from the official Facebook Page.';
        }

        return $this->cleanText(implode("\n\n", $parts));
    }

    protected function cleanText(string $value): string
    {
        $value = trim($value);

        if ($value === '') {
            return '';
        }

        if (function_exists('mb_scrub')) {
            $value = mb_scrub($value, 'UTF-8');
        } elseif (function_exists('mb_check_encoding') && ! mb_check_encoding($value, 'UTF-8')) {
            $converted = @mb_convert_encoding($value, 'UTF-8', 'UTF-8');

            if (is_string($converted)) {
                $value = $converted;
            }
        }

        $converted = @iconv('UTF-8', 'UTF-8//IGNORE', $value);

        if (is_string($converted)) {
            $value = $converted;
        }

        $withoutControlCharacters = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value);

        if (is_string($withoutControlCharacters)) {
            $value = $withoutControlCharacters;
        }

        return trim($value);
    }

    protected function firstAttachment(array $post): array
    {
        $attachments = data_get($post, 'attachments.data', []);

        if (! is_array($attachments)) {
            return [];
        }

        foreach ($attachments as $attachment) {
            if (is_array($attachment)) {
                return $attachment;
            }
        }

        return [];
    }

    protected function endpoint(): string
    {
        $version = (string) config('services.facebook.graph_version', 'v25.0');
        $pageId = (string) config('services.facebook.page_id');

        return "https://graph.facebook.com/{$version}/{$pageId}/posts";
    }

    protected function requestedFields(): string
    {
        return 'id,message,permalink_url,created_time,updated_time,full_picture,attachments{title,description,url,target}';
    }

    protected function nextPageUrl(array $payload): ?string
    {
        $next = trim((string) data_get($payload, 'paging.next', ''));

        return $next !== '' ? $next : null;
    }

    protected function parseTimestamp(mixed $value): ?Carbon
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (Throwable) {
            return null;
        }
    }

    protected function cleanUrl(string $value): string
    {
        $url = trim($value);

        return filter_var($url, FILTER_VALIDATE_URL) ? $url : '';
    }

    protected function summaryMessage(int $imported, int $updated, int $archived = 0): string
    {
        $importedLabel = $imported === 1 ? '1 new post' : "{$imported} new posts";
        $updatedLabel = $updated === 1 ? '1 existing announcement' : "{$updated} existing announcements";

        $message = "Facebook sync completed. Imported {$importedLabel} and updated {$updatedLabel}.";

        if ($archived > 0) {
            $archivedLabel = $archived === 1 ? '1 missing Facebook post' : "{$archived} missing Facebook posts";
            $message .= " Archived {$archivedLabel}.";
        }

        return $message;
    }

    protected function hasColumn(string $table, string $column): bool
    {
        return Schema::hasColumn($table, $column);
    }

    protected function isEnabled(): bool
    {
        return (bool) config('services.facebook.page_sync_enabled', false);
    }

    protected function isMisconfigured(): bool
    {
        return blank(config('services.facebook.page_id'))
            || blank(config('services.facebook.page_access_token'));
    }

    protected function reportFacebookFailure(string $message): void
    {
        report(new RuntimeException($this->sanitizeForLog($message)));
    }

    protected function reportFacebookException(string $message, Throwable $exception): void
    {
        report(new RuntimeException(
            $message.' '.$this->sanitizeForLog($exception->getMessage()),
            0,
            $exception
        ));
    }

    protected function sanitizeForLog(string $message): string
    {
        return preg_replace('/([?&]access_token=)[^&\s]+/i', '$1[redacted]', $message) ?? $message;
    }
}
