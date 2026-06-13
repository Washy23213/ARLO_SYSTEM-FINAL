<?php

namespace App\Http\Controllers\Alumni;

use App\Http\Controllers\Controller;
use App\Services\AlumniProfileSummaryService;

class AlumniProfileController extends Controller
{
    public function profile(AlumniProfileSummaryService $profileSummaryService)
    {
        $user = auth()->user();
        abort_if(!$user, 403);

        $summary = $profileSummaryService->summaryFor($user);

        return view('profile', [
            'user' => $user,
            'latestTracer' => $summary['latestTracer'],
            'profileChecklist' => $summary['profileChecklist'],
            'profilePercent' => $summary['profilePercent'],
            'employmentStatus' => $summary['employmentStatus'],
        ]);
    }
}
