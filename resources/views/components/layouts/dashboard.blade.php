@php
    $pageTitle = trim($__env->yieldContent('title', 'Alumni Portal'));
    $pageTitle = $pageTitle !== '' ? $pageTitle : 'Alumni Portal';
    $currentUser = Auth::user();
    $userName = trim((string) ($currentUser?->firstname ?? 'Alumni') . ' ' . (string) ($currentUser?->lastname ?? 'Member'));
    $displayPageTitle = $pageTitle;
    $degreeLabel = $currentUser?->degree ?: 'Complete your alumni profile';
    $departmentLabel = $currentUser?->department ?: 'CMU Alumni';
    $classLabel = $currentUser?->year_graduated ? 'Batch ' . $currentUser->year_graduated : 'Alumni';
    $todayLabel = now()->format('l, F j, Y');
    $sidebarStorageUserKey = substr(sha1((string) ($currentUser?->getAuthIdentifier() ?? 'guest')), 0, 16);
    $notificationBadgeCount = (int) ($sidebarNotificationIndicatorCount ?? 0);
    $notificationBadge = $notificationBadgeCount > 0 ? ($notificationBadgeCount > 99 ? '99+' : (string) $notificationBadgeCount) : null;
    $notificationBellIcon = $notificationBadgeCount > 0 ? 'bxs-bell-ring' : 'bx-bell';
    $notificationBellLabel = $notificationBadgeCount > 0
        ? 'Open updates center, ' . $notificationBadgeCount . ' new ' . \Illuminate\Support\Str::plural('update', $notificationBadgeCount)
        : 'Open updates center';
    $notificationLiveSummaryUrl = route('notifications.live-summary');
    $showProfileCompletionReminderOnLogin = (bool) session('show_profile_completion_reminder', false);
    $jobsBadge = ($sidebarAlumniReviewedJobSubmissionsCount ?? 0) > 0 ? (($sidebarAlumniReviewedJobSubmissionsCount ?? 0) > 9 ? '9+' : (string) ($sidebarAlumniReviewedJobSubmissionsCount ?? 0)) : null;
    $navigationItems = [
        [
            'label' => 'Dashboard',
            'description' => 'Dashboard home',
            'route' => route('dashboard'),
            'active' => request()->routeIs('dashboard'),
            'icon' => 'bx-home-circle',
            'badge' => null,
        ],
        [
            'label' => 'Profile',
            'description' => 'Profile',
            'route' => route('profile'),
            'active' => request()->routeIs('profile'),
            'icon' => 'bx-id-card',
            'badge' => null,
        ],
        [
            'label' => 'Tracer',
            'description' => 'Tracer records',
            'route' => route('tracer.index'),
            'active' => request()->routeIs('tracer.*'),
            'icon' => 'bx-spreadsheet',
            'badge' => null,
        ],
        [
            'label' => 'Job Opportunities',
            'description' => 'Job board',
            'route' => route('jobs'),
            'active' => request()->routeIs('jobs') || request()->routeIs('job.detail'),
            'icon' => 'bx-briefcase-alt-2',
            'badge' => $jobsBadge,
        ],
        [
            'label' => 'Feedback',
            'description' => 'Feedback',
            'route' => route('feedback.form'),
            'active' => request()->routeIs('feedback.form'),
            'icon' => 'bx-message-rounded-dots',
            'badge' => null,
        ],
        [
            'label' => 'Settings',
            'description' => 'Account settings',
            'route' => route('settings'),
            'active' => request()->routeIs('settings'),
            'icon' => 'bx-cog',
            'badge' => null,
        ],
        [
            'label' => 'Help Desk',
            'description' => 'System guide',
            'route' => route('help-desk'),
            'active' => request()->routeIs('help-desk'),
            'icon' => 'bx-help-circle',
            'badge' => null,
        ],
    ];
    $quickSearchItems = array_map(static function (array $item): array {
        $aliases = match ($item['label']) {
            'Dashboard' => 'home overview recent activity',
            'Profile' => 'personal information contact phone email birthdate college degree education address',
            'Tracer' => 'tracer survey employment work company salary graduate employment education',
            'Job Opportunities' => 'job board jobs openings career employer posting',
            'Feedback' => 'comments suggestions teaching facilities experience survey',
            'Settings' => 'account security password devices sessions privacy delete account',
            'Help Desk' => 'help support guide frequently asked questions',
            default => '',
        };
        $item['terms'] = trim(($item['description'] ?? '').' '.$item['label'].' '.$aliases);
        return $item;
    }, $navigationItems);
    array_unshift($quickSearchItems, [
        'label' => 'Notifications',
        'description' => 'Updates and unread notices',
        'route' => route('notifications.index'),
        'icon' => 'bx-bell',
        'terms' => 'notification alerts announcements unread updates news',
    ]);
    $quickSearchItems = array_merge($quickSearchItems, [
        [
            'label' => 'Change password',
            'description' => 'Account security',
            'route' => route('settings').'#password-settings',
            'icon' => 'bx-lock-alt',
            'terms' => 'settings password change reset security',
        ],
        [
            'label' => 'Signed-in devices',
            'description' => 'Manage active sessions',
            'route' => route('settings').'#device-sessions',
            'icon' => 'bx-devices',
            'terms' => 'settings devices sessions login browser sign out',
        ],
        [
            'label' => 'Close account',
            'description' => 'Account closure settings',
            'route' => route('settings').'#danger-zone',
            'icon' => 'bx-user-x',
            'terms' => 'settings delete account close account danger zone',
        ],
    ]);
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
        .alumni-portal .alumni-topbar {
            position: relative;
            overflow: hidden;
            padding: clamp(1.4rem, 1.8vw, 1.9rem);
            border: 1px solid rgba(255, 255, 255, 0.78);
            border-radius: 2rem;
            background:
                radial-gradient(circle at top right, rgba(200, 154, 45, 0.22), transparent 28%),
                radial-gradient(circle at bottom left, rgba(20, 87, 38, 0.12), transparent 30%),
                linear-gradient(135deg, rgba(255, 255, 255, 0.97), rgba(251, 248, 241, 0.93));
            box-shadow: 0 26px 58px rgba(7, 24, 14, 0.16);
        }

        .alumni-portal .alumni-topbar::before {
            content: "";
            position: absolute;
            inset: 0;
            pointer-events: none;
            background:
                linear-gradient(120deg, rgba(255, 255, 255, 0.52), transparent 38%),
                repeating-linear-gradient(90deg, rgba(11, 59, 31, 0.04) 0 1px, transparent 1px 34px);
            opacity: 0.42;
        }

        .alumni-portal .alumni-topbar::after {
            content: "";
            position: absolute;
            inset: auto -7% -72% auto;
            width: 18rem;
            height: 18rem;
            border-radius: 999px;
            pointer-events: none;
            background: radial-gradient(circle, rgba(200, 154, 45, 0.24), transparent 68%);
        }

        .alumni-portal .alumni-topbar__shell {
            position: relative;
            z-index: 1;
            display: grid;
            gap: 1.25rem;
        }

        .alumni-portal .alumni-topbar__main {
            display: flex;
            flex-direction: column;
            gap: 1.1rem;
            min-width: 0;
        }

        .alumni-portal .alumni-topbar__lead {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
        }

        .alumni-portal .alumni-topbar__copy {
            min-width: 0;
        }

        .alumni-portal .alumni-mobile-toggle {
            color: var(--alumni-emerald-deep);
            background: rgba(255, 255, 255, 0.84);
            border-color: rgba(11, 59, 31, 0.1);
            box-shadow: 0 12px 22px rgba(7, 24, 14, 0.12);
        }

        .alumni-portal .alumni-mobile-toggle:hover {
            background: #fff;
            border-color: rgba(20, 87, 38, 0.18);
            box-shadow: 0 16px 28px rgba(7, 24, 14, 0.16);
        }

        .alumni-portal .alumni-topbar__eyebrow {
            color: #9a6800;
            letter-spacing: 0.28em;
            font-size: 0.76rem;
            font-weight: 800;
        }

        .alumni-portal .alumni-topbar__title {
            max-width: 12ch;
            color: var(--alumni-emerald-deep);
            font-size: clamp(2.35rem, 4vw, 4.15rem);
            line-height: 0.92;
            text-wrap: balance;
        }

        .alumni-portal .alumni-topbar__meta {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
        }

        .alumni-portal .alumni-topbar__meta-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.6rem;
            max-width: 100%;
            padding: 0.8rem 1.05rem;
            border: 1px solid rgba(11, 59, 31, 0.1);
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.82);
            box-shadow: 0 14px 28px rgba(7, 24, 14, 0.08), inset 0 1px rgba(255, 255, 255, 0.75);
            color: var(--alumni-emerald-deep);
            font-size: 0.86rem;
            font-weight: 700;
            line-height: 1.35;
        }

        .alumni-portal .alumni-topbar__meta-chip i {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            width: 2rem;
            height: 2rem;
            border-radius: 999px;
            background: linear-gradient(135deg, rgba(20, 87, 38, 0.12), rgba(200, 154, 45, 0.18));
            box-shadow: inset 0 0 0 1px rgba(11, 59, 31, 0.08);
            color: var(--alumni-emerald-deep);
            font-size: 1rem;
        }

        .alumni-portal .alumni-topbar__spotlight {
            position: relative;
            overflow: hidden;
            padding: 1.3rem 1.35rem;
            border: 1px solid rgba(244, 230, 189, 0.24);
            border-radius: 1.55rem;
            background: linear-gradient(160deg, rgba(7, 39, 20, 0.97), rgba(15, 74, 43, 0.9));
            box-shadow: 0 22px 46px rgba(6, 21, 13, 0.28);
        }

        .alumni-portal .alumni-topbar__spotlight::before {
            content: "";
            position: absolute;
            inset: auto -22% -56% auto;
            width: 12rem;
            height: 12rem;
            border-radius: 999px;
            pointer-events: none;
            background: radial-gradient(circle, rgba(200, 154, 45, 0.34), transparent 66%);
        }

        .alumni-portal .alumni-topbar__spotlight::after {
            content: "";
            position: absolute;
            inset: 0;
            pointer-events: none;
            background: linear-gradient(140deg, rgba(255, 255, 255, 0.08), transparent 42%);
        }

        .alumni-portal .alumni-topbar__spotlight-header {
            position: relative;
            z-index: 1;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }

        .alumni-portal .alumni-topbar__spotlight-label {
            color: rgba(244, 230, 189, 0.92);
            letter-spacing: 0.24em;
            text-transform: uppercase;
            font-size: 0.68rem;
            font-weight: 800;
        }

        .alumni-portal .alumni-topbar__spotlight-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            width: 2.9rem;
            height: 2.9rem;
            border-radius: 1rem;
            background: rgba(255, 255, 255, 0.12);
            box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.08);
            color: #fff;
            font-size: 1.55rem;
        }

        .alumni-portal .alumni-topbar__spotlight-name {
            position: relative;
            z-index: 1;
            margin-top: 1rem;
            color: #fff;
            font-size: clamp(1.45rem, 2.1vw, 2rem);
            font-weight: 800;
            line-height: 1.05;
            text-wrap: balance;
        }

        .alumni-portal .alumni-topbar__spotlight-detail {
            position: relative;
            z-index: 1;
            margin-top: 0.55rem;
            color: rgba(255, 255, 255, 0.74);
            font-size: 0.94rem;
            line-height: 1.6;
        }

        @media (min-width: 1024px) {
            .alumni-portal .alumni-topbar__shell {
                grid-template-columns: minmax(0, 1.7fr) minmax(280px, 0.9fr);
                align-items: center;
            }

            .alumni-portal .alumni-topbar__spotlight {
                min-height: 100%;
            }
        }

        @media (max-width: 767px) {
            .alumni-portal .alumni-topbar {
                padding: 1.2rem;
                border-radius: 1.5rem;
            }

            .alumni-portal .alumni-topbar__lead {
                gap: 0.85rem;
            }

            .alumni-portal .alumni-topbar__title {
                max-width: none;
                font-size: 2.5rem;
            }

            .alumni-portal .alumni-topbar__meta-chip {
                width: 100%;
                border-radius: 1rem;
                justify-content: flex-start;
            }

            .alumni-portal .alumni-topbar__spotlight {
                padding: 1.15rem 1.1rem;
                border-radius: 1.3rem;
            }
        }

        body.alumni-dashboard-page .alumni-topbar {
            padding: 0.8rem 1rem;
            border-radius: 1.1rem;
        }

        body.alumni-dashboard-page .alumni-topbar__shell {
            grid-template-columns: minmax(0, 1fr) auto;
            align-items: center;
            gap: 0.75rem 1rem;
        }

        body.alumni-dashboard-page .alumni-topbar__main {
            flex-direction: row;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
        }

        body.alumni-dashboard-page .alumni-topbar__eyebrow {
            font-size: 0.62rem;
            letter-spacing: 0.18em;
        }

        body.alumni-dashboard-page .alumni-topbar__title {
            max-width: none;
            font-size: 1.35rem;
            line-height: 1.2;
        }

        body.alumni-dashboard-page .alumni-topbar__meta {
            justify-content: flex-end;
            gap: 0.4rem;
        }

        body.alumni-dashboard-page .alumni-topbar__meta-chip {
            gap: 0.4rem;
            padding: 0.35rem 0.6rem;
            font-size: 0.7rem;
        }

        body.alumni-dashboard-page .alumni-topbar__meta-chip i {
            width: 1.5rem;
            height: 1.5rem;
            font-size: 0.85rem;
        }

        body.alumni-dashboard-page .alumni-topbar__spotlight {
            display: grid;
            grid-template-columns: auto minmax(0, 1fr);
            align-items: center;
            gap: 0 0.65rem;
            min-height: 0;
            padding: 0.6rem 0.8rem;
            border-radius: 0.9rem;
            box-shadow: 0 8px 18px rgba(6, 21, 13, 0.16);
        }

        body.alumni-dashboard-page .alumni-topbar__spotlight-header {
            grid-column: 1;
            grid-row: 1 / span 2;
        }

        body.alumni-dashboard-page .alumni-topbar__spotlight-label {
            display: none;
        }

        body.alumni-dashboard-page .alumni-topbar__spotlight-icon {
            width: 2rem;
            height: 2rem;
            border-radius: 0.7rem;
            font-size: 1.15rem;
        }

        body.alumni-dashboard-page .alumni-topbar__spotlight-name {
            grid-column: 2;
            grid-row: 1;
            margin: 0;
            font-size: 0.9rem;
            line-height: 1.2;
        }

        body.alumni-dashboard-page .alumni-topbar__spotlight-detail {
            grid-column: 2;
            grid-row: 2;
            margin: 0.15rem 0 0;
            font-size: 0.72rem;
            line-height: 1.3;
        }

        @media (max-width: 767px) {
            body.alumni-dashboard-page .alumni-topbar__shell,
            body.alumni-dashboard-page .alumni-topbar__main {
                grid-template-columns: minmax(0, 1fr);
                flex-direction: column;
                align-items: stretch;
            }

            body.alumni-dashboard-page .alumni-topbar__lead {
                align-items: center;
            }

            body.alumni-dashboard-page .alumni-topbar__meta {
                justify-content: flex-start;
            }

            body.alumni-dashboard-page .alumni-topbar__meta-chip {
                width: auto;
                border-radius: 999px;
            }

            body.alumni-dashboard-page .alumni-topbar__spotlight {
                align-self: flex-start;
                min-width: min(100%, 18rem);
            }
        }

        @media (max-width: 480px) {
            body.alumni-dashboard-page .alumni-topbar__meta-chip:nth-child(n + 2) {
                display: none;
            }
        }
    </style>
