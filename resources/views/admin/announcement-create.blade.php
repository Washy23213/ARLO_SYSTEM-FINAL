@extends('admin.layout')

@section('title', 'Add Announcement')

@section('content')
@php
    $audienceOptions = ['All', 'Students', 'Alumni', 'Faculty', 'Staff', 'Employers'];
    $locationOptions = ['Main Campus', 'Online', 'Bukidnon', 'Cagayan de Oro', 'Davao City', 'Manila', 'Others'];
    $targetDepartmentOptions = $targetDepartmentOptions ?? [];
    $targetGraduationYearOptions = $targetGraduationYearOptions ?? [];
@endphp
<div class="p-6">
    <div class="mb-6 rounded-xl border border-blue-100 bg-gradient-to-r from-blue-50 to-cyan-50 p-6">
        <h1 class="text-3xl font-bold text-gray-900">Add Announcement</h1>
        <p class="text-gray-600 mt-1">Create a notification announcement for the alumni side.</p>
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

    <form action="{{ route('admin.announcements.store') }}" method="POST" class="space-y-6">
        @csrf
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Title</label>
                    <input type="text" name="title" value="{{ old('title') }}" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                    <select name="type" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                        <option value="">Select Type</option>
                        @foreach(['Event', 'News', 'Update', 'Alert', 'Job'] as $type)
                            <option value="{{ $type }}" {{ old('type') === $type ? 'selected' : '' }}>{{ $type }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Priority</label>
                    <select name="priority" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                        <option value="">Select Priority</option>
                        @foreach(['Low', 'Medium', 'High', 'Urgent'] as $priority)
                            <option value="{{ $priority }}" {{ old('priority', 'Medium') === $priority ? 'selected' : '' }}>{{ $priority }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Target Audience</label>
                    <select name="target_audience" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        @foreach($audienceOptions as $audience)
                            <option value="{{ $audience }}" {{ old('target_audience', 'All') === $audience ? 'selected' : '' }}>{{ $audience }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Target College (Optional)</label>
                    <select name="target_department" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">All Colleges</option>
                        @foreach($targetDepartmentOptions as $department)
                            <option value="{{ $department }}" {{ old('target_department') === $department ? 'selected' : '' }}>{{ $department }}</option>
                        @endforeach
                    </select>
                    <p class="mt-2 text-xs text-gray-500">Leave blank for all colleges.</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Target Graduation Year (Optional)</label>
                    <select name="target_graduation_year" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">All Years</option>
                        @foreach($targetGraduationYearOptions as $year)
                            <option value="{{ $year }}" {{ (string) old('target_graduation_year') === (string) $year ? 'selected' : '' }}>{{ $year }}</option>
                        @endforeach
                    </select>
                    <p class="mt-2 text-xs text-gray-500">Leave blank for all years.</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Event Date (Optional)</label>
                    <input type="datetime-local" name="event_date" value="{{ old('event_date') }}" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Event Location (Optional)</label>
                    <select id="event_location_select" name="event_location" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" onchange="toggleSpecificEventLocation()">
                        <option value="">Select Event Location</option>
                        @foreach($locationOptions as $location)
                            <option value="{{ $location }}" {{ old('event_location') === $location ? 'selected' : '' }}>{{ $location }}</option>
                        @endforeach
                    </select>
                    <div id="specific_event_location_group" class="mt-3 {{ old('event_location') === 'Others' ? '' : 'hidden' }}">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Specific Event Location</label>
                        <input id="specific_event_location_input" type="text" name="specific_event_location" value="{{ old('specific_event_location') }}" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter specific event location">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Expires At (Optional)</label>
                    <input type="datetime-local" name="expires_at" value="{{ old('expires_at') }}" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="flex items-end">
                    <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                        <input type="checkbox" name="is_published" value="1" {{ old('is_published', '1') ? 'checked' : '' }} class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        Publish now
                    </label>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Content</label>
                    <textarea name="content" rows="8" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" required>{{ old('content') }}</textarea>
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-2">
            <a href="{{ route('admin.announcements') }}" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-200 transition-colors">Cancel</a>
            <button type="submit" class="inline-flex items-center gap-2 bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors">
                <i class='bx bx-send'></i>
                Add Announcement
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
