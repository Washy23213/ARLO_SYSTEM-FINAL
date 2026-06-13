<?php

namespace App\Http\Requests\Admin;

use App\Models\Admin;
use App\Models\SuperAdmin;
use App\Support\SystemOptions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SaveAlumniRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isAdmin();
    }

    public function rules(): array
    {
        $userId = $this->route('id');
        $emailRule = Rule::unique('users', 'email');

        if ($userId !== null) {
            $emailRule = $emailRule->ignore($userId);
        }

        return [
            'firstname' => ['required', 'string', 'max:255'],
            'lastname' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', $emailRule],
            'password' => [$userId === null ? 'required' : 'nullable', 'string', 'min:8', 'confirmed'],
            'phone' => ['nullable', 'string', 'max:20'],
            'department' => ['nullable', 'string', 'max:255'],
            'degree' => ['nullable', 'string', 'max:255'],
            'year_graduated' => ['nullable', 'integer', 'min:1900', 'max:2100'],
            'employed_after_grad' => ['nullable', 'in:Yes,No'],
            'industry' => ['nullable', 'string', 'max:255'],
            'current_position' => ['nullable', 'string', 'max:255'],
            'company' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'specific_location' => ['required_if:location,Others', 'nullable', 'string', 'max:255'],
            'salary_range' => ['nullable', 'string', 'max:100'],
            'skills' => ['nullable', 'string'],
            'achievements' => ['nullable', 'string'],
            'further_education' => ['nullable', 'boolean'],
            'education_level' => ['nullable', 'string', 'max:100', Rule::in(SystemOptions::educationLevels())],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $department = SystemOptions::canonical($this->input('department'), SystemOptions::departments());
            $degree = SystemOptions::clean($this->input('degree'));

            $email = mb_strtolower(trim((string) $this->input('email')));
            if ($email !== '' && Admin::whereRaw('LOWER(email) = ?', [$email])->exists()) {
                $validator->errors()->add('email', 'This email is already used by an admin account.');
            }

            if ($email !== '' && SuperAdmin::whereRaw('LOWER(email) = ?', [$email])->exists()) {
                $validator->errors()->add('email', 'This email is already used by a super admin account.');
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

            if ((bool) $this->input('further_education') && blank($this->input('education_level'))) {
                $validator->errors()->add(
                    'education_level',
                    'Please select the education level when further education is enabled.'
                );
            }

        });
    }
}
