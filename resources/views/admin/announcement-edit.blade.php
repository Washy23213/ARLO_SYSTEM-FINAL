@extends('admin.layout')

@section('title', 'Edit Announcement')

@section('content')
@php
    $audienceOptions = ['All', 'Students', 'Alumni', 'Faculty', 'Staff', 'Employers'];
    $locationOptions = ['Main Campus', 'Online', 'Bukidnon', 'Cagayan de Oro', 'Davao City', 'Manila', 'Others'];
    $targetDepartmentOptions = $targetDepartmentOptions ?? [];
    $targetGraduationYearOptions = $targetGraduationYearOptions ?? [];
    $knownEventLocation = old('event_location', (in_array($announcement->event_location, $locationOptions, true) ? $announcement->event_location : (!empty($announcement->event_location) ? 'Others' : '')));
    $specificEventLocation = old('specific_event_location', (in_array($announcement->event_location, $locationOptions, true) ? '' : ($announcement->event_location ?? '')));
@endphp
<div class="p-6">
    <div class="mb-6 rounded-xl border border-green-100 bg-gradient-to-r from-green-50 to-emerald-50 p-6">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Edit Announcement</h1>
                <p class="text-gray-600 mt-1">Update announcement details</p>
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
        action="{{ route('admin.announcements.update', $announcement->id) }}"
        method="POST"
        class="space-y-6"
        data-admin-ajax-form
        data-admin-ajax-kind="announcement"
        data-confirm-submit="Save changes to this announcement?"
        data-confirm-title="Save announcement changes"
        data-confirm-label="Save Changes"
    >
        @csrf
        @method('PUT')
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Title</label>
                    <input type="text" name="title" value="{{ old('title', $announcement->title) }}" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                    <select name="type" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                        <option value="">Select Type</option>
                        @foreach(['Event', 'News', 'Update', 'Alert', 'Job'] as $type)
                            <option value="{{ $type }}" {{ old('type', $announcement->type ?? null) === $type ? 'selected' : '' }}>{{ $type }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Priority</label>
                    <select name="priority" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                        <option value="">Select Priority</option>
                        @foreach(['Low', 'Medium', 'High', 'Urgent'] as $priority)
                            <option value="{{ $priority }}" {{ old('priority', $announcement->priority ?? 'Medium') === $priority ? 'selected' : '' }}>{{ $priority }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Target Audience</label>
                    <select name="target_audience" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        @foreach($audienceOptions as $audience)
                            <option value="{{ $audience }}" {{ old('target_audience', $announcement->target_audience ?? 'All') === $audience ? 'selected' : '' }}>{{ $audience }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Target College (Optional)</label>
                    <select name="target_department" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">All Colleges</option>
                        @foreach($targetDepartmentOptions as $department)
                            <option value="{{ $department }}" {{ old('target_department', $announcement->target_department ?? '') === $department ? 'selected' : '' }}>{{ $department }}</option>
                        @endforeach
                    </select>
                    <p class="mt-2 text-xs text-gray-500">Leave blank for all colleges.</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Target Graduation Year (Optional)</label>
                    <select name="target_graduation_year" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">All Years</option>
                        @foreach($targetGraduationYearOptions as $year)
                            <option value="{{ $year }}" {{ (string) old('target_graduation_year', $announcement->target_graduation_year ?? '') === (string) $year ? 'selected' : '' }}>{{ $year }}</option>
                        @endforeach
                    </select>
                    <p class="mt-2 text-xs text-gray-500">Leave blank for all years.</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Event Date (Optional)</label>
                    <input type="datetime-local" name="event_date" value="{{ old('event_date', !empty($announcement->event_date) ? \Carbon\Carbon::parse($announcement->event_date)->format('Y-m-d\\TH:i') : '') }}" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Event Location (Optional)</label>
                    <select id="event_location_select" name="event_location" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" onchange="toggleSpecificEventLocation()">
                        <option value="">Select Event Location</option>
                        @foreach($locationOptions as $location)
                            <option value="{{ $location }}" {{ $knownEventLocation === $location ? 'selected' : '' }}>{{ $location }}</option>
                        @endforeach
                    </select>
                    <div id="specific_event_location_group" class="mt-3 {{ $knownEventLocation === 'Others' ? '' : 'hidden' }}">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Specific Event Location</label>
                        <input id="specific_event_location_input" type="text" name="specific_event_location" value="{{ $specificEventLocation }}" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter specific event location">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Expires At (Optional)</label>
                    <input type="datetime-local" name="expires_at" value="{{ old('expires_at', !empty($announcement->expires_at) ? \Carbon\Carbon::parse($announcement->expires_at)->format('Y-m-d\\TH:i') : '') }}" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="flex items-end">
                    <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                        <input type="checkbox" name="is_published" value="1" {{ old('is_published', (isset($announcement->is_published) ? (int) $announcement->is_published : 0)) ? 'checked' : '' }} class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        Publish
                    </label>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Content</label>
                    <textarea name="content" rows="8" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" required>{{ old('content', $announcement->content) }}</textarea>
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-2">
            <a href="{{ route('admin.announcements') }}" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-200 transition-colors">Cancel</a>
            <button type="submit" class="inline-flex items-center gap-2 bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors" data-admin-ajax-submit-button>
                <i class='bx bx-save'></i>
                <span data-admin-ajax-submit-label>Save Changes</span>
            </button>
        </div>
    </form>
</div>
<script>
    function toggleSpecificEventLocation() {
        const eventLocationSelect = document.getElementById('event_location_select');
        const specificGroup = document.getElementById('specific_event_location_group');
        const specificInput = document.getElementById('specific_event_location_input');
        if (!eventLocationSelect || !specificGroup || !specificInput) return;

        if (eventLocationSelect.value === 'Others') {
            specificGroup.classList.remove('hidden');
            specificInput.setAttribute('required', 'required');
        } else {
            specificGroup.classList.add('hidden');
            specificInput.removeAttribute('required');
            specificInput.value = '';
        }
    }

    document.addEventListener('admin:page-ready', toggleSpecificEventLocation, { once: true });
</script>
@endsection
