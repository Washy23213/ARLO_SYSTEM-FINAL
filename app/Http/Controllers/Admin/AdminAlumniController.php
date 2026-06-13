<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\AdminControllerSupport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveAlumniRequest;
use App\Models\Admin;
use App\Models\AlumniImportBatch;
use App\Models\SuperAdmin;
use App\Models\TracerRecord;
use App\Models\User;
use App\Notifications\AdminDirectAlumniNotification;
use App\Services\AlumniBatchImportService;
use App\Services\AuditLogService;
use App\Support\EmploymentProofStorage;
use App\Support\SystemOptions;
use App\Support\TracerHistoryPresenter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Throwable;

class AdminAlumniController extends Controller
{
    use AdminControllerSupport;

    public function alumniManagement(Request $request)
    {
        $this->ensureAdmin();

        [$search, $selectedCollege, $selectedYearFrom, $selectedYearTo, $selectedStatus, $selectedImportBatch] = $this->alumniFilterValues($request);
        $selectedAccountStatus = strtolower(trim((string) $request->query('account_status', '')));
        $alumniQuery = $this->filteredAlumniQuery($request);

        $this->withEmploymentProofSignals($alumniQuery);
        if (Schema::hasTable('alumni_import_batches') && Schema::hasColumn('users', 'alumni_import_batch_id')) {
            $alumniQuery->with('importBatch');
        }

        $alumni = $alumniQuery
            ->orderByDesc('pending_employment_proof_sort')
            ->orderByDesc('has_employment_proof_sort')
            ->orderByDesc('latest_employment_proof_uploaded_at')
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        $alumniStats = $this->alumniStatsPayload($request);
        $thisMonthAlumni = $alumniStats['this_month'];
        $activeAlumni = $alumniStats['employed'];
        $pendingAlumni = $alumniStats['needs_update'];
        $pendingProofAlumni = $alumniStats['pending_proofs'];
        $collegeOptions = User::query()
            ->alumni()
            ->registeredAlumniAccount()
            ->whereNotNull('department')
            ->whereRaw("TRIM(department) <> ''")
            ->distinct()
            ->orderBy('department')
            ->pluck('department');
        $yearOptions = User::query()
            ->alumni()
            ->registeredAlumniAccount()
            ->whereNotNull('year_graduated')
            ->distinct()
            ->pluck('year_graduated')
            ->map(fn ($year) => (int) $year)
            ->merge(range(2000, (int) date('Y')))
            ->unique()
            ->sortDesc()
            ->values();
        $importBatchOptions = $this->importBatchOptions();

        return view('admin.alumni-management', compact(
            'alumni',
            'thisMonthAlumni',
            'activeAlumni',
            'pendingAlumni',
            'pendingProofAlumni',
            'search',
            'selectedCollege',
            'selectedYearFrom',
            'selectedYearTo',
            'selectedStatus',
            'selectedImportBatch',
            'selectedAccountStatus',
            'collegeOptions',
            'yearOptions',
            'importBatchOptions'
        ));
    }

    public function alumniStats(Request $request)
    {
        $this->ensureAdmin();

        return response()->json($this->alumniStatsPayload($request));
    }

    protected function alumniFilterValues(Request $request): array
    {
        $legacyYear = $this->normalizedYearFilter($request->query('year'));
        $selectedYearFrom = $this->normalizedYearFilter($request->query('year_from')) ?? $legacyYear;
        $selectedYearTo = $this->normalizedYearFilter($request->query('year_to')) ?? $legacyYear;

        if ($selectedYearFrom !== null && $selectedYearTo !== null && $selectedYearFrom > $selectedYearTo) {
            [$selectedYearFrom, $selectedYearTo] = [$selectedYearTo, $selectedYearFrom];
        }

        return [
            trim((string) $request->query('search', '')),
            trim((string) $request->query('college', '')),
            $selectedYearFrom !== null ? (string) $selectedYearFrom : '',
            $selectedYearTo !== null ? (string) $selectedYearTo : '',
            strtolower(trim((string) $request->query('status', ''))),
            $this->normalizedImportBatchFilter($request->query('import_batch')),
        ];
    }

    protected function normalizedImportBatchFilter(mixed $value): string
    {
        $value = trim((string) $value);

        return preg_match('/^[0-9a-fA-F-]{36}$/', $value) ? $value : '';
    }

    protected function importBatchOptions()
    {
        if (! Schema::hasTable('alumni_import_batches')) {
            return collect();
        }

        return AlumniImportBatch::query()
            ->select(['id', 'name', 'source_file_name', 'imported_at', 'rows_count', 'imported_count', 'updated_count'])
            ->orderByDesc('imported_at')
            ->orderByDesc('created_at')
            ->get();
    }

