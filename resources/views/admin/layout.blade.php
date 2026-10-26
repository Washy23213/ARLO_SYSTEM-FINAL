@php
    $pageTitle = trim($__env->yieldContent('title', 'Admin Portal'));
    $pageTitle = $pageTitle !== '' ? $pageTitle : 'Admin Portal';
    $pageTitle = html_entity_decode($pageTitle, ENT_QUOTES, 'UTF-8');
    $currentSuperAdmin = Auth::guard('super_admin')->user();
    $currentAdmin = $currentSuperAdmin ?: Auth::guard('admin')->user();
    $currentUser = $currentAdmin ?: Auth::user();
    $isSuperAdminConsole = (bool) $currentSuperAdmin;
    $adminName = trim((string) ($currentUser?->firstname ?? 'System') . ' ' . (string) ($currentUser?->lastname ?? 'Administrator'));
    $adminName = $adminName !== '' ? $adminName : 'System Administrator';
    $adminInitials = strtoupper(substr((string) ($currentUser?->firstname ?? 'A'), 0, 1) . substr((string) ($currentUser?->lastname ?? 'D'), 0, 1));
    $adminInitials = $adminInitials !== '' ? $adminInitials : 'AD';
    $sidebarStorageUserKey = substr(sha1((string) ($currentUser?->getAuthIdentifier() ?? 'guest')), 0, 16);
    $canAccessAdminPermission = function (string $permission) use ($isSuperAdminConsole, $currentAdmin, $currentUser): bool {
        if ($isSuperAdminConsole) {
            return true;
        }

        if ($currentAdmin instanceof \App\Models\Admin) {
            return $currentAdmin->hasPermission($permission);
        }

        if ($currentUser instanceof \App\Models\User && $currentUser->isAdmin()) {
            return in_array($permission, \App\Models\Admin::permissionsForRole(\App\Models\Admin::ROLE_ADMIN), true);
        }

        return false;
    };
    $canAccessAdminAnalytics = $canAccessAdminPermission(\App\Models\Admin::PERMISSION_VIEW_ANALYTICS);
    $canAccessAdminAuditLogs = $canAccessAdminPermission(\App\Models\Admin::PERMISSION_VIEW_AUDIT_LOGS);
    $canAccessAdminNotifications = $canAccessAdminPermission(\App\Models\Admin::PERMISSION_MANAGE_ANNOUNCEMENTS);
    $jobsBadge = ($sidebarPendingJobSubmissionsCount ?? 0) > 0 ? (($sidebarPendingJobSubmissionsCount ?? 0) > 99 ? '99+' : (string) ($sidebarPendingJobSubmissionsCount ?? 0)) : null;
    $announcementBadgeCount = (int) ($sidebarAnnouncementIndicatorCount ?? 0);
    $announcementBadge = $announcementBadgeCount > 0 ? ($announcementBadgeCount > 99 ? '99+' : (string) $announcementBadgeCount) : null;
    $announcementBellIcon = $announcementBadge ? 'bxs-bell-ring' : 'bx-bell';
    $announcementBellLabel = $announcementBadge
        ? 'Open notifications, ' . $announcementBadgeCount . ' new ' . \Illuminate\Support\Str::plural('record', $announcementBadgeCount)
        : 'Open notifications';
    $feedbackBadge = ($sidebarFeedbackIndicatorCount ?? 0) > 0 ? (($sidebarFeedbackIndicatorCount ?? 0) > 99 ? '99+' : (string) ($sidebarFeedbackIndicatorCount ?? 0)) : null;
    $auditBadge = ($sidebarAuditLogIndicatorCount ?? 0) > 0 ? (($sidebarAuditLogIndicatorCount ?? 0) > 99 ? '99+' : (string) ($sidebarAuditLogIndicatorCount ?? 0)) : null;
    $superAdminStats = $isSuperAdminConsole ? [
        'admins' => \App\Models\Admin::query()->count(),
        'alumni' => \App\Models\User::query()->alumni()->count(),
        'logs' => \Illuminate\Support\Facades\Schema::hasTable('system_logs') ? \Illuminate\Support\Facades\DB::table('system_logs')->count() : 0,
    ] : [];
    $auditLogRoute = $isSuperAdminConsole ? route('super-admin.audit-logs') : route('admin.audit-logs');
    $operationalNavigationItems = [
        [
            'label' => 'Dashboard',
            'description' => 'Dashboard',
            'route' => route('admin.dashboard'),
            'active' => request()->routeIs('admin.dashboard'),
            'icon' => 'bx-home-circle',
            'badge' => null,
            'permission' => \App\Models\Admin::PERMISSION_VIEW_ADMIN_DASHBOARD,
        ],
        [
            'label' => 'Alumni',
            'description' => 'Alumni records',
            'route' => route('admin.alumni'),
            'active' => request()->routeIs('admin.alumni*'),
            'icon' => 'bx-group',
            'badge' => null,
            'permission' => \App\Models\Admin::PERMISSION_MANAGE_ALUMNI,
        ],
        [
            'label' => 'Jobs',
            'description' => 'Job postings',
            'route' => route('admin.jobs'),
            'active' => request()->routeIs('admin.jobs*') || request()->routeIs('admin.job-submissions.*'),
            'icon' => 'bx-briefcase-alt-2',
            'badge' => $jobsBadge,
            'permission' => \App\Models\Admin::PERMISSION_MANAGE_JOBS,
        ],
        [
            'label' => 'Feedback',
            'description' => 'Feedback queue',
            'route' => route('admin.feedback'),
            'active' => request()->routeIs('admin.feedback*'),
            'icon' => 'bx-message-rounded-dots',
            'badge' => $feedbackBadge,
            'permission' => \App\Models\Admin::PERMISSION_MANAGE_FEEDBACK,
        ],
        [
            'label' => 'Analytics',
            'description' => 'Reports and charts',
            'route' => route('admin.analytics'),
            'active' => request()->routeIs('admin.analytics'),
            'icon' => 'bx-bar-chart-alt-2',
            'badge' => null,
            'permission' => \App\Models\Admin::PERMISSION_VIEW_ANALYTICS,
        ],
        [
            'label' => 'Audit Logs',
            'description' => 'Activity log',
            'route' => $auditLogRoute,
            'active' => request()->routeIs('admin.audit-logs') || request()->routeIs('super-admin.audit-logs'),
            'icon' => 'bx-history',
            'badge' => $auditBadge,
            'permission' => \App\Models\Admin::PERMISSION_VIEW_AUDIT_LOGS,
        ],
    ];
    $navigationItems = $isSuperAdminConsole ? array_merge([
        [
            'label' => 'Admin Accounts',
            'description' => 'Create and manage admin accounts',
            'route' => route('super-admin.admin-accounts.index'),
            'active' => request()->routeIs('super-admin.admin-accounts*'),
            'icon' => 'bx-shield-quarter',
            'badge' => null,
        ],
    ], $operationalNavigationItems) : $operationalNavigationItems;

    if (! $isSuperAdminConsole) {
        $navigationItems = array_values(array_filter(
            $navigationItems,
            fn (array $item): bool => empty($item['permission']) || $canAccessAdminPermission($item['permission'])
        ));
    }
    $quickSearchItems = array_map(static function (array $item): array {
        $aliases = match ($item['label']) {
            'Alumni' => 'graduates users roster records profile tracer',
            'Jobs' => 'job opportunities openings postings career',
            'Feedback' => 'suggestions comments responses',
            'Analytics' => 'reports charts employment statistics',
            'Audit Logs' => 'history actions activity security logs',
            'Admin Accounts' => 'superadmin administrators users permissions',
            default => '',
        };
        $item['terms'] = trim(($item['description'] ?? '').' '.$item['label'].' '.$aliases);
        return $item;
    }, $navigationItems);
    if ($canAccessAdminNotifications) {
        $quickSearchItems[] = [
            'label' => 'Notifications',
            'description' => 'Announcements and updates',
            'route' => route('admin.announcements'),
            'icon' => 'bx-bell',
            'terms' => 'notifications announcement announcements publish update facebook news',
        ];
    }
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $pageTitle }} | CMU OARL</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .admin-portal .alumni-topbar {
            position: relative;
            overflow: hidden;
            padding: clamp(1.05rem, 1.35vw, 1.35rem);
            border: 1px solid rgba(255, 255, 255, 0.78);
            border-radius: 1.55rem;
            background:
                radial-gradient(circle at top right, rgba(87, 135, 190, 0.16), transparent 30%),
                radial-gradient(circle at bottom left, rgba(64, 105, 150, 0.1), transparent 34%),
                linear-gradient(135deg, rgba(255, 255, 255, 0.98), rgba(239, 245, 251, 0.96));
            box-shadow: 0 18px 42px rgba(23, 43, 68, 0.12);
        }

        .admin-portal .alumni-topbar::before {
            content: "";
            position: absolute;
            inset: 0;
            pointer-events: none;
            background:
                linear-gradient(120deg, rgba(255, 255, 255, 0.64), transparent 38%),
                repeating-linear-gradient(90deg, rgba(48, 82, 118, 0.035) 0 1px, transparent 1px 34px);
            opacity: 0.42;
        }

        .admin-portal .alumni-topbar::after {
            content: "";
            position: absolute;
            inset: auto -7% -72% auto;
            width: 18rem;
            height: 18rem;
            border-radius: 999px;
            pointer-events: none;
            background: radial-gradient(circle, rgba(87, 135, 190, 0.2), transparent 68%);
        }

        .admin-portal .alumni-topbar__shell {
            position: relative;
            z-index: 1;
            display: grid;
            gap: 0.95rem;
            align-items: start;
        }

        .admin-portal .alumni-topbar__main {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            min-width: 0;
        }

        .admin-portal .alumni-topbar__copy {
            max-width: 48rem;
        }

        .admin-portal .alumni-topbar__lead {
            display: flex;
            align-items: flex-start;
            gap: 0.8rem;
        }

        .admin-portal .alumni-mobile-toggle {
            color: var(--alumni-emerald-deep);
            background: rgba(255, 255, 255, 0.84);
            border-color: rgba(11, 59, 31, 0.1);
            box-shadow: 0 12px 22px rgba(7, 24, 14, 0.12);
        }

        .admin-portal .alumni-mobile-toggle:hover {
            background: #fff;
            border-color: rgba(20, 87, 38, 0.18);
            box-shadow: 0 16px 28px rgba(7, 24, 14, 0.16);
        }

        .admin-portal .alumni-topbar__eyebrow {
            margin: 0;
            color: #466c95;
            letter-spacing: 0.28em;
            font-size: 0.68rem;
            font-weight: 800;
        }

        .admin-portal .alumni-topbar__title {
            margin: 0;
            max-width: 12ch;
            padding-inline-start: 0.08rem;
            color: #18344f;
            font-size: clamp(1.85rem, 3vw, 3.2rem);
            line-height: 0.94;
            text-wrap: balance;
        }

        .admin-portal .alumni-topbar__meta {
            display: flex;
            flex-wrap: wrap;
            gap: 0.6rem;
        }

        .admin-portal .alumni-topbar__meta-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.6rem;
            max-width: 100%;
            padding: 0.62rem 0.85rem;
            border: 1px solid rgba(48, 82, 118, 0.13);
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.9);
            box-shadow: 0 10px 20px rgba(23, 43, 68, 0.07), inset 0 1px rgba(255, 255, 255, 0.85);
            color: #24486c;
            font-size: 0.8rem;
            font-weight: 700;
            line-height: 1.35;
        }

        .admin-portal .alumni-topbar__meta-chip i {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            width: 1.65rem;
            height: 1.65rem;
            border-radius: 999px;
            background: linear-gradient(135deg, rgba(70, 108, 149, 0.13), rgba(130, 163, 198, 0.18));
            box-shadow: inset 0 0 0 1px rgba(48, 82, 118, 0.08);
            color: #315b83;
            font-size: 0.88rem;
        }

        .admin-portal .alumni-topbar__toolbar {
            position: relative;
            z-index: 1;
            display: flex;
            justify-content: flex-start;
            align-items: flex-start;
        }

        .admin-portal .alumni-topbar__actions {
            display: inline-flex;
            flex-wrap: wrap;
            gap: 0.6rem;
            align-items: center;
            padding: 0.42rem;
            border: 1px solid rgba(11, 59, 31, 0.1);
            border-radius: 1.1rem;
            background: rgba(255, 255, 255, 0.72);
            box-shadow:
                0 14px 28px rgba(7, 24, 14, 0.08),
                inset 0 1px rgba(255, 255, 255, 0.78);
            backdrop-filter: blur(10px);
        }

        .admin-portal .alumni-topbar-notice,
        .admin-portal .alumni-topbar-cta {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.65rem;
            min-height: 2.55rem;
            padding: 0.64rem 0.95rem;
            border-radius: 0.9rem;
            font-size: 0.82rem;
            font-weight: 700;
            transition: transform 0.18s ease, box-shadow 0.18s ease, background-color 0.18s ease, border-color 0.18s ease;
        }

        .admin-portal .alumni-topbar-notice {
            color: var(--alumni-emerald-deep);
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.98), rgba(243, 247, 244, 0.92));
            border: 1px solid rgba(11, 59, 31, 0.1);
            box-shadow:
                0 12px 22px rgba(7, 24, 14, 0.08),
                inset 0 1px rgba(255, 255, 255, 0.82);
        }

        .admin-portal .alumni-topbar-cta {
            color: var(--alumni-emerald-deep);
            background: linear-gradient(135deg, rgba(244, 230, 189, 0.96), rgba(200, 154, 45, 0.9));
            border: 1px solid rgba(200, 154, 45, 0.3);
            box-shadow: 0 14px 28px rgba(6, 21, 13, 0.16);
        }

        .admin-portal .alumni-topbar-notice:hover,
        .admin-portal .alumni-topbar-cta:hover {
            transform: translateY(-1px);
        }

        .admin-portal .alumni-topbar-notice:hover {
            background: linear-gradient(135deg, rgba(255, 255, 255, 1), rgba(239, 246, 241, 0.96));
            border-color: rgba(20, 87, 38, 0.18);
            box-shadow:
                0 16px 28px rgba(7, 24, 14, 0.11),
                inset 0 1px rgba(255, 255, 255, 0.92);
        }

        .admin-portal .alumni-topbar-cta:hover {
            box-shadow: 0 18px 34px rgba(6, 21, 13, 0.22);
            filter: saturate(1.03);
        }

        .admin-portal .alumni-topbar-notice__dot {
            position: absolute;
            top: -0.1rem;
            right: -0.1rem;
            width: 0.7rem;
            height: 0.7rem;
            border-radius: 999px;
            border: 2px solid rgba(255, 255, 255, 0.96);
            background: #ef4444;
        }

        .admin-portal .alumni-topbar-notice__count {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 1.35rem;
            height: 1.35rem;
            padding: 0 0.35rem;
            border-radius: 999px;
            background: rgba(20, 87, 38, 0.12);
            color: var(--alumni-emerald-deep);
            font-size: 0.7rem;
            font-weight: 800;
        }

        .admin-portal .dashboard-shell > header,
        .admin-portal .dashboard-shell > main,
        .admin-portal .dashboard-shell > footer {
            padding-left: clamp(1.35rem, 1.8vw, 1.8rem);
            padding-right: clamp(1.15rem, 1.6vw, 1.6rem);
        }

        @media (min-width: 1024px) {
            .admin-portal .dashboard-shell {
                margin-left: 18.75rem;
            }

            body.admin-portal.sidebar-collapsed .dashboard-shell,
            body.admin-portal.sidebar-hover-open .dashboard-shell {
                margin-left: 6.75rem;
            }

            .admin-portal .alumni-topbar__shell {
                grid-template-columns: minmax(0, 1fr) auto;
                gap: 1rem 1.4rem;
            }

            .admin-portal .alumni-topbar__toolbar {
                justify-content: flex-end;
                align-self: end;
            }
        }

        @media (max-width: 1023px) {
            .admin-portal .dashboard-shell,
            body.admin-portal.sidebar-collapsed .dashboard-shell,
            body.admin-portal.sidebar-hover-open .dashboard-shell {
                width: 100% !important;
                max-width: 100% !important;
                margin-left: 0 !important;
                margin-right: 0 !important;
            }

            .admin-portal .admin-sidebar {
                width: calc(100vw - 1.25rem) !important;
                max-width: 21rem !important;
                transform: translateX(-108%) !important;
            }

            body.admin-portal.mobile-sidebar-open .admin-sidebar {
                transform: translateX(0) !important;
            }
        }

        @media (max-width: 767px) {
            .admin-portal .alumni-topbar {
                padding: 1.2rem;
                border-radius: 1.5rem;
            }

            .admin-portal .alumni-topbar__lead {
                flex-direction: column;
                align-items: stretch;
                gap: 0.7rem;
            }

            .admin-portal .alumni-mobile-toggle {
                align-self: flex-start;
            }

            .admin-portal .alumni-topbar__copy {
                width: 100%;
            }

            .admin-portal .alumni-topbar__title {
                max-width: none;
                padding-inline-start: 0.1rem;
                font-size: 2rem;
            }

            .admin-portal .alumni-topbar__toolbar,
            .admin-portal .alumni-topbar__actions {
                width: 100%;
            }

            .admin-portal .alumni-topbar__meta-chip,
            .admin-portal .alumni-topbar-notice,
            .admin-portal .alumni-topbar-cta {
                width: 100%;
                justify-content: flex-start;
                border-radius: 1rem;
            }

            .admin-portal .alumni-topbar__actions .admin-topbar-notification {
                width: auto;
                justify-content: center;
            }
        }
    </style>
