<?php

namespace App\Http\Controllers\Alumni;

use App\Http\Controllers\Controller;
use App\Services\AlumniProfileSummaryService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class AlumniDashboardController extends Controller
{
    public function index(
        Request $request,
        AlumniProfileSummaryService $profileSummaryService
    )
    {
        $user = $request->user();
        abort_if($user === null, 403);

        $profileSummary = $profileSummaryService->summaryFor($user);
        $profileChecklist = $profileSummary['profileChecklist'];
        $profileCompleted = collect($profileChecklist)->filter()->count();
        $latestTracer = $profileSummary['latestTracer'];

        return view('alumni-dashboard', [
            'user' => $user,
            'latestTracer' => $latestTracer,
            'profileChecklist' => $profileChecklist,
            'profileCompleted' => $profileCompleted,
            'profilePercent' => $profileSummary['profilePercent'],
            'employmentStatus' => $profileSummary['employmentStatus'],
            'tracerStatus' => $this->tracerStatus($latestTracer),
            'jobOpportunitySummary' => $this->jobOpportunitySummaryFor($user),
            'jobOpportunityListings' => $this->jobOpportunityListings(),
        ]);
    }

    protected function tracerStatus(mixed $latestTracer): array
    {
        if ($latestTracer === null) {
            return [
                'label' => 'Not submitted',
                'tone' => 'amber',
                'detail' => 'Submit your tracer form to get started.',
                'date_label' => 'No submission yet',
            ];
        }

        $submittedAt = $this->normalizeDate($latestTracer->tracer_date ?? $latestTracer->created_at ?? null);
        $isFresh = $submittedAt !== null && $submittedAt->greaterThanOrEqualTo(now()->subMonths(6));

        return [
            'label' => $isFresh ? 'Submitted' : 'Update needed',
            'tone' => $isFresh ? 'emerald' : 'amber',
            'detail' => $isFresh
                ? 'Your tracer form is current.'
                : 'Please update your tracer form.',
            'date_label' => $submittedAt?->format('M d, Y') ?? 'Tracer saved',
        ];
    }

    protected function jobOpportunitySummaryFor(mixed $user): array
    {
        $summary = [
            'total' => 0,
            'pending' => 0,
            'reviewed' => 0,
            'latest_status' => 'No submissions yet',
            'latest_job_title' => 'You have not posted a job opportunity yet.',
            'latest_company' => null,
            'latest_submitted_label' => 'Open the jobs module to post an opportunity.',
        ];

        if ($user === null || !Schema::hasTable('job_submissions')) {
            return $summary;
        }

        $submissions = DB::table('job_submissions')
            ->where('user_id', $user->getAuthIdentifier())
            ->select(
                'title',
                'company',
                'status',
                'created_at',
                'updated_at'
            )
            ->orderByDesc('created_at')
            ->get();
        $latestSubmission = $submissions->first();

        $summary['total'] = $submissions->count();
        $summary['pending'] = $submissions
            ->filter(fn ($submission) => strtolower(trim((string) ($submission->status ?? ''))) === 'pending')
            ->count();
        $summary['reviewed'] = max(0, $summary['total'] - $summary['pending']);

        if ($latestSubmission !== null) {
            $submittedAt = $this->normalizeDate($latestSubmission->created_at ?? $latestSubmission->updated_at ?? null);

            $summary['latest_status'] = ucfirst((string) ($latestSubmission->status ?? 'Submitted'));
            $summary['latest_job_title'] = (string) ($latestSubmission->title ?? 'Job opportunity submitted');
            $summary['latest_company'] = filled($latestSubmission->company ?? null)
                ? (string) $latestSubmission->company
                : null;
            $summary['latest_submitted_label'] = $submittedAt?->diffForHumans() ?? 'Recently submitted';
        }

        return $summary;
    }

    protected function jobOpportunityListings()
    {
        if (!Schema::hasTable('jobs')) {
            return collect();
        }

        $selectColumns = [
            'id',
            'title',
            'company',
            'type',
            'location',
            'created_at',
        ];

        if (Schema::hasColumn('jobs', 'application_deadline')) {
            $selectColumns[] = 'application_deadline';
        }

        $query = DB::table('jobs')->select($selectColumns);

        if (Schema::hasColumn('jobs', 'archived_at')) {
            $query->whereNull('archived_at');
        }

        if (Schema::hasColumn('jobs', 'is_active')) {
            $query->where('is_active', true);
        }

        return $query
            ->orderByDesc('created_at')
            ->limit(3)
            ->get();
    }

    protected function normalizeDate(mixed $value): ?Carbon
    {
        if ($value instanceof Carbon) {
            return $value;
        }

        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }
}
