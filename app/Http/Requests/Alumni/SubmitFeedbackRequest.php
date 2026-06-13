<?php

namespace App\Http\Requests\Alumni;

use App\Support\SystemOptions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubmitFeedbackRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'min:10'],
            'type' => ['required', 'string', Rule::in(SystemOptions::feedbackTypes())],
        ];
    }
}
