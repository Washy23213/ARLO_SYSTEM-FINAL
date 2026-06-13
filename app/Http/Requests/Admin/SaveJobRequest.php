<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class SaveJobRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isAdmin();
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'company' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'max:100'],
            'location' => ['required', 'string', 'max:255'],
            'specific_location' => ['required_if:location,Others', 'nullable', 'string', 'max:255'],
            'description' => ['required', 'string', 'min:20'],
            'requirements' => ['nullable', 'string'],
            'contact_email' => ['required', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:30'],
            'application_deadline' => ['nullable', 'date'],
            'salary_range' => ['nullable', 'string', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
