<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\AdminControllerSupport;
use App\Http\Controllers\Controller;
use App\Services\AdminAnalyticsService;
use App\Services\AdminDashboardService;

class AdminDashboardController extends Controller
{
    use AdminControllerSupport;

    public function index(AdminDashboardService $dashboardService, AdminAnalyticsService $analyticsService)
    {
        $this->ensureAdmin();
        $analytics = $analyticsService->analyticsPayload();

        return view('admin.dashboard', array_merge(
            $dashboardService->summary(),
            [
                'employmentTrends' => $analytics['employmentTrends'],
                'tracedGraduates' => $analytics['tracedGraduates'],
                'industryData' => $analytics['industryData'],
                'employabilityData' => $analytics['employabilityData'],
                'globalData' => $analytics['globalData'],
                'feedbacksData' => $analytics['feedbacksData'],
                'facilitiesSatisfactionData' => $analytics['facilitiesSatisfactionData'],
                'teachingFeedbackData' => $analytics['teachingFeedbackData'],
                'overallExperienceData' => $analytics['overallExperienceData'],
                'reasonsData' => $analytics['reasonsData'],
                'csvNameSummary' => $analytics['csvNameSummary'],
            ]
        ));
    }
}
