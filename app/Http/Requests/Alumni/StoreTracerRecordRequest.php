<?php

namespace App\Http\Requests\Alumni;

use App\Support\SystemOptions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTracerRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $requiresDataPrivacyConsent = !($this->user()?->data_privacy_consent ?? false);
        $requiresCurrentWorkDetails = fn () => $this->requiresCurrentWorkDetails();
        $curriculumRelevant = fn () => $this->booleanInput('curriculum_relevant_to_first_job') === true;
        $curriculumNotRelevant = fn () => $this->booleanInput('curriculum_relevant_to_first_job') === false;
        $employedAfterGraduation = fn () => $this->booleanInput('employed_after_graduation') === true;
        $presentlyEmployed = fn () => $this->input('presently_employed') === 'Yes';

        return [
            'record_id' => ['nullable', 'uuid'],
            'employment_type' => ['required', Rule::in(SystemOptions::employmentTypes())],
            'current_position' => [Rule::requiredIf($requiresCurrentWorkDetails), 'nullable', 'string', 'max:200'],
            'current_company' => [Rule::requiredIf($requiresCurrentWorkDetails), 'nullable', 'string', 'max:200'],
            'monthly_income' => [
                Rule::requiredIf($requiresCurrentWorkDetails),
                'nullable',
                'string',
                'max:100',
                Rule::in(SystemOptions::presentJobEarningsOptions()),
            ],
            'industry' => [Rule::requiredIf($requiresCurrentWorkDetails), 'nullable', 'string', 'max:100'],
            'job_satisfaction' => ['required', 'integer', 'between:1,5'],
            'skills_used' => ['required', 'string'],
            'curriculum_relevant_to_first_job' => ['required', 'boolean'],
            'first_job_useful_competencies' => [Rule::requiredIf($curriculumRelevant), 'array', 'min:1'],
            'first_job_useful_competencies.*' => ['nullable', 'string', 'max:100'],
            'first_job_useful_competencies_other' => ['nullable', 'string', 'max:200'],
            'curriculum_not_relevant_reason' => [Rule::requiredIf($curriculumNotRelevant), 'nullable', 'string', 'max:200'],
            'curriculum_not_relevant_reason_other' => ['nullable', 'string', 'max:200'],
            'employed_after_graduation' => ['required', 'boolean'],
            'time_to_first_job' => [Rule::requiredIf($employedAfterGraduation), 'nullable', 'string', 'max:100'],
            'time_to_first_job_other' => ['nullable', 'string', 'max:200'],
            'first_job_related_to_course' => [Rule::requiredIf($employedAfterGraduation), 'nullable', 'string', 'max:20', Rule::in(SystemOptions::firstJobRelatedOptions())],
            'first_job_finding_method' => [Rule::requiredIf($employedAfterGraduation), 'nullable', 'string', 'max:150'],
            'first_job_finding_method_other' => ['nullable', 'string', 'max:200'],
            'first_job_monthly_earnings' => [Rule::requiredIf($employedAfterGraduation), 'nullable', 'string', 'max:100'],
            'first_job_duration' => [Rule::requiredIf($employedAfterGraduation), 'nullable', 'string', 'max:100'],
            'first_job_duration_other' => ['nullable', 'string', 'max:200'],
            'presently_employed' => ['required', 'string', 'max:30', Rule::in(['Yes', 'No', 'Never Employed'])],
            'present_employment_status' => [Rule::requiredIf($presentlyEmployed), 'nullable', 'string', 'max:100'],
            'direct_supervisor_name' => ['nullable', 'string', 'max:200'],
            'employer_email' => ['nullable', 'email', 'max:200'],
            'is_first_job_after_college' => [Rule::requiredIf($presentlyEmployed), 'nullable', 'boolean'],
            'employment_proof' => ['nullable', 'file', 'mimes:pdf', 'mimetypes:application/pdf', 'max:10240'],
            'job_level' => [Rule::requiredIf($presentlyEmployed), 'nullable', 'string', 'max:100'],
            'job_level_other' => ['nullable', 'string', 'max:200'],
            'present_job_acceptance_reasons' => [Rule::requiredIf($presentlyEmployed), 'array', 'min:1'],
            'present_job_acceptance_reasons.*' => ['nullable', 'string', 'max:100'],
            'present_job_acceptance_other' => ['nullable', 'string', 'max:200'],
            'present_job_monthly_earnings' => [Rule::requiredIf($presentlyEmployed), 'nullable', 'string', 'max:100'],
            'has_employee_awards' => [Rule::requiredIf($presentlyEmployed), 'nullable', 'boolean'],
            'employee_awards' => ['nullable', 'string'],
            'teaching_faculty_enthusiastic' => ['required', 'integer', 'between:1,5'],
            'teaching_concepts_clear' => ['required', 'integer', 'between:1,5'],
            'teaching_faculty_knowledgeable' => ['required', 'integer', 'between:1,5'],
            'teaching_commitment' => ['required', 'integer', 'between:1,5'],
            'degree_program_quality' => ['required', 'integer', 'between:1,5'],
            'has_curriculum_suggestions' => ['required', 'boolean'],
            'curriculum_suggestions' => ['nullable', 'string'],
            'has_employability_suggestions' => ['required', 'boolean'],
            'employability_suggestions' => ['nullable', 'string'],
            'facilities_satisfaction' => ['required', 'string', 'max:50', Rule::in(SystemOptions::facilitiesSatisfactionOptions())],
            'has_facility_suggestions' => ['required', 'boolean'],
            'facility_suggestions' => ['nullable', 'string'],
            'overall_recommend_institution' => ['required', 'integer', 'between:1,5'],
            'overall_objectives_met' => ['required', 'integer', 'between:1,5'],
            'overall_university_experience' => ['required', 'integer', 'between:1,5'],
            'further_education' => ['required', 'boolean'],
            'education_level' => ['nullable', 'string', 'max:100', Rule::in(SystemOptions::educationLevels())],
            'challenges_faced' => ['required', 'string'],
            'recommendations' => ['required', 'string'],
            'contact_permission' => ['required', 'boolean'],
            'information_attestation' => ['accepted'],
            'data_privacy_consent' => $requiresDataPrivacyConsent ? ['accepted'] : ['nullable'],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Please answer :attribute. This question is required for alumni analytics.',
            'first_job_useful_competencies.min' => 'Please choose at least one useful competency for alumni analytics.',
            'present_job_acceptance_reasons.min' => 'Please choose at least one reason for accepting the present job.',
            'information_attestation.accepted' => 'Please confirm that the information is true and accurate before saving your tracer record.',
            'data_privacy_consent.accepted' => 'Please agree to the Data Privacy Consent before saving your tracer record.',
            'employment_proof.mimes' => 'Please upload the employment proof as a PDF file.',
            'employment_proof.mimetypes' => 'Please upload the employment proof as a PDF file.',
        ];
    }

    public function attributes(): array
    {
        return [
            'employment_type' => 'Employment Type',
            'current_position' => 'Current Position',
            'current_company' => 'Current Company / Business',
            'monthly_income' => 'Monthly Income Range',
            'industry' => 'Industry',
            'job_satisfaction' => 'Job Satisfaction',
            'skills_used' => 'Skills Used in Work',
            'curriculum_relevant_to_first_job' => 'whether your college curriculum was relevant to your first job',
            'first_job_useful_competencies' => 'Useful Competencies in your first job',
            'curriculum_not_relevant_reason' => 'Reason if the curriculum was not relevant',
            'employed_after_graduation' => 'whether you were employed after graduation',
            'time_to_first_job' => 'Time to First Job',
            'first_job_related_to_course' => 'whether your first job was related to your course',
            'first_job_finding_method' => 'How You Found Your First Job',
            'first_job_monthly_earnings' => 'First Job Monthly Earnings',
            'first_job_duration' => 'First Job Duration',
            'presently_employed' => 'whether you are presently employed',
            'present_employment_status' => 'Present Employment Status',
            'is_first_job_after_college' => 'whether this is your first job after college',
            'employment_proof' => 'employment proof PDF',
            'job_level' => 'Job Level Position',
            'present_job_acceptance_reasons' => 'Reasons for Accepting the Present Job',
            'present_job_monthly_earnings' => 'Present Job Monthly Earnings',
            'has_employee_awards' => 'whether you received employee awards',
            'teaching_faculty_enthusiastic' => 'Faculty Enthusiasm Rating',
            'teaching_concepts_clear' => 'Faculty Concept Explanation Rating',
            'teaching_faculty_knowledgeable' => 'Faculty Knowledge Rating',
            'teaching_commitment' => 'Teaching Commitment Rating',
            'degree_program_quality' => 'Degree Program Quality Rating',
            'has_curriculum_suggestions' => 'whether you have curriculum suggestions',
            'has_employability_suggestions' => 'whether you have employability suggestions',
            'facilities_satisfaction' => 'Facilities Satisfaction',
            'has_facility_suggestions' => 'whether you have facility suggestions',
            'overall_recommend_institution' => 'Institution Recommendation Rating',
            'overall_objectives_met' => 'Objectives Met Rating',
            'overall_university_experience' => 'University Experience Rating',
            'education_level' => 'Education Level',
            'challenges_faced' => 'Challenges Faced',
            'recommendations' => 'Recommendations',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->sometimes('education_level', ['required'], function ($input) {
            return (bool) ($input->further_education ?? false);
        });

        $validator->after(function ($validator) {
            $competencies = array_filter((array) $this->input('first_job_useful_competencies', []));
            $reasons = array_filter((array) $this->input('present_job_acceptance_reasons', []));

            if (
                $this->boolean('curriculum_relevant_to_first_job')
                && in_array('Other', $competencies, true)
                && blank($this->input('first_job_useful_competencies_other'))
            ) {
                $validator->errors()->add('first_job_useful_competencies_other', 'Please specify the other useful competency.');
            }

            if (
                $this->input('curriculum_not_relevant_reason') === 'Other'
                && blank($this->input('curriculum_not_relevant_reason_other'))
            ) {
                $validator->errors()->add('curriculum_not_relevant_reason_other', 'Please specify the other reason.');
            }

            if ($this->input('time_to_first_job') === 'Other' && blank($this->input('time_to_first_job_other'))) {
                $validator->errors()->add('time_to_first_job_other', 'Please specify the time to first job.');
            }

            if (
                $this->input('first_job_finding_method') === 'Other'
                && blank($this->input('first_job_finding_method_other'))
            ) {
                $validator->errors()->add('first_job_finding_method_other', 'Please specify how you found your first job.');
            }

            if ($this->input('first_job_duration') === 'Other' && blank($this->input('first_job_duration_other'))) {
                $validator->errors()->add('first_job_duration_other', 'Please specify how long you stayed in your first job.');
            }

            if ($this->input('job_level') === 'Other' && blank($this->input('job_level_other'))) {
                $validator->errors()->add('job_level_other', 'Please specify the other job level.');
            }

            if (in_array('Other', $reasons, true) && blank($this->input('present_job_acceptance_other'))) {
                $validator->errors()->add('present_job_acceptance_other', 'Please specify the other reason for accepting the present job.');
            }

            if ($this->boolean('has_employee_awards') && blank($this->input('employee_awards'))) {
                $validator->errors()->add('employee_awards', 'Please list the awards received as an employee.');
            }

            if ($this->boolean('has_curriculum_suggestions') && blank($this->input('curriculum_suggestions'))) {
                $validator->errors()->add('curriculum_suggestions', 'Please enter your curriculum suggestion.');
            }

            if ($this->boolean('has_employability_suggestions') && blank($this->input('employability_suggestions'))) {
                $validator->errors()->add('employability_suggestions', 'Please enter your employability suggestion.');
            }

            if ($this->boolean('has_facility_suggestions') && blank($this->input('facility_suggestions'))) {
                $validator->errors()->add('facility_suggestions', 'Please enter your facility suggestion.');
            }
        });
    }

    protected function requiresCurrentWorkDetails(): bool
    {
        $employmentType = trim((string) $this->input('employment_type', ''));

        return $employmentType !== '' && mb_strtolower($employmentType) !== 'unemployed';
    }

    protected function booleanInput(string $key): ?bool
    {
        if (!$this->has($key) || $this->input($key) === '') {
            return null;
        }

        return filter_var($this->input($key), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    }
}