    protected function normalizedYearFilter(mixed $value): ?int
    {
        $value = trim((string) $value);

        return preg_match('/^\d{4}$/', $value) ? (int) $value : null;
    }

    protected function filteredAlumniQuery(Request $request)
    {
        [$search, $selectedCollege, $selectedYearFrom, $selectedYearTo, $selectedStatus, $selectedImportBatch] = $this->alumniFilterValues($request);
        $selectedAccountStatus = strtolower(trim((string) $request->query('account_status', '')));
        $alumniQuery = User::query()->alumni();

        if ($selectedAccountStatus === 'preloaded') {
            if (Schema::hasColumn('users', 'is_batch_imported')) {
                $alumniQuery->where('is_batch_imported', true);
            } else {
                $alumniQuery->whereRaw('1 = 0');
            }
        } else {
            $alumniQuery->registeredAlumniAccount();
        }

        if ($search !== '') {
            $this->applyCaseInsensitiveSearch(
                $alumniQuery,
                $search,
                ['firstname', 'middlename', 'lastname', 'email', 'department', 'degree', 'alumni_idnum', 'student_idnum', 'course', 'major'],
                [['firstname', 'lastname']]
            );
        }

        if ($selectedCollege !== '') {
            $alumniQuery->where('department', $selectedCollege);
        }

        if ($selectedYearFrom !== '') {
            $alumniQuery->where('year_graduated', '>=', (int) $selectedYearFrom);
        }

        if ($selectedYearTo !== '') {
            $alumniQuery->where('year_graduated', '<=', (int) $selectedYearTo);
        }

        if ($selectedImportBatch !== '' && Schema::hasColumn('users', 'alumni_import_batch_id')) {
            $alumniQuery->where('alumni_import_batch_id', $selectedImportBatch);
        }

        if ($selectedStatus === 'employed') {
            $alumniQuery->where('employed_after_grad', 'Yes');
        } elseif ($selectedStatus === 'unemployed') {
            $alumniQuery->where('employed_after_grad', 'No');
        } elseif ($selectedStatus === 'unknown') {
            $alumniQuery->where(function ($unknownQuery): void {
                $unknownQuery
                    ->whereNull('employed_after_grad')
                    ->orWhereRaw("TRIM(COALESCE(employed_after_grad, '')) = ''");
            });
        }

        return $alumniQuery;
    }

