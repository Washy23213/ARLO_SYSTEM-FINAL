<?php

namespace App\Http\Requests\Alumni;

use Illuminate\Foundation\Http\FormRequest;

class VerifyDeleteAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'current_password' => ['required', 'string'],
        ];
    }
}
