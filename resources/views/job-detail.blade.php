@extends('components.layouts.dashboard')

@section('title', 'Job Details')

@section('content')
<div class="pl-8 pr-6 pt-6 pb-6">
    <div class="mb-6">
        <a href="{{ route('jobs') }}" class="inline-flex items-center text-green-600 hover:text-green-700 mb-4">
            <i class="bx bx-arrow-back mr-2"></i>
            Back to Job Opportunities
        </a>
        <h1 class="text-3xl font-bold text-gray-900">{{ $job['title'] }}</h1>
        <p class="text-xl text-gray-600 mt-1">{{ $job['company'] }}</p>
    </div>

    <!-- Job Header -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
        <div class="flex flex-wrap justify-between items-start gap-4">
            <div class="flex-1">
                <div class="flex flex-wrap gap-6 text-sm text-gray-600 mb-4">
                    <span class="flex items-center">
                        <i class="bx bx-map-pin mr-2 text-lg"></i>
                        {{ $job['location'] }}
                    </span>
                    <span class="flex items-center">
                        <i class="bx bx-time mr-2 text-lg"></i>
                        Posted {{ $job['posted'] }}
                    </span>
                    <span class="flex items-center">
                        <i class="bx bx-calendar mr-2 text-lg"></i>
                        Deadline: {{ $job['application_deadline'] }}
                    </span>
                </div>
                <div class="flex items-center gap-4">
                    <span class="px-4 py-2 text-sm font-medium rounded-full bg-green-100 text-green-800">
                        {{ $job['type'] }}
                    </span>
                    <span class="px-4 py-2 text-sm font-medium rounded-full bg-blue-100 text-blue-800">
                        Active
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Job Description -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
        <h2 class="text-2xl font-semibold text-gray-900 mb-4">Job Description</h2>
        <p class="text-gray-700 leading-relaxed">{{ $job['description'] }}</p>
    </div>

    <!-- Requirements -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
        <h2 class="text-2xl font-semibold text-gray-900 mb-4">Requirements</h2>
        <ul class="space-y-3">
            @foreach($job['requirements'] as $requirement)
            <li class="flex items-start">
                <i class="bx bx-check text-green-600 mr-3 mt-1 flex-shrink-0"></i>
                <span class="text-gray-700">{{ $requirement }}</span>
            </li>
            @endforeach
        </ul>
    </div>

    <!-- Responsibilities -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
        <h2 class="text-2xl font-semibold text-gray-900 mb-4">Key Responsibilities</h2>
        <ul class="space-y-3">
            @foreach($job['responsibilities'] as $responsibility)
            <li class="flex items-start">
                <i class="bx bx-right-arrow-alt text-blue-600 mr-3 mt-1 flex-shrink-0"></i>
                <span class="text-gray-700">{{ $responsibility }}</span>
            </li>
            @endforeach
        </ul>
    </div>

    <!-- Benefits -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
        <h2 class="text-2xl font-semibold text-gray-900 mb-4">Benefits & Perks</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            @foreach($job['benefits'] as $benefit)
            <div class="flex items-center">
                <i class="bx bx-star text-yellow-500 mr-3"></i>
                <span class="text-gray-700">{{ $benefit }}</span>
            </div>
            @endforeach
        </div>
    </div>

    <!-- Contact Information -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h2 class="text-2xl font-semibold text-gray-900 mb-4">How to Apply</h2>
        <div class="bg-gray-50 rounded-lg p-4">
            <p class="text-gray-700 mb-3">
                Interested candidates are encouraged to send their resume and cover letter to:
            </p>
            <div class="flex items-center text-green-600 font-medium">
                <i class="bx bx-envelope mr-2"></i>
                {{ $job['contact_email'] }}
            </div>
            <p class="text-sm text-gray-600 mt-3">
                Please include "Job Application - {{ $job['title'] }}" in the subject line.
            </p>
        </div>
    </div>
</div>
@endsection