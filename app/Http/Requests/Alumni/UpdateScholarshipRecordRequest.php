<?php

namespace App\Http\Requests\Alumni;

use App\Support\SystemOptions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateScholarshipRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'has_scholarship' => ['required', 'boolean'],
            'scholarship_name' => [
                'nullable',
                'string',
                'max:255',
                Rule::requiredIf(fn () => $this->boolean('has_scholarship')),
                Rule::in(SystemOptions::scholarshipNames()),
            ],
            'scholarship_name_other' => [
                'nullable',
                'string',
                'max:255',
                Rule::requiredIf(fn () => $this->boolean('has_scholarship') && $this->input('scholarship_name') === 'Other'),
            ],
            'scholarship_year_awarded' => [
                'nullable',
                'integer',
                'min:1900',
                'max:' . now()->year,
                Rule::requiredIf(fn () => $this->boolean('has_scholarship')),
            ],
            'scholarship_helped_get_job' => [
                'nullable',
                'boolean',
                Rule::requiredIf(fn () => $this->boolean('has_scholarship')),
            ],
            'scholarship_attestation' => ['accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'scholarship_attestation.accepted' => 'Please confirm that the scholarship information is true and accurate before saving it.',
        ];
    }

    protected function getRedirectUrl(): string
    {
        return route('tracer.index') . '#scholarship-record';
    }
}
