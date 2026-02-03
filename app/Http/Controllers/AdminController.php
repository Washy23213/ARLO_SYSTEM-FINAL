<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class AdminController extends Controller
{
    public function index()
    {
        // Admin dashboard statistics
        $totalAlumni = User::count();
        $totalJobs = 3; // Placeholder - would come from jobs table
        $totalAnnouncements = 5; // Placeholder - would come from announcements table
        $recentRegistrations = User::latest()->take(5)->get();

        // Analytics data - same as alumni dashboard
        $registrationTrends = [
            'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
            'data' => [45, 52, 38, 61, 55, 67]
        ];

        $alumniByCollege = [
            'labels' => ['Engineering', 'Business', 'Arts & Sciences', 'Education', 'Nursing'],
            'data' => [245, 189, 156, 98, 87]
        ];

        $employmentStatus = [
            'labels' => ['Employed', 'Self-Employed', 'Unemployed', 'Student'],
            'data' => [580, 120, 95, 45]
        ];

        // Employment trends data (same as alumni dashboard)
        $employmentTrends = [
            'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
            'employed' => [450, 480, 520, 550, 580, 600],
            'selfEmployed' => [80, 85, 90, 95, 110, 120],
            'unemployed' => [120, 110, 95, 85, 75, 70]
        ];

        // Traced graduates data
        $tracedGraduates = [
            'labels' => ['2019', '2020', '2021', '2022', '2023', '2024'],
            'data' => [120, 145, 180, 210, 245, 280]
        ];

        // Industry distribution data
        $industryData = [
            'labels' => ['Technology', 'Healthcare', 'Education', 'Finance', 'Manufacturing', 'Others'],
            'data' => [280, 180, 120, 95, 85, 70]
        ];

        // College trends data
        $collegeTrends = [
            'labels' => ['Engineering', 'Business', 'Arts & Sciences', 'Education', 'Nursing'],
            'employed' => [180, 140, 110, 75, 65],
            'selfEmployed' => [35, 28, 22, 15, 12],
            'unemployed' => [25, 20, 18, 12, 10]
        ];

        return view('admin.dashboard', compact(
            'totalAlumni',
            'totalJobs',
            'totalAnnouncements',
            'recentRegistrations',
            'registrationTrends',
            'alumniByCollege',
            'employmentStatus',
            'employmentTrends',
            'tracedGraduates',
            'industryData',
            'collegeTrends'
        ));
    }

    public function alumniManagement()
    {
        $alumni = User::paginate(20);
        return view('admin.alumni-management', compact('alumni'));
    }

    public function jobManagement()
    {
        // For now, using dummy data with pagination structure
        $jobs = collect([
            (object)[
                'id' => 1,
                'title' => 'Software Engineer',
                'company' => 'Tech Corp',
                'type' => 'Full-time',
                'location' => 'Manila',
                'created_at' => now()->subDays(5),
                'applications' => 12
            ],
            (object)[
                'id' => 2,
                'title' => 'Marketing Specialist',
                'company' => 'Marketing Inc',
                'type' => 'Full-time',
                'location' => 'Cebu',
                'created_at' => now()->subDays(7),
                'applications' => 8
            ],
            (object)[
                'id' => 3,
                'title' => 'Data Analyst',
                'company' => 'Data Solutions',
                'type' => 'Full-time',
                'location' => 'Davao',
                'created_at' => now()->subDays(3),
                'applications' => 15
            ]
        ]);

        // Create a simple paginator
        $perPage = 20;
        $currentPage = request()->get('page', 1);
        $jobs = new \Illuminate\Pagination\LengthAwarePaginator(
            $jobs->forPage($currentPage, $perPage),
            $jobs->count(),
            $perPage,
            $currentPage,
            ['path' => request()->url(), 'pageName' => 'page']
        );

        return view('admin.job-management', compact('jobs'));
    }

    public function announcementManagement()
    {
        // For now, using dummy data with pagination structure
        $announcements = collect([
            (object)[
                'id' => 1,
                'title' => 'Software Engineer Position Available',
                'type' => 'Job',
                'priority' => 'High',
                'created_at' => now()->subDays(5),
                'views' => 45
            ],
            (object)[
                'id' => 2,
                'title' => 'Alumni Networking Event This Weekend',
                'type' => 'Event',
                'priority' => 'Medium',
                'created_at' => now()->subDays(7),
                'views' => 78
            ],
            (object)[
                'id' => 3,
                'title' => 'Data Analyst Opening at Tech Corp',
                'type' => 'Job',
                'priority' => 'High',
                'created_at' => now()->subDays(3),
                'views' => 32
            ],
            (object)[
                'id' => 4,
                'title' => 'CMU Career Fair 2026 Registration Open',
                'type' => 'Event',
                'priority' => 'High',
                'created_at' => now()->subDays(9),
                'views' => 156
            ],
            (object)[
                'id' => 5,
                'title' => 'New Scholarship Opportunities Available',
                'type' => 'Announcement',
                'priority' => 'Medium',
                'created_at' => now()->subDays(10),
                'views' => 89
            ]
        ]);

        // Create a simple paginator
        $perPage = 20;
        $currentPage = request()->get('page', 1);
        $announcements = new \Illuminate\Pagination\LengthAwarePaginator(
            $announcements->forPage($currentPage, $perPage),
            $announcements->count(),
            $perPage,
            $currentPage,
            ['path' => request()->url(), 'pageName' => 'page']
        );

        return view('admin.announcement-management', compact('announcements'));
    }

    public function analytics()
    {
        // Get actual user count
        $totalUsers = User::count();

        // Comprehensive analytics data
        $userGrowth = [
            'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            'registrations' => [45, 52, 38, 61, 55, 67, 72, 58, 63, 71, 69, 74],
            'active_users' => [380, 425, 450, 498, 540, 595, 650, 685, 720, 775, 820, 875]
        ];

        $engagementMetrics = [
            'page_views' => 15420,
            'unique_visitors' => 3240,
            'job_views' => 2850,
            'profile_views' => 1890,
            'announcement_clicks' => 950
        ];

        $systemHealth = [
            'uptime' => '99.8%',
            'response_time' => '245ms',
            'error_rate' => '0.02%',
            'active_sessions' => 127
        ];

        return view('admin.analytics', compact('totalUsers', 'userGrowth', 'engagementMetrics', 'systemHealth'));
    }
}