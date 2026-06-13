@extends('admin.layout')

@section('title', 'Admin Overview')

@section('content')
<div class="space-y-6">
    <div class="portal-stat-grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3">
        <div data-tooltip="All alumni roster records: registered accounts plus CSV-only records waiting for signup" data-portal-card-link="{{ route('admin.alumni') }}#alumni-directory" role="link" tabindex="0" aria-label="Open alumni management records" class="portal-stat-card portal-stat-card--blue">
            <div class="portal-stat-card__layout">
                <span class="portal-stat-card__icon">
                    <i class='bx bx-group text-2xl'></i>
                </span>
                <div class="portal-stat-card__content">
                    <p class="portal-stat-card__kicker">Total Alumni Roster</p>
                    <p class="portal-stat-card__value">{{ number_format($totalAlumniRecords ?? $totalAlumni) }}</p>
                    <p class="portal-stat-card__meta">Registered + CSV-only</p>
                </div>
            </div>
        </div>

        <div data-tooltip="Alumni who completed signup and can log in to the alumni portal" data-portal-card-link="{{ route('admin.alumni') }}#alumni-directory" role="link" tabindex="0" aria-label="Open registered alumni accounts" class="portal-stat-card portal-stat-card--green">
            <div class="portal-stat-card__layout">
                <span class="portal-stat-card__icon">
                    <i class='bx bx-user-check text-2xl'></i>
                </span>
                <div class="portal-stat-card__content">
                    <p class="portal-stat-card__kicker">Registered Accounts</p>
                    <p class="portal-stat-card__value">{{ number_format($registeredAlumni ?? 0) }}</p>
                    <p class="portal-stat-card__meta">Completed signups</p>
                </div>
            </div>
        </div>

        <div data-tooltip="Imported CSV alumni records that have not completed account registration yet" data-portal-card-link="{{ route('admin.alumni', ['account_status' => 'preloaded']) }}#alumni-directory" role="link" tabindex="0" aria-label="Open unregistered CSV records" class="portal-stat-card portal-stat-card--orange">
            <div class="portal-stat-card__layout">
                <span class="portal-stat-card__icon">
                    <i class='bx bx-user-x text-2xl'></i>
                </span>
                <div class="portal-stat-card__content">
                    <p class="portal-stat-card__kicker">Unregistered CSV Records</p>
                    <p class="portal-stat-card__value">{{ number_format($notRegisteredAlumni ?? 0) }}</p>
                    <p class="portal-stat-card__meta">Imported, not claimed</p>
                </div>
            </div>
        </div>

        <div data-tooltip="Job posts that are active and not archived" data-portal-card-link="{{ route('admin.jobs', ['status' => 'active']) }}#job-records" role="link" tabindex="0" aria-label="Open active job posts" class="portal-stat-card portal-stat-card--green">
            <div class="portal-stat-card__layout">
                <span class="portal-stat-card__icon">
                    <i class='bx bx-briefcase text-2xl'></i>
                </span>
                <div class="portal-stat-card__content">
                    <p class="portal-stat-card__kicker">Open Job Posts</p>
                    <p class="portal-stat-card__value">{{ number_format($activeJobs ?? $totalJobs) }}</p>
                    <p class="portal-stat-card__meta">Active, not archived</p>
                </div>
            </div>
        </div>

        <div data-tooltip="Published announcements currently visible to alumni as notifications" data-portal-card-link="{{ route('admin.announcements', ['status' => 'published']) }}#notification-records" role="link" tabindex="0" aria-label="Open published notifications" class="portal-stat-card portal-stat-card--purple">
            <div class="portal-stat-card__layout">
                <span class="portal-stat-card__icon">
                    <i class='bx bx-bell text-2xl'></i>
                </span>
                <div class="portal-stat-card__content">
                    <p class="portal-stat-card__kicker">Published Notifications</p>
                    <p class="portal-stat-card__value">{{ number_format($publishedAnnouncements ?? $totalAnnouncements) }}</p>
                    <p class="portal-stat-card__meta">Visible to alumni</p>
                </div>
            </div>
        </div>

        <div data-tooltip="New completed alumni signups this month. CSV-only imports are counted separately." data-portal-card-link="{{ route('admin.alumni') }}#alumni-directory" role="link" tabindex="0" aria-label="Open alumni records for new accounts this month" class="portal-stat-card portal-stat-card--emerald">
            <div class="portal-stat-card__layout">
                <span class="portal-stat-card__icon">
                    <i class='bx bx-trending-up text-2xl'></i>
                </span>
                <div class="portal-stat-card__content">
                    <p class="portal-stat-card__kicker">New Accounts This Month</p>
                    <p class="portal-stat-card__value">+{{ number_format($newRegisteredAlumniThisMonth ?? $newAlumniThisMonth) }}</p>
                    <p class="portal-stat-card__meta">{{ now()->format('M Y') }} signups</p>
                </div>
            </div>
        </div>
    </div>

    @php
        $overviewAnalyticsTargets = [
            'employmentTrendsChart' => route('admin.analytics', ['target_chart' => 'employmentTrendsChart']).'#employmentTrendsChart',
            'tracedGraduatesChart' => route('admin.analytics', ['target_chart' => 'tracedGraduatesChart']).'#tracedGraduatesChart',
            'industryChart' => route('admin.analytics', ['target_chart' => 'industryChart']).'#industryChart',
            'employabilityChart' => route('admin.analytics', ['target_chart' => 'employabilityChart']).'#employabilityChart',
            'globalDistributionChart' => route('admin.analytics', ['target_chart' => 'globalDistributionChart']).'#globalDistributionChart',
            'feedbacksChart' => route('admin.analytics', ['target_chart' => 'feedbacksChart']).'#feedbacksChart',
            'teachingFeedbackChart' => route('admin.analytics', ['target_chart' => 'teachingFeedbackChart']).'#teachingFeedbackChart',
            'facilitiesSatisfactionChart' => route('admin.analytics', ['target_chart' => 'facilitiesSatisfactionChart']).'#facilitiesSatisfactionChart',
            'overallExperienceChart' => route('admin.analytics', ['target_chart' => 'overallExperienceChart']).'#overallExperienceChart',
            'reasonsChart' => route('admin.analytics', ['target_chart' => 'reasonsChart']).'#reasonsChart',
        ];
        $analyticsLinkClasses = 'inline-flex items-center gap-1 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700';
        $clickableCardClasses = 'surface-card p-6 cursor-pointer transition hover:-translate-y-0.5 hover:border-emerald-300 hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-emerald-500/40';
    @endphp

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <div class="{{ $clickableCardClasses }}" data-analytics-card-link="{{ $overviewAnalyticsTargets['employmentTrendsChart'] }}" role="link" tabindex="0" aria-label="Open Employment Trends in Analytics">
            <div class="mb-4 flex items-start justify-between gap-3">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Employment Trends</h3>
                </div>
                <span class="{{ $analyticsLinkClasses }}">View in Analytics <i class='bx bx-right-arrow-alt text-base'></i></span>
            </div>
            <div class="h-80">
                <canvas id="employmentTrendsChart"></canvas>
            </div>
        </div>

        <div class="{{ $clickableCardClasses }}" data-analytics-card-link="{{ $overviewAnalyticsTargets['tracedGraduatesChart'] }}" role="link" tabindex="0" aria-label="Open Traced Graduates in Analytics">
            <div class="mb-4 flex items-start justify-between gap-3">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Traced Graduates</h3>
                </div>
                <span class="{{ $analyticsLinkClasses }}">View in Analytics <i class='bx bx-right-arrow-alt text-base'></i></span>
            </div>
            <div class="h-80">
                <canvas id="tracedGraduatesChart"></canvas>
            </div>
        </div>

        <div class="{{ $clickableCardClasses }}" data-analytics-card-link="{{ $overviewAnalyticsTargets['industryChart'] }}" role="link" tabindex="0" aria-label="Open Industry Distribution in Analytics">
            <div class="mb-4 flex items-start justify-between gap-3">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Industry Distribution</h3>
                </div>
                <span class="{{ $analyticsLinkClasses }}">View in Analytics <i class='bx bx-right-arrow-alt text-base'></i></span>
            </div>
            <div class="flex h-80 items-center justify-center">
                <canvas id="industryChart" style="max-width: 300px; max-height: 300px;"></canvas>
            </div>
        </div>

        <div class="{{ $clickableCardClasses }}" data-analytics-card-link="{{ $overviewAnalyticsTargets['employabilityChart'] }}" role="link" tabindex="0" aria-label="Open Employability Rate in Analytics">
            <div class="mb-4 flex items-start justify-between gap-3">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Employability Rate</h3>
                </div>
                <span class="{{ $analyticsLinkClasses }}">View in Analytics <i class='bx bx-right-arrow-alt text-base'></i></span>
            </div>
            <div class="h-80">
                <canvas id="employabilityChart"></canvas>
            </div>
        </div>

        <div class="{{ $clickableCardClasses }}" data-analytics-card-link="{{ $overviewAnalyticsTargets['globalDistributionChart'] }}" role="link" tabindex="0" aria-label="Open Global Distribution in Analytics">
            <div class="mb-4 flex items-start justify-between gap-3">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Global Distribution</h3>
                </div>
                <span class="{{ $analyticsLinkClasses }}">View in Analytics <i class='bx bx-right-arrow-alt text-base'></i></span>
            </div>
            <div class="h-80">
                <canvas id="globalDistributionChart"></canvas>
            </div>
        </div>

        <div class="{{ $clickableCardClasses }}" data-analytics-card-link="{{ $overviewAnalyticsTargets['feedbacksChart'] }}" role="link" tabindex="0" aria-label="Open Feedback Categories in Analytics">
            <div class="mb-4 flex items-start justify-between gap-3">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Feedback Categories</h3>
                </div>
                <span class="{{ $analyticsLinkClasses }}">View in Analytics <i class='bx bx-right-arrow-alt text-base'></i></span>
            </div>
            <div class="h-80">
                <canvas id="feedbacksChart"></canvas>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:col-span-2 lg:grid-cols-3">
            <div class="{{ $clickableCardClasses }}" data-analytics-card-link="{{ $overviewAnalyticsTargets['teachingFeedbackChart'] }}" role="link" tabindex="0" aria-label="Open Teaching Feedback in Analytics">
                <div class="mb-4 flex items-start justify-between gap-3">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Teaching Feedback</h3>
                        <p class="mt-1 text-sm text-gray-500">Average rating per teaching question</p>
                    </div>
                    <span class="{{ $analyticsLinkClasses }}">View in Analytics <i class='bx bx-right-arrow-alt text-base'></i></span>
                </div>
                <div class="h-80">
                    <canvas id="teachingFeedbackChart"></canvas>
                </div>
            </div>

            <div class="{{ $clickableCardClasses }}" data-analytics-card-link="{{ $overviewAnalyticsTargets['facilitiesSatisfactionChart'] }}" role="link" tabindex="0" aria-label="Open University Facilities in Analytics">
                <div class="mb-4 flex items-start justify-between gap-3">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">University Facilities</h3>
                        <p class="mt-1 text-sm text-gray-500">Latest tracer response per alumni</p>
                    </div>
                    <span class="{{ $analyticsLinkClasses }}">View in Analytics <i class='bx bx-right-arrow-alt text-base'></i></span>
                </div>
                <div class="h-80">
                    <canvas id="facilitiesSatisfactionChart"></canvas>
                </div>
            </div>

            <div class="{{ $clickableCardClasses }}" data-analytics-card-link="{{ $overviewAnalyticsTargets['overallExperienceChart'] }}" role="link" tabindex="0" aria-label="Open Overall Experience in Analytics">
                <div class="mb-4 flex items-start justify-between gap-3">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Overall Experience</h3>
                        <p class="mt-1 text-sm text-gray-500">Average rating per overall experience question</p>
                    </div>
                    <span class="{{ $analyticsLinkClasses }}">View in Analytics <i class='bx bx-right-arrow-alt text-base'></i></span>
                </div>
                <div class="h-80">
                    <canvas id="overallExperienceChart"></canvas>
                </div>
            </div>
        </div>

        <div class="{{ $clickableCardClasses }} lg:col-span-2" data-analytics-card-link="{{ $overviewAnalyticsTargets['reasonsChart'] }}" role="link" tabindex="0" aria-label="Open Reasons for Unemployment in Analytics">
            <div class="mb-4 flex items-start justify-between gap-3">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Reasons for Unemployment</h3>
                </div>
                <span class="{{ $analyticsLinkClasses }}">View in Analytics <i class='bx bx-right-arrow-alt text-base'></i></span>
            </div>
            <div class="h-80">
                <canvas id="reasonsChart"></canvas>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <div class="surface-card p-6">
            <div class="mb-4">
                <p class="section-kicker text-emerald-700/80">Recent Activity</p>
                <h3 class="mt-2 text-2xl font-bold tracking-tight text-slate-900">Recent Registrations</h3>
            </div>
            <div class="space-y-3">
                @forelse($recentRegistrations as $alumni)
                    <div class="flex items-center justify-between border-b border-gray-100 py-2 last:border-b-0">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-100">
                                <i class='bx bx-user text-blue-600'></i>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900">{{ $alumni->firstname ?? 'N/A' }} {{ $alumni->lastname ?? '' }}</p>
                                <p class="text-xs text-gray-500">{{ $alumni->department ?? 'N/A' }}</p>
                            </div>
                        </div>
                        <span class="text-xs text-gray-400">{{ $alumni->created_at ? $alumni->created_at->diffForHumans() : 'N/A' }}</span>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">No recent registrations</p>
                @endforelse
            </div>
        </div>

        <div class="space-y-6">
            <div class="surface-card p-6">
                <div class="mb-4">
                    <p class="section-kicker text-emerald-700/80">System Overview</p>
                    <h3 class="mt-2 text-2xl font-bold tracking-tight text-slate-900">Operations Summary</h3>
                </div>
                <div class="space-y-4">
                    <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                        <span class="text-sm text-gray-600">Total Alumni Roster Records</span>
                        <span class="text-sm font-medium text-gray-900">{{ number_format($totalAlumniRecords ?? $totalAlumni) }}</span>
                    </div>
                    <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                        <span class="text-sm text-gray-600">Registered Alumni Accounts</span>
                        <span class="text-sm font-medium text-gray-900">{{ number_format($registeredAlumni ?? 0) }}</span>
                    </div>
                    <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                        <span class="text-sm text-gray-600">Unregistered CSV-only Records</span>
                        <span class="text-sm font-medium text-gray-900">{{ number_format($notRegisteredAlumni ?? 0) }}</span>
                    </div>
                    <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                        <span class="text-sm text-gray-600">Active User Sessions Today</span>
                        <span class="text-sm font-medium text-gray-900">{{ number_format($activeUsersToday) }}</span>
                    </div>
                    <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                        <span class="text-sm text-gray-600">New Job Posts This Week</span>
                        <span class="text-sm font-medium text-gray-900">{{ number_format($jobsPostedThisWeek) }}</span>
                    </div>
                    <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                        <span class="text-sm text-gray-600">Published Notifications This Month</span>
                        <span class="text-sm font-medium text-gray-900">{{ number_format($publishedAnnouncementsThisMonth ?? $announcementsThisMonth) }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600">New Registered Accounts This Month</span>
                        <span class="text-sm font-medium text-gray-900">{{ number_format($newRegisteredAlumniThisMonth ?? $newAlumniThisMonth) }}</span>
                    </div>
                </div>
            </div>

            <div class="surface-card p-6">
                <div class="mb-4">
                    <p class="section-kicker text-emerald-700/80">Quick Actions</p>
                    <h3 class="mt-2 text-2xl font-bold tracking-tight text-slate-900">Management Shortcuts</h3>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <a href="{{ route('admin.alumni') }}" class="flex flex-col items-center rounded-lg bg-blue-50 p-4 text-center transition-colors hover:bg-blue-100">
                        <i class='bx bx-group text-2xl text-blue-600'></i>
                        <span class="mt-2 text-sm font-medium text-gray-900">Manage Alumni</span>
                    </a>
                    <a href="{{ route('admin.jobs', ['create' => 1]) }}" class="flex flex-col items-center rounded-lg bg-green-50 p-4 text-center transition-colors hover:bg-green-100">
                        <i class='bx bx-plus-circle text-2xl text-green-600'></i>
                        <span class="mt-2 text-sm font-medium text-gray-900">Add Job</span>
                    </a>
                    <a href="{{ route('admin.announcements', ['create' => 1]) }}" class="flex flex-col items-center rounded-lg bg-purple-50 p-4 text-center transition-colors hover:bg-purple-100">
                        <i class='bx bx-bell-plus text-2xl text-purple-600'></i>
                        <span class="mt-2 text-sm font-medium text-gray-900">Add Announcement</span>
                    </a>
                    <a href="{{ route('admin.analytics') }}" class="flex flex-col items-center rounded-lg bg-orange-50 p-4 text-center transition-colors hover:bg-orange-100">
                        <i class='bx bx-bar-chart text-2xl text-orange-600'></i>
                        <span class="mt-2 text-sm font-medium text-gray-900">View Analytics</span>
                    </a>
                    <a href="{{ route('admin.feedback') }}" class="flex flex-col items-center rounded-lg bg-rose-50 p-4 text-center transition-colors hover:bg-rose-100">
                        <i class='bx bx-message-rounded-dots text-2xl text-rose-600'></i>
                        <span class="mt-2 text-sm font-medium text-gray-900">Monitor Feedback</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(() => {
    const palette = {
        green: '#02681e',
        greenSoft: 'rgba(2, 104, 30, 0.12)',
        yellow: '#ffc600',
        yellowSoft: 'rgba(255, 198, 0, 0.16)',
        red: '#dc2626',
        redSoft: 'rgba(220, 38, 38, 0.12)',
        teal: '#00491e',
        orange: '#ffc600',
        indigo: '#00491e',
        cyan: '#02681e',
        violet: '#00491e',
        slate: '#475569',
    };

    const axisTitle = (text) => ({
        display: true,
        text,
        color: '#334155',
        font: {
            family: "'Plus Jakarta Sans', system-ui, sans-serif",
            size: 11,
            weight: '700'
        },
        padding: {
            top: 8,
            bottom: 4
        }
    });

    let charts = {};

    const destroyCharts = () => {
        Object.values(charts).forEach((chart) => {
            if (chart && typeof chart.destroy === 'function') {
                chart.destroy();
            }
        });

        charts = {};
    };

    const createChart = (id, config) => {
        const canvas = document.getElementById(id);
        if (!canvas || !window.Chart) {
            return;
        }

        charts[id] = new window.Chart(canvas.getContext('2d'), config);
    };

    const openDashboardAnalyticsTarget = (card) => {
        const href = card?.getAttribute('data-analytics-card-link');

        if (href) {
            window.location.href = href;
        }
    };

    const initAdminDashboardPage = () => {
        if (!window.Chart || !document.getElementById('employmentTrendsChart')) {
            return;
        }

        destroyCharts();

        createChart('employmentTrendsChart', {
            type: 'line',
            data: {
                labels: @json($employmentTrends['labels']),
                datasets: [{
                    label: 'Employed',
                    data: @json($employmentTrends['employed']),
                    borderColor: palette.green,
                    backgroundColor: palette.greenSoft,
                    tension: 0.4
                }, {
                    label: 'Self-Employed',
                    data: @json($employmentTrends['selfEmployed']),
                    borderColor: palette.yellow,
                    backgroundColor: palette.yellowSoft,
                    tension: 0.4
                }, {
                    label: 'Unemployed',
                    data: @json($employmentTrends['unemployed']),
                    borderColor: palette.red,
                    backgroundColor: palette.redSoft,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        title: axisTitle('Months')
                    },
                    y: {
                        title: axisTitle('Alumni'),
                        beginAtZero: true
                    }
                }
            }
        });

        createChart('tracedGraduatesChart', {
            type: 'bar',
            data: {
                labels: @json($tracedGraduates['labels']),
                datasets: [{
                    label: 'Traced Graduates',
                    data: @json($tracedGraduates['data']),
                    backgroundColor: palette.teal,
                    borderColor: palette.teal,
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
                    x: {
                        title: axisTitle('Graduation Year')
                    },
                    y: {
                        title: axisTitle('Traced Alumni'),
                        beginAtZero: true
                    }
                }
            }
        });

        createChart('industryChart', {
            type: 'doughnut',
            data: {
                labels: @json($industryData['labels']),
                datasets: [{
                    data: @json($industryData['data']),
                    backgroundColor: [
                        palette.orange,
                        palette.teal,
                        palette.indigo,
                        palette.yellow,
                        palette.red,
                        palette.green,
                        palette.violet,
                        palette.slate
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        createChart('employabilityChart', {
            type: 'line',
            data: {
                labels: @json($employabilityData['labels']),
                datasets: [{
                    label: 'Employability Rate (%)',
                    data: @json($employabilityData['data']),
                    borderColor: palette.indigo,
                    backgroundColor: 'rgba(99, 102, 241, 0.12)',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        title: axisTitle('Graduation Year')
                    },
                    y: {
                        title: axisTitle('Employability Rate (%)'),
                        beginAtZero: true,
                        max: 100
                    }
                }
            }
        });

        createChart('globalDistributionChart', {
            type: 'bar',
            data: {
                labels: @json($globalData['labels']),
                datasets: [{
                    label: 'Alumni Distribution',
                    data: @json($globalData['data']),
                    backgroundColor: palette.cyan
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
                    x: {
                        title: axisTitle('Location')
                    },
                    y: {
                        title: axisTitle('Alumni'),
                        beginAtZero: true
                    }
                }
            }
        });

        createChart('feedbacksChart', {
            type: 'bar',
            data: {
                labels: @json($feedbacksData['labels']),
                datasets: [{
                    label: 'Feedback Count',
                    data: @json($feedbacksData['data']),
                    backgroundColor: palette.orange
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
                    x: {
                        title: axisTitle('Feedback Category')
                    },
                    y: {
                        title: axisTitle('Feedback Count'),
                        beginAtZero: true
                    }
                }
            }
        });

        createChart('teachingFeedbackChart', {
            type: 'bar',
            data: {
                labels: @json($teachingFeedbackData['labels']),
                datasets: [{
                    label: 'Average Rating',
                    data: @json($teachingFeedbackData['data']),
                    backgroundColor: palette.teal,
                    borderColor: palette.teal,
                    borderWidth: 1,
                    borderRadius: 4,
                    maxBarThickness: 34
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
                        title: axisTitle('Average Rating'),
                        beginAtZero: true,
                        min: 0,
                        max: 5,
                        ticks: {
                            stepSize: 1
                        }
                    },
                    y: {
                        title: axisTitle('Teaching Area'),
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });

        createChart('facilitiesSatisfactionChart', {
            type: 'pie',
            data: {
                labels: @json($facilitiesSatisfactionData['labels']),
                datasets: [{
                    data: @json($facilitiesSatisfactionData['data']),
                    backgroundColor: [
                        palette.green,
                        palette.yellow,
                        palette.red,
                        palette.indigo,
                        palette.teal,
                        palette.orange
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        createChart('overallExperienceChart', {
            type: 'bar',
            data: {
                labels: @json($overallExperienceData['labels']),
                datasets: [{
                    label: 'Average Rating',
                    data: @json($overallExperienceData['data']),
                    backgroundColor: palette.indigo,
                    borderColor: palette.indigo,
                    borderWidth: 1,
                    borderRadius: 4,
                    maxBarThickness: 34
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
                        title: axisTitle('Average Rating'),
                        beginAtZero: true,
                        min: 0,
                        max: 5,
                        ticks: {
                            stepSize: 1
                        }
                    },
                    y: {
                        title: axisTitle('Experience Area'),
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });

        createChart('reasonsChart', {
            type: 'bar',
            data: {
                labels: @json($reasonsData['labels']),
                datasets: [{
                    label: 'Number of Alumni',
                    data: @json($reasonsData['data']),
                    backgroundColor: palette.red
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
                        title: axisTitle('Alumni'),
                        beginAtZero: true
                    },
                    y: {
                        title: axisTitle('Reasons')
                    }
                }
            }
        });
    };

    const handleAdminReady = () => {
        initAdminDashboardPage();
    };

    const handleDashboardCardClick = (event) => {
        const card = event.target.closest('[data-analytics-card-link]');

        if (!card) {
            return;
        }

        openDashboardAnalyticsTarget(card);
    };

    const handleDashboardCardKeydown = (event) => {
        if (!['Enter', ' '].includes(event.key)) {
            return;
        }

        const card = event.target.closest('[data-analytics-card-link]');

        if (!card) {
            return;
        }

        event.preventDefault();
        openDashboardAnalyticsTarget(card);
    };

    document.addEventListener('click', handleDashboardCardClick);
    document.addEventListener('keydown', handleDashboardCardKeydown);
    document.addEventListener('admin:page-ready', handleAdminReady);

    window.__portalPageCleanup = () => {
        document.removeEventListener('click', handleDashboardCardClick);
        document.removeEventListener('keydown', handleDashboardCardKeydown);
        document.removeEventListener('admin:page-ready', handleAdminReady);
        destroyCharts();
    };
})();
</script>
@endsection
