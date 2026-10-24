<?php

namespace App\Http\Controllers\Alumni;

use App\Http\Controllers\Controller;
use App\Notifications\AnnouncementPublishedNotification;
use App\Services\AnnouncementAudienceService;
use App\Services\AnnouncementReadTracker;
use App\Services\AlumniFeedService;
use App\Services\AlumniNotificationFeedService;
use App\Support\PortalIndicatorState;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AlumniNotificationController extends Controller
{
    public function index(
        Request $request,
        AlumniNotificationFeedService $activityFeedService
    )
    {
        $status = strtolower(trim((string) $request->query('status', 'all')));
        $search = trim((string) $request->query('search', ''));
        $from = $this->normalizeDateFilter($request->query('from'));
        $to = $this->normalizeDateFilter($request->query('to'));

        if (!in_array($status, ['all', 'unread', 'read'], true)) {
            $status = 'all';
        }

        $activityItems = new LengthAwarePaginator([], 0, 10, (int) $request->query('page', 1), [
            'path' => $request->url(),
            'pageName' => 'page',
        ]);
        $totalNotifications = 0;
        $unreadNotifications = 0;
        $readNotifications = 0;

        $mergedItems = $activityFeedService->activityItemsFor($request->user());
        $filteredItems = $this->filterActivityItems($mergedItems, $status, $search, $from, $to);
        $activityItems = $this->paginateActivityItems($filteredItems, $request);
        $totalNotifications = $mergedItems->count();
        $unreadNotifications = $mergedItems->where('is_unread', true)->count();
        $readNotifications = max(0, $totalNotifications - $unreadNotifications);

        PortalIndicatorState::markSeen(
            $request,
            PortalIndicatorState::ALUMNI_NOTIFICATIONS,
            $request->user()?->getAuthIdentifier()
        );

        return view('notifications', compact(
            'activityItems',
            'status',
            'search',
            'from',
            'to',
            'totalNotifications',
            'unreadNotifications',
            'readNotifications'
        ));
    }

    public function liveSummary(
        Request $request,
        AlumniNotificationFeedService $activityFeedService
    ) {
        $user = $request->user();
        $activityItems = $activityFeedService->activityItemsFor($user);
        $notificationsLastSeen = PortalIndicatorState::lastSeen(
            $request,
            PortalIndicatorState::ALUMNI_NOTIFICATIONS,
            $user?->getAuthIdentifier()
        );
        $latestItem = $activityItems->first();

        return response()->json([
            'total' => $activityItems->count(),
            'unread' => $activityItems->where('is_unread', true)->count(),
            'new' => $activityFeedService->indicatorCountSince($user, $notificationsLastSeen),
            'latest' => $latestItem ? [
                'title' => Str::limit((string) ($latestItem['title'] ?? 'Update'), 48),
                'summary' => Str::limit((string) ($latestItem['summary'] ?? 'There is a new update waiting in your portal.'), 110),
                'category' => (string) ($latestItem['category'] ?? 'Update'),
                'icon' => (string) ($latestItem['icon'] ?? 'bx-bell'),
                'url' => (string) ($latestItem['open_url'] ?? route('notifications.index')),
                'is_unread' => (bool) ($latestItem['is_unread'] ?? false),
                'time_label' => (string) ($latestItem['time_label'] ?? 'Just now'),
            ] : null,
            'refreshed_at' => now()->toIso8601String(),
        ]);
    }

    public function open(
        Request $request,
        string $notificationId,
        AnnouncementAudienceService $announcementAudienceService,
        AnnouncementReadTracker $announcementReadTracker
    )
    {
        $notification = $this->findNotification($request, $notificationId);

        abort_if($notification === null, 404);

        if (is_null($notification->read_at)) {
            $notification->markAsRead();
        }

        PortalIndicatorState::markSeen(
            $request,
            PortalIndicatorState::ALUMNI_NOTIFICATIONS,
            $request->user()?->getAuthIdentifier()
        );

        if (! $this->announcementNotificationTargetIsAvailable($notification, $request, $announcementAudienceService)) {
            return redirect()
                ->route('notifications.index')
                ->with('error', 'This announcement is no longer available.');
        }

        $this->markAnnouncementFromNotificationRead($request, $notification, $announcementReadTracker);

        return redirect()->to($this->resolveRedirectUrl(
            $request,
            data_get($notification->data, 'url')
        ));
    }

    public function markRead(Request $request, string $notificationId, AnnouncementReadTracker $announcementReadTracker)
    {
        $notification = $this->findNotification($request, $notificationId);

        abort_if($notification === null, 404);

        if (is_null($notification->read_at)) {
            $notification->markAsRead();
        }

        $this->markAnnouncementFromNotificationRead($request, $notification, $announcementReadTracker);

        PortalIndicatorState::markSeen(
            $request,
            PortalIndicatorState::ALUMNI_NOTIFICATIONS,
            $request->user()?->getAuthIdentifier()
        );

        return back()->with('success', 'Notification marked as read.');
    }

    public function markAllRead(
        Request $request,
        AlumniFeedService $feedService,
        AnnouncementReadTracker $announcementReadTracker
    )
    {
        if (Schema::hasTable('notifications')) {
            $request->user()?->unreadNotifications()->update(['read_at' => now()]);
        }

        $announcementReadTracker->markManyRead(
            $request->user(),
            $feedService->latestAnnouncements(null, $request->user())->pluck('id')->all()
        );

        PortalIndicatorState::markSeen(
            $request,
            PortalIndicatorState::ALUMNI_NOTIFICATIONS,
            $request->user()?->getAuthIdentifier()
        );

        return back()->with('success', 'Notifications marked as read.');
    }

    protected function findNotification(Request $request, string $notificationId): ?DatabaseNotification
    {
        if (!Schema::hasTable('notifications') || $request->user() === null) {
            return null;
        }

        return $request->user()
            ->notifications()
            ->whereKey($notificationId)
            ->first();
    }

    protected function markAnnouncementFromNotificationRead(
        Request $request,
        DatabaseNotification $notification,
        AnnouncementReadTracker $announcementReadTracker
    ): void {
        if ($notification->type !== AnnouncementPublishedNotification::class) {
            return;
        }

        $announcementId = trim((string) data_get($notification->data, 'announcement_id', ''));

        if ($announcementId !== '') {
            $announcementReadTracker->markRead($request->user(), $announcementId);
        }
    }

    protected function announcementNotificationTargetIsAvailable(
        DatabaseNotification $notification,
        Request $request,
        AnnouncementAudienceService $announcementAudienceService
    ): bool {
        if ($notification->type !== AnnouncementPublishedNotification::class) {
            return true;
        }

        $announcementId = trim((string) data_get($notification->data, 'announcement_id', ''));

        if ($announcementId === '' || ! Schema::hasTable('announcements')) {
            return false;
        }

        $announcement = DB::table('announcements')->where('id', $announcementId)->first();

        if ($announcement === null) {
            return false;
        }

        if (Schema::hasColumn('announcements', 'archived_at') && ! empty($announcement->archived_at)) {
            return false;
        }

        if (Schema::hasColumn('announcements', 'is_published') && ! (bool) ($announcement->is_published ?? false)) {
            return false;
        }

        if (Schema::hasColumn('announcements', 'expires_at') && ! empty($announcement->expires_at)) {
            try {
                if (Carbon::parse($announcement->expires_at)->isPast()) {
                    return false;
                }
            } catch (\Throwable) {
                return false;
            }
        }

        if (Schema::hasColumn('announcements', 'target_audience')
            && Schema::hasColumn('announcements', 'target_department')
            && Schema::hasColumn('announcements', 'target_graduation_year')
            && ! $announcementAudienceService->isVisibleToUser($announcement, $request->user())) {
            return false;
        }

        return true;
    }

    protected function resolveRedirectUrl(Request $request, mixed $targetUrl): string
    {
        if (!is_string($targetUrl) || trim($targetUrl) === '') {
            return route('notifications.index');
        }

        if (str_starts_with($targetUrl, '/')) {
            return $targetUrl;
        }

        $targetHost = parse_url($targetUrl, PHP_URL_HOST);
        $targetPath = parse_url($targetUrl, PHP_URL_PATH);
        $targetQuery = parse_url($targetUrl, PHP_URL_QUERY);

        if ($targetHost === null || $targetHost === $request->getHost()) {
            return $targetUrl;
        }

        if ($this->isInternalRedirectPath($targetPath)) {
            return $targetPath . ($targetQuery ? "?{$targetQuery}" : '');
        }

        return route('notifications.index');
    }

    protected function isInternalRedirectPath(mixed $path): bool
    {
        if (! is_string($path) || $path === '') {
            return false;
        }

        return str_starts_with($path, '/announcements/')
            || str_starts_with($path, '/jobs/')
            || $path === '/jobs'
            || $path === '/notifications'
            || str_starts_with($path, '/notifications/');
    }

    protected function filterActivityItems(Collection $items, string $status, string $search, ?string $from, ?string $to): Collection
    {
        $fromTimestamp = $from !== null ? Carbon::parse($from)->timestamp : null;
        $toTimestamp = $to !== null ? Carbon::parse($to)->endOfMinute()->timestamp : null;

        return (match ($status) {
            'unread' => $items->where('is_unread', true)->values(),
            'read' => $items->where('is_unread', false)->values(),
            default => $items->values(),
        })->filter(function (array $item) use ($search, $fromTimestamp, $toTimestamp): bool {
            $itemTimestamp = (int) ($item['sort_timestamp'] ?? 0);

            if ($fromTimestamp !== null && $itemTimestamp < $fromTimestamp) {
                return false;
            }

            if ($toTimestamp !== null && $itemTimestamp > $toTimestamp) {
                return false;
            }

            if ($search === '') {
                return true;
            }

            $searchableText = implode(' ', [
                (string) ($item['title'] ?? ''),
                (string) ($item['summary'] ?? ''),
                (string) ($item['category'] ?? ''),
                (string) ($item['region'] ?? ''),
                (string) ($item['priority'] ?? ''),
            ]);

            return str_contains(mb_strtolower($searchableText), mb_strtolower($search));
        })->values();
    }

    protected function normalizeDateFilter(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->format('Y-m-d H:i:s');
        } catch (\Throwable) {
            return null;
        }
    }

    protected function paginateActivityItems(Collection $items, Request $request, int $perPage = 10): LengthAwarePaginator
    {
        $page = max(1, (int) $request->query('page', 1));

        return (new LengthAwarePaginator(
            $items->forPage($page, $perPage)->values(),
            $items->count(),
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'pageName' => 'page',
            ]
        ))->appends($request->query());
    }
}
