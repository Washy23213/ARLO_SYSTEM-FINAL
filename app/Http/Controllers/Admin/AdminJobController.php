<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\AdminControllerSupport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RejectJobSubmissionRequest;
use App\Http\Requests\Admin\SaveJobRequest;
use App\Models\User;
use App\Notifications\JobSubmissionApprovedNotification;
use App\Notifications\JobSubmissionRejectedNotification;
use App\Services\AuditLogService;
use App\Support\SystemOptions;
use Illuminate\Http\Request;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

class AdminJobController extends Controller
{
    use AdminControllerSupport;

    public function jobManagement(Request $request)
    {
        $this->ensureAdmin();

        $search = trim((string) $request->query('search', ''));
        $selectedCompany = trim((string) $request->query('company', ''));
        $selectedType = trim((string) $request->query('type', ''));
        $selectedStatus = strtolower(trim((string) $request->query('status', '')));

        $jobColumns = ['id', 'title', 'company', 'type', 'location', 'created_at'];
        if ($this->columnExists('jobs', 'is_active')) {
            $jobColumns[] = 'is_active';
        }
        if ($this->columnExists('jobs', 'archived_at')) {
            $jobColumns[] = 'archived_at';
        }

        $jobsQuery = DB::table('jobs')
            ->select($jobColumns)
            ->orderByDesc('created_at');

        if ($search !== '') {
            $this->applyCaseInsensitiveSearch(
                $jobsQuery,
                $search,
                ['title', 'company', 'location', 'type']
            );
        }

        if ($selectedCompany !== '') {
            $jobsQuery->where('company', $selectedCompany);
        }

        if ($selectedType !== '') {
            $jobsQuery->where('type', $selectedType);
        }

        if ($this->columnExists('jobs', 'archived_at')) {
            if ($selectedStatus === 'archived') {
                $jobsQuery->whereNotNull('archived_at');
            } else {
                $jobsQuery->whereNull('archived_at');
            }
        }

        if ($this->columnExists('jobs', 'is_active') && $selectedStatus !== 'archived') {
            if ($selectedStatus === 'active') {
                $jobsQuery->where('is_active', true);
            } elseif (in_array($selectedStatus, ['inactive', 'expired', 'draft'], true)) {
                $jobsQuery->where('is_active', false);
            }
        }

        $jobs = $jobsQuery->paginate(20)->withQueryString();

        $companyOptions = DB::table('jobs')
            ->when($this->columnExists('jobs', 'archived_at'), fn ($query) => $query->whereNull('archived_at'))
            ->whereNotNull('company')
            ->whereRaw("TRIM(company) <> ''")
            ->distinct()
            ->orderBy('company')
            ->pluck('company');
        $typeOptions = DB::table('jobs')
            ->when($this->columnExists('jobs', 'archived_at'), fn ($query) => $query->whereNull('archived_at'))
            ->whereNotNull('type')
            ->whereRaw("TRIM(type) <> ''")
            ->distinct()
            ->orderBy('type')
            ->pluck('type');

        $hasJobArchive = $this->columnExists('jobs', 'archived_at');
        $hasJobActive = $this->columnExists('jobs', 'is_active');
        $jobPostsCount = DB::table('jobs')
            ->when($hasJobArchive, fn ($query) => $query->whereNull('archived_at'))
            ->count();
        $activeJobsCount = DB::table('jobs')
            ->when($hasJobArchive, fn ($query) => $query->whereNull('archived_at'))
            ->when($hasJobActive, fn ($query) => $query->where('is_active', true))
            ->count();
        $pendingSubmissionCount = $this->tableExists('job_submissions')
            ? DB::table('job_submissions')->whereRaw('LOWER(status) = ?', ['pending'])->count()
            : 0;
        $reviewedSubmissionCount = $this->tableExists('job_submissions')
            ? DB::table('job_submissions')
                ->whereRaw('LOWER(status) IN (?, ?)', ['approved', 'rejected'])
                ->count()
            : 0;

        $pendingSubmissions = collect();
        if (Schema::hasTable('job_submissions')) {
            $pendingSubmissions = DB::table('job_submissions as js')
                ->join('users as u', 'u.id', '=', 'js.user_id')
                ->select(
                    'js.id',
                    'js.title',
                    'js.company',
                    'js.type',
                    'js.location',
                    'js.contact_email',
                    'js.application_deadline',
                    'js.description',
                    'js.requirements',
                    'js.created_at',
                    'u.firstname',
                    'u.lastname',
                    'u.email as submitted_by_email'
                )
                ->where('js.status', 'pending')
                ->orderByDesc('js.created_at')
                ->get();
        }

        return view('admin.job-management', compact(
            'jobs',
            'pendingSubmissions',
            'jobPostsCount',
            'activeJobsCount',
            'pendingSubmissionCount',
            'reviewedSubmissionCount',
            'search',
            'selectedCompany',
            'selectedType',
            'selectedStatus',
            'companyOptions',
            'typeOptions'
        ));
    }

    public function jobCreate()
    {
        $this->ensureAdmin();
        return view('admin.job-create');
    }

