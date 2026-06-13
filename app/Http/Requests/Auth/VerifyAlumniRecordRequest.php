<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class VerifyAlumniRecordRequest extends FormRequest
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
            'yearGraduated' => ['required', 'integer', 'min:1900', 'max:' . ((int) now()->format('Y'))],
        ];
    }

    public function messages(): array
    {
        return [
            'middlename.regex' => 'Middle name may only contain letters, spaces, periods, apostrophes, and hyphens.',
        ];
    }

    public function attributes(): array
    {
        return [
            'middlename' => 'middle name',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (! $this->hasFullMiddleName($this->input('middlename'))) {
                $validator->errors()->add('middlename', 'Please enter your full middle name.');
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
}
