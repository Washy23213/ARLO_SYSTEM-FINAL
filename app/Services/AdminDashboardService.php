<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AdminDashboardService
{
    public function summary(): array
    {
        $totalAlumniRecords = $this->alumniQuery()->count();
        $registeredAlumni = $this->registeredAlumniQuery()->count();
        $notRegisteredAlumni = $this->notRegisteredAlumniQuery()->count();
        $newRegisteredAlumniThisMonth = $this->registeredAlumniQuery()
            ->where('created_at', '>=', now()->startOfMonth())
            ->count();
        $activeJobs = $this->tableExists('jobs') ? $this->activeJobsQuery()->count() : 0;
        $publishedAnnouncements = $this->tableExists('announcements') ? $this->publishedAnnouncementsQuery()->count() : 0;

        return [
            'totalAlumni' => $totalAlumniRecords,
            'totalAlumniRecords' => $totalAlumniRecords,
            'registeredAlumni' => $registeredAlumni,
            'notRegisteredAlumni' => $notRegisteredAlumni,
            'totalJobs' => $this->tableExists('jobs') ? DB::table('jobs')->count() : 0,
            'activeJobs' => $activeJobs,
            'totalAnnouncements' => $this->tableExists('announcements') ? DB::table('announcements')->count() : 0,
            'publishedAnnouncements' => $publishedAnnouncements,
            'newAlumniThisMonth' => $newRegisteredAlumniThisMonth,
            'newRegisteredAlumniThisMonth' => $newRegisteredAlumniThisMonth,
            'preloadedAlumniThisMonth' => $this->preloadedAlumniThisMonth(),
            'activeUsersToday' => $this->activeUsersToday(),
            'jobsPostedThisWeek' => $this->tableExists('jobs')
                ? DB::table('jobs')->where('created_at', '>=', now()->subDays(7))->count()
                : 0,
            'announcementsThisMonth' => $this->tableExists('announcements')
                ? DB::table('announcements')->where('created_at', '>=', now()->startOfMonth())->count()
                : 0,
            'publishedAnnouncementsThisMonth' => $this->tableExists('announcements')
                ? $this->publishedAnnouncementsQuery()->where('created_at', '>=', now()->startOfMonth())->count()
                : 0,
            'recentRegistrations' => $this->registeredAlumniQuery()->latest()->take(5)->get(),
        ];
    }

    protected function alumniQuery()
    {
        $query = User::query();

        if (Schema::hasColumn('users', 'role')) {
            return $query->where('role', User::ROLE_ALUMNI);
        }

        if (Schema::hasColumn('users', 'is_admin')) {
            return $query->where(function ($query): void {
                $query->whereNull('is_admin')
                    ->orWhere('is_admin', false);
            });
        }

        return $query;
    }

    protected function registeredAlumniQuery()
    {
        $query = $this->alumniQuery();

        if (Schema::hasColumn('users', 'is_batch_imported')) {
            $query->where(function ($query): void {
                $query->whereNull('is_batch_imported')
                    ->orWhere('is_batch_imported', false);
            });
        }

        return $query;
    }

    protected function notRegisteredAlumniQuery()
    {
        $query = $this->alumniQuery();

        if (Schema::hasColumn('users', 'is_batch_imported')) {
            return $query->where('is_batch_imported', true);
        }

        return $query->whereRaw('1 = 0');
    }

    protected function preloadedAlumniThisMonth(): int
    {
        if (! Schema::hasColumn('users', 'is_batch_imported')) {
            return 0;
        }

        $query = $this->notRegisteredAlumniQuery();

        if (Schema::hasColumn('users', 'batch_imported_at')) {
            return $query->where('batch_imported_at', '>=', now()->startOfMonth())->count();
        }

        return $query->where('created_at', '>=', now()->startOfMonth())->count();
    }

    protected function activeUsersToday(): int
    {
        if (!$this->tableExists('sessions')) {
            return 0;
        }

        return DB::table('sessions')
            ->where('last_activity', '>=', now()->subDay()->timestamp)
            ->count();
    }

    protected function activeJobsQuery()
    {
        $query = DB::table('jobs');

        if (Schema::hasColumn('jobs', 'archived_at')) {
            $query->whereNull('archived_at');
        }

        if (Schema::hasColumn('jobs', 'is_active')) {
            $query->where('is_active', true);
        }

        return $query;
    }

    protected function publishedAnnouncementsQuery()
    {
        $query = DB::table('announcements');

        if (Schema::hasColumn('announcements', 'archived_at')) {
            $query->whereNull('archived_at');
        }

        if (Schema::hasColumn('announcements', 'is_published')) {
            $query->where('is_published', true);
        }

        return $query;
    }

    protected function tableExists(string $table): bool
    {
        return Schema::hasTable($table);
    }
}