    public function jobShow($id)
    {
        $this->ensureAdmin();

        $job = DB::table('jobs')->where('id', $id)->first();
        if (!$job) {
            abort(404, 'Job not found');
        }

        return view('admin.job-show', compact('job'));
    }

    public function jobEdit($id)
    {
        $this->ensureAdmin();

        $job = DB::table('jobs')->where('id', $id)->first();
        if (!$job) {
            abort(404, 'Job not found');
        }

        return view('admin.job-edit', compact('job'));
    }

    public function jobStore(SaveJobRequest $request)
    {
        $this->ensureAdmin();

        $validated = $request->validated();

        $finalLocation = $validated['location'] === 'Others'
            ? ($validated['specific_location'] ?? '')
            : $validated['location'];

        $jobData = [
            'id' => (string) Str::uuid(),
            'title' => SystemOptions::clean($validated['title']),
            'company' => SystemOptions::clean($validated['company']),
            'type' => SystemOptions::canonical($validated['type'], SystemOptions::jobTypes()),
            'location' => SystemOptions::canonical($finalLocation, SystemOptions::locations()),
            'description' => $validated['description'],
            'requirements' => $validated['requirements'] ?? null,
            'contact_email' => $validated['contact_email'],
        ];

        if ($this->columnExists('jobs', 'contact_phone')) {
            $jobData['contact_phone'] = $validated['contact_phone'] ?? null;
        }
        if ($this->columnExists('jobs', 'application_deadline')) {
            $jobData['application_deadline'] = $validated['application_deadline'] ?? null;
        }

        $salaryRange = SystemOptions::salaryRangeLabel($validated['salary_range'] ?? null) ?? '';
        if ($this->columnExists('jobs', 'salary_range')) {
            $jobData['salary_range'] = $salaryRange !== '' ? $salaryRange : null;
        }

        if ($this->columnExists('jobs', 'is_active')) {
            $jobData['is_active'] = (bool) ($validated['is_active'] ?? true);
        }
        if ($this->columnExists('jobs', 'views_count')) {
            $jobData['views_count'] = 0;
        }
        if ($this->columnExists('jobs', 'created_by')) {
            $jobData['created_by'] = $this->adminOwnedActorId();
        }
        if ($this->columnExists('jobs', 'created_at')) {
            $jobData['created_at'] = now();
        }
        if ($this->columnExists('jobs', 'updated_at')) {
            $jobData['updated_at'] = now();
        }

        DB::table('jobs')->insert($jobData);

        app(AuditLogService::class)->log(
            (string) auth()->id(),
            'admin.jobs.create',
            "Created job posting {$jobData['title']} ({$jobData['id']}).",
            $request
        );

        return redirect()
            ->route('admin.jobs')
            ->with('success', 'Job posted successfully.');
    }

