<?php

namespace App\Http\Requests\Alumni;

use App\Models\Admin;
use App\Models\SuperAdmin;
use App\Support\SystemOptions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateSettingsProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'firstname' => ['required', 'string', 'max:100'],
            'lastname' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->user()?->id)],
            'phone' => ['nullable', 'string', 'max:20'],
            'department' => ['nullable', 'string', 'max:100'],
            'degree' => ['nullable', 'string', 'max:200'],
            'year_graduated' => ['nullable', 'integer', 'min:1900', 'max:2100'],
            'location' => ['nullable', 'string', 'max:200'],
            'specific_location' => ['required_if:location,Others', 'nullable', 'string', 'max:200'],
            'skills' => ['nullable', 'string'],
            'achievements' => ['nullable', 'string'],
            'newsletter_opt_in' => ['nullable', 'boolean'],
            'profile_information_attestation' => ['accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'profile_information_attestation.accepted' => 'Please confirm that the profile information is true and accurate before saving it.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $department = SystemOptions::canonical($this->input('department'), SystemOptions::departments());
            $degree = SystemOptions::clean($this->input('degree'));

            $email = mb_strtolower(trim((string) $this->input('email')));
            if ($email !== '' && Admin::whereRaw('LOWER(email) = ?', [$email])->exists()) {
                $validator->errors()->add('email', 'This email is already used by another account.');
            }

            if ($email !== '' && SuperAdmin::whereRaw('LOWER(email) = ?', [$email])->exists()) {
                $validator->errors()->add('email', 'This email is already used by another account.');
            }

            if ($department === null || $degree === null) {
                return;
            }

            if (!SystemOptions::programBelongsToDepartment($department, $degree)) {
                $validator->errors()->add(
                    'degree',
                    'The selected degree or program does not belong to the chosen college.'
                );
            }

        });
    }
}
