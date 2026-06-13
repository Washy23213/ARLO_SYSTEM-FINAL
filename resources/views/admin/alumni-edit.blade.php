@extends('admin.layout')

@section('title', 'Edit Alumni')

@section('content')
@php
    $departmentOptions = \App\Support\SystemOptions::departments();
    $programCatalog = \App\Support\SystemOptions::programCatalogByDepartment();
    $industryOptions = \App\Support\SystemOptions::industries();
    $locationOptions = \App\Support\SystemOptions::locations();
    $salaryOptions = \App\Support\SystemOptions::salaryRanges();
    $educationOptions = \App\Support\SystemOptions::educationLevels();
    $currentYear = (int) date('Y');
    $knownLocation = old('location', (in_array($alumni->location, $locationOptions, true) ? $alumni->location : (!empty($alumni->location) ? 'Others' : '')));
    $specificLocation = old('specific_location', (in_array($alumni->location, $locationOptions, true) ? '' : ($alumni->location ?? '')));
    $selectedDepartment = old('department', $alumni->department);
    $selectedDegree = old('degree', $alumni->degree);
    $selectedSalaryRange = \App\Support\SystemOptions::salaryRangeLabel(old('salary_range', $alumni->salary_range));
    if ($selectedSalaryRange && !in_array($selectedSalaryRange, $salaryOptions, true)) {
        $salaryOptions[] = $selectedSalaryRange;
    }
    $degreeOptions = \App\Support\SystemOptions::programsForDepartment($selectedDepartment);
    $furtherEducationValue = old('further_education', ($latestTracer?->further_education ?? false) ? '1' : '0');
    $educationLevelValue = old('education_level', $latestTracer?->education_level);
@endphp
<style>
    .pretty-select {
        appearance: none;
        -webkit-appearance: none;
        -moz-appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='20' height='20' viewBox='0 0 20 20' fill='none'%3E%3Cpath d='M5 7.5L10 12.5L15 7.5' stroke='%236B7280' stroke-width='1.5' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 0.65rem center;
        padding-right: 2.25rem;
    }
