@extends('components.layouts.dashboard')

@section('title', 'Announcement Details')

@section('content')
<div class="pl-8 pr-6 pt-6 pb-6">
    <div class="mb-6">
        <a href="{{ route('dashboard') }}" class="inline-flex items-center text-green-600 hover:text-green-700 mb-4">
            <i class="bx bx-arrow-back mr-2"></i>
            Back to Dashboard
        </a>
        <h1 class="text-3xl font-bold text-gray-900">{{ $announcement['title'] }}</h1>
        <p class="text-xl text-gray-600 mt-1">{{ $announcement['company'] }}</p>
    </div>

    <!-- Announcement Header -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
        <div class="flex flex-wrap justify-between items-start gap-4">
            <div class="flex-1">
                <div class="flex flex-wrap gap-6 text-sm text-gray-600 mb-4">
                    <span class="flex items-center">
                        <i class="bx bx-time mr-2 text-lg"></i>
                        Posted {{ $announcement['posted'] }}
                    </span>
                    @if($announcement['deadline'])
                    <span class="flex items-center">
                        <i class="bx bx-calendar mr-2 text-lg"></i>
                        Deadline: {{ $announcement['deadline'] }}
                    </span>
                    @endif
                </div>
                <div class="flex items-center gap-4">
                    @if($announcement['type'] === 'job')
                        <span class="px-4 py-2 text-sm font-medium rounded-full bg-blue-100 text-blue-800">
                            Job Opportunity
                        </span>
                    @elseif($announcement['type'] === 'event')
                        <span class="px-4 py-2 text-sm font-medium rounded-full bg-purple-100 text-purple-800">
                            Event
                        </span>
                    @else
                        <span class="px-4 py-2 text-sm font-medium rounded-full bg-green-100 text-green-800">
                            Announcement
                        </span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Announcement Content -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
        <h2 class="text-2xl font-semibold text-gray-900 mb-4">Details</h2>
        <p class="text-gray-700 leading-relaxed mb-6">{{ $announcement['content'] }}</p>

        @if(isset($announcement['details']) && is_array($announcement['details']))
            <div class="space-y-3">
                @foreach($announcement['details'] as $detail)
                    @if(strpos($detail, ':') !== false)
                        @php
                            $parts = explode(':', $detail, 2);
                            $label = trim($parts[0]);
                            $value = trim($parts[1]);
                        @endphp
                        <div class="flex justify-between items-start py-2 border-b border-gray-100 last:border-b-0">
                            <span class="font-medium text-gray-900 flex-shrink-0 mr-4">{{ $label }}:</span>
                            <span class="text-gray-700 text-right">{{ $value }}</span>
                        </div>
                    @else
                        <div class="bg-gray-50 rounded-lg p-3">
                            <p class="text-gray-800">{{ $detail }}</p>
                        </div>
                    @endif
                @endforeach
            </div>
        @endif
    </div>

    <!-- Contact Information -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h2 class="text-2xl font-semibold text-gray-900 mb-4">Contact Information</h2>
        <div class="bg-gray-50 rounded-lg p-4">
            <p class="text-gray-700 mb-3">{{ $announcement['contact_info'] }}</p>
            @if($announcement['type'] === 'job')
                <p class="text-sm text-gray-600">
                    Interested candidates should send their resume and cover letter with the subject line: "Application - {{ $announcement['title'] }}"
                </p>
            @elseif($announcement['type'] === 'event')
                <p class="text-sm text-gray-600">
                    Don't miss this opportunity to connect with fellow alumni and industry professionals!
                </p>
            @else
                <p class="text-sm text-gray-600">
                    For more information, please contact the organization directly.
                </p>
            @endif
        </div>
    </div>
</div>
@endsection