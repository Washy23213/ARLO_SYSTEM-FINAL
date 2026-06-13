<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class SaveAnnouncementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isAdmin();
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string', 'min:10'],
            'type' => ['required', 'in:Event,News,Update,Alert,Job'],
            'priority' => ['required', 'in:Low,Medium,High,Urgent'],
            'target_audience' => ['nullable', 'in:All,Students,Alumni,Faculty,Staff,Employers'],
            'target_department' => ['nullable', 'string', 'max:150'],
            'target_graduation_year' => ['nullable', 'regex:/^\d{4}$/'],
            'event_date' => ['nullable', 'date'],
            'event_location' => ['nullable', 'string', 'max:255'],
            'specific_event_location' => ['required_if:event_location,Others', 'nullable', 'string', 'max:255'],
            'expires_at' => ['nullable', 'date'],
            'is_published' => ['nullable', 'boolean'],
        ];
    }
}