    protected function withEmploymentProofSignals($alumniQuery): void
    {
        if (! Schema::hasTable('tracer_records')) {
            $alumniQuery
                ->select('users.*')
                ->selectRaw('0 as pending_employment_proof_sort')
                ->selectRaw('0 as has_employment_proof_sort')
                ->selectRaw('NULL as latest_employment_proof_uploaded_at');

            return;
        }

        $proofPathPresent = "proof_records.employment_proof_path IS NOT NULL AND TRIM(COALESCE(proof_records.employment_proof_path, '')) <> ''";
        $proofStatusExpression = Schema::hasColumn('tracer_records', 'employment_proof_status')
            ? "LOWER(COALESCE(NULLIF(TRIM(proof_records.employment_proof_status), ''), 'pending'))"
            : "'pending'";
        $proofUploadedColumn = Schema::hasColumn('tracer_records', 'employment_proof_uploaded_at')
            ? 'proof_records.employment_proof_uploaded_at'
            : 'proof_records.created_at';

        $alumniQuery
            ->select('users.*')
            ->with([
                'tracerRecords' => fn ($query) => $query
                    ->whereNotNull('employment_proof_path')
                    ->whereRaw("TRIM(COALESCE(employment_proof_path, '')) <> ''")
                    ->orderByRaw("
                        CASE
                            WHEN LOWER(COALESCE(NULLIF(TRIM(employment_proof_status), ''), 'pending')) = 'pending' THEN 0
                            WHEN LOWER(COALESCE(NULLIF(TRIM(employment_proof_status), ''), 'pending')) = 'rejected' THEN 1
                            ELSE 2
                        END
                    ")
                    ->orderByDesc('employment_proof_uploaded_at')
                    ->orderByDesc('tracer_date')
                    ->orderByDesc('created_at'),
            ])
            ->selectSub(function ($query) use ($proofPathPresent, $proofStatusExpression): void {
                $query->from('tracer_records as proof_records')
                    ->selectRaw("
                        COALESCE(MAX(CASE
                            WHEN {$proofPathPresent}
                                AND {$proofStatusExpression} = 'pending'
                            THEN 1
                            ELSE 0
                        END), 0)
                    ")
                    ->whereColumn('proof_records.user_id', 'users.id');
            }, 'pending_employment_proof_sort')
            ->selectSub(function ($query) use ($proofPathPresent): void {
                $query->from('tracer_records as proof_records')
                    ->selectRaw("
                        COALESCE(MAX(CASE
                            WHEN {$proofPathPresent}
                            THEN 1
                            ELSE 0
                        END), 0)
                    ")
                    ->whereColumn('proof_records.user_id', 'users.id');
            }, 'has_employment_proof_sort')
            ->selectSub(function ($query) use ($proofPathPresent, $proofUploadedColumn): void {
                $query->from('tracer_records as proof_records')
                    ->selectRaw("MAX({$proofUploadedColumn})")
                    ->whereColumn('proof_records.user_id', 'users.id')
                    ->whereRaw($proofPathPresent);
            }, 'latest_employment_proof_uploaded_at');
    }

    protected function alumniStatsPayload(Request $request): array
    {
        $query = $this->filteredAlumniQuery($request);

        return [
            'total' => (clone $query)->count(),
            'this_month' => (clone $query)
                ->where('created_at', '>=', now()->startOfMonth())
                ->count(),
            'employed' => (clone $query)
                ->where('employed_after_grad', 'Yes')
                ->count(),
            'needs_update' => (clone $query)
                ->where(function ($needsUpdateQuery) {
                    $needsUpdateQuery
                        ->whereNull('employed_after_grad')
                        ->orWhereRaw("TRIM(COALESCE(employed_after_grad, '')) = ''");
                })
                ->count(),
            'pending_proofs' => $this->pendingEmploymentProofCount($query),
            'updated_at' => now()->format('M d, Y h:i A'),
            'needs_update_meaning' => 'Registered alumni accounts with missing employment status.',
        ];
    }

    protected function pendingEmploymentProofCount($baseQuery): int
    {
        if (! Schema::hasTable('tracer_records')) {
            return 0;
        }

        $statusColumn = Schema::hasColumn('tracer_records', 'employment_proof_status')
            ? "LOWER(COALESCE(NULLIF(TRIM(tr.employment_proof_status), ''), 'pending')) = 'pending'"
            : "1 = 1";

        return (clone $baseQuery)
            ->whereExists(function ($query) use ($statusColumn): void {
                $query->selectRaw('1')
                    ->from('tracer_records as tr')
                    ->whereColumn('tr.user_id', 'users.id')
                    ->whereNotNull('tr.employment_proof_path')
                    ->whereRaw("TRIM(COALESCE(tr.employment_proof_path, '')) <> ''")
                    ->whereRaw($statusColumn);
            })
            ->count();
    }

    public function downloadBatchTemplate(AlumniBatchImportService $importer)
    {
        $this->ensureAdmin();

        return response($importer->templateCsv(), 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="oarl-alumni-batch-template.csv"',
        ]);
    }

    public function importBatch(Request $request, AlumniBatchImportService $importer)
    {
        $this->ensureAdmin();

        $files = $request->file('batch_files', []);
        $files = is_array($files) ? $files : [$files];

        if ($files === [] && $request->hasFile('batch_file')) {
            $files = [$request->file('batch_file')];
        }

        $validated = Validator::make([
            'batch_files' => array_values(array_filter($files)),
        ], [
            'batch_files' => ['required', 'array', 'min:1', 'max:3'],
            'batch_files.*' => ['required', 'file', 'max:10240', 'extensions:csv'],
        ], [
            'batch_files.required' => 'Please choose at least one CSV file.',
            'batch_files.array' => 'Please choose valid CSV files.',
            'batch_files.min' => 'Please choose at least one CSV file.',
            'batch_files.max' => 'You can upload a maximum of 3 CSV files at one time.',
            'batch_files.*.extensions' => 'Only CSV files are accepted. Open the spreadsheet in Excel, choose Save As, then select CSV before uploading.',
            'batch_files.*.file' => 'Each upload must be a valid CSV file.',
            'batch_files.*.max' => 'Each CSV file must be 10 MB or smaller.',
        ])->validate();

        try {
            $result = $importer->importMany($validated['batch_files']);
        } catch (\Throwable $exception) {
            report($exception);

            return redirect()
                ->route('admin.alumni')
                ->with('error', $exception->getMessage());
        }

        if (empty($result['success'])) {
            return redirect()
                ->route('admin.alumni')
                ->with('error', $result['message'] ?? 'Batch import failed.')
                ->with('batch_import_header_validation', $result['header_validation'] ?? null);
        }

        app(AuditLogService::class)->log(
            (string) auth()->id(),
            'admin.alumni.batch-import',
            "Imported alumni batch. New: {$result['imported']}; Updated: {$result['updated']}; Skipped: ".count($result['skipped'] ?? []).'.',
            $request
        );

        $routeParameters = [];
        if (count($result['years'] ?? []) === 1) {
            $routeParameters['year'] = (string) $result['years'][0];
        }

        return redirect()
            ->route('admin.alumni', $routeParameters)
            ->with('success', $result['message'])
            ->with('batch_import_result', $result);
    }

    public function checkDuplicates(Request $request)
    {
        $this->ensureAdmin();

        $firstname = SystemOptions::clean((string) $request->query('firstname', ''));
        $lastname = SystemOptions::clean((string) $request->query('lastname', ''));
        $email = trim((string) $request->query('email', ''));
        $yearGraduated = $request->query('year_graduated');

        $emailExists = false;
        $emailMessage = '';
        if ($email !== '') {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $emailMessage = 'Please enter a valid email format.';
            } else {
                $emailExists = User::query()->where('email', $email)->exists()
                    || Admin::query()->where('email', $email)->exists()
                    || SuperAdmin::query()->where('email', $email)->exists();
                $emailMessage = $emailExists
                    ? 'This email is already registered.'
                    : 'Email is available.';
            }
        }

        $duplicateRecord = null;
        if ($firstname !== null && $lastname !== null && is_numeric((string) $yearGraduated)) {
            $duplicateRecord = User::query()
                ->alumni()
                ->whereRaw('LOWER(firstname) = ?', [mb_strtolower($firstname)])
                ->whereRaw('LOWER(lastname) = ?', [mb_strtolower($lastname)])
                ->where('year_graduated', (int) $yearGraduated)
                ->orderBy('created_at')
                ->first();
        }

        $nameMatches = collect();
        if ($firstname !== null && $lastname !== null) {
            $nameMatches = User::query()
                ->alumni()
                ->whereRaw('LOWER(firstname) = ?', [mb_strtolower($firstname)])
                ->whereRaw('LOWER(lastname) = ?', [mb_strtolower($lastname)])
                ->orderByDesc('year_graduated')
                ->orderBy('created_at')
                ->limit(5)
                ->get();
        }

        return response()->json([
            'email' => [
                'exists' => $emailExists,
                'message' => $emailMessage,
            ],
            'alumni_record' => [
                'exists' => $duplicateRecord !== null,
                'message' => $duplicateRecord !== null
                    ? 'An alumni account with the same first name, last name, and graduation year already exists.'
                    : (($firstname !== null && $lastname !== null && is_numeric((string) $yearGraduated))
                        ? 'No duplicate alumni record found for this name and graduation year.'
                        : 'Enter first name, last name, and graduation year to check for duplicate alumni records.'),
            ],
            'name_matches' => [
                'count' => $nameMatches->count(),
                'message' => $nameMatches->isNotEmpty()
                    ? 'Possible existing alumni records with the same first and last name were found. You can edit or delete one below if it is the same alumni.'
                    : null,
                'records' => $nameMatches
                    ->map(fn (User $user) => [
                        'id' => (string) $user->id,
                        'name' => trim((string) $user->firstname . ' ' . (string) $user->lastname),
                        'email' => (string) $user->email,
                        'department' => SystemOptions::clean($user->department),
                        'year_graduated' => $user->year_graduated,
                        'show_url' => route('admin.alumni.show', $user->id),
                        'edit_url' => route('admin.alumni.edit', $user->id),
                        'delete_url' => route('admin.alumni.delete', $user->id),
                    ])
                    ->values()
                    ->all(),
            ],
        ]);
    }

    public function alumniShow($id)
    {
        $this->ensureAdmin();

        $alumni = User::query()
            ->alumni()
            ->with([
                'tracerRecords' => fn ($query) => $query->orderByDesc('tracer_date')->orderByDesc('created_at'),
            ])
            ->findOrFail($id);

        $latestTracer = $alumni->tracerRecords->first();
        $tracerHistory = TracerHistoryPresenter::present(
            TracerHistoryPresenter::visible($alumni->tracerRecords)
        );

        return view('admin.alumni-show', compact('alumni', 'latestTracer', 'tracerHistory'));
    }

    public function downloadEmploymentProof(User $alumni, TracerRecord $record)
    {
        $this->ensureAdmin();

        abort_if(! $alumni->isAlumni(), 404, 'Employment proof not found.');
        abort_if((string) $record->user_id !== (string) $alumni->id, 404, 'Employment proof not found.');

        $file = EmploymentProofStorage::resolveFile($record);
        abort_if(!$file, 404, 'Employment proof not found.');

        $headers = [];
        if (!empty($file['mime_type'])) {
            $headers['Content-Type'] = $file['mime_type'];
        }

        return Storage::disk($file['disk'])->response(
            $file['path'],
            $file['download_name'],
            $headers,
            'inline'
        );
    }

    public function verifyEmploymentProof(Request $request, User $alumni, TracerRecord $record)
    {
        $this->ensureAdmin();
        $this->ensureEmploymentProofBelongsToAlumni($alumni, $record);

        $record->forceFill([
            'employment_proof_status' => TracerRecord::EMPLOYMENT_PROOF_STATUS_VERIFIED,
            'employment_proof_reviewed_by_guard' => Auth::getDefaultDriver(),
            'employment_proof_reviewed_by_id' => Auth::id(),
            'employment_proof_reviewed_at' => now(),
            'employment_proof_rejection_note' => null,
        ])->save();

        $this->markAlumniAsEmployedFromProof($alumni, $record);

        app(AuditLogService::class)->log(
            (string) Auth::id(),
            'admin.alumni.proof.verify',
            "Verified employment proof for {$alumni->email} ({$record->id}).",
            $request
        );

        return redirect()
            ->route('admin.alumni.show', $alumni->id)
            ->with('success', 'Employment proof verified successfully.');
    }

    public function rejectEmploymentProof(Request $request, User $alumni, TracerRecord $record)
    {
        $this->ensureAdmin();
        $this->ensureEmploymentProofBelongsToAlumni($alumni, $record);

        $validated = $request->validate([
            'rejection_note' => ['nullable', 'string', 'max:500'],
        ]);

        $record->forceFill([
            'employment_proof_status' => TracerRecord::EMPLOYMENT_PROOF_STATUS_REJECTED,
            'employment_proof_reviewed_by_guard' => Auth::getDefaultDriver(),
            'employment_proof_reviewed_by_id' => Auth::id(),
            'employment_proof_reviewed_at' => now(),
            'employment_proof_rejection_note' => SystemOptions::clean($validated['rejection_note'] ?? null),
        ])->save();

        $this->markAlumniAsUnemployed($alumni);

        app(AuditLogService::class)->log(
            (string) Auth::id(),
            'admin.alumni.proof.reject',
            "Rejected employment proof for {$alumni->email} ({$record->id}).",
            $request
        );

        return redirect()
            ->route('admin.alumni.show', $alumni->id)
            ->with('success', 'Employment proof rejected. The alumni can upload a replacement proof.');
    }

    protected function ensureEmploymentProofBelongsToAlumni(User $alumni, TracerRecord $record): void
    {
        abort_if(! $alumni->isAlumni(), 404, 'Employment proof not found.');
        abort_if((string) $record->user_id !== (string) $alumni->id, 404, 'Employment proof not found.');
        abort_if(! $record->hasEmploymentProof(), 404, 'Employment proof not found.');
        abort_if(! EmploymentProofStorage::resolveFile($record), 404, 'Employment proof not found.');
    }

    protected function markAlumniAsEmployedFromProof(User $alumni, TracerRecord $record): void
    {
        $alumni->forceFill([
            'employed_after_grad' => 'Yes',
            'industry' => SystemOptions::clean($record->industry),
            'current_position' => SystemOptions::clean($record->current_position),
            'company' => SystemOptions::clean($record->current_company),
            'salary_range' => SystemOptions::presentJobEarningsLabel($record->present_job_monthly_earnings)
                ?? SystemOptions::salaryRangeForIncome($record->monthly_income),
        ])->save();
    }

    protected function markAlumniAsUnemployed(User $alumni): void
    {
        $alumni->forceFill([
            'employed_after_grad' => 'No',
            'industry' => null,
            'current_position' => null,
            'company' => null,
            'salary_range' => null,
        ])->save();
    }

    public function alumniCreate()
    {
        $this->ensureAdmin();
        return view('admin.alumni-create');
    }

    public function alumniStore(SaveAlumniRequest $request)
    {
        $this->ensureAdmin();

        $validated = $request->validated();
        $plainPassword = (string) ($validated['password'] ?? '');
        $normalizedDepartment = SystemOptions::canonical($validated['department'] ?? null, SystemOptions::departments());
        $normalizedDegree = SystemOptions::canonical($validated['degree'] ?? null, SystemOptions::programsForDepartment($normalizedDepartment))
            ?? SystemOptions::clean($validated['degree'] ?? null);
        $employmentStatus = $validated['employed_after_grad'] ?? null;
        $hasEmploymentDetails = $employmentStatus !== 'No';

        $finalLocation = ($validated['location'] ?? null) === 'Others'
            ? ($validated['specific_location'] ?? null)
            : ($validated['location'] ?? null);

        $normalizedFirstname = SystemOptions::clean($validated['firstname']);
        $normalizedLastname = SystemOptions::clean($validated['lastname']);
        $normalizedYearGraduated = $validated['year_graduated'] ?? null;

        $duplicateExists = User::query()
            ->alumni()
            ->whereRaw('LOWER(firstname) = ?', [mb_strtolower((string) $normalizedFirstname)])
            ->whereRaw('LOWER(lastname) = ?', [mb_strtolower((string) $normalizedLastname)])
            ->where('year_graduated', $normalizedYearGraduated)
            ->exists();

        if ($duplicateExists) {
            return back()
                ->withErrors(['email' => 'An alumni account with the same name and graduation year already exists.'])
                ->withInput();
        }

        $createPayload = [
            'firstname' => $normalizedFirstname,
            'lastname' => $normalizedLastname,
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'phone' => $validated['phone'] ?? null,
            'department' => $normalizedDepartment,
            'degree' => $normalizedDegree,
            'year_graduated' => $normalizedYearGraduated,
            'employed_after_grad' => $employmentStatus,
            'industry' => $hasEmploymentDetails
                ? SystemOptions::canonical($validated['industry'] ?? null, SystemOptions::industries())
                : null,
            'current_position' => $hasEmploymentDetails
                ? SystemOptions::clean($validated['current_position'] ?? null)
                : null,
            'company' => $hasEmploymentDetails
                ? SystemOptions::clean($validated['company'] ?? null)
                : null,
            'location' => SystemOptions::canonical($finalLocation, SystemOptions::locations()),
            'salary_range' => $hasEmploymentDetails
                ? SystemOptions::salaryRangeLabel($validated['salary_range'] ?? null)
                : null,
            'skills' => SystemOptions::clean($validated['skills'] ?? null),
            'achievements' => SystemOptions::clean($validated['achievements'] ?? null),
            'role' => User::ROLE_ALUMNI,
            'is_admin' => false,
        ];

        if (Schema::hasColumn('users', 'newsletter_opt_in')) {
            $createPayload['newsletter_opt_in'] = true;
        }

        $createdUser = User::create($createPayload);

        app(AuditLogService::class)->log(
            (string) auth()->id(),
            'admin.alumni.create',
            "Created alumni record for {$createdUser->email} ({$createdUser->id}).",
            $request
        );

        return redirect()
            ->route('admin.alumni')
            ->with('success', 'Alumni record created successfully.')
            ->with('created_alumni_credentials', [
                'name' => trim($normalizedFirstname . ' ' . $normalizedLastname),
                'email' => $validated['email'],
                'password' => $plainPassword,
            ]);
    }

    public function alumniEdit($id)
    {
        $this->ensureAdmin();

        $alumni = User::query()->alumni()->findOrFail($id);
        $latestTracer = $alumni->tracerRecords()
            ->orderByDesc('tracer_date')
            ->orderByDesc('created_at')
            ->first();

        return view('admin.alumni-edit', compact('alumni', 'latestTracer'));
    }

    public function notifyAlumni(Request $request, $id)
    {
        $this->ensureAdmin();

        $alumni = User::query()->alumni()->findOrFail($id);

        if ((bool) $alumni->is_batch_imported || Str::endsWith((string) $alumni->email, '@oarl-batch.local')) {
            return back()->with('error', 'This alumni record is still CSV-only and has not completed account registration yet.');
        }

        $validated = $request->validate([
            'level' => ['required', 'string', 'in:notice,reminder,warning'],
            'title' => ['required', 'string', 'max:120'],
            'message' => ['required', 'string', 'max:1000'],
        ]);

        $actor = auth()->user();
        $actorName = $actor
            ? trim((string) ($actor->firstname ?? '') . ' ' . (string) ($actor->lastname ?? ''))
            : null;
        $actorName = $actorName !== '' ? $actorName : ($actor->email ?? null);

        $notification = new AdminDirectAlumniNotification(
            title: trim($validated['title']),
            message: trim($validated['message']),
            level: $validated['level'],
            sentByName: $actorName,
            sentByEmail: $actor?->email
        );

        Notification::sendNow($alumni, $notification, ['database']);

        $emailSent = true;
        $emailFailureMessage = null;
        try {
            Notification::sendNow($alumni, $notification, ['mail']);
        } catch (Throwable $exception) {
            report($exception);
            $emailSent = false;
            $emailFailureMessage = Str::contains($exception->getMessage(), 'Daily user sending limit exceeded')
                ? 'The Gmail sender account has reached its daily sending limit.'
                : 'Check the mail account limit or mail settings.';
        }

        app(AuditLogService::class)->log(
            (string) auth()->id(),
            'admin.alumni.notify',
            "Sent {$validated['level']} notification to {$alumni->email} ({$alumni->id}).",
            $request
        );

        return back()->with(
            $emailSent ? 'success' : 'warning',
            $emailSent
                ? 'Notification sent to the alumni account and email address.'
                : 'Notification was saved in the alumni account, but email could not be sent. '.$emailFailureMessage
        );
    }

    public function alumniUpdate(SaveAlumniRequest $request, $id)
    {
        $this->ensureAdmin();

        $alumni = User::query()->alumni()->find($id);
        if (!$alumni) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Alumni record not found.',
                ], 404);
            }

            return redirect()
                ->route('admin.alumni')
                ->with('error', 'Alumni record not found.');
        }

        $validated = $request->validated();
        $normalizedDepartment = SystemOptions::canonical($validated['department'] ?? null, SystemOptions::departments());
        $normalizedDegree = SystemOptions::canonical($validated['degree'] ?? null, SystemOptions::programsForDepartment($normalizedDepartment))
            ?? SystemOptions::clean($validated['degree'] ?? null);
        $employmentStatus = $validated['employed_after_grad'] ?? null;
        $hasEmploymentDetails = $employmentStatus !== 'No';

        $finalLocation = ($validated['location'] ?? null) === 'Others'
            ? ($validated['specific_location'] ?? null)
            : ($validated['location'] ?? null);

        $normalizedFirstname = SystemOptions::clean($validated['firstname']);
        $normalizedLastname = SystemOptions::clean($validated['lastname']);
        $normalizedYearGraduated = $validated['year_graduated'] ?? null;
        $latestTracer = $alumni->tracerRecords()
            ->orderByDesc('tracer_date')
            ->orderByDesc('created_at')
            ->first();

        $duplicateExists = User::query()
            ->alumni()
            ->where('id', '!=', $alumni->id)
            ->whereRaw('LOWER(firstname) = ?', [mb_strtolower((string) $normalizedFirstname)])
            ->whereRaw('LOWER(lastname) = ?', [mb_strtolower((string) $normalizedLastname)])
            ->where('year_graduated', $normalizedYearGraduated)
            ->exists();

        if ($duplicateExists) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Another alumni account with the same name and graduation year already exists.',
                    'errors' => [
                        'email' => ['Another alumni account with the same name and graduation year already exists.'],
                    ],
                ], 422);
            }

            return back()
                ->withErrors(['email' => 'Another alumni account with the same name and graduation year already exists.'])
                ->withInput();
        }

        $payload = [
            'firstname' => $normalizedFirstname,
            'lastname' => $normalizedLastname,
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'department' => $normalizedDepartment,
            'degree' => $normalizedDegree,
            'year_graduated' => $normalizedYearGraduated,
            'employed_after_grad' => $employmentStatus,
            'industry' => $hasEmploymentDetails
                ? SystemOptions::canonical($validated['industry'] ?? null, SystemOptions::industries())
                : null,
            'current_position' => $hasEmploymentDetails
                ? SystemOptions::clean($validated['current_position'] ?? null)
                : null,
            'company' => $hasEmploymentDetails
                ? SystemOptions::clean($validated['company'] ?? null)
                : null,
            'location' => SystemOptions::canonical($finalLocation, SystemOptions::locations()),
            'salary_range' => $hasEmploymentDetails
                ? SystemOptions::salaryRangeLabel($validated['salary_range'] ?? null)
                : null,
            'skills' => SystemOptions::clean($validated['skills'] ?? null),
            'achievements' => SystemOptions::clean($validated['achievements'] ?? null),
            'role' => User::ROLE_ALUMNI,
            'is_admin' => false,
        ];

        $alumni->update($payload);
        $tracerHistorySynced = $this->syncTracerHistoryFromAdminUpdate($alumni, $validated, $payload, $latestTracer);

        app(AuditLogService::class)->log(
            (string) auth()->id(),
            'admin.alumni.update',
            "Updated alumni record for {$alumni->email} ({$alumni->id}).",
            $request
        );

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Alumni record updated successfully.',
                'displayName' => trim($alumni->firstname . ' ' . $alumni->lastname),
            ]);
        }

        return redirect()
            ->route('admin.alumni')
            ->with('success', $tracerHistorySynced
                ? 'Alumni record updated successfully and tracer history was synced.'
                : 'Alumni record updated successfully.');
    }

    protected function syncTracerHistoryFromAdminUpdate(
        User $alumni,
        array $validated,
        array $payload,
        ?TracerRecord $latestTracer
    ): bool {
        $summary = $this->buildAdminTracerSummary($validated, $payload, $latestTracer);

        if (!$this->adminTracerSummaryChanged($summary, $latestTracer)) {
            return false;
        }

        if ($latestTracer) {
            $record = $latestTracer->replicate();
            $record->tracer_number = null;
            $record->user_id = $alumni->id;
            $record->tracer_date = now()->toDateString();
        } else {
            $record = new TracerRecord([
                'user_id' => $alumni->id,
                'tracer_date' => now()->toDateString(),
                'contact_permission' => true,
            ]);
        }

        $record->is_employed = $summary['is_employed'];
        $record->employment_type = $summary['employment_type'];
        $record->current_position = $summary['current_position'];
        $record->current_company = $summary['current_company'];
        $record->industry = $summary['industry'];
        $record->further_education = $summary['further_education'];
        $record->education_level = $summary['education_level'];

        if ($summary['is_employed'] === false) {
            $record->monthly_income = null;
            $record->present_job_monthly_earnings = null;
        }

        if ($record->contact_permission === null) {
            $record->contact_permission = true;
        }

        $record->save();

        return true;
    }

    protected function buildAdminTracerSummary(array $validated, array $payload, ?TracerRecord $latestTracer): array
    {
        $employmentType = $this->resolveAdminEmploymentType($payload, $latestTracer);
        $isEmployed = $employmentType === null
            ? null
            : mb_strtolower($employmentType) !== 'unemployed';
        $furtherEducation = (bool) ($validated['further_education'] ?? false);

        return [
            'is_employed' => $isEmployed,
            'employment_type' => $employmentType,
            'current_position' => $isEmployed === false
                ? null
                : SystemOptions::clean($payload['current_position'] ?? null),
            'current_company' => $isEmployed === false
                ? null
                : SystemOptions::clean($payload['company'] ?? null),
            'industry' => $isEmployed === false
                ? null
                : SystemOptions::clean($payload['industry'] ?? null),
            'further_education' => $furtherEducation,
            'education_level' => $furtherEducation
                ? SystemOptions::canonical($validated['education_level'] ?? null, SystemOptions::educationLevels())
                : null,
        ];
    }

    protected function resolveAdminEmploymentType(array $payload, ?TracerRecord $latestTracer): ?string
    {
        $employmentStatus = SystemOptions::clean($payload['employed_after_grad'] ?? null);
        $hasEmploymentSummary = filled($payload['current_position'] ?? null)
            || filled($payload['company'] ?? null)
            || filled($payload['industry'] ?? null);
        $latestEmploymentType = SystemOptions::clean($latestTracer?->employment_type);

        if ($employmentStatus === 'No') {
            return 'Unemployed';
        }

        if ($employmentStatus === 'Yes') {
            return $latestEmploymentType !== null && mb_strtolower($latestEmploymentType) !== 'unemployed'
                ? $latestEmploymentType
                : 'Employed';
        }

        if ($hasEmploymentSummary) {
            return $latestEmploymentType !== null && mb_strtolower($latestEmploymentType) !== 'unemployed'
                ? $latestEmploymentType
                : 'Employed';
        }

        return $latestEmploymentType;
    }

    protected function adminTracerSummaryChanged(array $summary, ?TracerRecord $latestTracer): bool
    {
        if (!$latestTracer) {
            return $summary['employment_type'] !== null
                || $summary['current_position'] !== null
                || $summary['current_company'] !== null
                || $summary['industry'] !== null
                || $summary['further_education'] === true
                || $summary['education_level'] !== null;
        }

        return $summary['employment_type'] !== SystemOptions::clean($latestTracer->employment_type)
            || $summary['current_position'] !== SystemOptions::clean($latestTracer->current_position)
            || $summary['current_company'] !== SystemOptions::clean($latestTracer->current_company)
            || $summary['industry'] !== SystemOptions::clean($latestTracer->industry)
            || $summary['further_education'] !== (bool) $latestTracer->further_education
            || $summary['education_level'] !== SystemOptions::clean($latestTracer->education_level);
    }

    public function alumniDelete($id)
    {
        $this->ensureAdmin();

        $alumni = User::query()->alumni()->findOrFail($id);

        $alumni->delete();

        app(AuditLogService::class)->log(
            (string) auth()->id(),
            'admin.alumni.delete',
            "Deleted alumni record for {$alumni->email} ({$alumni->id}).",
            request()
        );

        return redirect()
            ->route('admin.alumni')
            ->with('success', 'Alumni record deleted successfully.');
    }
}
