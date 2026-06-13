<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class SendResetLinkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'Please enter the email address associated with your CMU OARL account.',
            'email.email' => 'Please enter a valid email address.',
        ];
    }
}
