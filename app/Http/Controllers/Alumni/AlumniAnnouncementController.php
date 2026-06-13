<?php

namespace App\Http\Controllers\Alumni;

use App\Http\Controllers\Controller;
use App\Notifications\AnnouncementPublishedNotification;
use App\Services\AnnouncementAudienceService;
use App\Services\AnnouncementReadTracker;
use App\Services\FacebookPostUrlResolver;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AlumniAnnouncementController extends Controller
{
    public function index(
        Request $request,
        AnnouncementAudienceService $announcementAudienceService,
        FacebookPostUrlResolver $facebookPostUrlResolver,
        AnnouncementReadTracker $announcementReadTracker
    ) {
        $search = trim((string) $request->query('search', ''));
        $selectedType = trim((string) $request->query('type', ''));
        $selectedPriority = trim((string) $request->query('priority', ''));

        if (! Schema::hasTable('announcements')) {
            $announcements = new LengthAwarePaginator([], 0, 12, (int) $request->query('page', 1), [
                'path' => $request->url(),
                'pageName' => 'page',
            ]);

            return view('announcements', [
                'announcements' => $announcements,
                'featuredAnnouncement' => null,
                'announcementCount' => 0,
                'facebookSyncedCount' => 0,
                'highPriorityCount' => 0,
                'search' => $search,
                'selectedType' => $selectedType,
                'selectedPriority' => $selectedPriority,
                'typeOptions' => collect(),
                'priorityOptions' => collect(),
            ]);
        }

        $announcementColumns = ['id', 'title', 'content', 'created_at'];

        foreach ([
            'type',
            'priority',
            'views_count',
            'published_at',
            'expires_at',
            'event_date',
            'event_location',
            'facebook_post_id',
            'facebook_post_url',
        ] as $column) {
            if (Schema::hasColumn('announcements', $column)) {
                $announcementColumns[] = $column;
            }
        }

        $baseQuery = DB::table('announcements')->select(array_values(array_unique($announcementColumns)));
        $this->applyVisibleAnnouncementScope($baseQuery, $announcementAudienceService, $request->user());

        if ($search !== '') {
            $searchTerm = '%'.mb_strtolower($search).'%';

            $baseQuery->where(function ($query) use ($searchTerm) {
                $query->whereRaw("LOWER(COALESCE(title, '')) LIKE ?", [$searchTerm])
                    ->orWhereRaw("LOWER(COALESCE(content, '')) LIKE ?", [$searchTerm]);

                if (Schema::hasColumn('announcements', 'type')) {
                    $query->orWhereRaw("LOWER(COALESCE(type, '')) LIKE ?", [$searchTerm]);
                }
            });
        }

        if ($selectedType !== '' && Schema::hasColumn('announcements', 'type')) {
            $baseQuery->where('type', $selectedType);
        }

        if ($selectedPriority !== '' && Schema::hasColumn('announcements', 'priority')) {
            $baseQuery->where('priority', $selectedPriority);
        }

        $announcementCount = (clone $baseQuery)->count();
        $facebookSyncedCount = Schema::hasColumn('announcements', 'facebook_post_id')
            ? (clone $baseQuery)->whereNotNull('facebook_post_id')->count()
            : 0;
        $highPriorityCount = Schema::hasColumn('announcements', 'priority')
            ? (clone $baseQuery)->whereIn('priority', ['High', 'Urgent'])->count()
            : 0;
        $typeOptions = Schema::hasColumn('announcements', 'type')
            ? (clone $baseQuery)
                ->whereNotNull('type')
                ->whereRaw("TRIM(type) <> ''")
                ->distinct()
                ->orderBy('type')
                ->pluck('type')
            : collect();
        $priorityOptions = Schema::hasColumn('announcements', 'priority')
            ? (clone $baseQuery)
                ->whereNotNull('priority')
                ->whereRaw("TRIM(priority) <> ''")
                ->distinct()
                ->orderBy('priority')
                ->pluck('priority')
            : collect();

        if (Schema::hasColumn('announcements', 'published_at')) {
            $baseQuery->orderByDesc('published_at');
        }

        $announcements = $baseQuery
            ->orderByDesc('created_at')
            ->paginate(12)
            ->withQueryString();
        $readMap = $announcementReadTracker->readMapFor(
            $request->user(),
            $announcements->getCollection()->pluck('id')->all()
        );
        $announcements->getCollection()->transform(function ($announcement) use ($facebookPostUrlResolver, $announcementReadTracker, $readMap) {
            $announcement->facebook_open_url = $facebookPostUrlResolver->resolve(
                $announcement->facebook_post_url ?? null,
                $announcement->facebook_post_id ?? null
            );

            return $announcementReadTracker->decorate($announcement, $readMap);
        });

        return view('announcements', [
            'announcements' => $announcements,
            'featuredAnnouncement' => $announcements->first(),
            'announcementCount' => $announcementCount,
            'facebookSyncedCount' => $facebookSyncedCount,
            'highPriorityCount' => $highPriorityCount,
            'search' => $search,
            'selectedType' => $selectedType,
            'selectedPriority' => $selectedPriority,
            'typeOptions' => $typeOptions,
            'priorityOptions' => $priorityOptions,
        ]);
    }

    public function announcementDetail(
        $id,
        AnnouncementAudienceService $announcementAudienceService,
        FacebookPostUrlResolver $facebookPostUrlResolver,
        AnnouncementReadTracker $announcementReadTracker
    ) {
        $row = DB::table('announcements')->where('id', $id)->first();
        if (! $row) {
            abort(404, 'Announcement not found');
        }
        if (Schema::hasColumn('announcements', 'archived_at') && ! empty($row->archived_at)) {
            abort(404, 'Announcement not available');
        }
        if (Schema::hasColumn('announcements', 'is_published') && ! (bool) ($row->is_published ?? false)) {
            abort(404, 'Announcement not available');
        }
        if (Schema::hasColumn('announcements', 'expires_at') && ! empty($row->expires_at) && \Carbon\Carbon::parse($row->expires_at)->isPast()) {
            abort(404, 'Announcement expired');
        }
        if (Schema::hasColumn('announcements', 'target_audience')
            && Schema::hasColumn('announcements', 'target_department')
            && Schema::hasColumn('announcements', 'target_graduation_year')
            && ! $announcementAudienceService->isVisibleToUser($row, auth()->user())) {
            abort(404, 'Announcement not available');
        }

        if (Schema::hasColumn('announcements', 'views_count')) {
            DB::table('announcements')
                ->where('id', $id)
                ->update([
                    'views_count' => DB::raw('COALESCE(views_count, 0) + 1'),
                ]);

            $row = DB::table('announcements')->where('id', $id)->first();
        }

        $announcementReadTracker->markRead(auth()->user(), (string) $id);
        $readMap = $announcementReadTracker->readMapFor(auth()->user(), [$id]);
        $readAt = $readMap[(string) $id] ?? now();

        $normalizedType = strtolower((string) ($row->type ?? 'announcement'));
        $announcementType = $normalizedType === 'job' ? 'job' : ($normalizedType === 'event' ? 'event' : 'announcement');

        $details = [];
        $details[] = 'Type: '.($row->type ?: 'Announcement');
        $details[] = 'Priority: '.($row->priority ?: 'Medium');
        if (! empty($row->target_audience)) {
            $details[] = 'Audience: '.$row->target_audience;
        }
        if (! empty($row->event_date)) {
            $details[] = 'Event Date: '.\Carbon\Carbon::parse($row->event_date)->format('F j, Y g:i A');
        }
        if (! empty($row->event_location)) {
            $details[] = 'Location: '.$row->event_location;
        }

        $storedReferenceUrl = trim((string) ($row->facebook_post_url ?? $row->attachment_url ?? ''));
        $facebookOpenUrl = $facebookPostUrlResolver->resolve(
            $row->facebook_post_url ?? null,
            $row->facebook_post_id ?? null
        );
        $referenceUrl = $facebookOpenUrl ?: $storedReferenceUrl;

        $announcement = [
            'id' => $row->id,
            'title' => $row->title,
            'company' => 'CMU OARL',
            'type' => $announcementType,
            'posted' => optional($row->created_at ? \Carbon\Carbon::parse($row->created_at) : null)->diffForHumans() ?? 'N/A',
            'content' => $row->content ?: 'No content provided.',
            'details' => $details,
            'contact_info' => $referenceUrl !== ''
                ? 'This announcement is linked to the official Facebook Page post.'
                : 'For inquiries, contact the system administrator.',
            'deadline' => ! empty($row->expires_at) ? \Carbon\Carbon::parse($row->expires_at)->format('F j, Y') : null,
            'views_count' => (int) ($row->views_count ?? 0),
            'reference_url' => $referenceUrl !== '' ? $referenceUrl : null,
            'stored_reference_url' => $storedReferenceUrl !== '' ? $storedReferenceUrl : null,
            'is_facebook_post' => ! empty($row->facebook_post_url),
            'is_read' => true,
            'read_at' => $readAt,
        ];

        if (auth()->check() && Schema::hasTable('notifications')) {
            auth()->user()
                ->unreadNotifications
                ->filter(function ($notification) use ($id) {
                    return $notification->type === AnnouncementPublishedNotification::class
                        && (string) ($notification->data['announcement_id'] ?? '') === (string) $id;
                })
                ->each
                ->markAsRead();
        }

        return view('announcement-detail', compact('announcement'));
    }

    protected function applyVisibleAnnouncementScope($query, AnnouncementAudienceService $announcementAudienceService, mixed $user): void
    {
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

        if (Schema::hasColumn('announcements', 'target_audience')
            && Schema::hasColumn('announcements', 'target_department')
            && Schema::hasColumn('announcements', 'target_graduation_year')) {
            $announcementAudienceService->applyVisibilityScope($query, $user);
        }
    }
}