</head>
<body class="portal-page alumni-portal font-['Plus_Jakarta_Sans'] text-slate-900 {{ request()->routeIs('dashboard') ? 'alumni-dashboard-page' : '' }}" data-sidebar-storage-key="cmu-arlo-sidebar-collapsed-{{ $sidebarStorageUserKey }}" data-profile-reminder-on-login="{{ $showProfileCompletionReminderOnLogin ? 'true' : 'false' }}">
    <div class="portal-flash-overlay fixed right-3 top-3 z-[100] sm:right-6 sm:top-6">
        @include('components.flash-messages')
    </div>

    <div id="mobileSidebarOverlay" class="alumni-sidebar-overlay lg:hidden"></div>

    <aside class="alumni-sidebar z-40 h-full overflow-y-auto overflow-x-visible p-4">
        <div class="alumni-sidebar-content">
            <div class="alumni-sidebar-panel border-b border-white/10 pb-5">
                <div class="sidebar-brand flex items-start justify-between gap-3">
                    <div class="flex min-w-0 items-center gap-3">
                        <div class="alumni-brand-mark">
                            <img src="{{ asset('images/cmu-sidebar.png') }}" alt="CMU logo" class="alumni-brand-mark__image">
                        </div>
                        <div class="sidebar-brand-copy min-w-0">
                            <p class="alumni-brand-kicker">CMU Alumni Portal</p>
                            <h1 class="alumni-brand-title">OARL</h1>
                            <p class="alumni-brand-subtitle">Opportunities and updates</p>
                        </div>
                    </div>
                    <button id="sidebarToggle" type="button" class="portal-sidebar-toggle alumni-mobile-toggle hidden flex-shrink-0 lg:inline-flex" aria-label="Collapse sidebar" aria-expanded="true" data-tooltip="Hide sidebar">
                        <i class='bx bx-menu-alt-left text-xl'></i>
                    </button>
                </div>
            </div>

            <div class="alumni-membership-card mt-5">
                <div class="flex items-center gap-4">
                    <div class="alumni-membership-card__avatar">
                        <span>{{ strtoupper(substr((string) ($currentUser?->firstname ?? 'A'), 0, 1) . substr((string) ($currentUser?->lastname ?? 'M'), 0, 1)) }}</span>
                    </div>
                    <div class="alumni-membership-card__copy min-w-0 flex-1">
                        <p class="alumni-membership-card__kicker">Alumni Member</p>
                        <h2 class="alumni-membership-card__name">{{ $userName }}</h2>
                        <p class="alumni-membership-card__subtitle">{{ $degreeLabel }}</p>
                    </div>
                </div>

                <div class="alumni-membership-card__details sidebar-collapse-hide">
                    <p class="alumni-membership-card__meta">{{ $classLabel }}</p>
                    <p class="alumni-membership-card__meta">{{ $departmentLabel }}</p>
                </div>
            </div>

            <nav class="alumni-sidebar-panel mt-5">
                <div class="sidebar-collapse-hide mb-3">
                    <p class="sidebar-panel-label">Navigate</p>
                </div>
                <div class="alumni-nav-group">
                    @foreach ($navigationItems as $item)
                        <a data-tooltip="{{ $item['description'] }}" href="{{ $item['route'] }}" class="alumni-sidebar-link {{ $item['active'] ? 'is-active' : '' }} {{ $showProfileCompletionReminderOnLogin && $item['label'] === 'Profile' ? 'is-reminder-target' : '' }}">
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
        <nav class="alumni-primary-nav" aria-label="Primary navigation">
            <a href="{{ route('dashboard') }}" class="alumni-primary-nav__brand" aria-label="CMU OARL Alumni Portal home">
                <span class="alumni-primary-nav__brand-mark">
                    <img src="{{ asset('images/cmu-sidebar.png') }}" alt="">
                </span>
                <span class="alumni-primary-nav__brand-copy">
                    <strong>CMU OARL</strong>
                    <small>Alumni Portal</small>
                </span>
            </a>

            <div class="alumni-primary-nav__links">
                @foreach ($navigationItems as $item)
                    @if (in_array($item['label'], ['Profile', 'Help Desk'], true))
                        @continue
                    @endif
                    <a
                        href="{{ $item['route'] }}"
                        class="alumni-primary-nav__link {{ $item['active'] ? 'is-active' : '' }} {{ $showProfileCompletionReminderOnLogin && $item['label'] === 'Profile' ? 'is-reminder-target' : '' }}"
                        title="{{ $item['description'] }}"
                        @if ($item['active']) aria-current="page" @endif
                    >
                        <i class="bx {{ $item['icon'] }}" aria-hidden="true"></i>
                        <span>{{ $item['label'] }}</span>
                        @if ($item['badge'])
                            <span class="alumni-primary-nav__badge">{{ $item['badge'] }}</span>
                        @endif
                    </a>
                @endforeach
            </div>

            <div class="alumni-primary-nav__quick-search">
                @include('components.portal-quick-search', ['items' => $quickSearchItems])
            </div>

            <div class="alumni-primary-nav__tools" aria-label="Account tools">
                <div class="alumni-notification-dock">
                    <a
                        href="{{ route('notifications.index') }}"
                        class="alumni-live-bell alumni-notification-dock__bell {{ $notificationBadge ? 'has-unread' : '' }}"
                        aria-label="{{ $notificationBellLabel }}"
                        title="Notifications"
                        data-live-notification-endpoint="{{ $notificationLiveSummaryUrl }}"
                        data-live-notification-button
                    >
                        <span class="alumni-live-bell__icon">
                            <i class='bx {{ $notificationBellIcon }} text-2xl' data-live-notification-icon></i>
                        </span>
                        <span class="alumni-live-bell__badge {{ $notificationBadge ? '' : 'hidden' }}" data-live-notification-badge>{{ $notificationBadge }}</span>
                        <span class="sr-only">Open updates center</span>
                    </a>
                    <a
                        href="{{ route('notifications.index') }}"
                        class="alumni-notification-preview hidden"
                        data-live-notification-preview
                        data-live-notification-preview-link
                        aria-live="polite"
                    >
                        <span class="alumni-notification-preview__icon" aria-hidden="true">
                            <i class="bx bx-bell" data-live-notification-preview-icon></i>
                        </span>
                        <span class="alumni-notification-preview__body">
                            <span class="alumni-notification-preview__meta">
                                <span data-live-notification-preview-category>Latest update</span>
                                <span data-live-notification-preview-time>Just now</span>
                            </span>
                            <span class="alumni-notification-preview__title" data-live-notification-preview-title>Open notifications</span>
                            <span class="alumni-notification-preview__summary" data-live-notification-preview-summary>See the newest update in your portal.</span>
                        </span>
                    </a>
                </div>

                <a href="{{ route('help-desk') }}" class="alumni-primary-nav__tool" aria-label="Help Desk" title="Help Desk">
                    <i class="bx bx-help-circle" aria-hidden="true"></i>
                </a>

                <a href="{{ route('profile') }}" class="alumni-primary-nav__profile {{ request()->routeIs('profile') ? 'is-active' : '' }}" aria-label="Profile: {{ $userName }}" title="Profile" @if (request()->routeIs('profile')) aria-current="page" @endif>
                    {{ strtoupper(substr((string) ($currentUser?->firstname ?? 'A'), 0, 1) . substr((string) ($currentUser?->lastname ?? 'M'), 0, 1)) }}
                </a>

                <form method="POST" action="{{ route('logout') }}" class="alumni-primary-nav__logout-form">
                    @csrf
                    <button type="submit" class="alumni-primary-nav__tool" aria-label="Sign out" title="Sign out">
                        <i class="bx bx-log-out" aria-hidden="true"></i>
                    </button>
                </form>
            </div>
        </nav>

        <header class="px-6 pt-6">
            <div class="alumni-topbar">
                <div class="alumni-topbar__shell">
                    <div class="alumni-topbar__main">
                        <div class="alumni-topbar__lead">
                            <button id="mobileSidebarToggle" type="button" class="alumni-mobile-toggle lg:hidden" aria-label="Open sidebar" aria-expanded="false">
                                <i class='bx bx-menu-alt-left text-xl'></i>
                            </button>
                            <div class="alumni-topbar__copy min-w-0">
                                <p class="alumni-topbar__eyebrow">Alumni Portal</p>
                                <h2 class="alumni-topbar__title">{{ $displayPageTitle }}</h2>
                            </div>
                        </div>
                        <div class="alumni-topbar__meta" aria-label="Current alumni context">
                            <span class="alumni-topbar__meta-chip">
                                <i class='bx bx-calendar-star'></i>
                                <span>{{ $todayLabel }}</span>
                            </span>
                            <span class="alumni-topbar__meta-chip">
                                <i class='bx bx-medal'></i>
                                <span>{{ $classLabel }}</span>
                            </span>
                            <span class="alumni-topbar__meta-chip">
                                <i class='bx bx-building-house'></i>
                                <span>{{ $departmentLabel }}</span>
                            </span>
                        </div>
                    </div>
                    <aside class="alumni-topbar__spotlight" aria-label="Alumni details">
                        <div class="alumni-topbar__spotlight-header">
                            <span class="alumni-topbar__spotlight-label">My Account</span>
                            <span class="alumni-topbar__spotlight-icon" aria-hidden="true">
                                <i class='bx bx-user-circle'></i>
                            </span>
                        </div>
                        <p class="alumni-topbar__spotlight-name">{{ $userName }}</p>
                        <p class="alumni-topbar__spotlight-detail">{{ $degreeLabel }}</p>
                    </aside>
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
                            <p class="alumni-footer-card__title">Office of the Alumni Relations and Linkages (OARL)</p>
                        </div>
                    </div>
                    <p class="alumni-footer-card__copy">A more professional, connected alumni experience for records, opportunities, and meaningful campus ties.</p>
                </div>
            </div>
        </footer>
    </div>
</body>
</html>
