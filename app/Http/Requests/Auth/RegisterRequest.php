<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'firstname' => ['required', 'string', 'max:255'],
            'middlename' => ['nullable', 'string', 'max:100', 'regex:/^[A-Za-z][A-Za-z\s.\'-]*$/'],
            'lastname' => ['required', 'string', 'max:255'],
            'studentIdnum' => ['nullable', 'string', 'max:100'],
            'birthdate' => ['required', 'date', 'before_or_equal:today'],
            'collegeGraduated' => ['required', 'string', 'max:255'],
            'degreeGraduated' => ['nullable', 'string', 'max:255'],
            'yearGraduated' => [
                'required',
                function ($attribute, $value, $fail) {
                    if ($value === 'N/A') {
                        return;
                    }

                    if (!is_numeric($value) || (string) (int) $value !== (string) $value) {
                        $fail('Year graduated must be a valid year.');
                    }
                },
            ],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'min:8', 'confirmed'],
            'newsletter_opt_in' => ['nullable', 'boolean'],
            'sex' => ['nullable', 'in:Male,Female'],
            'civilstatus' => ['nullable', 'in:Single,Married,Divorced,Widowed,Separated'],
            'hasUndergraduateDegree' => ['required', 'in:Yes,No'],
            'hasScholarship' => ['required', 'in:Yes,No'],
            'scholarshipName' => ['required_if:hasScholarship,Yes', 'nullable', 'string', 'max:255'],
            'scholarshipOther' => ['required_if:scholarshipName,Other', 'nullable', 'string', 'max:255'],
            'scholarshipYear' => ['required_if:hasScholarship,Yes', 'nullable', 'integer', 'min:1900', 'max:2100'],
            'scholarshipHelpedJob' => ['required_if:hasScholarship,Yes', 'nullable', 'in:Yes,No'],
            'country' => ['required', 'string', 'max:100'],
            'countryOther' => ['required_if:country,Other', 'nullable', 'string', 'max:100'],
            'regionOfOrigin' => ['required', 'string', 'max:150'],
            'municipalityCity' => ['required', 'string', 'max:150'],
            'department' => ['nullable', 'string', 'max:100'],
            'departmentOther' => ['required_if:department,Other', 'nullable', 'string', 'max:100'],
            'degree' => ['nullable', 'string', 'max:200'],
            'degreeOther' => ['required_if:degree,Other', 'nullable', 'string', 'max:200'],
            'curriculumRelevant' => ['required', 'in:Yes,No'],
            'firstJobCompetencies' => ['nullable', 'array', 'required_if:curriculumRelevant,Yes', 'min:1'],
            'firstJobCompetencies.*' => ['nullable', 'string', 'max:100'],
            'firstJobCompetenciesOther' => ['nullable', 'string', 'max:200'],
            'curriculumNotRelevantReason' => ['nullable', 'string', 'max:200', 'required_if:curriculumRelevant,No'],
            'curriculumNotRelevantReasonOther' => ['nullable', 'string', 'max:200'],
            'employedAfterGrad' => ['required', 'in:Yes,No'],
            'firstJobTime' => ['nullable', 'string', 'max:100', 'required_if:employedAfterGrad,Yes'],
            'firstJobTimeOther' => ['nullable', 'string', 'max:200'],
            'firstJobRelated' => ['nullable', 'string', 'max:20', 'required_if:employedAfterGrad,Yes', 'in:Yes,No,N/A'],
            'shareMoreFirstJobDetails' => ['nullable', 'in:Yes,No'],
            'firstJobHow' => ['nullable', 'string', 'max:150'],
            'firstJobHowOther' => ['nullable', 'string', 'max:200'],
            'firstJobEarnings' => ['nullable', 'string', 'max:100'],
            'firstJobDuration' => ['nullable', 'string', 'max:100'],
            'firstJobDurationOther' => ['nullable', 'string', 'max:200'],
            'presentlyEmployed' => ['required', 'in:Yes,No,Never Employed'],
            'presentEmploymentStatus' => ['nullable', 'string', 'max:100', 'required_if:presentlyEmployed,Yes'],
            'presentOccupation' => ['nullable', 'string', 'max:200', 'required_if:presentlyEmployed,Yes'],
            'presentCompany' => ['nullable', 'string', 'max:200', 'required_if:presentlyEmployed,Yes'],
            'directSupervisor' => ['nullable', 'string', 'max:200'],
            'employerEmail' => ['nullable', 'email', 'max:200'],
            'businessLine' => ['nullable', 'string', 'max:150', 'required_if:presentlyEmployed,Yes'],
            'businessLineOther' => ['nullable', 'string', 'max:200'],
            'shareMoreEmploymentDetails' => ['nullable', 'in:Yes,No'],
            'firstJobIsCurrent' => ['nullable', 'in:Yes,No'],
            'employmentProof' => ['nullable', 'file', 'mimes:pdf', 'mimetypes:application/pdf', 'max:10240'],
            'jobLevel' => ['nullable', 'string', 'max:100'],
            'jobLevelOther' => ['nullable', 'string', 'max:200'],
            'presentJobReasons' => ['nullable', 'array'],
            'presentJobReasons.*' => ['nullable', 'string', 'max:100'],
            'presentJobReasonsOther' => ['nullable', 'string', 'max:200'],
            'presentJobEarnings' => ['nullable', 'string', 'max:100', 'required_if:presentlyEmployed,Yes'],
            'hasEmployeeAwards' => ['nullable', 'in:Yes,No'],
            'employeeAwards' => ['nullable', 'string'],
            'teachingFeedbackSummary' => ['nullable', 'integer', 'between:1,5'],
            'teachingFacultyEnthusiastic' => ['nullable', 'integer', 'between:1,5'],
            'teachingConceptsClear' => ['nullable', 'integer', 'between:1,5'],
            'teachingKnowledgeable' => ['nullable', 'integer', 'between:1,5'],
            'teachingCommitment' => ['nullable', 'integer', 'between:1,5'],
            'degreeProgramQuality' => ['nullable', 'integer', 'between:1,5'],
            'curriculumSuggestions' => ['required', 'string'],
            'employabilitySuggestions' => ['required', 'string'],
            'facilitiesSatisfaction' => ['required', 'string', 'max:50', 'in:Very Satisfied,Satisfied,Not Satisfied'],
            'facilitySuggestions' => ['required', 'string'],
            'overallExperienceSummary' => ['nullable', 'integer', 'between:1,5'],
            'overallRecommendInstitution' => ['nullable', 'integer', 'between:1,5'],
            'overallObjectivesMet' => ['nullable', 'integer', 'between:1,5'],
            'overallUniversityExperience' => ['nullable', 'integer', 'between:1,5'],
            'data_privacy_consent' => ['accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'data_privacy_consent.accepted' => 'Please agree to the Data Privacy Consent before submitting your registration.',
            'curriculumSuggestions.required' => 'Please enter your course curriculum comments or suggestions.',
            'employabilitySuggestions.required' => 'Please enter your employability comments or suggestions.',
            'facilitySuggestions.required' => 'Please enter your university facilities comments or suggestions.',
            'middlename.regex' => 'Middle name may only contain letters, spaces, periods, apostrophes, and hyphens.',
            'sex.in' => 'Please select a valid sex.',
            'employmentProof.mimes' => 'Please upload the employment proof as a PDF file.',
            'employmentProof.mimetypes' => 'Please upload the employment proof as a PDF file.',
        ];
    }

    public function attributes(): array
    {
        return [
            'middlename' => 'middle name',
            'newsletter_opt_in' => 'newsletter preference',
            'sex' => 'sex',
            'employmentProof' => 'employment proof PDF',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (! $this->hasFullMiddleName($this->input('middlename'))) {
                $validator->errors()->add('middlename', 'Please enter your full middle name.');
            }

            $firstJobCompetencies = array_filter((array) $this->input('firstJobCompetencies', []));
            $presentJobReasons = array_filter((array) $this->input('presentJobReasons', []));
            $wantsMoreFirstJobDetails = $this->input('employedAfterGrad') === 'Yes'
                && (
                    $this->input('shareMoreFirstJobDetails') === 'Yes'
                    || filled($this->input('firstJobHow'))
                    || filled($this->input('firstJobEarnings'))
                    || filled($this->input('firstJobDuration'))
                );
            $wantsMoreEmploymentDetails = $this->input('presentlyEmployed') === 'Yes'
                && (
                    $this->input('shareMoreEmploymentDetails') === 'Yes'
                    || filled($this->input('directSupervisor'))
                    || filled($this->input('employerEmail'))
                    || filled($this->input('jobLevel'))
                    || filled($this->input('firstJobIsCurrent'))
                    || !empty($presentJobReasons)
                    || filled($this->input('hasEmployeeAwards'))
                    || filled($this->input('employeeAwards'))
                );
            $hasTeachingSummary = filled($this->input('teachingFeedbackSummary'));
            $hasFullTeachingBreakdown =
                filled($this->input('teachingFacultyEnthusiastic'))
                && filled($this->input('teachingConceptsClear'))
                && filled($this->input('teachingKnowledgeable'))
                && filled($this->input('teachingCommitment'))
                && filled($this->input('degreeProgramQuality'));
            $hasOverallSummary = filled($this->input('overallExperienceSummary'));
            $hasFullOverallBreakdown =
                filled($this->input('overallRecommendInstitution'))
                && filled($this->input('overallObjectivesMet'))
                && filled($this->input('overallUniversityExperience'));

            if (
                $this->input('curriculumRelevant') === 'Yes'
                && in_array('Other', $firstJobCompetencies, true)
                && blank($this->input('firstJobCompetenciesOther'))
            ) {
                $validator->errors()->add('firstJobCompetenciesOther', 'Please specify the other useful competency.');
            }

            if (
                $this->input('curriculumRelevant') === 'No'
                && $this->input('curriculumNotRelevantReason') === 'Other'
                && blank($this->input('curriculumNotRelevantReasonOther'))
            ) {
                $validator->errors()->add('curriculumNotRelevantReasonOther', 'Please specify the other reason.');
            }

            if (
                $wantsMoreFirstJobDetails
                && blank($this->input('firstJobHow'))
            ) {
                $validator->errors()->add('firstJobHow', 'Please choose how you found your first job.');
            }

            if (
                $wantsMoreFirstJobDetails
                && blank($this->input('firstJobEarnings'))
            ) {
                $validator->errors()->add('firstJobEarnings', 'Please choose your first job monthly earnings.');
            }

            if (
                $wantsMoreFirstJobDetails
                && blank($this->input('firstJobDuration'))
            ) {
                $validator->errors()->add('firstJobDuration', 'Please choose how long you stayed in your first job.');
            }

            if (
                $wantsMoreFirstJobDetails
                && $this->input('firstJobTime') === 'Other'
                && blank($this->input('firstJobTimeOther'))
            ) {
                $validator->errors()->add('firstJobTimeOther', 'Please specify the time to first job.');
            }

            if (
                $wantsMoreFirstJobDetails
                && $this->input('firstJobHow') === 'Other'
                && blank($this->input('firstJobHowOther'))
            ) {
                $validator->errors()->add('firstJobHowOther', 'Please specify how you found your first job.');
            }

            if (
                $wantsMoreFirstJobDetails
                && $this->input('firstJobDuration') === 'Other'
                && blank($this->input('firstJobDurationOther'))
            ) {
                $validator->errors()->add('firstJobDurationOther', 'Please specify how long you stayed in your first job.');
            }

            if (
                $wantsMoreEmploymentDetails
                && blank($this->input('jobLevel'))
            ) {
                $validator->errors()->add('jobLevel', 'Please choose the job level position.');
            }

            if (
                $wantsMoreEmploymentDetails
                && blank($this->input('firstJobIsCurrent'))
            ) {
                $validator->errors()->add('firstJobIsCurrent', 'Please tell us if this is your first job after college.');
            }

            if (
                $wantsMoreEmploymentDetails
                && empty($presentJobReasons)
            ) {
                $validator->errors()->add('presentJobReasons', 'Please choose at least one reason for accepting the present job.');
            }

            if (
                $wantsMoreEmploymentDetails
                && blank($this->input('hasEmployeeAwards'))
            ) {
                $validator->errors()->add('hasEmployeeAwards', 'Please tell us if you received any employee awards.');
            }

            if (
                $this->input('presentlyEmployed') === 'Yes'
                && $this->input('businessLine') === 'Other'
                && blank($this->input('businessLineOther'))
            ) {
                $validator->errors()->add('businessLineOther', 'Please specify the other business line.');
            }

            if (
                $wantsMoreEmploymentDetails
                && $this->input('jobLevel') === 'Other'
                && blank($this->input('jobLevelOther'))
            ) {
                $validator->errors()->add('jobLevelOther', 'Please specify the other job level.');
            }

            if (
                $wantsMoreEmploymentDetails
                && in_array('Other', $presentJobReasons, true)
                && blank($this->input('presentJobReasonsOther'))
            ) {
                $validator->errors()->add('presentJobReasonsOther', 'Please specify the other reason for accepting the present job.');
            }

            if (
                $wantsMoreEmploymentDetails
                && $this->input('hasEmployeeAwards') === 'Yes'
                && blank($this->input('employeeAwards'))
            ) {
                $validator->errors()->add('employeeAwards', 'Please list the awards received as an employee.');
            }

            if (!$hasTeachingSummary && !$hasFullTeachingBreakdown) {
                $validator->errors()->add('teachingFeedbackSummary', 'Please choose a teaching feedback rating.');
            }

            if (!$hasOverallSummary && !$hasFullOverallBreakdown) {
                $validator->errors()->add('overallExperienceSummary', 'Please choose an overall experience rating.');
            }

            $country = trim((string) $this->input('country', ''));
            $regionOfOrigin = trim((string) $this->input('regionOfOrigin', ''));
            $municipalityCity = trim((string) $this->input('municipalityCity', ''));
            $regionXMunicipality = $this->canonicalRegionXMunicipality($municipalityCity);

            if ($country === 'Philippines' && $municipalityCity !== '') {
                if ($this->isRegionXSelection($regionOfOrigin) && $regionXMunicipality === null) {
                    $validator->errors()->add('municipalityCity', 'Please choose a municipality or city from Region X - Northern Mindanao.');
                }

                if (!$this->isRegionXSelection($regionOfOrigin) && $regionXMunicipality !== null) {
                    $validator->errors()->add(
                        'municipalityCity',
                        $regionXMunicipality.' belongs to Region X - Northern Mindanao. Please select Region X or enter a municipality from the selected region.'
                    );
                }
            }
        });
    }

    private function hasFullMiddleName(mixed $value): bool
    {
        $middleName = trim((string) $value);
        if ($middleName === '') {
            return true;
        }

        preg_match_all('/[A-Za-z]/', $middleName, $letters);

        return count($letters[0] ?? []) >= 2;
    }

    private function isRegionXSelection(?string $value): bool
    {
        $normalized = $this->normalizeLocationValue($value);

        return (bool) preg_match('/\bregion\s*x\b/', $normalized)
            || (bool) preg_match('/\bregion\s*10\b/', $normalized)
            || str_contains($normalized, 'northern mindanao')
            || in_array($normalized, [
                'bukidnon',
                'camiguin',
                'lanao del norte',
                'misamis occidental',
                'misamis oriental',
            ], true);
    }

    private function canonicalRegionXMunicipality(?string $value): ?string
    {
        $normalized = $this->normalizeLocationValue($value);
        if ($normalized === '') {
            return null;
        }

        foreach ($this->regionXMunicipalities() as $municipality) {
            if ($this->normalizeLocationValue($municipality) === $normalized) {
                return $municipality;
            }
        }

        return null;
    }

    private function normalizeLocationValue(?string $value): string
    {
        $normalized = mb_strtolower(trim((string) $value));
        $normalized = preg_replace('/\bcity\b/u', '', $normalized);
        $normalized = preg_replace('/[–—-]/u', ' ', $normalized);
        $normalized = preg_replace('/[.,()]/u', ' ', $normalized);

        return trim(preg_replace('/\s+/u', ' ', $normalized ?? '') ?? '');
    }

    private function regionXMunicipalities(): array
    {
        return [
            'Baungon',
            'Cabanglasan',
            'Damulog',
            'Dangcagan',
            'Don Carlos',
            'Impasug-ong',
            'Kadingilan',
            'Kalilangan',
            'Kibawe',
            'Kitaotao',
            'Lantapan',
            'Libona',
            'Malaybalay City',
            'Malitbog',
            'Manolo Fortich',
            'Maramag',
            'Pangantucan',
            'Quezon',
            'San Fernando',
            'Sumilao',
            'Talakag',
            'Valencia City',
            'Catarman',
            'Guinsiliban',
            'Mahinog',
            'Mambajao',
            'Sagay',
            'Bacolod',
            'Baloi',
            'Baroy',
            'Iligan City',
            'Kapatagan',
            'Sultan Naga Dimaporo',
            'Kauswagan',
            'Kolambugan',
            'Lala',
            'Linamon',
            'Magsaysay',
            'Maigo',
            'Matungao',
            'Munai',
            'Nunungan',
            'Pantao Ragat',
            'Pantar',
            'Poona Piagapo',
            'Salvador',
            'Sapad',
            'Tagoloan',
            'Tangcal',
            'Tubod',
            'Aloran',
            'Baliangao',
            'Bonifacio',
            'Calamba',
            'Clarin',
            'Concepcion',
            'Don Victoriano Chiongbian',
            'Jimenez',
            'Lopez Jaena',
            'Oroquieta City',
            'Ozamiz City',
            'Panaon',
            'Plaridel',
            'Sapang Dalaga',
            'Sinacaban',
            'Tangub City',
            'Tudela',
            'Alubijid',
            'Balingasag',
            'Balingoan',
            'Binuangan',
            'Cagayan de Oro City',
            'Claveria',
            'El Salvador City',
            'Gingoog City',
            'Gitagum',
            'Initao',
            'Jasaan',
            'Kinoguitan',
            'Lagonglong',
            'Laguindingan',
            'Libertad',
            'Lugait',
            'Manticao',
            'Medina',
            'Naawan',
            'Opol',
            'Salay',
            'Sugbongcogon',
            'Talisayan',
            'Villanueva',
        ];
    }
}
