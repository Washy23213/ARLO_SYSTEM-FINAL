@extends('components.layouts.dashboard')

@section('title', 'Dashboard')

@section('content')
<div class="pl-8 pr-6 pt-6 pb-6"></div>
        <!-- STAT CARDS -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 flex items-center gap-4">
                <div class="bg-green-700 text-white p-3 rounded-lg">
                    <i class='bx bxs-user-detail text-xl'></i>
                </div>
                <div>
                    <p class="text-gray-600 text-sm font-medium">Total Alumni</p>
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($totalAlumni) }}</p>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 flex items-center gap-4">
                <div class="bg-green-600 text-white p-3 rounded-lg">
                    <i class='bx bxs-briefcase text-xl'></i>
                </div>
                <div>
                    <p class="text-gray-600 text-sm font-medium">Employed</p>
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($employed) }}</p>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 flex items-center gap-4">
                <div class="bg-yellow-500 text-white p-3 rounded-lg">
                    <i class='bx bxs-store text-xl'></i>
                </div>
                <div>
                    <p class="text-gray-600 text-sm font-medium">Self-Employed</p>
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($selfEmployed) }}</p>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 flex items-center gap-4">
                <div class="bg-red-500 text-white p-3 rounded-lg">
                    <i class='bx bx-history text-xl'></i>
                </div>
                <div>
                    <p class="text-gray-600 text-sm font-medium">Unemployed</p>
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($unemployed) }}</p>
                </div>
            </div>
        </div>

        <!-- Alumni Records Summary -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-8">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-lg font-semibold text-gray-900">Recent Alumni Tracer Records</h2>
                <a href="#" class="text-green-700 hover:text-green-800 text-sm font-medium">View All</a>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Year Graduated</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Degree</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employment Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Industry</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($recentAlumni as $alumni)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                {{ $alumni->firstname ?? 'N/A' }} {{ $alumni->lastname ?? '' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $alumni->yearGraduated ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $alumni->degree ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($alumni->employedAfterGrad ?? null == 'Yes')
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Employed</span>
                                @elseif($alumni->employedAfterGrad ?? null == 'No')
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Unemployed</span>
                                @else
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">Unknown</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $alumni->industry ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <button class="text-green-600 hover:text-green-900">View Details</button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-6 py-4 text-center text-gray-500">No alumni records found</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- CHARTS -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <!-- Employment Trends -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Employment Trends</h3>
                    <select class="border border-gray-300 rounded-md px-3 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
                        <option>Last 12 Months</option>
                        <option>Last 6 Months</option>
                        <option>Last 3 Months</option>
                    </select>
                </div>
                <div class="h-80">
                    <canvas id="employmentChart"></canvas>
                </div>
            </div>

            <!-- Traced Graduates Per Year/Month/College -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Traced Graduates</h3>
                    <div class="flex gap-2">
                        <select class="border border-gray-300 rounded-md px-3 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
                            <option>Per Year</option>
                            <option>Per Month</option>
                        </select>
                        <select class="border border-gray-300 rounded-md px-3 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
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

            <!-- Industry Distribution -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Industry Distribution</h3>
                    <div class="flex gap-2">
                        <select class="border border-gray-300 rounded-md px-3 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
                            <option>Per Industry</option>
                        </select>
                        <select class="border border-gray-300 rounded-md px-3 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
                            <option>2024</option>
                            <option>2023</option>
                        </select>
                        <select class="border border-gray-300 rounded-md px-3 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
                            <option>All Colleges</option>
                            <option>College of Engineering</option>
                        </select>
                    </div>
                </div>
                <div class="h-80 flex items-center justify-center">
                    <canvas id="industryChart" style="max-width: 300px; max-height: 300px;"></canvas>
                </div>
            </div>

            <!-- Employability Rate -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Employability Rate</h3>
                    <div class="flex gap-2">
                        <select class="border border-gray-300 rounded-md px-3 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
                            <option>Per Industry</option>
                        </select>
                        <select class="border border-gray-300 rounded-md px-3 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
                            <option>2024</option>
                            <option>2023</option>
                        </select>
                        <select class="border border-gray-300 rounded-md px-3 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
                            <option>All Colleges</option>
                            <option>College of Engineering</option>
                        </select>
                    </div>
                </div>
                <div class="h-80">
                    <canvas id="employabilityChart"></canvas>
                </div>
            </div>

            <!-- Global Distribution Bar Graph -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Global Distribution</h3>
                    <div class="flex gap-2">
                        <select class="border border-gray-300 rounded-md px-3 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
                            <option>Per Industry</option>
                        </select>
                        <select class="border border-gray-300 rounded-md px-3 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
                            <option>All Colleges</option>
                            <option>College of Engineering</option>
                        </select>
                    </div>
                </div>
                <div class="h-80">
                    <canvas id="globalDistributionChart"></canvas>
                </div>
            </div>

            <!-- Feedbacks Bar Graph -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Feedbacks</h3>
                </div>
                <div class="h-80">
                    <canvas id="feedbacksChart"></canvas>
                </div>
            </div>

            <!-- Reasons Why Not Yet Employed -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 lg:col-span-2">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Reasons Why Not Yet Employed</h3>
                </div>
                <div class="h-80">
                    <canvas id="reasonsChart"></canvas>
                </div>
            </div>
        </div>
    </div>

<script>
// Current date
document.getElementById('current-date').textContent = new Date().toLocaleDateString('en-US', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric'
});

// Employment Status Trends Chart
const employmentCtx = document.getElementById('employmentChart').getContext('2d');
new Chart(employmentCtx, {
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

// Traced Graduates Per Year Chart
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

// Employment Trends by College Chart
const collegeCtx = document.getElementById('collegeTrendsChart').getContext('2d');
new Chart(collegeCtx, {
    type: 'bar',
    data: {
        labels: @json($collegeTrends['labels']),
        datasets: [{
            label: 'Employed',
            data: @json($collegeTrends['employed']),
            backgroundColor: '#16a34a'
        }, {
            label: 'Self-Employed',
            data: @json($collegeTrends['selfEmployed']),
            backgroundColor: '#eab308'
        }, {
            label: 'Unemployed',
            data: @json($collegeTrends['unemployed']),
            backgroundColor: '#dc2626'
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
            x: {
                stacked: true
            },
            y: {
                stacked: true,
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
    type: 'horizontalBar',
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