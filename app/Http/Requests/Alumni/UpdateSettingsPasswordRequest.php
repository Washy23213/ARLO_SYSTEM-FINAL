<?php

namespace App\Http\Requests\Alumni;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'current_password' => ['required', 'string', 'current_password:web'],
            'new_password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'current_password.current_password' => 'The current password does not match your account password.',
            'new_password.confirmed' => 'The new password confirmation does not match.',
        ];
    }
}
