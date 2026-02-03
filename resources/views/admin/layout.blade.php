<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'CMU ARLO - Admin Dashboard' }}</title>

    {{-- Tailwind CSS --}}
    <script src="https://cdn.tailwindcss.com"></script>

    {{-- Boxicons --}}
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>

    {{-- Fonts --}}
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    {{-- Chart.js --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-100 font-['Inter']">
    {{-- Sidebar --}}
    <div class="fixed left-0 top-0 h-full w-64 bg-slate-800 shadow-lg border-r border-slate-700 z-10 p-4 overflow-y-auto">
        {{-- Brand --}}
        <div class="p-6 border-b border-slate-700">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 bg-red-600 rounded-lg flex items-center justify-center">
                    <i class='bx bx-shield text-white text-lg'></i>
                </div>
                <div>
                    <h1 class="font-bold text-white">CMU ARLO</h1>
                    <p class="text-xs text-slate-400">Admin Panel</p>
                </div>
            </div>
        </div>

        {{-- Admin Info --}}
        <div class="p-4 border-b border-slate-700">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center">
                    <i class='bx bx-user text-red-700'></i>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-white truncate">Administrator</p>
                    <p class="text-xs text-red-400">Admin</p>
                </div>
            </div>
        </div>

        {{-- Navigation --}}
        <nav class="flex-1 p-4">
            <ul class="space-y-2">
                <li>
                    <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 px-3 py-2 text-slate-300 rounded-lg hover:bg-slate-700 hover:text-white transition-colors {{ request()->routeIs('admin.dashboard') ? 'bg-slate-700 text-white' : '' }}">
                        <i class='bx bx-home text-lg'></i>
                        <span class="font-medium">Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('admin.alumni') }}" class="flex items-center gap-3 px-3 py-2 text-slate-300 rounded-lg hover:bg-slate-700 hover:text-white transition-colors {{ request()->routeIs('admin.alumni') ? 'bg-slate-700 text-white' : '' }}">
                        <i class='bx bx-group text-lg'></i>
                        <span class="font-medium">Alumni Management</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('admin.jobs') }}" class="flex items-center gap-3 px-3 py-2 text-slate-300 rounded-lg hover:bg-slate-700 hover:text-white transition-colors {{ request()->routeIs('admin.jobs') ? 'bg-slate-700 text-white' : '' }}">
                        <i class='bx bx-briefcase text-lg'></i>
                        <span class="font-medium">Job Management</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('admin.announcements') }}" class="flex items-center gap-3 px-3 py-2 text-slate-300 rounded-lg hover:bg-slate-700 hover:text-white transition-colors {{ request()->routeIs('admin.announcements') ? 'bg-slate-700 text-white' : '' }}">
                        <i class='bx bx-bell text-lg'></i>
                        <span class="font-medium">Announcements</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('admin.analytics') }}" class="flex items-center gap-3 px-3 py-2 text-slate-300 rounded-lg hover:bg-slate-700 hover:text-white transition-colors {{ request()->routeIs('admin.analytics') ? 'bg-slate-700 text-white' : '' }}">
                        <i class='bx bx-bar-chart text-lg'></i>
                        <span class="font-medium">Analytics</span>
                    </a>
                </li>
            </ul>
        </nav>

        {{-- Quick Stats --}}
        <div class="p-4 border-t border-slate-700">
            <h3 class="text-sm font-semibold text-white mb-3">Quick Stats</h3>
            <div class="space-y-2 text-xs">
                <div class="flex justify-between text-slate-400">
                    <span>Active Users:</span>
                    <span class="text-white">127</span>
                </div>
                <div class="flex justify-between text-slate-400">
                    <span>Jobs Posted:</span>
                    <span class="text-white">3</span>
                </div>
                <div class="flex justify-between text-slate-400">
                    <span>Announcements:</span>
                    <span class="text-white">5</span>
                </div>
            </div>
        </div>

        {{-- Logout --}}
        <div class="p-4 border-t border-slate-700">
            <a href="{{ route('logout') }}" class="flex items-center gap-3 px-3 py-2 text-slate-300 rounded-lg hover:bg-red-600 hover:text-white transition-colors">
                <i class='bx bx-log-out text-lg'></i>
                <span class="font-medium">Logout</span>
            </a>
        </div>
    </div>

    {{-- Main Content --}}
    <div class="ml-64 mr-4 min-h-screen">
        @yield('content')
    </div>

    {{-- Optional JS --}}
    @vite('resources/js/app.js')
</body>
</html>