</style>
<div class="p-6">
    <div class="admin-record-hero mb-6 p-6">
        <div class="admin-record-hero__content flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Edit Alumni</h1>
                <p class="text-gray-600 mt-1">Update alumni record details</p>
                <p class="text-sm text-gray-500 mt-2" data-admin-alumni-record-name>Record: {{ $alumni->firstname }} {{ $alumni->lastname }}</p>
            </div>
        </div>
    </div>

    @if($errors->any())
    <div class="mb-4 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
        <ul class="list-disc pl-5">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form
        action="{{ route('admin.alumni.update', $alumni->id) }}"
        method="POST"
        class="space-y-6"
        data-admin-ajax-form
        data-admin-ajax-kind="alumni"
        data-confirm-submit="Save changes to this alumni record?"
        data-confirm-title="Save alumni changes"
        data-confirm-label="Save Changes"
    >
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            @csrf
            @method('PUT')
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Basic Information</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">First Name</label>
                    <input type="text" name="firstname" value="{{ old('firstname', $alumni->firstname) }}" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Last Name</label>
                    <input type="text" name="lastname" value="{{ old('lastname', $alumni->lastname) }}" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" name="email" value="{{ old('email', $alumni->email) }}" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                    <input type="text" name="phone" value="{{ old('phone', $alumni->phone) }}" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Education and Employment</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">College</label>
                    <select id="alumni_edit_department_select" name="department" class="pretty-select w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                        <option value="">Select College</option>
                        @foreach($departmentOptions as $option)
                            <option value="{{ $option }}" {{ old('department', $alumni->department) === $option ? 'selected' : '' }}>{{ $option }}</option>
                        @endforeach
                        @if($selectedDepartment !== '' && !in_array($selectedDepartment, $departmentOptions, true))
                            <option value="{{ $selectedDepartment }}" selected>{{ $selectedDepartment }}</option>
                        @endif
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Degree</label>
                    <select id="alumni_edit_degree_select" name="degree" class="pretty-select w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white" {{ $selectedDepartment === '' ? 'disabled' : '' }}>
                        <option value="">{{ $selectedDepartment === '' ? 'Select college first' : 'Select Degree / Program' }}</option>
                        @foreach($degreeOptions as $option)
                            <option value="{{ $option }}" {{ old('degree', $alumni->degree) === $option ? 'selected' : '' }}>{{ $option }}</option>
                        @endforeach
                        @if($selectedDegree !== '' && !in_array($selectedDegree, $degreeOptions, true))
                            <option value="{{ $selectedDegree }}" selected>{{ $selectedDegree }}</option>
                        @endif
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Batch</label>
                    <select name="year_graduated" class="pretty-select w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                        <option value="">Select Year</option>
                        @for($year = $currentYear; $year >= 1990; $year--)
                            <option value="{{ $year }}" {{ (string) old('year_graduated', $alumni->year_graduated) === (string) $year ? 'selected' : '' }}>{{ $year }}</option>
                        @endfor
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Employment Status</label>
                    <select id="alumni_edit_employment_status_select" name="employed_after_grad" class="pretty-select w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                        <option value="">Unknown</option>
                        <option value="Yes" {{ old('employed_after_grad', $alumni->employed_after_grad) === 'Yes' ? 'selected' : '' }}>Employed</option>
                        <option value="No" {{ old('employed_after_grad', $alumni->employed_after_grad) === 'No' ? 'selected' : '' }}>Unemployed</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Industry</label>
                    <select id="alumni_edit_industry_select" name="industry" class="pretty-select w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                        <option value="">Select Industry</option>
                        @foreach($industryOptions as $option)
                            <option value="{{ $option }}" {{ old('industry', $alumni->industry) === $option ? 'selected' : '' }}>{{ $option }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Current Position</label>
                    <input id="alumni_edit_current_position_input" type="text" name="current_position" value="{{ old('current_position', $alumni->current_position) }}" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Current Company / Business</label>
                    <input id="alumni_edit_company_input" type="text" name="company" value="{{ old('company', $alumni->company) }}" placeholder="Enter company or business name" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Further Education</label>
                    <select id="alumni_edit_further_education_select" name="further_education" class="pretty-select w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                        <option value="0" {{ (string) $furtherEducationValue === '0' ? 'selected' : '' }}>No</option>
                        <option value="1" {{ (string) $furtherEducationValue === '1' ? 'selected' : '' }}>Yes</option>
                    </select>
                </div>
                <div id="alumni_edit_education_level_group" class="{{ (string) $furtherEducationValue === '1' ? '' : 'hidden' }}">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Education Level</label>
                    <select id="alumni_edit_education_level_select" name="education_level" class="pretty-select w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                        <option value="">Select Education Level</option>
                        @foreach($educationOptions as $option)
                            <option value="{{ $option }}" {{ $educationLevelValue === $option ? 'selected' : '' }}>{{ $option }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Location</label>
                    <select id="alumni_edit_location_select" name="location" class="pretty-select w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                        <option value="">Select Location</option>
                        @foreach($locationOptions as $option)
                            <option value="{{ $option }}" {{ $knownLocation === $option ? 'selected' : '' }}>{{ $option }}</option>
                        @endforeach
                    </select>
                    <div id="alumni_edit_specific_location_group" class="mt-3 {{ $knownLocation === 'Others' ? '' : 'hidden' }}">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Specific Location</label>
                        <input id="alumni_edit_specific_location_input" type="text" name="specific_location" value="{{ $specificLocation }}" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter specific location">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Salary Range</label>
                    <select id="alumni_edit_salary_range_select" name="salary_range" class="pretty-select w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                        <option value="">Select Salary Range</option>
                        @foreach($salaryOptions as $option)
                            <option value="{{ $option }}" {{ $selectedSalaryRange === $option ? 'selected' : '' }}>{{ $option }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Profile Notes</h2>
            <div class="grid grid-cols-1 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Skills</label>
                    <textarea name="skills" rows="4" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter the alumni's skills">{{ old('skills', $alumni->skills) }}</textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Achievements</label>
                    <textarea name="achievements" rows="4" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter notable achievements">{{ old('achievements', $alumni->achievements) }}</textarea>
                </div>
            </div>
        </div>

        <div class="mt-4 flex justify-end gap-2 border-t border-gray-200 pt-6">
            <a
                href="{{ route('admin.alumni') }}"
                data-alumni-return-link
                data-base-href="{{ route('admin.alumni') }}"
                data-draft-href="{{ route('admin.alumni', ['create' => 1]) }}"
                data-portal-preserve-scroll="true"
                data-portal-skip-animation="true"
                class="bg-gray-100 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-200 transition-colors"
            >
                Cancel
            </a>
            <button type="submit" class="inline-flex items-center gap-2 bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors" data-admin-ajax-submit-button>
                <i class='bx bx-save'></i>
                <span data-admin-ajax-submit-label>Save Changes</span>
            </button>
        </div>
    </form>
</div>
@include('partials.department-program-form-script', [
    'programCatalog' => $programCatalog,
    'departmentProgramFieldConfig' => [
        'departmentSelectId' => 'alumni_edit_department_select',
        'degreeSelectId' => 'alumni_edit_degree_select',
        'selectedDegree' => $selectedDegree,
        'employmentStatusSelectId' => 'alumni_edit_employment_status_select',
        'employmentFieldIds' => [
            'alumni_edit_industry_select',
            'alumni_edit_current_position_input',
            'alumni_edit_company_input',
            'alumni_edit_salary_range_select',
        ],
        'locationSelectId' => 'alumni_edit_location_select',
        'specificLocationGroupId' => 'alumni_edit_specific_location_group',
        'specificLocationInputId' => 'alumni_edit_specific_location_input',
        'lockedEmploymentValues' => ['No', 'Unemployed'],
    ],
])
<script>
    (() => {
        const returnLinks = document.querySelectorAll('[data-alumni-return-link]');
        const form = document.querySelector('form[data-admin-ajax-kind="alumni"]');
        const furtherEducationSelect = document.getElementById('alumni_edit_further_education_select');
        const educationLevelGroup = document.getElementById('alumni_edit_education_level_group');
        const educationLevelSelect = document.getElementById('alumni_edit_education_level_select');

        returnLinks.forEach((returnLink) => {
            try {
                const storedDraft = window.sessionStorage.getItem('cmu-arlo-admin-alumni-create-return-draft');
                returnLink.href = storedDraft ? (returnLink.dataset.draftHref || returnLink.href) : (returnLink.dataset.baseHref || returnLink.href);
            } catch (error) {
                returnLink.href = returnLink.dataset.baseHref || returnLink.href;
            }
        });

        const syncEducationLevelVisibility = () => {
            const hasFurtherEducation = String(furtherEducationSelect?.value || '0') === '1';

            educationLevelGroup?.classList.toggle('hidden', !hasFurtherEducation);

            if (educationLevelSelect) {
                educationLevelSelect.disabled = !hasFurtherEducation;

                if (!hasFurtherEducation) {
                    educationLevelSelect.value = '';
                }
            }
        };

        syncEducationLevelVisibility();
        furtherEducationSelect?.addEventListener('change', syncEducationLevelVisibility);

        if (!form) {
            return;
        }

        form.addEventListener('submit', () => {
            try {
                window.sessionStorage.removeItem('cmu-arlo-admin-alumni-create-return-draft');
            } catch (error) {
                // Ignore storage access issues.
            }
        });
    })();
</script>
@endsection
