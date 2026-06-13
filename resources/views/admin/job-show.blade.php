@extends('admin.layout')

@section('title', 'Job Details')

@section('content')
<div class="p-6">
    <div class="mb-6 rounded-xl border border-blue-100 bg-gradient-to-r from-blue-50 to-indigo-50 p-6">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Job Details</h1>
                <p class="text-gray-600 mt-1">Posting details</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
                <p class="text-xs uppercase tracking-wide text-gray-500">Job Title</p>
                <p class="text-gray-900 font-medium mt-1">{{ $job->title ?? 'N/A' }}</p>
            </div>
            <div>
                <p class="text-xs uppercase tracking-wide text-gray-500">Company</p>
                <p class="text-gray-900 font-medium mt-1">{{ $job->company ?? 'N/A' }}</p>
            </div>
            <div>
                <p class="text-xs uppercase tracking-wide text-gray-500">Type</p>
                <p class="text-gray-900 font-medium mt-1">{{ $job->type ?? 'N/A' }}</p>
            </div>
            <div>
                <p class="text-xs uppercase tracking-wide text-gray-500">Location</p>
                <p class="text-gray-900 font-medium mt-1">{{ $job->location ?? 'N/A' }}</p>
            </div>
            <div>
                <p class="text-xs uppercase tracking-wide text-gray-500">Salary Range</p>
                <p class="text-gray-900 font-medium mt-1">{{ \App\Support\SystemOptions::salaryRangeLabel($job->salary_range ?? null) ?? 'N/A' }}</p>
            </div>
            <div>
                <p class="text-xs uppercase tracking-wide text-gray-500">Application Deadline</p>
                <p class="text-gray-900 font-medium mt-1">{{ !empty($job->application_deadline) ? \Carbon\Carbon::parse($job->application_deadline)->format('M d, Y') : 'N/A' }}</p>
            </div>
            <div>
                <p class="text-xs uppercase tracking-wide text-gray-500">Contact Email</p>
                <p class="text-gray-900 font-medium mt-1">{{ $job->contact_email ?? 'N/A' }}</p>
            </div>
            <div>
                <p class="text-xs uppercase tracking-wide text-gray-500">Contact Phone</p>
                <p class="text-gray-900 font-medium mt-1">{{ $job->contact_phone ?? 'N/A' }}</p>
            </div>
            <div class="md:col-span-2">
                <p class="text-xs uppercase tracking-wide text-gray-500">Description</p>
                <p class="text-gray-900 mt-1 whitespace-pre-line">{{ $job->description ?? 'N/A' }}</p>
            </div>
            <div class="md:col-span-2">
                <p class="text-xs uppercase tracking-wide text-gray-500">Requirements</p>
                <p class="text-gray-900 mt-1 whitespace-pre-line">{{ $job->requirements ?? 'N/A' }}</p>
            </div>
        </div>
    </div>
</div>
@endsection
