<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class FacebookPagePublisher
{
    public function __construct(
        protected FacebookPostUrlResolver $facebookPostUrlResolver
    ) {
    }

    public function publishAnnouncement(object $announcement): array
    {
        if (! $this->isEnabled()) {
            return [
                'attempted' => false,
                'success' => false,
                'message' => null,
            ];
        }

        if ($this->isMisconfigured()) {
            return [
                'attempted' => true,
                'success' => false,
                'message' => 'Facebook Page sync is enabled but incomplete.',
            ];
        }

        $payload = [
            'message' => $this->buildMessage($announcement),
            'access_token' => (string) config('services.facebook.page_access_token'),
        ];

        if (! $this->isExistingFacebookPost($announcement)) {
            $link = $this->announcementUrl($announcement);

            if ($link !== null) {
                $payload['link'] = $link;
            }
        }

        try {
            $response = Http::asForm()
                ->acceptJson()
                ->timeout(10)
                ->post($this->endpoint($announcement), $payload);

            if ($response->successful() && filled($response->json('id'))) {
                $postId = (string) $response->json('id');

                return [
                    'attempted' => true,
                    'success' => true,
                    'message' => $this->isExistingFacebookPost($announcement)
                        ? 'Updated Facebook Page post.'
                        : 'Shared to Facebook Page.',
                    'post_id' => $postId,
                    'post_url' => $this->facebookPostUrlResolver->resolve(
                        $announcement->facebook_post_url ?? null,
                        $postId
                    ),
                ];
            }

            if ($response->successful() && $this->isExistingFacebookPost($announcement)) {
                $postId = (string) ($announcement->facebook_post_id ?? '');

                return [
                    'attempted' => true,
                    'success' => true,
                    'message' => 'Updated Facebook Page post.',
                    'post_id' => $postId,
                    'post_url' => $this->facebookPostUrlResolver->resolve(
                        $announcement->facebook_post_url ?? null,
                        $postId
                    ),
                ];
            }

            $this->reportFacebookFailure(sprintf(
                'Facebook Page sync failed with status %s: %s',
                $response->status(),
                Str::limit((string) $response->body(), 500)
            ));
        } catch (Throwable $exception) {
            $this->reportFacebookException('Facebook Page sync request failed.', $exception);
        }

        return [
            'attempted' => true,
            'success' => false,
            'message' => 'Facebook Page sync failed.',
        ];
    }

    protected function endpoint(object $announcement): string
    {
        $version = (string) config('services.facebook.graph_version', 'v25.0');
        $postId = trim((string) ($announcement->facebook_post_id ?? ''));

        if ($postId !== '') {
            return "https://graph.facebook.com/{$version}/{$postId}";
        }

        $pageId = (string) config('services.facebook.page_id');

        return "https://graph.facebook.com/{$version}/{$pageId}/feed";
    }

    protected function buildMessage(object $announcement): string
    {
        $title = trim((string) ($announcement->title ?? 'New Announcement'));
        $content = trim(preg_replace('/\s+/', ' ', (string) ($announcement->content ?? '')) ?? '');

        return implode("\n\n", array_filter([
            $title,
            $content,
        ]));
    }

    protected function announcementUrl(object $announcement): ?string
    {
        $id = trim((string) ($announcement->id ?? ''));

        if ($id === '') {
            return null;
        }

        $url = route('announcement.detail', $id);

        return $this->isPublicUrl($url) ? $url : null;
    }

    protected function isPublicUrl(string $url): bool
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));

        return $host !== '' && ! in_array($host, ['localhost', '127.0.0.1', '::1'], true);
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

    protected function isExistingFacebookPost(object $announcement): bool
    {
        return trim((string) ($announcement->facebook_post_id ?? '')) !== '';
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
