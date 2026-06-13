<?php

namespace App\Services;

use Illuminate\Support\Str;

class FacebookPostUrlResolver
{
    public function resolve(?string $storedUrl, ?string $postId): ?string
    {
        $storedUrl = $this->cleanUrl($storedUrl);

        if ($storedUrl !== null && $this->shouldKeepStoredUrl($storedUrl)) {
            return $storedUrl;
        }

        return $this->storyUrlFromPostId($postId) ?? $storedUrl;
    }

    protected function storyUrlFromPostId(?string $postId): ?string
    {
        $postId = trim((string) $postId);

        if ($postId === '') {
            return null;
        }

        $pageId = (string) config('services.facebook.page_id');
        $storyId = $postId;

        if (str_contains($postId, '_')) {
            [$pageId, $storyId] = explode('_', $postId, 2);
        }

        $pageId = trim($pageId);
        $storyId = trim($storyId);

        if ($pageId === '' || $storyId === '') {
            return null;
        }

        return "https://www.facebook.com/story.php?story_fbid={$storyId}&id={$pageId}";
    }

    protected function shouldKeepStoredUrl(string $url): bool
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));

        if (! Str::contains($host, 'facebook.com')) {
            return true;
        }

        $path = strtolower((string) parse_url($url, PHP_URL_PATH));

        return Str::contains($path, [
            '/photo',
            '/photos',
            '/reel',
            '/videos',
            '/watch',
        ]);
    }

    protected function cleanUrl(?string $url): ?string
    {
        $url = trim((string) $url);

        if ($url === '') {
            return null;
        }

        return filter_var($url, FILTER_VALIDATE_URL) ? $url : null;
    }
}
