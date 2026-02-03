@extends('components.layouts.dashboard')

@section('title', 'Job Opportunities')

@section('content')
<div class="pl-8 pr-6 pt-6 pb-6">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Job Opportunities</h1>
        <p class="text-gray-600 mt-1">View job opportunities posted by administrators</p>
    </div>

    <!-- Job Listings -->
    <div class="space-y-4">
        @forelse($jobs as $job)
        <a href="{{ route('job.detail', $job['id']) }}" class="block bg-white rounded-lg shadow-sm border border-gray-200 p-6 hover:shadow-md hover:border-green-300 transition-all duration-200 cursor-pointer">
            <div class="flex justify-between items-start mb-4">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 hover:text-green-700 transition-colors">{{ $job['title'] ?? 'Job Title' }}</h3>
                    <p class="text-gray-600">{{ $job['company'] ?? 'Company Name' }}</p>
                </div>
                <span class="px-3 py-1 text-sm font-medium rounded-full bg-green-100 text-green-800">
                    {{ $job['type'] ?? 'Full-time' }}
                </span>
            </div>
            <div class="flex flex-wrap gap-4 text-sm text-gray-600 mb-4">
                <span><i class="bx bx-map-pin mr-1"></i>{{ $job['location'] ?? 'Location' }}</span>
                <span><i class="bx bx-time mr-1"></i>{{ $job['posted'] ?? 'Posted date' }}</span>
            </div>
            <p class="text-gray-700 mb-4">{{ $job['description'] ?? 'Job description...' }}</p>
            <div class="flex flex-wrap gap-2">
                @foreach($job['tags'] ?? ['Tag1', 'Tag2'] as $tag)
                <span class="px-2 py-1 text-xs bg-gray-100 text-gray-600 rounded">{{ $tag }}</span>
                @endforeach
            </div>
        </a>
        @empty
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 text-center">
            <p class="text-gray-500">No job opportunities available at the moment.</p>
        </div>
        @endforelse
    </div>
</div>
@endsection