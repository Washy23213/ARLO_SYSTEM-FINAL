<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TracerRecord extends Model
{
    use HasUuids;

    public const EMPLOYMENT_PROOF_STATUS_PENDING = 'pending';
    public const EMPLOYMENT_PROOF_STATUS_VERIFIED = 'verified';
    public const EMPLOYMENT_PROOF_STATUS_REJECTED = 'rejected';

    public const EMPLOYMENT_PROOF_STATUSES = [
        self::EMPLOYMENT_PROOF_STATUS_PENDING,
        self::EMPLOYMENT_PROOF_STATUS_VERIFIED,
        self::EMPLOYMENT_PROOF_STATUS_REJECTED,
    ];

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'tracer_number',
        'user_id',
        'tracer_date',
        'is_employed',
        'employment_type',
        'current_position',
        'current_company',
        'monthly_income',
        'industry',
        'job_satisfaction',
        'skills_used',
        'curriculum_relevant_to_first_job',
        'first_job_useful_competencies',
        'first_job_useful_competencies_other',
        'curriculum_not_relevant_reason',
        'curriculum_not_relevant_reason_other',
        'employed_after_graduation',
        'time_to_first_job',
        'time_to_first_job_other',
        'first_job_related_to_course',
        'first_job_finding_method',
        'first_job_finding_method_other',
        'first_job_monthly_earnings',
        'first_job_duration',
        'first_job_duration_other',
        'presently_employed',
        'present_employment_status',
        'direct_supervisor_name',
        'employer_email',
        'is_first_job_after_college',
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
        'job_level',
        'job_level_other',
        'present_job_acceptance_reasons',
        'present_job_acceptance_other',
        'present_job_monthly_earnings',
        'has_employee_awards',
        'employee_awards',
        'teaching_faculty_enthusiastic',
        'teaching_concepts_clear',
        'teaching_faculty_knowledgeable',
        'teaching_commitment',
        'degree_program_quality',
        'has_curriculum_suggestions',
        'curriculum_suggestions',
        'has_employability_suggestions',
        'employability_suggestions',
        'facilities_satisfaction',
        'has_facility_suggestions',
        'facility_suggestions',
        'overall_recommend_institution',
        'overall_objectives_met',
        'overall_university_experience',
        'further_education',
        'education_level',
        'challenges_faced',
        'recommendations',
        'contact_permission',
    ];

    protected function casts(): array
    {
        return [
            'tracer_date' => 'date',
            'is_employed' => 'boolean',
            'job_satisfaction' => 'integer',
            'curriculum_relevant_to_first_job' => 'boolean',
            'first_job_useful_competencies' => 'array',
            'employed_after_graduation' => 'boolean',
            'is_first_job_after_college' => 'boolean',
            'employment_proof_size_bytes' => 'integer',
            'employment_proof_uploaded_at' => 'datetime',
            'employment_proof_reviewed_at' => 'datetime',
            'present_job_acceptance_reasons' => 'array',
            'has_employee_awards' => 'boolean',
            'teaching_faculty_enthusiastic' => 'integer',
            'teaching_concepts_clear' => 'integer',
            'teaching_faculty_knowledgeable' => 'integer',
            'teaching_commitment' => 'integer',
            'degree_program_quality' => 'integer',
            'has_curriculum_suggestions' => 'boolean',
            'has_employability_suggestions' => 'boolean',
            'has_facility_suggestions' => 'boolean',
            'overall_recommend_institution' => 'integer',
            'overall_objectives_met' => 'integer',
            'overall_university_experience' => 'integer',
            'further_education' => 'boolean',
            'contact_permission' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (TracerRecord $record) {
            if (
                filled($record->tracer_number)
                || ! Schema::hasTable('tracer_records')
                || ! Schema::hasColumn('tracer_records', 'tracer_number')
            ) {
                return;
            }

            $record->tracer_number = static::nextTracerNumber();
        });
    }

    public static function nextTracerNumber(): string
    {
        $highest = DB::table('tracer_records')
            ->whereNotNull('tracer_number')
            ->pluck('tracer_number')
            ->map(function ($value) {
                preg_match('/^UTF-(\d+)A$/i', (string) $value, $matches);

                return isset($matches[1]) ? (int) $matches[1] : 0;
            })
            ->max() ?? 0;

        do {
            $highest++;
            $candidate = static::formatTracerNumber($highest);
        } while (
            DB::table('tracer_records')
                ->where('tracer_number', $candidate)
                ->exists()
        );

        return $candidate;
    }

    public static function formatTracerNumber(int $number): string
    {
        return sprintf('UTF-%05dA', max(1, $number));
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function hasEmploymentProof(): bool
    {
        return filled($this->employment_proof_path);
    }

    public function employmentProofStatus(): ?string
    {
        $status = trim((string) ($this->employment_proof_status ?? ''));

        if (in_array($status, self::EMPLOYMENT_PROOF_STATUSES, true)) {
            return $status;
        }

        return $this->hasEmploymentProof() ? self::EMPLOYMENT_PROOF_STATUS_PENDING : null;
    }

    public function employmentProofIsPending(): bool
    {
        return $this->employmentProofStatus() === self::EMPLOYMENT_PROOF_STATUS_PENDING;
    }

    public function employmentProofIsVerified(): bool
    {
        return $this->employmentProofStatus() === self::EMPLOYMENT_PROOF_STATUS_VERIFIED;
    }

    public function employmentProofIsRejected(): bool
    {
        return $this->employmentProofStatus() === self::EMPLOYMENT_PROOF_STATUS_REJECTED;
    }

    public function employmentProofStatusLabel(): string
    {
        return match ($this->employmentProofStatus()) {
            self::EMPLOYMENT_PROOF_STATUS_VERIFIED => 'Verified',
            self::EMPLOYMENT_PROOF_STATUS_REJECTED => 'Rejected',
            self::EMPLOYMENT_PROOF_STATUS_PENDING => 'Pending Review',
            default => 'No Proof',
        };
    }
}
