<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminAnalyticsFiltersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'sex' => ['nullable', Rule::in(['Male', 'Female'])],
            'civil_status' => ['nullable', Rule::in(['Single', 'Married', 'Divorced', 'Widowed', 'Separated'])],
            'age_range' => ['nullable', Rule::in(['18-24', '25-34', '35-44', '45-54', '55+'])],
            'age_from' => ['nullable', 'integer', 'between:0,120'],
            'age_to' => ['nullable', 'integer', 'between:0,120'],
            'college' => ['nullable', 'string', 'max:150'],
            'degree' => ['nullable', 'string', 'max:200'],
            'graduation_year' => ['nullable', 'regex:/^\d{4}$/'],
            'graduation_year_from' => ['nullable', 'regex:/^\d{4}$/'],
            'graduation_year_to' => ['nullable', 'regex:/^\d{4}$/'],
            'employment_status' => ['nullable', Rule::in(['employed', 'self_employed', 'unemployed', 'unknown'])],
            'industry' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'salary_range' => ['nullable', 'string', 'max:100'],
            'charts' => ['nullable', 'array'],
            'charts.*' => ['string', 'max:80'],
            'force_refresh' => ['nullable', 'boolean'],
        ];
    }

    public function filters(): array
    {
        return $this->safe()->only([
            'date_from',
            'date_to',
            'sex',
            'civil_status',
            'age_range',
            'age_from',
            'age_to',
            'college',
            'degree',
            'graduation_year',
            'graduation_year_from',
            'graduation_year_to',
            'employment_status',
            'industry',
            'location',
            'salary_range',
            'charts',
        ]);
    }

    public function forceRefresh(): bool
    {
        return $this->boolean('force_refresh');
    }
}
