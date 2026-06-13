<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class RejectJobSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::guard('super_admin')->check()
            || Auth::guard('admin')->check()
            || (bool) $this->user()?->isAdmin();
    }

    public function rules(): array
    {
        return [
            'admin_notes' => ['required', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'admin_notes.required' => 'Please tell the alumni why the job submission was rejected.',
            'admin_notes.max' => 'The rejection reason must not be longer than 1000 characters.',
        ];
    }
}