    public function jobUpdate(SaveJobRequest $request, $id)
    {
        $this->ensureAdmin();

        $existingJob = DB::table('jobs')->where('id', $id)->first();
        if (!$existingJob) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Job not found.',
                ], 404);
            }

            return redirect()->route('admin.jobs')->with('error', 'Job not found.');
        }

        $validated = $request->validated();

        $finalLocation = $validated['location'] === 'Others'
            ? ($validated['specific_location'] ?? '')
            : ($validated['location'] ?? '');

        $updateData = [
            'title' => SystemOptions::clean($validated['title']),
            'company' => SystemOptions::clean($validated['company']),
            'type' => SystemOptions::canonical($validated['type'], SystemOptions::jobTypes()),
            'location' => SystemOptions::canonical($finalLocation, SystemOptions::locations()),
            'description' => $validated['description'],
            'requirements' => $validated['requirements'] ?? null,
            'contact_email' => $validated['contact_email'],
        ];

        if ($this->columnExists('jobs', 'contact_phone')) {
            $updateData['contact_phone'] = $validated['contact_phone'] ?? null;
        }
        if ($this->columnExists('jobs', 'application_deadline')) {
            $updateData['application_deadline'] = $validated['application_deadline'] ?? null;
        }

        $salaryRange = SystemOptions::salaryRangeLabel($validated['salary_range'] ?? null) ?? '';
        if ($this->columnExists('jobs', 'salary_range')) {
            $updateData['salary_range'] = $salaryRange !== '' ? $salaryRange : null;
        }

        if ($this->columnExists('jobs', 'is_active')) {
            $updateData['is_active'] = (bool) ($validated['is_active'] ?? false);
        }
        if ($this->columnExists('jobs', 'updated_at')) {
            $updateData['updated_at'] = now();
        }

        DB::table('jobs')->where('id', $id)->update($updateData);

        app(AuditLogService::class)->log(
            (string) auth()->id(),
            'admin.jobs.update',
            "Updated job posting {$updateData['title']} ({$id}).",
            $request
        );

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Job updated successfully.',
            ]);
        }

        return redirect()->route('admin.jobs')->with('success', 'Job updated successfully.');
    }

    public function jobDelete($id)
    {
        $this->ensureAdmin();

        $existingJob = DB::table('jobs')->where('id', $id)->first();
        if (!$existingJob) {
            return redirect()->route('admin.jobs')->with('error', 'Job not found.');
        }

        DB::transaction(function () use ($id) {
            DB::table('jobs')->where('id', $id)->delete();
        });

        app(AuditLogService::class)->log(
            (string) auth()->id(),
            'admin.jobs.delete',
            "Deleted job posting {$existingJob->title} ({$id}).",
            request()
        );

        return redirect()->route('admin.jobs')->with('success', 'Job posting deleted successfully.');
    }

    public function approveJobSubmission($id)
    {
        $this->ensureAdmin();

        if (!Schema::hasTable('job_submissions')) {
            return redirect()->route('admin.jobs')->with('error', 'Job submissions table is not ready yet.');
        }

        $submission = DB::table('job_submissions')
            ->where('id', $id)
            ->where('status', 'pending')
            ->first();

        if (!$submission) {
            return redirect()->route('admin.jobs')->with('error', 'Submission not found or already reviewed.');
        }

        $adminOwnedActorId = $this->adminOwnedActorId();

        $jobId = DB::transaction(function () use ($submission, $adminOwnedActorId) {
            $jobData = [
                'id' => (string) Str::uuid(),
                'title' => $submission->title,
                'company' => $submission->company,
                'description' => $submission->description,
                'requirements' => $submission->requirements,
                'location' => $submission->location,
                'type' => $submission->type,
                'contact_email' => $submission->contact_email,
                'application_deadline' => $submission->application_deadline,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (Schema::hasColumn('jobs', 'created_by')) {
                $jobData['created_by'] = $adminOwnedActorId;
            }
            if (Schema::hasColumn('jobs', 'is_active')) {
                $jobData['is_active'] = true;
            }

            DB::table('jobs')->insert($jobData);

            $updateData = [
                'status' => 'approved',
                'updated_at' => now(),
            ];

            if (Schema::hasColumn('job_submissions', 'reviewed_by')) {
                $updateData['reviewed_by'] = $adminOwnedActorId;
            }
            if (Schema::hasColumn('job_submissions', 'reviewed_at')) {
                $updateData['reviewed_at'] = now();
            }

            DB::table('job_submissions')
                ->where('id', $submission->id)
                ->update($updateData);

            return $jobData['id'];
        });

        $notificationSent = $this->notifySubmissionReview(
            $submission,
            new JobSubmissionApprovedNotification(
                $submission->title,
                $submission->company,
                $jobId
            )
        );

        app(AuditLogService::class)->log(
            (string) auth()->id(),
            'admin.job-submissions.approve',
            "Approved job submission {$submission->id} and posted it.",
            request()
        );

        $message = 'Job submission approved and posted.';
        if (!$notificationSent) {
            $message .= ' The job was posted, but the email notification could not be sent.';
        }

        return redirect()->route('admin.jobs')->with('success', $message);
    }

    public function rejectJobSubmission(RejectJobSubmissionRequest $request, $id)
    {
        $this->ensureAdmin();

        if (!Schema::hasTable('job_submissions')) {
            return redirect()->route('admin.jobs')->with('error', 'Job submissions table is not ready yet.');
        }

        $validated = $request->validated();
        $adminNotes = SystemOptions::clean($validated['admin_notes'] ?? '');

        $submission = DB::table('job_submissions')
            ->where('id', $id)
            ->where('status', 'pending')
            ->first();

        if (!$submission) {
            return redirect()->route('admin.jobs')->with('error', 'Submission not found or already reviewed.');
        }

        $updateData = [
            'status' => 'rejected',
            'updated_at' => now(),
        ];

        if (Schema::hasColumn('job_submissions', 'reviewed_by')) {
            $updateData['reviewed_by'] = $this->adminOwnedActorId();
        }
        if (Schema::hasColumn('job_submissions', 'reviewed_at')) {
            $updateData['reviewed_at'] = now();
        }
        if (Schema::hasColumn('job_submissions', 'admin_notes')) {
            $updateData['admin_notes'] = $adminNotes;
        }

        DB::table('job_submissions')
            ->where('id', $submission->id)
            ->update($updateData);

        $notificationSent = $this->notifySubmissionReview(
            $submission,
            new JobSubmissionRejectedNotification(
                $submission->title,
                $submission->company,
                $adminNotes
            )
        );

        app(AuditLogService::class)->log(
            (string) auth()->id(),
            'admin.job-submissions.reject',
            "Rejected job submission {$submission->id} with a reason.",
            $request
        );

        $message = 'Job submission rejected.';
        if (!$notificationSent) {
            $message .= ' The review was saved, but the email notification could not be sent.';
        }

        return redirect()->route('admin.jobs')->with('success', $message);
    }

    protected function notifySubmissionReview(object $submission, Notification $notification): bool
    {
        $user = User::find($submission->user_id);

        if (!$user || empty($user->email)) {
            return false;
        }

        try {
            $user->notify($notification);

            return true;
        } catch (Throwable $exception) {
            report($exception);

            return false;
        }
    }
}
