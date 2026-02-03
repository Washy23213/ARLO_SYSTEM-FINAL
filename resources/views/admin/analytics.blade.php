@extends('admin.layout')

@section('title', 'Analytics & Reports')

@section('content')
<div class="p-6">
    <div class="mb-8">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Analytics & Reports</h1>
                <p class="text-gray-600 mt-1">Comprehensive system analytics and insights</p>
            </div>
            <div class="flex gap-3">
                <button class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition-colors">
                    <i class='bx bx-export mr-2'></i>
                    Export Report
                </button>
                <button class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                    <i class='bx bx-refresh mr-2'></i>
                    Refresh Data
                </button>
            </div>
        </div>
    </div>

    {{-- Key Metrics --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Total Users</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $totalUsers ?? 0 }}</p>
                    <p class="text-xs text-green-600 mt-1">+12% from last month</p>
                </div>
                <i class='bx bx-group text-blue-600 text-2xl'></i>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Active Sessions</p>
                    <p class="text-2xl font-bold text-gray-900">1,247</p>
                    <p class="text-xs text-green-600 mt-1">+8% from yesterday</p>
                </div>
                <i class='bx bx-activity text-green-600 text-2xl'></i>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Job Applications</p>
                    <p class="text-2xl font-bold text-gray-900">3,456</p>
                    <p class="text-xs text-red-600 mt-1">-3% from last week</p>
                </div>
                <i class='bx bx-file text-orange-600 text-2xl'></i>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Data Completeness</p>
                    <p class="text-2xl font-bold text-gray-900">94.2%</p>
                    <p class="text-xs text-green-600 mt-1">+2.1% from last month</p>
                </div>
                <i class='bx bx-data text-purple-600 text-2xl'></i>
            </div>
        </div>
    </div>

    {{-- Charts Row --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        {{-- User Growth Chart --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">User Growth</h3>
            <canvas id="userGrowthChart" width="400" height="200"></canvas>
        </div>

        {{-- Job Applications Chart --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Job Applications Trend</h3>
            <canvas id="jobApplicationsChart" width="400" height="200"></canvas>
        </div>
    </div>

    {{-- Additional Charts --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        {{-- College Distribution --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Alumni by College</h3>
            <canvas id="collegeDistributionChart" width="400" height="200"></canvas>
        </div>

        {{-- Employment Status --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Employment Status</h3>
            <canvas id="employmentStatusChart" width="400" height="200"></canvas>
        </div>

        {{-- Graduation Year Distribution --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Graduation Year Trends</h3>
            <canvas id="graduationYearChart" width="400" height="200"></canvas>
        </div>

        {{-- Industry Growth --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Industry Growth</h3>
            <canvas id="industryGrowthChart" width="400" height="200"></canvas>
        </div>
    </div>

    {{-- Detailed Reports --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- College Performance Analytics --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">College Performance</h3>
            <div class="space-y-3">
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">Engineering</span>
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-medium text-gray-900">85% employed</span>
                        <div class="w-16 bg-gray-200 rounded-full h-2">
                            <div class="bg-green-600 h-2 rounded-full" style="width: 85%"></div>
                        </div>
                    </div>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">Business</span>
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-medium text-gray-900">78% employed</span>
                        <div class="w-16 bg-gray-200 rounded-full h-2">
                            <div class="bg-green-600 h-2 rounded-full" style="width: 78%"></div>
                        </div>
                    </div>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">Arts & Sciences</span>
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-medium text-gray-900">72% employed</span>
                        <div class="w-16 bg-gray-200 rounded-full h-2">
                            <div class="bg-green-600 h-2 rounded-full" style="width: 72%"></div>
                        </div>
                    </div>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">Education</span>
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-medium text-gray-900">68% employed</span>
                        <div class="w-16 bg-gray-200 rounded-full h-2">
                            <div class="bg-green-600 h-2 rounded-full" style="width: 68%"></div>
                        </div>
                    </div>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">Nursing</span>
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-medium text-gray-900">82% employed</span>
                        <div class="w-16 bg-gray-200 rounded-full h-2">
                            <div class="bg-green-600 h-2 rounded-full" style="width: 82%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Recent Activity --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Recent Activity</h3>
            <div class="space-y-3">
                <div class="flex items-start space-x-3">
                    <i class='bx bx-user-plus text-green-600 mt-1'></i>
                    <div>
                        <p class="text-sm text-gray-900">New alumni registration</p>
                        <p class="text-xs text-gray-500">2 minutes ago</p>
                    </div>
                </div>
                <div class="flex items-start space-x-3">
                    <i class='bx bx-briefcase text-blue-600 mt-1'></i>
                    <div>
                        <p class="text-sm text-gray-900">Job posting created</p>
                        <p class="text-xs text-gray-500">15 minutes ago</p>
                    </div>
                </div>
                <div class="flex items-start space-x-3">
                    <i class='bx bx-bell text-orange-600 mt-1'></i>
                    <div>
                        <p class="text-sm text-gray-900">Announcement published</p>
                        <p class="text-xs text-gray-500">1 hour ago</p>
                    </div>
                </div>
                <div class="flex items-start space-x-3">
                    <i class='bx bx-file text-purple-600 mt-1'></i>
                    <div>
                        <p class="text-sm text-gray-900">Application submitted</p>
                        <p class="text-xs text-gray-500">2 hours ago</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Employment Analytics --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Employment Analytics</h3>
            <div class="space-y-4">
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span class="text-gray-600">Employment Rate</span>
                        <span class="text-green-600 font-medium">78.5%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-green-600 h-2 rounded-full" style="width: 78.5%"></div>
                    </div>
                </div>
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span class="text-gray-600">Self-Employment Rate</span>
                        <span class="text-yellow-600 font-medium">8.2%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-yellow-600 h-2 rounded-full" style="width: 8.2%"></div>
                    </div>
                </div>
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span class="text-gray-600">Unemployment Rate</span>
                        <span class="text-red-600 font-medium">13.3%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-red-600 h-2 rounded-full" style="width: 13.3%"></div>
                    </div>
                </div>
                <div class="pt-2 border-t border-gray-200">
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <p class="text-gray-600">Avg. Time to Employment</p>
                            <p class="font-medium text-gray-900">6.2 months</p>
                        </div>
                        <div>
                            <p class="text-gray-600">Top Industry</p>
                            <p class="font-medium text-gray-900">Technology</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // User Growth Chart
    const userGrowthCtx = document.getElementById('userGrowthChart').getContext('2d');
    new Chart(userGrowthCtx, {
        type: 'line',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
            datasets: [{
                label: 'New Users',
                data: [65, 89, 120, 156, 189, 234],
                borderColor: 'rgb(59, 130, 246)',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
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

    // Job Applications Chart
    const jobAppsCtx = document.getElementById('jobApplicationsChart').getContext('2d');
    new Chart(jobAppsCtx, {
        type: 'bar',
        data: {
            labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
            datasets: [{
                label: 'Applications',
                data: [45, 67, 89, 123, 156, 78, 34],
                backgroundColor: 'rgba(16, 185, 129, 0.8)',
                borderColor: 'rgb(16, 185, 129)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
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

    // College Distribution Chart
    const collegeCtx = document.getElementById('collegeDistributionChart').getContext('2d');
    new Chart(collegeCtx, {
        type: 'doughnut',
        data: {
            labels: ['Engineering', 'Business', 'Arts & Sciences', 'Medicine', 'Law'],
            datasets: [{
                data: [35, 25, 20, 12, 8],
                backgroundColor: [
                    'rgb(59, 130, 246)',
                    'rgb(16, 185, 129)',
                    'rgb(245, 158, 11)',
                    'rgb(239, 68, 68)',
                    'rgb(139, 92, 246)'
                ]
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });

    // Employment Status Chart
    const employmentCtx = document.getElementById('employmentStatusChart').getContext('2d');
    new Chart(employmentCtx, {
        type: 'pie',
        data: {
            labels: ['Employed', 'Unemployed', 'Student', 'Self-Employed'],
            datasets: [{
                data: [58, 22, 15, 5],
                backgroundColor: [
                    'rgb(16, 185, 129)',
                    'rgb(239, 68, 68)',
                    'rgb(59, 130, 246)',
                    'rgb(245, 158, 11)'
                ]
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });

    // Graduation Year Distribution Chart
    const graduationCtx = document.getElementById('graduationYearChart').getContext('2d');
    new Chart(graduationCtx, {
        type: 'bar',
        data: {
            labels: ['2019', '2020', '2021', '2022', '2023', '2024'],
            datasets: [{
                label: 'Graduates',
                data: [145, 167, 189, 203, 234, 198],
                backgroundColor: 'rgba(139, 92, 246, 0.8)',
                borderColor: 'rgb(139, 92, 246)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
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

    // Industry Growth Chart
    const industryGrowthCtx = document.getElementById('industryGrowthChart').getContext('2d');
    new Chart(industryGrowthCtx, {
        type: 'line',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
            datasets: [{
                label: 'Technology',
                data: [120, 135, 148, 162, 178, 195],
                borderColor: 'rgb(16, 185, 129)',
                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                tension: 0.4
            }, {
                label: 'Healthcare',
                data: [95, 98, 105, 112, 118, 125],
                borderColor: 'rgb(59, 130, 246)',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                tension: 0.4
            }, {
                label: 'Finance',
                data: [75, 82, 88, 94, 98, 105],
                borderColor: 'rgb(245, 158, 11)',
                backgroundColor: 'rgba(245, 158, 11, 0.1)',
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'top'
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
});
</script>
@endsection