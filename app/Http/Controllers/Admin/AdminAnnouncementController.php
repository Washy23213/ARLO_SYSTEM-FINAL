<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\AdminControllerSupport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveAnnouncementRequest;
use App\Models\User;
use App\Services\AnnouncementNotificationService;
use App\Services\AnnouncementReadTracker;
use App\Services\AuditLogService;
use App\Services\FacebookPageAnnouncementSynchronizer;
use App\Services\FacebookPagePublisher;
use App\Services\FacebookPostUrlResolver;
use App\Support\PortalIndicatorState;
use App\Support\SystemOptions;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AdminAnnouncementController extends Controller
{
    use AdminControllerSupport;

    public function __construct(
        protected AnnouncementNotificationService $announcementNotificationService,
        protected AnnouncementReadTracker $announcementReadTracker,
        protected FacebookPageAnnouncementSynchronizer $facebookPageAnnouncementSynchronizer,
        protected FacebookPagePublisher $facebookPagePublisher,
        protected FacebookPostUrlResolver $facebookPostUrlResolver
    ) {}

    public function announcementManagement(Request $request)
    {
        $this->ensureAdmin();
        PortalIndicatorState::markSeen(
            $request,
            PortalIndicatorState::ADMIN_ANNOUNCEMENTS,
            $request->user()?->getAuthIdentifier()
        );

        $search = trim((string) $request->query('search', ''));
        $selectedType = trim((string) $request->query('type', ''));
        $selectedStatus = strtolower(trim((string) $request->query('status', '')));
        $selectedPriority = trim((string) $request->query('priority', ''));
        $from = $this->normalizeDateFilter($request->query('from'));
        $to = $this->normalizeDateFilter($request->query('to'));
        $selectedStatus = in_array($selectedStatus, ['published', 'draft'], true) ? $selectedStatus : '';

        if ($this->tableExists('announcements')) {
            $announcementColumns = ['id', 'title', 'content', 'type', 'priority', 'created_at'];
            if ($this->columnExists('announcements', 'views_count')) {
                $announcementColumns[] = 'views_count';
            }
            if ($this->columnExists('announcements', 'is_published')) {
                $announcementColumns[] = 'is_published';
            }
            if ($this->columnExists('announcements', 'archived_at')) {
                $announcementColumns[] = 'archived_at';
            }
            if ($this->columnExists('announcements', 'published_at')) {
                $announcementColumns[] = 'published_at';
            }
            foreach (['event_location', 'target_audience', 'target_department'] as $optionalColumn) {
                if ($this->columnExists('announcements', $optionalColumn)) {
                    $announcementColumns[] = $optionalColumn;
                }
            }
            if ($this->columnExists('announcements', 'facebook_post_url')) {
                $announcementColumns[] = 'facebook_post_url';
            }
            if ($this->columnExists('announcements', 'facebook_post_id')) {
                $announcementColumns[] = 'facebook_post_id';
            }

            $query = DB::table('announcements')->select($announcementColumns);

            if ($search !== '') {
                $this->applyCaseInsensitiveSearch(
                    $query,
                    $search,
                    ['title', 'content', 'type']
                );
            }

            if ($selectedType !== '') {
                $query->where('type', $selectedType);
            }

            if ($selectedPriority !== '') {
                $query->where('priority', $selectedPriority);
            }

            $timestampExpression = $this->columnExists('announcements', 'published_at')
                ? 'COALESCE(published_at, created_at)'
                : 'created_at';
            if ($from !== null) {
                $query->whereRaw($timestampExpression.' >= ?', [$from]);
            }
            if ($to !== null) {
                $query->whereRaw($timestampExpression.' <= ?', [$to]);
            }

            if ($this->columnExists('announcements', 'archived_at')) {
                $query->whereNull('archived_at');
            }

            if ($this->columnExists('announcements', 'is_published')) {
                if ($selectedStatus === 'published') {
                    $query->where('is_published', true);
                } elseif ($selectedStatus === 'draft') {
                    $query->where('is_published', false);
                }
            }

            $announcements = $query->orderByDesc('created_at')->paginate(20)->withQueryString();
            $readMap = [];
            $announcements->getCollection()->transform(
                fn ($announcement) => $this->announcementReadTracker->decorate(
                    $this->withFacebookOpenUrl($announcement),
                    $readMap
                )
            );

            $hasAnnouncementArchive = $this->columnExists('announcements', 'archived_at');
            $hasAnnouncementPublished = $this->columnExists('announcements', 'is_published');
            $hasAnnouncementExpiry = $this->columnExists('announcements', 'expires_at');

            $notificationRecordsCount = DB::table('announcements')->count();
            $activeAnnouncements = DB::table('announcements')
                ->when($hasAnnouncementArchive, fn ($query) => $query->whereNull('archived_at'))
                ->when($hasAnnouncementPublished, fn ($query) => $query->where('is_published', true))
                ->when($hasAnnouncementExpiry, function ($query) {
                    $query->where(function ($builder) {
                        $builder->whereNull('expires_at')
                            ->orWhere('expires_at', '>', now());
                    });
                })
                ->count();
            $priorityNoticeCount = $this->columnExists('announcements', 'priority')
                ? DB::table('announcements')
                    ->when($hasAnnouncementArchive, fn ($query) => $query->whereNull('archived_at'))
                    ->when($hasAnnouncementPublished, fn ($query) => $query->where('is_published', true))
                    ->when($hasAnnouncementExpiry, function ($query) {
                        $query->where(function ($builder) {
                            $builder->whereNull('expires_at')
                                ->orWhere('expires_at', '>', now());
                        });
                    })
                    ->whereIn('priority', ['High', 'Urgent'])
                    ->count()
                : 0;
            $facebookSyncedCount = $this->columnExists('announcements', 'facebook_post_id')
                ? DB::table('announcements')
                    ->when($hasAnnouncementArchive, fn ($query) => $query->whereNull('archived_at'))
                    ->whereNotNull('facebook_post_id')
                    ->whereRaw("TRIM(facebook_post_id) <> ''")
                    ->count()
                : 0;

            $typeOptions = DB::table('announcements')
                ->when($this->columnExists('announcements', 'archived_at'), fn ($query) => $query->whereNull('archived_at'))
                ->whereNotNull('type')
                ->whereRaw("TRIM(type) <> ''")
                ->distinct()
                ->orderBy('type')
                ->pluck('type');
            $priorityOptions = DB::table('announcements')
                ->when($this->columnExists('announcements', 'archived_at'), fn ($query) => $query->whereNull('archived_at'))
                ->whereNotNull('priority')
                ->whereRaw("TRIM(priority) <> ''")
                ->distinct()
                ->orderBy('priority')
                ->pluck('priority');
        } else {
            $announcements = new LengthAwarePaginator([], 0, 20, request()->get('page', 1), [
                'path' => request()->url(),
                'pageName' => 'page',
            ]);
            $notificationRecordsCount = 0;
            $activeAnnouncements = 0;
            $priorityNoticeCount = 0;
            $facebookSyncedCount = 0;
            $typeOptions = collect();
            $priorityOptions = collect();
        }

        return view('admin.announcement-management', array_merge(compact(
            'announcements',
            'notificationRecordsCount',
            'activeAnnouncements',
            'priorityNoticeCount',
            'facebookSyncedCount',
            'search',
            'selectedType',
            'selectedStatus',
            'selectedPriority',
            'from',
            'to',
            'typeOptions',
            'priorityOptions'
        ), $this->targetingOptions()));
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

    public function announcementCreate()
    {
        $this->ensureAdmin();
        PortalIndicatorState::markSeen(
            request(),
            PortalIndicatorState::ADMIN_ANNOUNCEMENTS,
            request()->user()?->getAuthIdentifier()
        );

        return view('admin.announcement-create', $this->targetingOptions());
    }

    public function announcementStore(SaveAnnouncementRequest $request)
    {
        $this->ensureAdmin();

        $validated = $request->validated();

        $finalEventLocation = ($validated['event_location'] ?? '') === 'Others'
            ? ($validated['specific_event_location'] ?? null)
            : ($validated['event_location'] ?? null);

        $data = [
            'id' => (string) Str::uuid(),
            'title' => SystemOptions::clean($validated['title']),
            'content' => SystemOptions::clean($validated['content']),
        ];

        if ($this->columnExists('announcements', 'type')) {
            $data['type'] = SystemOptions::canonical($validated['type'], SystemOptions::announcementTypes());
        }
        if ($this->columnExists('announcements', 'priority')) {
            $data['priority'] = SystemOptions::canonical($validated['priority'], SystemOptions::announcementPriorities());
        }
        if ($this->columnExists('announcements', 'target_audience')) {
            $data['target_audience'] = SystemOptions::canonical($validated['target_audience'] ?? 'All', SystemOptions::audiences());
        }
        if ($this->columnExists('announcements', 'target_department')) {
            $data['target_department'] = SystemOptions::clean($validated['target_department'] ?? null);
        }
        if ($this->columnExists('announcements', 'target_graduation_year')) {
            $data['target_graduation_year'] = $this->normalizeTargetGraduationYear($validated['target_graduation_year'] ?? null);
        }
        if ($this->columnExists('announcements', 'event_date')) {
            $data['event_date'] = $validated['event_date'] ?? null;
        }
        if ($this->columnExists('announcements', 'event_location')) {
            $data['event_location'] = SystemOptions::canonical($finalEventLocation, array_merge(['Main Campus', 'Online'], SystemOptions::locations()));
        }
        if ($this->columnExists('announcements', 'expires_at')) {
            $data['expires_at'] = $validated['expires_at'] ?? null;
        }
        if ($this->columnExists('announcements', 'is_published')) {
            $published = (bool) ($validated['is_published'] ?? false);
            $data['is_published'] = $published;
            if ($published && $this->columnExists('announcements', 'published_at')) {
                $data['published_at'] = now();
            }
        }
        if ($this->columnExists('announcements', 'views_count')) {
            $data['views_count'] = 0;
        }
        if ($this->columnExists('announcements', 'created_by')) {
            $data['created_by'] = $this->adminOwnedActorId();
        }
        if ($this->columnExists('announcements', 'created_at')) {
            $data['created_at'] = now();
        }
        if ($this->columnExists('announcements', 'updated_at')) {
            $data['updated_at'] = now();
        }

        DB::table('announcements')->insert($data);

        $publishedNow = $this->announcementNotificationService->shouldNotifyOnPublication(null, $data);
        $notifiedCount = $publishedNow
            ? $this->announcementNotificationService->notifyRecipients((object) $data)
            : 0;
        $facebookPublish = ! empty($data['is_published'])
            ? $this->facebookPagePublisher->publishAnnouncement((object) $data)
            : ['attempted' => false, 'message' => null];
        $this->persistFacebookSyncMetadata((string) $data['id'], $facebookPublish);

        app(AuditLogService::class)->log(
            (string) auth()->id(),
            'admin.announcements.create',
            "Created announcement {$data['title']} ({$data['id']}).",
            $request
        );

        $message = 'Announcement created successfully.';
        if ($notifiedCount > 0) {
            $message .= " Notified {$notifiedCount} alumni.";
        }
        if (! empty($facebookPublish['attempted']) && ! empty($facebookPublish['message'])) {
            $message .= ' '.$facebookPublish['message'];
        }

        return redirect()->route('admin.announcements')->with('success', $message);
    }

    public function announcementShow($id)
    {
        $this->ensureAdmin();
        PortalIndicatorState::markSeen(
            request(),
            PortalIndicatorState::ADMIN_ANNOUNCEMENTS,
            request()->user()?->getAuthIdentifier()
        );
        $announcement = DB::table('announcements')->where('id', $id)->first();
        if (! $announcement) {
            abort(404, 'Announcement not found');
        }

        $readMap = [];
        $announcement = $this->announcementReadTracker->decorate(
            $this->withFacebookOpenUrl($announcement),
            $readMap
        );

        return view('admin.announcement-show', compact('announcement'));
    }

    public function announcementEdit($id)
    {
        $this->ensureAdmin();
        PortalIndicatorState::markSeen(
            request(),
            PortalIndicatorState::ADMIN_ANNOUNCEMENTS,
            request()->user()?->getAuthIdentifier()
        );
        $announcement = DB::table('announcements')->where('id', $id)->first();
        if (! $announcement) {
            abort(404, 'Announcement not found');
        }

        return view('admin.announcement-edit', array_merge(
            ['announcement' => $announcement],
            $this->targetingOptions()
        ));
    }

    public function announcementUpdate(SaveAnnouncementRequest $request, $id)
    {
        $this->ensureAdmin();
        $existing = DB::table('announcements')->where('id', $id)->first();
        if (! $existing) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Announcement not found.',
                ], 404);
            }

            return redirect()->route('admin.announcements')->with('error', 'Announcement not found.');
        }

        $validated = $request->validated();

        $finalEventLocation = ($validated['event_location'] ?? '') === 'Others'
            ? ($validated['specific_event_location'] ?? null)
            : ($validated['event_location'] ?? null);

        $data = [
            'title' => SystemOptions::clean($validated['title']),
            'content' => SystemOptions::clean($validated['content']),
        ];

        if ($this->columnExists('announcements', 'type')) {
            $data['type'] = SystemOptions::canonical($validated['type'], SystemOptions::announcementTypes());
        }
        if ($this->columnExists('announcements', 'priority')) {
            $data['priority'] = SystemOptions::canonical($validated['priority'], SystemOptions::announcementPriorities());
        }
        if ($this->columnExists('announcements', 'target_audience')) {
            $data['target_audience'] = SystemOptions::canonical($validated['target_audience'] ?? 'All', SystemOptions::audiences());
        }
        if ($this->columnExists('announcements', 'target_department')) {
            $data['target_department'] = SystemOptions::clean($validated['target_department'] ?? null);
        }
        if ($this->columnExists('announcements', 'target_graduation_year')) {
            $data['target_graduation_year'] = $this->normalizeTargetGraduationYear($validated['target_graduation_year'] ?? null);
        }
        if ($this->columnExists('announcements', 'event_date')) {
            $data['event_date'] = $validated['event_date'] ?? null;
        }
        if ($this->columnExists('announcements', 'event_location')) {
            $data['event_location'] = SystemOptions::canonical($finalEventLocation, array_merge(['Main Campus', 'Online'], SystemOptions::locations()));
        }
        if ($this->columnExists('announcements', 'expires_at')) {
            $data['expires_at'] = $validated['expires_at'] ?? null;
        }
        if ($this->columnExists('announcements', 'is_published')) {
            $published = (bool) ($validated['is_published'] ?? false);
            $data['is_published'] = $published;
            if ($this->columnExists('announcements', 'published_at')) {
                $data['published_at'] = $published ? ($existing->published_at ?? now()) : null;
            }
        }
        if ($this->columnExists('announcements', 'updated_at')) {
            $data['updated_at'] = now();
        }

        DB::table('announcements')->where('id', $id)->update($data);

        $announcement = (object) array_merge((array) $existing, $data, ['id' => $id]);
        $publishedNow = $this->announcementNotificationService->shouldNotifyOnPublication($existing, $data);
        $notifiedCount = $publishedNow
            ? $this->announcementNotificationService->notifyRecipients($announcement)
            : 0;
        $facebookPublish = ! empty($announcement->is_published)
            ? $this->facebookPagePublisher->publishAnnouncement($announcement)
            : ['attempted' => false, 'message' => null];
        $this->persistFacebookSyncMetadata((string) $id, $facebookPublish);

        app(AuditLogService::class)->log(
            (string) auth()->id(),
            'admin.announcements.update',
            "Updated announcement {$data['title']} ({$id}).",
            $request
        );

        $message = 'Announcement updated successfully.';
        if ($notifiedCount > 0) {
            $message .= " Notified {$notifiedCount} alumni.";
        }
        if (! empty($facebookPublish['attempted']) && ! empty($facebookPublish['message'])) {
            $message .= ' '.$facebookPublish['message'];
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
            ]);
        }

        return redirect()->route('admin.announcements')->with('success', $message);
    }

    public function announcementDelete($id)
    {
        $this->ensureAdmin();
        $existing = DB::table('announcements')->where('id', $id)->first();
        if (! $existing) {
            return redirect()->route('admin.announcements')->with('error', 'Announcement not found.');
        }

        DB::transaction(function () use ($id): void {
            if ($this->tableExists('announcement_reads')) {
                DB::table('announcement_reads')->where('announcement_id', $id)->delete();
            }

            if ($this->tableExists('notifications') && $this->columnExists('notifications', 'data')) {
                DB::table('notifications')
                    ->where('type', 'App\\Notifications\\AnnouncementPublishedNotification')
                    ->where('data', 'like', "%{$id}%")
                    ->delete();
            }

            DB::table('announcements')->where('id', $id)->delete();
        });

        app(AuditLogService::class)->log(
            (string) auth()->id(),
            'admin.announcements.delete',
            "Deleted announcement ({$id}).",
            request()
        );

        return redirect()->route('admin.announcements')->with('success', 'Announcement deleted successfully.');
    }

    public function announcementSyncFacebookPage(Request $request)
    {
        $this->ensureAdmin();

        $limit = max((int) $request->input('limit', 100), 1);
        $result = $this->facebookPageAnnouncementSynchronizer->syncRecentPosts($limit);

        app(AuditLogService::class)->log(
            (string) auth()->id(),
            'admin.announcements.facebook-sync',
            "Synced {$limit} Facebook page posts into announcements. Imported {$result['imported']} and updated {$result['updated']}.",
            $request
        );

        return redirect()
            ->route('admin.announcements')
            ->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    protected function targetingOptions(): array
    {
            return [
                'targetDepartmentOptions' => User::query()
                    ->alumni()
                    ->whereNotNull('department')
                    ->whereRaw("TRIM(department) <> ''")
                    ->distinct()
                ->orderBy('department')
                ->pluck('department')
                ->values()
                ->all(),
                'targetGraduationYearOptions' => User::query()
                    ->alumni()
                    ->whereNotNull('year_graduated')
                    ->distinct()
                ->orderByDesc('year_graduated')
                ->pluck('year_graduated')
                ->map(fn ($year) => (int) $year)
                ->values()
                ->all(),
        ];
    }

    protected function normalizeTargetGraduationYear(mixed $year): ?int
    {
        if (! is_scalar($year) || trim((string) $year) === '') {
            return null;
        }

        return preg_match('/^\d{4}$/', (string) $year) ? (int) $year : null;
    }

    protected function persistFacebookSyncMetadata(string $announcementId, array $facebookPublish): void
    {
        $postId = trim((string) ($facebookPublish['post_id'] ?? ''));

        if ($postId === '') {
            return;
        }

        $payload = [];

        if ($this->columnExists('announcements', 'facebook_post_id')) {
            $payload['facebook_post_id'] = $postId;
        }

        $postUrl = trim((string) ($facebookPublish['post_url'] ?? ''));

        if ($postUrl !== '' && $this->columnExists('announcements', 'facebook_post_url')) {
            $payload['facebook_post_url'] = $postUrl;
        }

        if ($this->columnExists('announcements', 'facebook_last_synced_at')) {
            $payload['facebook_last_synced_at'] = now();
        }

        if ($payload === []) {
            return;
        }

        DB::table('announcements')
            ->where('id', $announcementId)
            ->update($payload);
    }

    protected function withFacebookOpenUrl(object $announcement): object
    {
        $announcement->facebook_open_url = $this->facebookPostUrlResolver->resolve(
            $announcement->facebook_post_url ?? null,
            $announcement->facebook_post_id ?? null
        );

        return $announcement;
    }
}
