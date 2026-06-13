<?php

namespace App\Http\Requests\Admin;

use App\Support\SystemOptions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFeedbackRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isAdmin();
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(SystemOptions::feedbackStatuses())],
            'priority' => ['required', Rule::in(SystemOptions::feedbackPriorities())],
            'assigned_to' => [
                'nullable',
                Rule::exists('admins', 'id'),
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $assignedTo = $this->input('assigned_to');

        if (!is_scalar($assignedTo) || trim((string) $assignedTo) === '') {
            $this->merge(['assigned_to' => null]);
        }
    }
}
