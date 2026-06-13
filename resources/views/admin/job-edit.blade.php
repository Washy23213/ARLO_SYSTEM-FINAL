@extends('admin.layout')

@section('title', 'Edit Job')

@section('content')
@php
    $locationOptions = ['Bukidnon', 'Cagayan de Oro', 'Davao City', 'Manila', 'Cebu', 'Remote', 'Abroad', 'Others'];
    $salaryOptions = array_values(array_diff(\App\Support\SystemOptions::salaryRanges(), ['Prefer not to say']));
    $jobSalaryRange = \App\Support\SystemOptions::salaryRangeLabel($job->salary_range ?? null);
    if ($jobSalaryRange && !in_array($jobSalaryRange, $salaryOptions, true)) {
        $salaryOptions[] = $jobSalaryRange;
    }
    $knownLocation = old('location', (in_array($job->location, $locationOptions, true) ? $job->location : (!empty($job->location) ? 'Others' : '')));
    $specificLocation = old('specific_location', (in_array($job->location, $locationOptions, true) ? '' : ($job->location ?? '')));
@endphp
<div class="p-6">
    <div class="mb-6 rounded-xl border border-green-100 bg-gradient-to-r from-green-50 to-emerald-50 p-6">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Edit Job</h1>
                <p class="text-gray-600 mt-1">Update job posting details</p>
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
        action="{{ route('admin.jobs.update', $job->id) }}"
        method="POST"
        class="space-y-6"
        data-admin-ajax-form
        data-admin-ajax-kind="job"
        data-confirm-submit="Save changes to this job posting?"
        data-confirm-title="Save job changes"
        data-confirm-label="Save Changes"
    >
        @csrf
        @method('PUT')

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Job Details</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Job Title</label>
                    <input type="text" name="title" value="{{ old('title', $job->title) }}" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Company</label>
                    <input type="text" name="company" value="{{ old('company', $job->company) }}" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Job Type</label>
                    <select name="type" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                        <option value="">Select Type</option>
                        @foreach(['Full-time', 'Part-time', 'Contract', 'Internship'] as $type)
                            <option value="{{ $type }}" {{ old('type', $job->type) === $type ? 'selected' : '' }}>{{ $type }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Location</label>
                    <select id="location_select" name="location" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" onchange="toggleSpecificLocation()" required>
                        <option value="">Select Location</option>
                        @foreach($locationOptions as $option)
                            <option value="{{ $option }}" {{ $knownLocation === $option ? 'selected' : '' }}>{{ $option }}</option>
                        @endforeach
                    </select>
                    <div id="specific_location_group" class="mt-3 {{ $knownLocation === 'Others' ? '' : 'hidden' }}">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Specific Location</label>
                        <input id="specific_location_input" type="text" name="specific_location" value="{{ $specificLocation }}" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter specific location">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Salary Range</label>
                    <select name="salary_range" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Select Salary Range</option>
                        @foreach($salaryOptions as $range)
                            <option value="{{ $range }}" {{ \App\Support\SystemOptions::salaryRangeLabel(old('salary_range', $jobSalaryRange)) === $range ? 'selected' : '' }}>{{ $range }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Application Deadline</label>
                    <input type="date" name="application_deadline" value="{{ old('application_deadline', !empty($job->application_deadline) ? \Carbon\Carbon::parse($job->application_deadline)->format('Y-m-d') : '') }}" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <textarea name="description" rows="5" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" required>{{ old('description', $job->description) }}</textarea>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Requirements</label>
                    <textarea name="requirements" rows="4" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">{{ old('requirements', $job->requirements) }}</textarea>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Contact Information</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Contact Email</label>
                    <input type="email" name="contact_email" value="{{ old('contact_email', $job->contact_email) }}" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Contact Phone</label>
                    <input type="text" name="contact_phone" value="{{ old('contact_phone', $job->contact_phone ?? '') }}" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="md:col-span-2">
                    <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', (isset($job->is_active) ? (int) $job->is_active : 1)) ? 'checked' : '' }} class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        Active job posting
                    </label>
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-2">
            <a href="{{ route('admin.jobs') }}" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-200 transition-colors">Cancel</a>
            <button type="submit" class="inline-flex items-center gap-2 bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors" data-admin-ajax-submit-button>
                <i class='bx bx-save'></i>
                <span data-admin-ajax-submit-label>Save Changes</span>
            </button>
        </div>
    </form>
</div>
<script>
    function toggleSpecificLocation() {
        const locationSelect = document.getElementById('location_select');
        const specificGroup = document.getElementById('specific_location_group');
        const specificInput = document.getElementById('specific_location_input');
        if (!locationSelect || !specificGroup || !specificInput) return;

        if (locationSelect.value === 'Others') {
            specificGroup.classList.remove('hidden');
            specificInput.setAttribute('required', 'required');
        } else {
            specificGroup.classList.add('hidden');
            specificInput.removeAttribute('required');
            specificInput.value = '';
        }
    }

    document.addEventListener('admin:page-ready', toggleSpecificLocation, { once: true });
</script>
@endsection
