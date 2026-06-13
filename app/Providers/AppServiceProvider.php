<?php

namespace App\Providers;

use App\Models\User;
use App\Services\AlumniNotificationFeedService;
use App\Support\PortalIndicatorState;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        View::composer('admin.layout', function ($view) {
            $request = request();
            $userId = Auth::guard('super_admin')->id()
                ?? Auth::guard('admin')->id()
                ?? Auth::id();
            $activeUsers = 0;
            $jobsPosted = 0;
            $announcementsCount = 0;
            $auditLogCount = 0;
            $announcementIndicatorCount = 0;
            $pendingJobSubmissionsCount = 0;
            $feedbackIndicatorCount = 0;
            $auditIndicatorCount = 0;

            if (Schema::hasTable('sessions')) {
                $activeUsers = DB::table('sessions')
                    ->where('last_activity', '>=', now()->subMinutes(30)->timestamp)
                    ->count();
            }

            if (Schema::hasTable('jobs')) {
                $jobsPosted = DB::table('jobs')->count();
            }

            if (Schema::hasTable('job_submissions')
                && Schema::hasColumn('job_submissions', 'status')
                && Schema::hasColumn('job_submissions', 'user_id')
                && Schema::hasTable('users')
                && Schema::hasColumn('users', 'role')) {
                $pendingJobSubmissionsCount = DB::table('job_submissions as js')
                    ->join('users as u', 'u.id', '=', 'js.user_id')
                    ->where('u.role', User::ROLE_ALUMNI)
                    ->whereRaw('LOWER(status) = ?', ['pending'])
                    ->count();
            }

            if (Schema::hasTable('announcements')) {
                $announcementsCount = DB::table('announcements')->count();

                $announcementTimestampColumn = Schema::hasColumn('announcements', 'updated_at')
                    ? 'updated_at'
                    : 'created_at';
                $announcementLastSeen = PortalIndicatorState::lastSeen(
                    $request,
                    PortalIndicatorState::ADMIN_ANNOUNCEMENTS,
                    $userId
                );

                $announcementIndicatorCount = DB::table('announcements')
                    ->when(
                        Schema::hasColumn('announcements', 'archived_at'),
                        fn ($query) => $query->whereNull('archived_at')
                    )
                    ->when(
                        $announcementLastSeen,
                        fn ($query) => $query->where($announcementTimestampColumn, '>', $announcementLastSeen)
                    )
                    ->count();
            }

            if (Schema::hasTable('system_logs')) {
                $auditLogCount = DB::table('system_logs')
                    ->where('created_at', '>=', now()->subDays(7))
                    ->count();

                $auditLastSeen = PortalIndicatorState::lastSeen(
                    $request,
                    PortalIndicatorState::ADMIN_AUDIT_LOGS,
                    $userId
                );

                $auditIndicatorCount = DB::table('system_logs')
                    ->where('action', 'not like', 'auth.%')
                    ->when(
                        $auditLastSeen,
                        fn ($query) => $query->where('created_at', '>', $auditLastSeen)
                    )
                    ->count();
            }

            if (Schema::hasTable('feedback')) {
                $feedbackLastSeen = PortalIndicatorState::lastSeen(
                    $request,
                    PortalIndicatorState::ADMIN_FEEDBACK,
                    $userId
                );

                $feedbackIndicatorCount = DB::table('feedback')
                    ->whereIn('status', ['Open', 'In Progress'])
                    ->when(
                        $feedbackLastSeen,
                        fn ($query) => $query->where('updated_at', '>', $feedbackLastSeen)
                    )
                    ->count();
            }

            $view->with([
                'sidebarActiveUsers' => $activeUsers,
                'sidebarJobsPosted' => $jobsPosted,
                'sidebarPendingJobSubmissionsCount' => $pendingJobSubmissionsCount,
                'sidebarAnnouncements' => $announcementsCount,
                'sidebarAuditLogCount' => $auditLogCount,
                'sidebarAnnouncementIndicatorCount' => $announcementIndicatorCount,
                'sidebarFeedbackIndicatorCount' => $feedbackIndicatorCount,
                'sidebarAuditLogIndicatorCount' => $auditIndicatorCount,
            ]);
        });

        View::composer('components.layouts.dashboard', function ($view) {
            $request = request();
            $recentNotifications = collect();
            $unreadNotificationsCount = 0;
            $notificationIndicatorCount = 0;
            $reviewedJobSubmissionsCount = 0;

            if (Auth::check()) {
                $user = Auth::user();

                if (Schema::hasTable('notifications')) {
                    $recentNotifications = $user->notifications()->latest()->limit(5)->get();
                    $unreadNotificationsCount = $user->unreadNotifications()->count();
                }

                $notificationIndicatorCount = app(AlumniNotificationFeedService::class)
                    ->indicatorCountSince(
                        $user,
                        PortalIndicatorState::lastSeen(
                            $request,
                            PortalIndicatorState::ALUMNI_NOTIFICATIONS,
                            $user->getAuthIdentifier()
                        )
                    );

                if (Schema::hasTable('job_submissions') && Schema::hasColumn('job_submissions', 'status')) {
                    $jobSubmissionTimestampColumn = Schema::hasColumn('job_submissions', 'updated_at')
                        ? 'updated_at'
                        : (Schema::hasColumn('job_submissions', 'created_at') ? 'created_at' : null);
                    $jobsLastSeen = PortalIndicatorState::lastSeen(
                        $request,
                        PortalIndicatorState::ALUMNI_JOBS,
                        $user->getAuthIdentifier()
                    );

                    $reviewedJobSubmissionsCount = DB::table('job_submissions')
                        ->where('user_id', $user->getAuthIdentifier())
                        ->whereRaw('LOWER(status) IN (?, ?)', ['approved', 'rejected'])
                        ->when(
                            $jobsLastSeen && $jobSubmissionTimestampColumn,
                            fn ($query) => $query->where($jobSubmissionTimestampColumn, '>', $jobsLastSeen)
                        )
                        ->count();
                }
            }

            $view->with([
                'sidebarNotifications' => $recentNotifications,
                'sidebarUnreadNotificationsCount' => $unreadNotificationsCount,
                'sidebarNotificationIndicatorCount' => $notificationIndicatorCount,
                'sidebarAlumniReviewedJobSubmissionsCount' => $reviewedJobSubmissionsCount,
            ]);
        });
    }
}
