<?php

namespace App\Http\Controllers\Alumni;

use App\Http\Controllers\Controller;
use App\Http\Requests\Alumni\StoreTracerRecordRequest;
use App\Models\TracerRecord;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\EmploymentProofReviewNotificationService;
use App\Support\EmploymentProofStorage;
use App\Support\SystemOptions;
use App\Support\TracerHistoryPresenter;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class AlumniTracerController extends Controller
{
    protected const TRACER_HISTORY_DISPLAY_LIMIT = 10;

    public function index(Request $request)
    {
        $user = $request->user();
        abort_if(!$user, 403);

        $suppressEditAutoscroll = (bool) $request->session()->pull('tracer_skip_autoscroll', false);

        return view('tracer', $this->buildTracerPageData(
            $user,
            $request->query('edit'),
            $suppressEditAutoscroll
        ));
    }

    public function showDraft(Request $request)
    {
        $user = $request->user();
        abort_if(!$user, 403);

        if (!Schema::hasTable('tracer_drafts')) {
            return response()->json(['draft' => null, 'available' => false], 503);
        }

        $draft = DB::table('tracer_drafts')->where('user_id', $user->id)->first();
        if (!$draft) {
            return response()->json(['draft' => null]);
        }

        try {
            $data = json_decode(Crypt::decryptString($draft->payload), true, 512, JSON_THROW_ON_ERROR);
        } catch (DecryptException|\JsonException) {
            DB::table('tracer_drafts')->where('user_id', $user->id)->delete();

            return response()->json(['draft' => null]);
        }

        return response()->json([
            'draft' => [
                'data' => is_array($data) ? $data : [],
                'step' => max(1, min(5, (int) $draft->current_step)),
                'saved_at' => $draft->updated_at,
                'consented' => $draft->consented_at !== null,
            ],
        ]);
    }

    public function saveDraft(Request $request)
    {
        $user = $request->user();
        abort_if(!$user, 403);

        if (!Schema::hasTable('tracer_drafts')) {
            return response()->json(['message' => 'Draft saving is not available until the database update is applied.'], 503);
        }

        $validated = $request->validate([
            'step' => ['required', 'integer', 'between:1,5'],
            'consent' => ['accepted'],
            'data' => ['required', 'array'],
        ]);

        $allowedFields = array_diff((new TracerRecord())->getFillable(), [
            'tracer_number',
            'user_id',
            'tracer_date',
            'is_employed',
            'employment_proof_path',
            'employment_proof_disk',
            'employment_proof_original_name',
            'employment_proof_mime_type',
            'employment_proof_size_bytes',
            'employment_proof_uploaded_at',
            'employment_proof_status',
            'employment_proof_reviewed_by_guard',
            'employment_proof_reviewed_by_id',
            'employment_proof_reviewed_at',
            'employment_proof_rejection_note',
            'contact_permission',
        ]);
        $draftData = [];

        foreach ($validated['data'] as $field => $value) {
            if (!in_array($field, $allowedFields, true)) {
                continue;
            }

            if (is_array($value)) {
                if (count($value) > 40 || collect($value)->contains(fn ($item) => !is_scalar($item) && $item !== null)) {
                    continue;
                }
                $value = array_map(static fn ($item) => is_string($item) ? mb_substr($item, 0, 10000) : $item, $value);
            } elseif (is_string($value)) {
                $value = mb_substr($value, 0, 10000);
            } elseif (!is_scalar($value) && $value !== null) {
                continue;
            }

            $draftData[$field] = $value;
        }

        $encryptedPayload = Crypt::encryptString(json_encode($draftData, JSON_THROW_ON_ERROR));
        if (strlen($encryptedPayload) > 90000) {
            return response()->json(['message' => 'This draft is too large to save. Please shorten the longer answers and try again.'], 413);
        }

        $now = now();
        $existingDraft = DB::table('tracer_drafts')->where('user_id', $user->id)->exists();
        $values = [
            'payload' => $encryptedPayload,
            'current_step' => (int) $validated['step'],
            'consented_at' => $now,
            'updated_at' => $now,
        ];

        if ($existingDraft) {
            DB::table('tracer_drafts')->where('user_id', $user->id)->update($values);
        } else {
            DB::table('tracer_drafts')->insert([
                'user_id' => $user->id,
                ...$values,
                'created_at' => $now,
            ]);
        }

        return response()->json(['message' => 'Draft saved securely.', 'saved_at' => $now->toIso8601String()]);
    }

    public function deleteDraft(Request $request)
    {
        $user = $request->user();
        abort_if(!$user, 403);

        if (Schema::hasTable('tracer_drafts')) {
            DB::table('tracer_drafts')->where('user_id', $user->id)->delete();
        }

        return response()->json(['message' => 'Saved draft deleted.']);
    }

    public function store(StoreTracerRecordRequest $request)
    {
        $user = $request->user();
        abort_if(!$user, 403);

        $validated = $request->validated();
        $sourceRecord = null;

        if (!empty($validated['record_id'])) {
            $sourceRecord = $user->tracerRecords()->where('id', $validated['record_id'])->first();
            abort_if(!$sourceRecord, 404, 'Tracer record not found');
        }

        if (
            !$user->data_privacy_consent
            && $request->boolean('data_privacy_consent')
        ) {
            $user->forceFill([
                'data_privacy_consent' => true,
                'data_privacy_consented_at' => now(),
            ])->save();
        }

        $employmentType = SystemOptions::canonical($validated['employment_type'] ?? null, SystemOptions::employmentTypes());
        $industry = SystemOptions::canonical($validated['industry'] ?? null, SystemOptions::industries());
        $isEmployed = $this->employmentFlag($employmentType);
        $tracerDate = now()->toDateString();
        $comparisonRecord = $sourceRecord ?? $this->latestSavedTracerFor($user);

        $curriculumRelevant = $request->has('curriculum_relevant_to_first_job')
            ? $request->boolean('curriculum_relevant_to_first_job')
            : null;
        $employedAfterGraduation = $request->has('employed_after_graduation')
            ? $request->boolean('employed_after_graduation')
            : null;
        $presentlyEmployed = $this->presentlyEmployedState($validated['presently_employed'] ?? null, $employmentType);
        $hasPresentEmploymentDetails = $presentlyEmployed === 'Yes';
        $uploadedEmploymentProof = $request->hasFile('employment_proof');
        $hasEmployeeAwards = $request->has('has_employee_awards')
            ? $request->boolean('has_employee_awards')
            : null;
        $hasCurriculumSuggestions = $request->has('has_curriculum_suggestions')
            ? $request->boolean('has_curriculum_suggestions')
            : null;
        $hasEmployabilitySuggestions = $request->has('has_employability_suggestions')
            ? $request->boolean('has_employability_suggestions')
            : null;
        $hasFacilitySuggestions = $request->has('has_facility_suggestions')
            ? $request->boolean('has_facility_suggestions')
            : null;
        $employmentProofData = $this->employmentProofPayload($request, $sourceRecord ?? $comparisonRecord, $presentlyEmployed);

        $payload = [
            'tracer_date' => $tracerDate,
            'is_employed' => $isEmployed,
            'employment_type' => $employmentType,
            'current_position' => $this->employmentText($employmentType, $validated['current_position'] ?? null),
            'current_company' => $this->employmentText($employmentType, $validated['current_company'] ?? null),
            'monthly_income' => $this->employmentIncome($employmentType, $validated['monthly_income'] ?? null),
            'industry' => $this->employmentText($employmentType, $industry),
            'job_satisfaction' => $validated['job_satisfaction'] ?? null,
            'skills_used' => SystemOptions::clean($validated['skills_used'] ?? null),
            'curriculum_relevant_to_first_job' => $curriculumRelevant,
            'first_job_useful_competencies' => $curriculumRelevant === true
                ? $this->normalizedSelections((array) ($validated['first_job_useful_competencies'] ?? []))
                : [],
            'first_job_useful_competencies_other' => $curriculumRelevant === true
                && in_array('Other', (array) ($validated['first_job_useful_competencies'] ?? []), true)
                    ? SystemOptions::clean($validated['first_job_useful_competencies_other'] ?? null)
                    : null,
            'curriculum_not_relevant_reason' => $curriculumRelevant === false
                ? SystemOptions::clean($validated['curriculum_not_relevant_reason'] ?? null)
                : null,
            'curriculum_not_relevant_reason_other' => $curriculumRelevant === false
                && ($validated['curriculum_not_relevant_reason'] ?? null) === 'Other'
                    ? SystemOptions::clean($validated['curriculum_not_relevant_reason_other'] ?? null)
                    : null,
            'employed_after_graduation' => $employedAfterGraduation,
            'time_to_first_job' => $employedAfterGraduation === true
                ? SystemOptions::clean($validated['time_to_first_job'] ?? null)
                : null,
            'time_to_first_job_other' => $employedAfterGraduation === true
                && ($validated['time_to_first_job'] ?? null) === 'Other'
                    ? SystemOptions::clean($validated['time_to_first_job_other'] ?? null)
                    : null,
            'first_job_related_to_course' => $employedAfterGraduation === true
                ? SystemOptions::clean($validated['first_job_related_to_course'] ?? null)
                : null,
            'first_job_finding_method' => $employedAfterGraduation === true
                ? SystemOptions::clean($validated['first_job_finding_method'] ?? null)
                : null,
            'first_job_finding_method_other' => $employedAfterGraduation === true
                && ($validated['first_job_finding_method'] ?? null) === 'Other'
                    ? SystemOptions::clean($validated['first_job_finding_method_other'] ?? null)
                    : null,
            'first_job_monthly_earnings' => $employedAfterGraduation === true
                ? SystemOptions::firstJobEarningsLabel($validated['first_job_monthly_earnings'] ?? null)
                : null,
            'first_job_duration' => $employedAfterGraduation === true
                ? SystemOptions::clean($validated['first_job_duration'] ?? null)
                : null,
            'first_job_duration_other' => $employedAfterGraduation === true
                && ($validated['first_job_duration'] ?? null) === 'Other'
                    ? SystemOptions::clean($validated['first_job_duration_other'] ?? null)
                    : null,
            'presently_employed' => $presentlyEmployed,
            'present_employment_status' => $hasPresentEmploymentDetails
                ? SystemOptions::clean($validated['present_employment_status'] ?? null)
                : null,
            'direct_supervisor_name' => $hasPresentEmploymentDetails
                ? SystemOptions::clean($validated['direct_supervisor_name'] ?? null)
                : null,
            'employer_email' => $hasPresentEmploymentDetails
                ? SystemOptions::clean($validated['employer_email'] ?? null)
                : null,
            'is_first_job_after_college' => $hasPresentEmploymentDetails && $request->has('is_first_job_after_college')
                ? $request->boolean('is_first_job_after_college')
                : null,
            ...$employmentProofData,
            'job_level' => $hasPresentEmploymentDetails
                ? SystemOptions::clean($validated['job_level'] ?? null)
                : null,
            'job_level_other' => $hasPresentEmploymentDetails
                && ($validated['job_level'] ?? null) === 'Other'
                    ? SystemOptions::clean($validated['job_level_other'] ?? null)
                    : null,
            'present_job_acceptance_reasons' => $hasPresentEmploymentDetails
                ? $this->normalizedSelections((array) ($validated['present_job_acceptance_reasons'] ?? []))
                : [],
            'present_job_acceptance_other' => $hasPresentEmploymentDetails
                && in_array('Other', (array) ($validated['present_job_acceptance_reasons'] ?? []), true)
                    ? SystemOptions::clean($validated['present_job_acceptance_other'] ?? null)
                    : null,
            'present_job_monthly_earnings' => $hasPresentEmploymentDetails
                ? SystemOptions::presentJobEarningsLabel($validated['present_job_monthly_earnings'] ?? null)
                : null,
            'has_employee_awards' => $hasEmployeeAwards,
            'employee_awards' => $hasEmployeeAwards
                ? SystemOptions::clean($validated['employee_awards'] ?? null)
                : null,
            'teaching_faculty_enthusiastic' => $validated['teaching_faculty_enthusiastic'] ?? null,
            'teaching_concepts_clear' => $validated['teaching_concepts_clear'] ?? null,
            'teaching_faculty_knowledgeable' => $validated['teaching_faculty_knowledgeable'] ?? null,
            'teaching_commitment' => $validated['teaching_commitment'] ?? null,
            'degree_program_quality' => $validated['degree_program_quality'] ?? null,
            'has_curriculum_suggestions' => $hasCurriculumSuggestions,
            'curriculum_suggestions' => $hasCurriculumSuggestions
                ? SystemOptions::clean($validated['curriculum_suggestions'] ?? null)
                : null,
            'has_employability_suggestions' => $hasEmployabilitySuggestions,
            'employability_suggestions' => $hasEmployabilitySuggestions
                ? SystemOptions::clean($validated['employability_suggestions'] ?? null)
                : null,
            'facilities_satisfaction' => SystemOptions::clean($validated['facilities_satisfaction'] ?? null),
            'has_facility_suggestions' => $hasFacilitySuggestions,
            'facility_suggestions' => $hasFacilitySuggestions
                ? SystemOptions::clean($validated['facility_suggestions'] ?? null)
                : null,
            'overall_recommend_institution' => $validated['overall_recommend_institution'] ?? null,
            'overall_objectives_met' => $validated['overall_objectives_met'] ?? null,
            'overall_university_experience' => $validated['overall_university_experience'] ?? null,
            'further_education' => (bool) ($validated['further_education'] ?? false),
            'education_level' => (bool) ($validated['further_education'] ?? false)
                ? SystemOptions::canonical($validated['education_level'] ?? null, SystemOptions::educationLevels())
                : null,
            'challenges_faced' => SystemOptions::clean($validated['challenges_faced'] ?? null),
            'recommendations' => SystemOptions::clean($validated['recommendations'] ?? null),
            'contact_permission' => true,
        ];

        if ($comparisonRecord && $this->tracerPayloadMatchesRecord($payload, $comparisonRecord)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'You did not change anything yet.',
                ], 422);
            }

            $redirect = $sourceRecord
                ? redirect()->route('tracer.index', ['edit' => $sourceRecord->id])
                : redirect()->route('tracer.index');

            if ($sourceRecord) {
                $redirect->with('tracer_skip_autoscroll', true);
            }

            return $redirect->with('warning', 'You did not change anything yet.');
        }

        if ($sourceRecord) {
            $record = TracerRecord::query()->create([
                'user_id' => $user->id,
                ...$payload,
            ]);
        } else {
            $record = TracerRecord::query()->updateOrCreate(
                [
                    'user_id' => $user->id,
                    'tracer_date' => $tracerDate,
                ],
                $payload
            );
        }

        $summaryRecord = $record->fresh() ?? $record;
        $this->syncUserProfileSummary($user, $summaryRecord);

        if ($uploadedEmploymentProof && $summaryRecord->hasEmploymentProof()) {
            app(EmploymentProofReviewNotificationService::class)->notifyPendingReview($user, $summaryRecord);
        }

        $auditAction = $sourceRecord
            ? 'alumni.tracer.revise'
            : ($record->wasRecentlyCreated ? 'alumni.tracer.create' : 'alumni.tracer.update');
        app(AuditLogService::class)->log(
            (string) $user->id,
            $auditAction,
            sprintf(
                'Saved tracer record %s for %s. Employment type: %s.',
                $summaryRecord->id,
                $summaryRecord->tracer_date?->format('M j, Y') ?? $tracerDate,
                $summaryRecord->employment_type ?: 'N/A'
            ),
            $request
        );

        if (Schema::hasTable('tracer_drafts')) {
            DB::table('tracer_drafts')->where('user_id', $user->id)->delete();
        }

        $message = $uploadedEmploymentProof
            ? 'Saved. Your employment proof PDF is pending admin review before your account displays as employed.'
            : 'Saved. This is now your latest tracer record.';

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'page' => view('tracer', $this->buildTracerPageData($user))->render(),
            ]);
        }

        return redirect()
            ->route('tracer.index')
            ->with('success', $message);
    }

    public function downloadEmploymentProof(Request $request, TracerRecord $record)
    {
        $user = $request->user();
        abort_if(!$user, 403);
        abort_if((string) $record->user_id !== (string) $user->id, 404, 'Employment proof not found.');
        abort_if(! $record->employmentProofIsVerified(), 404, 'Employment proof not verified yet.');

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

    protected function syncUserProfileSummary(User $user, TracerRecord $record): void
    {
        $employmentType = SystemOptions::clean($record->employment_type);
        $isEmployed = $this->employmentFlag($employmentType);
        $hasVerifiedEmploymentProof = $isEmployed === true && $record->employmentProofIsVerified();

        $user->update([
            'employed_after_grad' => match (true) {
                $isEmployed === null => null,
                $isEmployed === false => 'No',
                $hasVerifiedEmploymentProof => 'Yes',
                default => null,
            },
            'industry' => $hasVerifiedEmploymentProof ? SystemOptions::clean($record->industry) : null,
            'current_position' => $hasVerifiedEmploymentProof ? SystemOptions::clean($record->current_position) : null,
            'company' => $hasVerifiedEmploymentProof ? SystemOptions::clean($record->current_company) : null,
            'salary_range' => $hasVerifiedEmploymentProof
                ? (SystemOptions::clean($record->present_job_monthly_earnings) ?? SystemOptions::salaryRangeForIncome($record->monthly_income))
                : null,
        ]);
    }

    protected function employmentFlag(?string $employmentType): ?bool
    {
        if ($employmentType === null) {
            return null;
        }

        return mb_strtolower($employmentType) !== 'unemployed';
    }

    protected function employmentText(?string $employmentType, ?string $value): ?string
    {
        if ($this->employmentFlag($employmentType) === false) {
            return null;
        }

        return SystemOptions::clean($value);
    }

    protected function employmentIncome(?string $employmentType, mixed $value): ?string
    {
        if ($this->employmentFlag($employmentType) === false || $value === null || $value === '') {
            return null;
        }

        return SystemOptions::presentJobEarningsRangeForIncome($value);
    }

    protected function tracerPayloadMatchesRecord(array $payload, TracerRecord $record): bool
    {
        $normalizedPayload = $this->normalizedTracerPayload($payload);
        $normalizedRecord = $this->normalizedTracerRecord($record);

        unset($normalizedPayload['tracer_date'], $normalizedRecord['tracer_date']);

        return $normalizedPayload === $normalizedRecord;
    }

    protected function normalizedTracerPayload(array $payload): array
    {
        return [
            'tracer_date' => $payload['tracer_date'] ?? null,
            'is_employed' => $payload['is_employed'] ?? null,
            'employment_type' => SystemOptions::clean($payload['employment_type'] ?? null),
            'current_position' => SystemOptions::clean($payload['current_position'] ?? null),
            'current_company' => SystemOptions::clean($payload['current_company'] ?? null),
            'monthly_income' => $this->normalizedTracerIncome($payload['monthly_income'] ?? null),
            'industry' => SystemOptions::clean($payload['industry'] ?? null),
            'job_satisfaction' => isset($payload['job_satisfaction']) ? (int) $payload['job_satisfaction'] : null,
            'skills_used' => SystemOptions::clean($payload['skills_used'] ?? null),
            'curriculum_relevant_to_first_job' => $payload['curriculum_relevant_to_first_job'] ?? null,
            'first_job_useful_competencies' => $this->normalizedTracerSelectionArray($payload['first_job_useful_competencies'] ?? []),
            'first_job_useful_competencies_other' => SystemOptions::clean($payload['first_job_useful_competencies_other'] ?? null),
            'curriculum_not_relevant_reason' => SystemOptions::clean($payload['curriculum_not_relevant_reason'] ?? null),
            'curriculum_not_relevant_reason_other' => SystemOptions::clean($payload['curriculum_not_relevant_reason_other'] ?? null),
            'employed_after_graduation' => $payload['employed_after_graduation'] ?? null,
            'time_to_first_job' => SystemOptions::clean($payload['time_to_first_job'] ?? null),
            'time_to_first_job_other' => SystemOptions::clean($payload['time_to_first_job_other'] ?? null),
            'first_job_related_to_course' => SystemOptions::clean($payload['first_job_related_to_course'] ?? null),
            'first_job_finding_method' => SystemOptions::clean($payload['first_job_finding_method'] ?? null),
            'first_job_finding_method_other' => SystemOptions::clean($payload['first_job_finding_method_other'] ?? null),
            'first_job_monthly_earnings' => SystemOptions::firstJobEarningsLabel($payload['first_job_monthly_earnings'] ?? null),
            'first_job_duration' => SystemOptions::clean($payload['first_job_duration'] ?? null),
            'first_job_duration_other' => SystemOptions::clean($payload['first_job_duration_other'] ?? null),
            'presently_employed' => SystemOptions::clean($payload['presently_employed'] ?? null),
            'present_employment_status' => SystemOptions::clean($payload['present_employment_status'] ?? null),
            'direct_supervisor_name' => SystemOptions::clean($payload['direct_supervisor_name'] ?? null),
            'employer_email' => SystemOptions::clean($payload['employer_email'] ?? null),
            'is_first_job_after_college' => $payload['is_first_job_after_college'] ?? null,
            'employment_proof_disk' => SystemOptions::clean($payload['employment_proof_disk'] ?? null),
            'employment_proof_path' => SystemOptions::clean($payload['employment_proof_path'] ?? null),
            'employment_proof_original_name' => SystemOptions::clean($payload['employment_proof_original_name'] ?? null),
            'employment_proof_mime_type' => SystemOptions::clean($payload['employment_proof_mime_type'] ?? null),
            'employment_proof_size_bytes' => isset($payload['employment_proof_size_bytes']) ? (int) $payload['employment_proof_size_bytes'] : null,
            'employment_proof_uploaded_at' => isset($payload['employment_proof_uploaded_at']) && $payload['employment_proof_uploaded_at'] !== null
                ? \Illuminate\Support\Carbon::parse($payload['employment_proof_uploaded_at'])->toISOString()
                : null,
            'job_level' => SystemOptions::clean($payload['job_level'] ?? null),
            'job_level_other' => SystemOptions::clean($payload['job_level_other'] ?? null),
            'present_job_acceptance_reasons' => $this->normalizedTracerSelectionArray($payload['present_job_acceptance_reasons'] ?? []),
            'present_job_acceptance_other' => SystemOptions::clean($payload['present_job_acceptance_other'] ?? null),
            'present_job_monthly_earnings' => SystemOptions::presentJobEarningsLabel($payload['present_job_monthly_earnings'] ?? null),
            'has_employee_awards' => $payload['has_employee_awards'] ?? null,
            'employee_awards' => SystemOptions::clean($payload['employee_awards'] ?? null),
            'teaching_faculty_enthusiastic' => isset($payload['teaching_faculty_enthusiastic']) ? (int) $payload['teaching_faculty_enthusiastic'] : null,
            'teaching_concepts_clear' => isset($payload['teaching_concepts_clear']) ? (int) $payload['teaching_concepts_clear'] : null,
            'teaching_faculty_knowledgeable' => isset($payload['teaching_faculty_knowledgeable']) ? (int) $payload['teaching_faculty_knowledgeable'] : null,
            'teaching_commitment' => isset($payload['teaching_commitment']) ? (int) $payload['teaching_commitment'] : null,
            'degree_program_quality' => isset($payload['degree_program_quality']) ? (int) $payload['degree_program_quality'] : null,
            'has_curriculum_suggestions' => $payload['has_curriculum_suggestions'] ?? null,
            'curriculum_suggestions' => SystemOptions::clean($payload['curriculum_suggestions'] ?? null),
            'has_employability_suggestions' => $payload['has_employability_suggestions'] ?? null,
            'employability_suggestions' => SystemOptions::clean($payload['employability_suggestions'] ?? null),
            'facilities_satisfaction' => SystemOptions::clean($payload['facilities_satisfaction'] ?? null),
            'has_facility_suggestions' => $payload['has_facility_suggestions'] ?? null,
            'facility_suggestions' => SystemOptions::clean($payload['facility_suggestions'] ?? null),
            'overall_recommend_institution' => isset($payload['overall_recommend_institution']) ? (int) $payload['overall_recommend_institution'] : null,
            'overall_objectives_met' => isset($payload['overall_objectives_met']) ? (int) $payload['overall_objectives_met'] : null,
            'overall_university_experience' => isset($payload['overall_university_experience']) ? (int) $payload['overall_university_experience'] : null,
            'further_education' => (bool) ($payload['further_education'] ?? false),
            'education_level' => SystemOptions::clean($payload['education_level'] ?? null),
            'challenges_faced' => SystemOptions::clean($payload['challenges_faced'] ?? null),
            'recommendations' => SystemOptions::clean($payload['recommendations'] ?? null),
            'contact_permission' => (bool) ($payload['contact_permission'] ?? false),
        ];
    }

    protected function normalizedTracerRecord(TracerRecord $record): array
    {
        return [
            'tracer_date' => $record->tracer_date?->toDateString(),
            'is_employed' => $record->is_employed,
            'employment_type' => SystemOptions::clean($record->employment_type),
            'current_position' => SystemOptions::clean($record->current_position),
            'current_company' => SystemOptions::clean($record->current_company),
            'monthly_income' => $this->normalizedTracerIncome($record->monthly_income),
            'industry' => SystemOptions::clean($record->industry),
            'job_satisfaction' => $record->job_satisfaction,
            'skills_used' => SystemOptions::clean($record->skills_used),
            'curriculum_relevant_to_first_job' => $record->curriculum_relevant_to_first_job,
            'first_job_useful_competencies' => $this->normalizedTracerSelectionArray($record->first_job_useful_competencies ?? []),
            'first_job_useful_competencies_other' => SystemOptions::clean($record->first_job_useful_competencies_other),
            'curriculum_not_relevant_reason' => SystemOptions::clean($record->curriculum_not_relevant_reason),
            'curriculum_not_relevant_reason_other' => SystemOptions::clean($record->curriculum_not_relevant_reason_other),
            'employed_after_graduation' => $record->employed_after_graduation,
            'time_to_first_job' => SystemOptions::clean($record->time_to_first_job),
            'time_to_first_job_other' => SystemOptions::clean($record->time_to_first_job_other),
            'first_job_related_to_course' => SystemOptions::clean($record->first_job_related_to_course),
            'first_job_finding_method' => SystemOptions::clean($record->first_job_finding_method),
            'first_job_finding_method_other' => SystemOptions::clean($record->first_job_finding_method_other),
            'first_job_monthly_earnings' => SystemOptions::firstJobEarningsLabel($record->first_job_monthly_earnings),
            'first_job_duration' => SystemOptions::clean($record->first_job_duration),
            'first_job_duration_other' => SystemOptions::clean($record->first_job_duration_other),
            'presently_employed' => SystemOptions::clean($record->presently_employed),
            'present_employment_status' => SystemOptions::clean($record->present_employment_status),
            'direct_supervisor_name' => SystemOptions::clean($record->direct_supervisor_name),
            'employer_email' => SystemOptions::clean($record->employer_email),
            'is_first_job_after_college' => $record->is_first_job_after_college,
            'employment_proof_disk' => SystemOptions::clean($record->employment_proof_disk),
            'employment_proof_path' => SystemOptions::clean($record->employment_proof_path),
            'employment_proof_original_name' => SystemOptions::clean($record->employment_proof_original_name),
            'employment_proof_mime_type' => SystemOptions::clean($record->employment_proof_mime_type),
            'employment_proof_size_bytes' => $record->employment_proof_size_bytes === null ? null : (int) $record->employment_proof_size_bytes,
            'employment_proof_uploaded_at' => $record->employment_proof_uploaded_at?->toISOString(),
            'job_level' => SystemOptions::clean($record->job_level),
            'job_level_other' => SystemOptions::clean($record->job_level_other),
            'present_job_acceptance_reasons' => $this->normalizedTracerSelectionArray($record->present_job_acceptance_reasons ?? []),
            'present_job_acceptance_other' => SystemOptions::clean($record->present_job_acceptance_other),
            'present_job_monthly_earnings' => SystemOptions::presentJobEarningsLabel($record->present_job_monthly_earnings),
            'has_employee_awards' => $record->has_employee_awards,
            'employee_awards' => SystemOptions::clean($record->employee_awards),
            'teaching_faculty_enthusiastic' => $record->teaching_faculty_enthusiastic,
            'teaching_concepts_clear' => $record->teaching_concepts_clear,
            'teaching_faculty_knowledgeable' => $record->teaching_faculty_knowledgeable,
            'teaching_commitment' => $record->teaching_commitment,
            'degree_program_quality' => $record->degree_program_quality,
            'has_curriculum_suggestions' => $record->has_curriculum_suggestions,
            'curriculum_suggestions' => SystemOptions::clean($record->curriculum_suggestions),
            'has_employability_suggestions' => $record->has_employability_suggestions,
            'employability_suggestions' => SystemOptions::clean($record->employability_suggestions),
            'facilities_satisfaction' => SystemOptions::clean($record->facilities_satisfaction),
            'has_facility_suggestions' => $record->has_facility_suggestions,
            'facility_suggestions' => SystemOptions::clean($record->facility_suggestions),
            'overall_recommend_institution' => $record->overall_recommend_institution,
            'overall_objectives_met' => $record->overall_objectives_met,
            'overall_university_experience' => $record->overall_university_experience,
            'further_education' => (bool) $record->further_education,
            'education_level' => SystemOptions::clean($record->education_level),
            'challenges_faced' => SystemOptions::clean($record->challenges_faced),
            'recommendations' => SystemOptions::clean($record->recommendations),
            'contact_permission' => (bool) $record->contact_permission,
        ];
    }

    protected function normalizedTracerIncome(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return SystemOptions::presentJobEarningsRangeForIncome($value);
    }

    protected function buildTracerPageData(
        User $user,
        ?string $editRecordId = null,
        bool $suppressEditAutoscroll = false
    ): array {
        $rawTracerHistory = $user->tracerRecords()->orderByDesc('tracer_date')->orderByDesc('created_at')->get();
        $visibleTracerHistory = TracerHistoryPresenter::visible($rawTracerHistory);
        $presentedTracerHistory = TracerHistoryPresenter::present($visibleTracerHistory);
        $latestTracer = $this->latestSavedTracerFor($user);
        $editingTracer = null;

        if ($editRecordId) {
            $editingTracer = $rawTracerHistory->firstWhere('id', $editRecordId);
            abort_if(!$editingTracer, 404, 'Tracer record not found');
        }

        $formTracer = $editingTracer ?? $latestTracer;

        return [
            'latestTracer' => $latestTracer,
            'editingTracer' => $editingTracer,
            'formTracer' => $formTracer,
            'suppressEditAutoscroll' => $suppressEditAutoscroll,
            'tracerHistory' => $presentedTracerHistory
                ->take(self::TRACER_HISTORY_DISPLAY_LIMIT)
                ->values(),
            'tracerHistoryTotal' => $presentedTracerHistory->count(),
            'tracerHistoryDisplayLimit' => self::TRACER_HISTORY_DISPLAY_LIMIT,
            'employmentTypes' => SystemOptions::employmentTypes(),
            'industryOptions' => SystemOptions::industries(),
            'curriculumCompetencies' => SystemOptions::curriculumCompetencies(),
            'curriculumReasonOptions' => SystemOptions::curriculumNotRelevantReasons(),
            'firstJobTimeOptions' => SystemOptions::firstJobTimeOptions(),
            'firstJobRelatedOptions' => SystemOptions::firstJobRelatedOptions(),
            'firstJobFindingOptions' => SystemOptions::firstJobFindingMethods(),
            'firstJobEarningsOptions' => SystemOptions::firstJobEarningsOptions(),
            'firstJobDurationOptions' => SystemOptions::firstJobDurationOptions(),
            'presentEmploymentStatuses' => SystemOptions::presentEmploymentStatuses(),
            'businessLineOptions' => SystemOptions::businessLines(),
            'jobLevelOptions' => SystemOptions::jobLevels(),
            'presentJobReasonOptions' => SystemOptions::presentJobAcceptanceReasons(),
            'presentJobEarningsOptions' => SystemOptions::presentJobEarningsOptions(),
            'facilitiesSatisfactionOptions' => SystemOptions::facilitiesSatisfactionOptions(),
            'agreementScaleOptions' => SystemOptions::agreementScaleOptions(),
            'educationLevels' => SystemOptions::educationLevels(),
            'jobSatisfactionOptions' => [
                5 => '5 - Very Satisfied',
                4 => '4 - Satisfied',
                3 => '3 - Neutral',
                2 => '2 - Dissatisfied',
                1 => '1 - Very Dissatisfied',
            ],
        ];
    }

    protected function latestSavedTracerFor(User $user): ?TracerRecord
    {
        return $user->tracerRecords()
            ->orderByDesc('updated_at')
            ->orderByDesc('created_at')
            ->orderByDesc('tracer_date')
            ->first();
    }

    protected function normalizedSelections(array $values): array
    {
        return collect($values)
            ->map(fn ($value) => SystemOptions::clean(is_string($value) ? $value : null))
            ->filter()
            ->unique(fn (?string $value) => mb_strtolower((string) $value))
            ->values()
            ->all();
    }

    protected function normalizedTracerSelectionArray(array $values): array
    {
        return collect($this->normalizedSelections($values))
            ->sort(fn ($left, $right) => strcmp(mb_strtolower($left), mb_strtolower($right)))
            ->values()
            ->all();
    }

    protected function presentlyEmployedState(?string $value, ?string $employmentType): ?string
    {
        $cleanValue = SystemOptions::clean($value);

        if ($cleanValue !== null) {
            return $cleanValue;
        }

        $flag = $this->employmentFlag($employmentType);

        if ($flag === null) {
            return null;
        }

        return $flag ? 'Yes' : 'No';
    }

    protected function employmentProofPayload(Request $request, ?TracerRecord $baseRecord, ?string $presentlyEmployed): array
    {
        if ($presentlyEmployed !== 'Yes') {
            return EmploymentProofStorage::emptyPayload();
        }

        if ($request->hasFile('employment_proof')) {
            return EmploymentProofStorage::storeUploadedFile($request->file('employment_proof'));
        }

        return EmploymentProofStorage::payloadFromRecord($baseRecord);
    }
}