</head>
<body class="portal-page admin-portal font-['Plus_Jakarta_Sans'] text-slate-900" data-sidebar-storage-key="{{ $isSuperAdminConsole ? 'cmu-arlo-super-admin-sidebar-collapsed' : 'cmu-arlo-admin-sidebar-collapsed' }}-{{ $sidebarStorageUserKey }}">
    <div class="portal-flash-overlay fixed right-3 top-3 z-[100] sm:right-6 sm:top-6">
        @include('components.flash-messages')
    </div>

    <div id="mobileSidebarOverlay" class="alumni-sidebar-overlay lg:hidden"></div>

    <aside class="admin-sidebar z-40 h-full overflow-y-auto overflow-x-visible p-4">
        <div class="admin-sidebar-content">
            <div class="alumni-sidebar-panel border-b border-white/10 pb-5">
                <div class="sidebar-brand flex items-start justify-between gap-3">
                    <div class="flex min-w-0 items-center gap-3">
                        <div class="alumni-brand-mark">
                            <img src="{{ asset('images/cmu-sidebar.png') }}" alt="CMU logo" class="alumni-brand-mark__image">
                        </div>
                        <div class="sidebar-brand-copy min-w-0">
                            <p class="alumni-brand-kicker">{{ $isSuperAdminConsole ? 'CMU Super Admin UI' : 'CMU Admin Workspace' }}</p>
                            <h1 class="alumni-brand-title">OARL</h1>
                            <p class="alumni-brand-subtitle">{{ $isSuperAdminConsole ? 'Full system access' : 'Records and reporting' }}</p>
                        </div>
                    </div>
                    <button id="sidebarToggle" type="button" class="portal-sidebar-toggle alumni-mobile-toggle hidden flex-shrink-0 lg:inline-flex" aria-label="Collapse sidebar" aria-expanded="true" data-tooltip="Hide sidebar">
                        <i class='bx bx-menu-alt-left text-xl'></i>
                    </button>
                </div>
            </div>

            <div class="alumni-membership-card admin-membership-card mt-5">
                <div class="flex items-center gap-4">
                    <div class="alumni-membership-card__avatar">
                        <span>{{ $adminInitials }}</span>
                    </div>
                    <div class="alumni-membership-card__copy min-w-0 flex-1">
                        <p class="alumni-membership-card__kicker">{{ $isSuperAdminConsole ? 'Super Admin' : 'Administrator' }}</p>
                        <h2 class="alumni-membership-card__name">{{ $adminName }}</h2>
                        <p class="alumni-membership-card__subtitle">{{ $isSuperAdminConsole ? 'All admin features' : 'Records and oversight' }}</p>
                    </div>
                </div>

                <div class="alumni-membership-card__details sidebar-collapse-hide">
                    <p class="alumni-membership-card__meta">{{ now()->format('F j, Y') }}</p>
                    <p class="alumni-membership-card__meta">Office of the Alumni Relations and Linkages (OARL)</p>
                </div>

                <div class="sidebar-collapse-hide mt-4 grid grid-cols-2 gap-2">
                    @if($isSuperAdminConsole)
                    <div class="alumni-mini-stat">
                        <span class="alumni-mini-stat__label">Admins</span>
                        <span class="alumni-mini-stat__value">{{ $superAdminStats['admins'] ?? 0 }}</span>
                    </div>
                    <div class="alumni-mini-stat">
                        <span class="alumni-mini-stat__label">Alumni</span>
                        <span class="alumni-mini-stat__value">{{ $superAdminStats['alumni'] ?? 0 }}</span>
                    </div>
                    <div class="alumni-mini-stat">
                        <span class="alumni-mini-stat__label">Logs</span>
                        <span class="alumni-mini-stat__value">{{ $superAdminStats['logs'] ?? 0 }}</span>
                    </div>
                    @else
                    <div class="alumni-mini-stat">
                        <span class="alumni-mini-stat__label">Users</span>
                        <span class="alumni-mini-stat__value">{{ $sidebarActiveUsers ?? 0 }}</span>
                    </div>
                    <div class="alumni-mini-stat">
                        <span class="alumni-mini-stat__label">Jobs</span>
                        <span class="alumni-mini-stat__value">{{ $sidebarJobsPosted ?? 0 }}</span>
                    </div>
                    <div class="alumni-mini-stat">
                        <span class="alumni-mini-stat__label">Posts</span>
                        <span class="alumni-mini-stat__value">{{ $sidebarAnnouncements ?? 0 }}</span>
                    </div>
                    <div class="alumni-mini-stat">
                        <span class="alumni-mini-stat__label">Logs</span>
                        <span class="alumni-mini-stat__value">{{ $sidebarAuditLogCount ?? 0 }}</span>
                    </div>
                    @endif
                </div>
            </div>

            <nav class="alumni-sidebar-panel mt-5">
                <div class="sidebar-collapse-hide mb-3">
                    <p class="sidebar-panel-label">Navigate</p>
                </div>
                <div class="alumni-nav-group">
                    @foreach ($navigationItems as $item)
                        <a data-tooltip="{{ $item['description'] }}" href="{{ $item['route'] }}" class="alumni-sidebar-link {{ $item['active'] ? 'is-active' : '' }}">
                            <span class="alumni-sidebar-link__icon">
                                <i class='bx {{ $item['icon'] }} text-xl'></i>
                            </span>
                            <span class="sidebar-label min-w-0 flex-1">
                                <span class="alumni-sidebar-link__label">{{ $item['label'] }}</span>
                            </span>
                            @if ($item['badge'])
                                <span class="alumni-sidebar-link__badge">{{ $item['badge'] }}</span>
                            @endif
                        </a>
                    @endforeach
                </div>
            </nav>
        </div>

        <div class="p-4 pt-5">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" data-tooltip="Sign out" class="sidebar-logout-button alumni-logout-button w-full">
                    <i class='bx bx-log-out text-lg'></i>
                    <span class="sidebar-label font-medium">Logout</span>
                </button>
            </form>
        </div>
    </aside>

    <div class="dashboard-shell min-h-screen flex flex-col">
        <header class="px-6 pt-6">
            <div class="alumni-topbar">
                <div class="alumni-topbar__shell">
                    <div class="alumni-topbar__main">
                        <div class="alumni-topbar__lead">
                            <button id="mobileSidebarToggle" type="button" class="alumni-mobile-toggle lg:hidden" aria-label="Open sidebar" aria-expanded="false">
                                <i class='bx bx-menu-alt-left text-xl'></i>
                            </button>
                            <div class="alumni-topbar__copy min-w-0">
                                <p class="alumni-topbar__eyebrow">{{ $isSuperAdminConsole ? 'CMU Super Admin UI' : 'CMU Admin Workspace' }}</p>
                                <h2 class="alumni-topbar__title">{!! $pageTitle !!}</h2>
                            </div>
                        </div>
                    </div>
                    <div class="alumni-topbar__toolbar">
                        @include('components.portal-quick-search', ['items' => $quickSearchItems])
                        <div class="alumni-topbar__actions">
                            @if($canAccessAdminNotifications)
                                <a href="{{ route('admin.announcements') }}" class="alumni-topbar-notice admin-topbar-notification" aria-label="{{ $announcementBellLabel }}" title="Notifications">
                                    <span class="relative inline-flex">
                                        <i class='bx {{ $announcementBellIcon }} text-lg'></i>
                                        @if($announcementBadge)
                                            <span class="alumni-topbar-notice__dot"></span>
                                        @endif
                                    </span>
                                    <span class="hidden xl:inline">Notifications</span>
                                    @if($announcementBadge)
                                        <span class="alumni-topbar-notice__count">{{ $announcementBadge }}</span>
                                    @endif
                                </a>
                            @endif
                            @if($isSuperAdminConsole)
                                <a href="{{ route('super-admin.admin-accounts.index') }}" class="alumni-topbar-notice">
                                    <i class='bx bx-shield-quarter text-lg'></i>
                                    <span>Admin Accounts</span>
                                </a>
                                <a href="{{ route('super-admin.audit-logs') }}" class="alumni-topbar-notice">
                                    <i class='bx bx-history text-lg'></i>
                                    <span>Audit Logs</span>
                                </a>
                                @if(! request()->routeIs('admin.analytics'))
                                    <a href="{{ route('admin.analytics') }}" class="alumni-topbar-cta">
                                        <i class='bx bx-bar-chart-alt-2 text-lg'></i>
                                        <span>Open Analytics</span>
                                    </a>
                                @elseif(! request()->routeIs('admin.alumni*'))
                                    <a href="{{ route('admin.alumni') }}" class="alumni-topbar-cta">
                                        <i class='bx bx-group text-lg'></i>
                                        <span>Alumni Records</span>
                                    </a>
                                @elseif(! request()->routeIs('admin.jobs*') && ! request()->routeIs('admin.job-submissions.*'))
                                    <a href="{{ route('admin.jobs') }}" class="alumni-topbar-cta">
                                        <i class='bx bx-briefcase-alt-2 text-lg'></i>
                                        <span>Job Management</span>
                                    </a>
                                @endif
                            @else
                                @if($canAccessAdminAuditLogs)
                                    <a href="{{ route('admin.audit-logs') }}" class="alumni-topbar-notice">
                                        <span class="relative inline-flex">
                                            <i class='bx bx-history text-lg'></i>
                                            @if (($sidebarAuditLogIndicatorCount ?? 0) > 0)
                                                <span class="alumni-topbar-notice__dot"></span>
                                            @endif
                                        </span>
                                        <span>Audit Logs</span>
                                        @if (($sidebarAuditLogIndicatorCount ?? 0) > 0)
                                            <span class="alumni-topbar-notice__count">{{ ($sidebarAuditLogIndicatorCount ?? 0) > 99 ? '99+' : $sidebarAuditLogIndicatorCount }}</span>
                                        @endif
                                    </a>
                                @endif
                                @if($canAccessAdminAnalytics && ! request()->routeIs('admin.analytics'))
                                    <a href="{{ route('admin.analytics') }}" class="alumni-topbar-cta">
                                        <i class='bx bx-bar-chart-alt-2 text-lg'></i>
                                        <span>Open Analytics</span>
                                    </a>
                                @elseif($canAccessAdminPermission(\App\Models\Admin::PERMISSION_MANAGE_ALUMNI) && ! request()->routeIs('admin.alumni*'))
                                    <a href="{{ route('admin.alumni') }}" class="alumni-topbar-cta">
                                        <i class='bx bx-group text-lg'></i>
                                        <span>Alumni Records</span>
                                    </a>
                                @elseif($canAccessAdminPermission(\App\Models\Admin::PERMISSION_MANAGE_JOBS) && ! request()->routeIs('admin.jobs*') && ! request()->routeIs('admin.job-submissions.*'))
                                    <a href="{{ route('admin.jobs') }}" class="alumni-topbar-cta">
                                        <i class='bx bx-briefcase-alt-2 text-lg'></i>
                                        <span>Job Management</span>
                                    </a>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <main class="flex-1 px-6 pb-6 pt-5">
            <div class="dashboard-content mx-auto">
                @yield('content')
            </div>
        </main>

        <footer class="px-6 pb-6 pt-2">
            <div class="alumni-footer-card">
                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div class="flex items-center gap-4">
                        <span class="alumni-footer-card__logo">
                            <img src="{{ asset('images/cmu-sidebar.png') }}" alt="CMU logo" class="alumni-footer-card__logo-image">
                        </span>
                        <div>
                            <p class="alumni-footer-card__eyebrow">CMU OARL</p>
                            <p class="alumni-footer-card__title">{{ $isSuperAdminConsole ? 'Super Admin Operations Desk' : 'Administrative Operations Desk' }}</p>
                        </div>
                    </div>
                    <p class="alumni-footer-card__copy">{{ $isSuperAdminConsole ? 'Full administrative access for accounts, records, jobs, notifications, reports, and audit monitoring.' : 'Institutional reporting, alumni records, job postings, and official updates in one connected workspace.' }}</p>
                </div>
            </div>
        </footer>
    </div>
</body>
</html>
