@extends('admin.layout')

@section('title', 'Add Alumni')

@section('content')
@php
    $departmentOptions = \App\Support\SystemOptions::departments();
    $selectedDepartment = old('department', '');
    $selectedDegree = old('degree', '');
    $programCatalog = \App\Support\SystemOptions::programCatalogByDepartment();
    $degreeOptions = \App\Support\SystemOptions::programsForDepartment($selectedDepartment);
    $industryOptions = \App\Support\SystemOptions::industries();
    $locationOptions = \App\Support\SystemOptions::locations();
    $salaryOptions = \App\Support\SystemOptions::salaryRanges();
    $selectedSalaryRange = \App\Support\SystemOptions::salaryRangeLabel(old('salary_range'));
    $currentYear = (int) date('Y');
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

    /* Hide browser-native password reveal icons to avoid duplicate eyes */
    input[type="password"]::-ms-reveal,
    input[type="password"]::-ms-clear {
        display: none;
    }

    input[type="password"]::-webkit-credentials-auto-fill-button {
        visibility: hidden;
        pointer-events: none;
        position: absolute;
        right: 0;
    }
</style>
<div class="p-6">
    <div class="mb-6 rounded-xl border border-blue-100 bg-gradient-to-r from-blue-50 to-cyan-50 p-6">
        <div>
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Add Alumni</h1>
                <p class="text-gray-600 mt-1">Create a new alumni account record</p>
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

    <form action="{{ route('admin.alumni.store') }}" method="POST" class="space-y-6">
        @csrf

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Account Information</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">First Name</label>
                    <input type="text" name="firstname" value="{{ old('firstname') }}" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Last Name</label>
                    <input type="text" name="lastname" value="{{ old('lastname') }}" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" name="email" value="{{ old('email') }}" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                    <input type="text" name="phone" value="{{ old('phone') }}" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                    <div class="relative">
                        <input id="password_input" type="password" name="password" class="w-full border border-gray-300 rounded-md px-3 py-2 pr-10 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                        <button type="button" onclick="togglePasswordVisibility('password_input', 'password_toggle_icon')" class="absolute inset-y-0 right-2 inline-flex items-center text-gray-500 hover:text-gray-700">
                            <i id="password_toggle_icon" class='bx bx-show text-lg'></i>
                        </button>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
                    <div class="relative">
                        <input id="password_confirmation_input" type="password" name="password_confirmation" class="w-full border border-gray-300 rounded-md px-3 py-2 pr-10 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                        <button type="button" onclick="togglePasswordVisibility('password_confirmation_input', 'password_confirmation_toggle_icon')" class="absolute inset-y-0 right-2 inline-flex items-center text-gray-500 hover:text-gray-700">
                            <i id="password_confirmation_toggle_icon" class='bx bx-show text-lg'></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Education and Employment</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">College</label>
                    <select id="alumni_create_department_select" name="department" class="pretty-select w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                        <option value="">Select College</option>
                        @foreach($departmentOptions as $option)
                            <option value="{{ $option }}" {{ old('department') === $option ? 'selected' : '' }}>{{ $option }}</option>
                        @endforeach
                        @if($selectedDepartment !== '' && !in_array($selectedDepartment, $departmentOptions, true))
                            <option value="{{ $selectedDepartment }}" selected>{{ $selectedDepartment }}</option>
                        @endif
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Degree</label>
                    <select id="alumni_create_degree_select" name="degree" class="pretty-select w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white" {{ $selectedDepartment === '' ? 'disabled' : '' }}>
                        <option value="">{{ $selectedDepartment === '' ? 'Select college first' : 'Select Degree / Program' }}</option>
                        @foreach($degreeOptions as $option)
                            <option value="{{ $option }}" {{ old('degree') === $option ? 'selected' : '' }}>{{ $option }}</option>
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
                            <option value="{{ $year }}" {{ (string) old('year_graduated') === (string) $year ? 'selected' : '' }}>{{ $year }}</option>
                        @endfor
                    </select>
                </div>
                <div class="tracer-guide-field" data-tracer-guide="Choose the alumni's latest known employment status." data-admin-employment-guide data-guide-active="Choose the alumni's latest known employment status." data-guide-locked="When set to unemployed, employment detail fields are disabled.">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Employment Status <i class='bx bx-info-circle tracer-guide-indicator' aria-hidden="true"></i></label>
                    <select id="alumni_create_employment_status_select" name="employed_after_grad" class="pretty-select w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                        <option value="">Unknown</option>
                        <option value="Yes" {{ old('employed_after_grad') === 'Yes' ? 'selected' : '' }}>Employed</option>
                        <option value="No" {{ old('employed_after_grad') === 'No' ? 'selected' : '' }}>Unemployed</option>
                    </select>
                </div>
                <div class="tracer-guide-field" data-tracer-guide="Select the industry that best matches the alumni's current work." data-admin-employment-guide data-guide-active="Select the industry that best matches the alumni's current work." data-guide-locked="Industry is unavailable while the employment status is unemployed.">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Industry <i class='bx bx-info-circle tracer-guide-indicator' aria-hidden="true"></i></label>
                    <select id="alumni_create_industry_select" name="industry" class="pretty-select w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                        <option value="">Select Industry</option>
                        @foreach($industryOptions as $option)
                            <option value="{{ $option }}" {{ old('industry') === $option ? 'selected' : '' }}>{{ $option }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="tracer-guide-field" data-tracer-guide="Enter the alumni's current job title." data-admin-employment-guide data-guide-active="Enter the alumni's current job title." data-guide-locked="Current position is unavailable while the employment status is unemployed.">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Current Position <i class='bx bx-info-circle tracer-guide-indicator' aria-hidden="true"></i></label>
                    <input id="alumni_create_current_position_input" type="text" name="current_position" value="{{ old('current_position') }}" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="tracer-guide-field" data-tracer-guide="Enter the alumni's current employer or business." data-admin-employment-guide data-guide-active="Enter the alumni's current employer or business." data-guide-locked="Employer details are unavailable while the employment status is unemployed.">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Current Company / Business <i class='bx bx-info-circle tracer-guide-indicator' aria-hidden="true"></i></label>
                    <input id="alumni_create_company_input" type="text" name="company" value="{{ old('company') }}" placeholder="Enter company or business name" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="tracer-guide-field" data-tracer-guide="Select where the alumni's current work is based." data-admin-employment-guide data-guide-active="Select where the alumni's current work is based." data-guide-locked="Work location is unavailable while the employment status is unemployed.">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Location <i class='bx bx-info-circle tracer-guide-indicator' aria-hidden="true"></i></label>
                    <select id="alumni_create_location_select" name="location" class="pretty-select w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                        <option value="">Select Location</option>
                        @foreach($locationOptions as $option)
                            <option value="{{ $option }}" {{ old('location') === $option ? 'selected' : '' }}>{{ $option }}</option>
                        @endforeach
                    </select>
                    <div id="alumni_create_specific_location_group" class="mt-3 {{ old('location') === 'Others' ? '' : 'hidden' }}">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Specific Location</label>
                        <input id="alumni_create_specific_location_input" type="text" name="specific_location" value="{{ old('specific_location') }}" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter specific location">
                    </div>
                </div>
                <div class="tracer-guide-field" data-tracer-guide="Select the alumni's current monthly salary range." data-admin-employment-guide data-guide-active="Select the alumni's current monthly salary range." data-guide-locked="Salary range is unavailable while the employment status is unemployed.">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Salary Range <i class='bx bx-info-circle tracer-guide-indicator' aria-hidden="true"></i></label>
                    <select id="alumni_create_salary_range_select" name="salary_range" class="pretty-select w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
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
                    <textarea name="skills" rows="4" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter the alumni's skills">{{ old('skills') }}</textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Achievements</label>
                    <textarea name="achievements" rows="4" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter notable achievements">{{ old('achievements') }}</textarea>
                </div>
            </div>
        </div>

        <div class="mt-4 flex justify-end gap-2 border-t border-gray-200 pt-6">
            <a href="{{ route('admin.alumni') }}" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-200 transition-colors">Cancel</a>
            <button type="submit" class="inline-flex items-center gap-2 bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors">
                <i class='bx bx-user-plus'></i>
                Create Alumni
            </button>
        </div>
    </form>
</div>
<script>
    function togglePasswordVisibility(inputId, iconId) {
        const input = document.getElementById(inputId);
        const icon = document.getElementById(iconId);
        if (!input || !icon) return;

        const isPassword = input.type === 'password';
        input.type = isPassword ? 'text' : 'password';
        icon.className = isPassword ? 'bx bx-hide text-lg' : 'bx bx-show text-lg';
    }
</script>
@include('partials.department-program-form-script', [
    'programCatalog' => $programCatalog,
    'departmentProgramFieldConfig' => [
        'departmentSelectId' => 'alumni_create_department_select',
        'degreeSelectId' => 'alumni_create_degree_select',
        'selectedDegree' => $selectedDegree,
        'employmentStatusSelectId' => 'alumni_create_employment_status_select',
        'employmentFieldIds' => [
            'alumni_create_industry_select',
            'alumni_create_current_position_input',
            'alumni_create_company_input',
            'alumni_create_salary_range_select',
        ],
        'locationSelectId' => 'alumni_create_location_select',
        'specificLocationGroupId' => 'alumni_create_specific_location_group',
        'specificLocationInputId' => 'alumni_create_specific_location_input',
        'lockedEmploymentValues' => ['No', 'Unemployed'],
    ],
])
@endsection
