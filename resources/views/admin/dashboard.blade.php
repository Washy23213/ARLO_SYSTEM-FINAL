@extends('admin.layout')

@section('title', 'Admin Dashboard')

@section('content')
<div class="p-6">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Admin Dashboard</h1>
        <p class="text-gray-600 mt-1">Manage alumni system and monitor activities</p>
    </div>

    {{-- Statistics Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-blue-100">
                    <i class='bx bx-group text-blue-600 text-2xl'></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Total Alumni</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $totalAlumni }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-green-100">
                    <i class='bx bx-briefcase text-green-600 text-2xl'></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Active Jobs</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $totalJobs }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-purple-100">
                    <i class='bx bx-bell text-purple-600 text-2xl'></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Announcements</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $totalAnnouncements }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-orange-100">
                    <i class='bx bx-trending-up text-orange-600 text-2xl'></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">This Month</p>
                    <p class="text-2xl font-bold text-gray-900">+67</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Charts Section - Same as Alumni Dashboard --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        {{-- Employment Trends --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Employment Trends</h3>
                <select class="border border-gray-300 rounded-md px-3 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option>Last 12 Months</option>
                    <option>Last 6 Months</option>
                    <option>Last 3 Months</option>
                </select>
            </div>
            <div class="h-80">
                <canvas id="employmentTrendsChart"></canvas>
            </div>
        </div>

        {{-- Traced Graduates --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Traced Graduates</h3>
                <div class="flex gap-2">
                    <select class="border border-gray-300 rounded-md px-3 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option>Per Year</option>
                        <option>Per Month</option>
                    </select>
                    <select class="border border-gray-300 rounded-md px-3 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option>All Colleges</option>
                        <option>College of Engineering</option>
                        <option>College of Business</option>
                    </select>
                </div>
            </div>
            <div class="h-80">
                <canvas id="tracedGraduatesChart"></canvas>
            </div>
        </div>

        {{-- Industry Distribution --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Industry Distribution</h3>
                <div class="flex gap-2">
                    <select class="border border-gray-300 rounded-md px-3 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option>Per Industry</option>
                    </select>
                    <select class="border border-gray-300 rounded-md px-3 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option>2024</option>
                        <option>2023</option>
                    </select>
                    <select class="border border-gray-300 rounded-md px-3 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option>All Colleges</option>
                        <option>College of Engineering</option>
                    </select>
                </div>
            </div>
            <div class="h-80 flex items-center justify-center">
                <canvas id="industryChart" style="max-width: 300px; max-height: 300px;"></canvas>
            </div>
        </div>

        {{-- Employability Rate --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Employability Rate</h3>
                <div class="flex gap-2">
                    <select class="border border-gray-300 rounded-md px-3 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option>Per Industry</option>
                    </select>
                    <select class="border border-gray-300 rounded-md px-3 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option>2024</option>
                        <option>2023</option>
                    </select>
                    <select class="border border-gray-300 rounded-md px-3 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option>All Colleges</option>
                        <option>College of Engineering</option>
                    </select>
                </div>
            </div>
            <div class="h-80">
                <canvas id="employabilityChart"></canvas>
            </div>
        </div>

        {{-- Global Distribution --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Global Distribution</h3>
                <div class="flex gap-2">
                    <select class="border border-gray-300 rounded-md px-3 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option>Per Industry</option>
                    </select>
                    <select class="border border-gray-300 rounded-md px-3 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option>All Colleges</option>
                        <option>College of Engineering</option>
                    </select>
                </div>
            </div>
            <div class="h-80">
                <canvas id="globalDistributionChart"></canvas>
            </div>
        </div>

        {{-- Feedbacks --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Feedbacks</h3>
            </div>
            <div class="h-80">
                <canvas id="feedbacksChart"></canvas>
            </div>
        </div>

        {{-- Reasons Why Not Yet Employed --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 lg:col-span-2">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Reasons Why Not Yet Employed</h3>
            </div>
            <div class="h-80">
                <canvas id="reasonsChart"></canvas>
            </div>
        </div>
    </div>

    {{-- Recent Activity --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        {{-- Recent Registrations --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Recent Registrations</h3>
            <div class="space-y-3">
                @forelse($recentRegistrations as $alumni)
                <div class="flex items-center justify-between py-2 border-b border-gray-100 last:border-b-0">
                    <div class="flex items-center">
                        <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                            <i class='bx bx-user text-blue-600'></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-gray-900">{{ $alumni->firstname ?? 'N/A' }} {{ $alumni->lastname ?? '' }}</p>
                            <p class="text-xs text-gray-500">{{ $alumni->department ?? 'N/A' }}</p>
                        </div>
                    </div>
                    <span class="text-xs text-gray-400">{{ $alumni->created_at ? $alumni->created_at->diffForHumans() : 'N/A' }}</span>
                </div>
                @empty
                <p class="text-gray-500 text-sm">No recent registrations</p>
                @endforelse
            </div>
        </div>

        {{-- System Overview --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">System Overview</h3>
            <div class="space-y-4">
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">Active Users Today</span>
                    <span class="text-sm font-medium text-gray-900">127</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">Jobs Posted This Week</span>
                    <span class="text-sm font-medium text-gray-900">12</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">Announcements This Month</span>
                    <span class="text-sm font-medium text-gray-900">8</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">System Uptime</span>
                    <span class="text-sm font-medium text-green-600">99.8%</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Quick Actions --}}
    <div class="mt-8 bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <a href="{{ route('admin.alumni') }}" class="flex flex-col items-center p-4 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors">
                <i class='bx bx-group text-blue-600 text-2xl mb-2'></i>
                <span class="text-sm font-medium text-gray-900">Manage Alumni</span>
            </a>
            <a href="{{ route('admin.jobs') }}" class="flex flex-col items-center p-4 bg-green-50 rounded-lg hover:bg-green-100 transition-colors">
                <i class='bx bx-plus-circle text-green-600 text-2xl mb-2'></i>
                <span class="text-sm font-medium text-gray-900">Add Job</span>
            </a>
            <a href="{{ route('admin.announcements') }}" class="flex flex-col items-center p-4 bg-purple-50 rounded-lg hover:bg-purple-100 transition-colors">
                <i class='bx bx-bell-plus text-purple-600 text-2xl mb-2'></i>
                <span class="text-sm font-medium text-gray-900">New Announcement</span>
            </a>
            <a href="{{ route('admin.analytics') }}" class="flex flex-col items-center p-4 bg-orange-50 rounded-lg hover:bg-orange-100 transition-colors">
                <i class='bx bx-bar-chart text-orange-600 text-2xl mb-2'></i>
                <span class="text-sm font-medium text-gray-900">View Analytics</span>
            </a>
        </div>
    </div>
</div>

<script>
    // Employment Trends Chart
    const employmentTrendsCtx = document.getElementById('employmentTrendsChart').getContext('2d');
    new Chart(employmentTrendsCtx, {
        type: 'line',
        data: {
            labels: @json($employmentTrends['labels']),
            datasets: [{
                label: 'Employed',
                data: @json($employmentTrends['employed']),
                borderColor: '#16a34a',
                backgroundColor: 'rgba(22, 163, 74, 0.1)',
                tension: 0.4
            }, {
                label: 'Self-Employed',
                data: @json($employmentTrends['selfEmployed']),
                borderColor: '#eab308',
                backgroundColor: 'rgba(234, 179, 8, 0.1)',
                tension: 0.4
            }, {
                label: 'Unemployed',
                data: @json($employmentTrends['unemployed']),
                borderColor: '#dc2626',
                backgroundColor: 'rgba(220, 38, 38, 0.1)',
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    // Industry Distribution Chart
    const industryCtx = document.getElementById('industryChart').getContext('2d');
    new Chart(industryCtx, {
        type: 'doughnut',
        data: {
            labels: @json($industryData['labels']),
            datasets: [{
                data: @json($industryData['data']),
                backgroundColor: [
                    '#16a34a',
                    '#059669',
                    '#047857',
                    '#065f46',
                    '#064e3b',
                    '#022c22'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                }
            }
        }
    });

    // Traced Graduates Chart
    const tracedCtx = document.getElementById('tracedGraduatesChart').getContext('2d');
    new Chart(tracedCtx, {
        type: 'bar',
        data: {
            labels: @json($tracedGraduates['labels']),
            datasets: [{
                label: 'Traced Graduates',
                data: @json($tracedGraduates['data']),
                backgroundColor: '#16a34a',
                borderColor: '#16a34a',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    // Employability Rate Chart
    const employabilityCtx = document.getElementById('employabilityChart').getContext('2d');
    new Chart(employabilityCtx, {
        type: 'line',
        data: {
            labels: ['2019', '2020', '2021', '2022', '2023', '2024'],
            datasets: [{
                label: 'Employability Rate (%)',
                data: [85, 82, 88, 90, 92, 94],
                borderColor: '#16a34a',
                backgroundColor: 'rgba(22, 163, 74, 0.1)',
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100
                }
            }
        }
    });

    // Global Distribution Chart
    const globalCtx = document.getElementById('globalDistributionChart').getContext('2d');
    new Chart(globalCtx, {
        type: 'bar',
        data: {
            labels: ['Philippines', 'USA', 'Canada', 'Australia', 'UK', 'Others'],
            datasets: [{
                label: 'Alumni Distribution',
                data: [1200, 300, 150, 100, 80, 200],
                backgroundColor: '#16a34a'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    // Feedbacks Chart
    const feedbacksCtx = document.getElementById('feedbacksChart').getContext('2d');
    new Chart(feedbacksCtx, {
        type: 'bar',
        data: {
            labels: ['Very Satisfied', 'Satisfied', 'Neutral', 'Dissatisfied', 'Very Dissatisfied'],
            datasets: [{
                label: 'Feedback Count',
                data: [450, 380, 120, 50, 20],
                backgroundColor: '#16a34a'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    // Reasons Why Not Yet Employed Chart
    const reasonsCtx = document.getElementById('reasonsChart').getContext('2d');
    new Chart(reasonsCtx, {
        type: 'bar',
        data: {
            labels: ['Lack of Experience', 'No Job Opportunities', 'Family Reasons', 'Further Studies', 'Health Issues', 'Other'],
            datasets: [{
                label: 'Number of Alumni',
                data: [120, 95, 60, 45, 25, 30],
                backgroundColor: '#dc2626'
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                x: {
                    beginAtZero: true
                }
            }
        }
    });
</script>
@endsection