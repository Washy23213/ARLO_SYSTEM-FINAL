<?php

namespace App\Http\Requests\Alumni;

use Illuminate\Foundation\Http\FormRequest;

class DeleteAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'delete_confirmation' => ['required', 'in:DELETE'],
        ];
    }
}
