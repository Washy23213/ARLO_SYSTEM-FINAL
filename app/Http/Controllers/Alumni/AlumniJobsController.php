<?php

namespace App\Http\Controllers\Alumni;

use App\Http\Controllers\Controller;
use App\Http\Requests\Alumni\SubmitJobRequest;
use App\Services\AlumniJobOpportunityService;
use App\Services\AuditLogService;
use App\Support\PortalIndicatorState;
use App\Support\SystemOptions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AlumniJobsController extends Controller
{
    public function jobs(Request $request, AlumniJobOpportunityService $jobOpportunityService)
    {
        $jobOpportunities = $jobOpportunityService->paginateForBoard($request);
        $mySubmissions = collect();

        if (auth()->check() && Schema::hasTable('job_submissions')) {
            $mySubmissions = DB::table('job_submissions')
                ->where('user_id', auth()->id())
                ->orderByDesc('created_at')
                ->limit(5)
                ->get();
        }

        PortalIndicatorState::markSeen(
            $request,
            PortalIndicatorState::ALUMNI_JOBS,
            $request->user()?->getAuthIdentifier()
        );

        return view('jobs', [
            'jobOpportunities' => $jobOpportunities,
            'mySubmissions' => $mySubmissions,
            'jobSearch' => mb_substr(trim((string) $request->query('q', '')), 0, 120),
            'jobTypeFilter' => trim((string) $request->query('type', '')),
            'jobLocationFilter' => trim((string) $request->query('location', '')),
            'jobTypeOptions' => SystemOptions::jobTypes(),
            'jobLocationOptions' => SystemOptions::locations(),
        ]);
    }

    public function submitJob(SubmitJobRequest $request)
    {
        $validated = $request->validated();

        if (!Schema::hasTable('job_submissions')) {
            return redirect()->route('jobs')->withErrors(['jobs' => 'Job submission table is not ready yet. Please run database migration first.']);
        }

        $finalLocation = ($validated['location'] ?? null) === 'Others'
            ? ($validated['specific_location'] ?? null)
            : ($validated['location'] ?? null);

        $submissionId = DB::table('job_submissions')->insertGetId([
            'user_id' => auth()->id(),
            'title' => SystemOptions::clean($validated['title']),
            'company' => SystemOptions::clean($validated['company']),
            'type' => SystemOptions::canonical($validated['type'], SystemOptions::jobTypes()),
            'location' => SystemOptions::canonical($finalLocation, SystemOptions::locations()),
            'contact_email' => $validated['contact_email'],
            'application_deadline' => $validated['application_deadline'] ?? null,
            'description' => $validated['description'],
            'requirements' => $validated['requirements'] ?? null,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        app(AuditLogService::class)->log(
            (string) auth()->id(),
            'alumni.jobs.submit',
            "Submitted job posting {$validated['title']} for admin review ({$submissionId}).",
            $request
        );

        return redirect()->route('jobs')->with('success', 'Your job was submitted and is now pending admin approval.');
    }

    public function jobDetail(Request $request, $id)
    {
        $jobRow = DB::table('jobs')->where('id', $id)->first();

        if (!$jobRow) {
            abort(404, 'Job not found');
        }
        if (Schema::hasColumn('jobs', 'archived_at') && !empty($jobRow->archived_at)) {
            abort(404, 'Job not available');
        }
        if (Schema::hasColumn('jobs', 'is_active') && !(bool) ($jobRow->is_active ?? false)) {
            abort(404, 'Job not available');
        }

        if (Schema::hasColumn('jobs', 'views_count')) {
            DB::table('jobs')
                ->where('id', $id)
                ->update([
                    'views_count' => DB::raw('COALESCE(views_count, 0) + 1'),
                ]);

            $jobRow = DB::table('jobs')->where('id', $id)->first();
        }

        $requirements = $this->parseRequirementLines($jobRow->requirements);

        $job = [
            'id' => $jobRow->id,
            'title' => $jobRow->title,
            'company' => $jobRow->company,
            'location' => $jobRow->location ?: 'N/A',
            'type' => $jobRow->type ?: 'N/A',
            'posted' => optional($jobRow->created_at ? \Carbon\Carbon::parse($jobRow->created_at) : null)->diffForHumans() ?? 'N/A',
            'description' => $jobRow->description ?: 'No description provided.',
            'requirements' => !empty($requirements) ? $requirements : ['No requirements provided.'],
            'responsibilities' => !empty($requirements) ? $requirements : ['No responsibilities provided.'],
            'benefits' => ['Contact employer for full benefits details.'],
            'contact_email' => $jobRow->contact_email ?: 'N/A',
            'application_deadline' => $jobRow->application_deadline ? \Carbon\Carbon::parse($jobRow->application_deadline)->format('F j, Y') : 'N/A',
            'views_count' => (int) ($jobRow->views_count ?? 0),
        ];

        PortalIndicatorState::markSeen(
            $request,
            PortalIndicatorState::ALUMNI_JOBS,
            $request->user()?->getAuthIdentifier()
        );

        return view('job-detail', compact('job'));
    }

    protected function parseRequirementLines(?string $requirements): array
    {
        return collect(preg_split('/\r\n|\r|\n/', (string) $requirements))
            ->map(function ($line) {
                $line = str_replace(["\xE2\x80\xA2", "\xC3\xA2\xE2\x82\xAC\xC2\xA2"], '', (string) $line);
                $line = preg_replace('/^[\s\-\*]+/u', '', $line);

                return trim((string) $line);
            })
            ->filter()
            ->values()
            ->all();
    }

}
