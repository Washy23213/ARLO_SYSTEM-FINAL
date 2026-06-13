<?php

namespace App\Http\Requests\Alumni;

use Illuminate\Foundation\Http\FormRequest;

class SubmitJobRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'company' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'max:100'],
            'location' => ['required', 'string', 'max:255'],
            'specific_location' => ['required_if:location,Others', 'nullable', 'string', 'max:255'],
            'contact_email' => ['required', 'email', 'max:255'],
            'application_deadline' => ['nullable', 'date'],
            'description' => ['required', 'string', 'min:20'],
            'requirements' => ['nullable', 'string'],
        ];
    }
}
