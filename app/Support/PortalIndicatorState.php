<?php

namespace App\Support;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class PortalIndicatorState
{
    public const ADMIN_ANNOUNCEMENTS = 'admin_announcements';
    public const ADMIN_FEEDBACK = 'admin_feedback';
    public const ADMIN_AUDIT_LOGS = 'admin_audit_logs';
    public const ALUMNI_JOBS = 'alumni_jobs';
    public const ALUMNI_NOTIFICATIONS = 'alumni_notifications';

    public static function lastSeen(Request $request, string $section, mixed $userId = null): ?Carbon
    {
        $sessionValue = $request->hasSession()
            ? $request->session()->get(self::sessionKey($section, $userId))
            : null;

        $cachedValue = $userId !== null
            ? self::store()->get(self::cacheKey($section, $userId))
            : null;

        return collect([
            self::parseTimestamp($sessionValue),
            self::parseTimestamp($cachedValue),
        ])
            ->filter()
            ->sortByDesc(fn (Carbon $timestamp) => $timestamp->getTimestamp())
            ->first();
    }

    public static function markSeen(
        Request $request,
        string $section,
        mixed $userId = null,
        CarbonInterface|string|null $timestamp = null
    ): void {
        $resolved = $timestamp instanceof CarbonInterface
            ? $timestamp
            : ($timestamp ? Carbon::parse($timestamp) : now());

        if ($request->hasSession()) {
            $request->session()->put(
                self::sessionKey($section, $userId),
                $resolved->toIso8601String()
            );
        }

        if ($userId !== null) {
            self::store()->forever(
                self::cacheKey($section, $userId),
                $resolved->toIso8601String()
            );
        }
    }

    public static function sessionKeyFor(string $section, mixed $userId = null): string
    {
        return self::sessionKey($section, $userId);
    }

    public static function cacheKeyFor(string $section, mixed $userId = null): string
    {
        return self::cacheKey($section, $userId);
    }

    protected static function sessionKey(string $section, mixed $userId = null): string
    {
        $userSuffix = $userId !== null ? (string) $userId : 'guest';

        return "portal.indicators.{$section}.{$userSuffix}";
    }

    protected static function cacheKey(string $section, mixed $userId = null): string
    {
        $userSuffix = $userId !== null ? (string) $userId : 'guest';

        return "portal.indicator-cache.{$section}.{$userSuffix}";
    }

    protected static function parseTimestamp(mixed $value): ?Carbon
    {
        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    protected static function store()
    {
        try {
            if (Schema::hasTable('cache')) {
                return Cache::store('database');
            }
        } catch (\Throwable) {
            // Fall back to the configured store when the cache table is unavailable.
        }

        $store = app()->environment('testing') ? config('cache.default') : 'file';

        return Cache::store($store);
    }
}
