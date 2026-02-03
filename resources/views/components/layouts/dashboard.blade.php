<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'CMU ARLO - Dashboard' }}</title>

    {{-- Tailwind CSS --}}
    <script src="https://cdn.tailwindcss.com"></script>

    {{-- Boxicons --}}
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>

    {{-- Fonts --}}
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    {{-- Chart.js --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-50 font-['Inter']">
    {{-- Sidebar --}}
    <div class="fixed left-0 top-0 h-full w-64 bg-white shadow-lg border-r border-gray-200 z-10 p-4 overflow-y-auto">
        {{-- Brand --}}
        <div class="p-6 border-b border-gray-200">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 bg-green-700 rounded-lg flex items-center justify-center">
                    <i class='bx bx-university text-white text-lg'></i>
                </div>
                <div>
                    <h1 class="font-bold text-gray-900">CMU ARLO</h1>
                    <p class="text-xs text-gray-500">Alumni System</p>
                </div>
            </div>
        </div>

        {{-- User Info --}}
        <div class="p-4 border-b border-gray-200">
            <a href="{{ route('profile') }}" class="flex items-center gap-3 hover:bg-green-50 rounded-lg p-2 -m-2 transition-colors">
                <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                    <i class='bx bx-user text-green-700'></i>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-gray-900 truncate">{{ Auth::user()->firstname ?? 'User' }} {{ Auth::user()->lastname ?? '' }}</p>
                    <p class="text-xs text-green-600">Alumni</p>
                </div>
                <i class='bx bx-chevron-right text-gray-400'></i>
            </a>
        </div>

        {{-- Navigation --}}
        <nav class="flex-1 p-4">
            <ul class="space-y-2">
                <li>
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-3 py-2 text-gray-700 rounded-lg hover:bg-green-50 hover:text-green-700 transition-colors {{ request()->routeIs('dashboard') ? 'bg-green-50 text-green-700' : '' }}">
                        <i class='bx bx-home text-lg'></i>
                        <span class="font-medium">Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('alumni.records') }}" class="flex items-center gap-3 px-3 py-2 text-gray-700 rounded-lg hover:bg-green-50 hover:text-green-700 transition-colors {{ request()->routeIs('alumni.records') ? 'bg-green-50 text-green-700' : '' }}">
                        <i class='bx bx-user text-lg'></i>
                        <span class="font-medium">Alumni Records</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('jobs') }}" class="flex items-center gap-3 px-3 py-2 text-gray-700 rounded-lg hover:bg-green-50 hover:text-green-700 transition-colors {{ request()->routeIs('jobs') ? 'bg-green-50 text-green-700' : '' }}">
                        <i class='bx bx-briefcase text-lg'></i>
                        <span class="font-medium">Jobs</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('settings') }}" class="flex items-center gap-3 px-3 py-2 text-gray-700 rounded-lg hover:bg-green-50 hover:text-green-700 transition-colors {{ request()->routeIs('settings') ? 'bg-green-50 text-green-700' : '' }}">
                        <i class='bx bx-cog text-lg'></i>
                        <span class="font-medium">Settings</span>
                    </a>
                </li>
            </ul>
        </nav>

        {{-- Job Offerings --}}
        <div class="p-4 border-t border-gray-200">
            <h3 class="text-sm font-semibold text-gray-900 mb-3">Latest Job Opportunities</h3>
            <div class="space-y-2 max-h-48 overflow-y-auto">
                <!-- Job announcements that match the jobs page -->
                <a href="{{ route('job.detail', 1) }}" class="block bg-blue-50 p-2 rounded-lg hover:bg-blue-100 transition-colors border-l-4 border-blue-500">
                    <p class="text-xs font-medium text-gray-900">Software Engineer</p>
                    <p class="text-xs text-gray-600">Tech Corp</p>
                    <p class="text-xs text-blue-600">Posted 2 days ago</p>
                </a>
                <a href="{{ route('job.detail', 2) }}" class="block bg-blue-50 p-2 rounded-lg hover:bg-blue-100 transition-colors border-l-4 border-blue-500">
                    <p class="text-xs font-medium text-gray-900">Marketing Specialist</p>
                    <p class="text-xs text-gray-600">Marketing Inc</p>
                    <p class="text-xs text-blue-600">Posted 1 week ago</p>
                </a>
                <a href="{{ route('job.detail', 3) }}" class="block bg-blue-50 p-2 rounded-lg hover:bg-blue-100 transition-colors border-l-4 border-blue-500">
                    <p class="text-xs font-medium text-gray-900">Data Analyst</p>
                    <p class="text-xs text-gray-600">Data Solutions</p>
                    <p class="text-xs text-blue-600">Posted 3 days ago</p>
                </a>
            </div>
            <div class="mt-3">
                <a href="{{ route('jobs') }}" class="text-xs text-green-600 hover:text-green-700 font-medium">
                    View all jobs →
                </a>
            </div>
        </div>

        {{-- General Announcements --}}
        <div class="p-4 border-t border-gray-200">
            <h3 class="text-sm font-semibold text-gray-900 mb-3">General Announcements</h3>
            <div class="space-y-2 max-h-32 overflow-y-auto">
                <!-- General announcements (non-job related) -->
                <a href="{{ route('announcement.detail', 2) }}" class="block bg-purple-50 p-2 rounded-lg hover:bg-purple-100 transition-colors border-l-4 border-purple-500">
                    <p class="text-xs font-medium text-gray-900">Alumni Networking Event</p>
                    <p class="text-xs text-gray-600">CMU Alumni Association</p>
                    <p class="text-xs text-purple-600">Posted 1 week ago</p>
                </a>
                <a href="{{ route('announcement.detail', 4) }}" class="block bg-purple-50 p-2 rounded-lg hover:bg-purple-100 transition-colors border-l-4 border-purple-500">
                    <p class="text-xs font-medium text-gray-900">CMU Career Fair 2026</p>
                    <p class="text-xs text-gray-600">CMU Career Services</p>
                    <p class="text-xs text-purple-600">Posted 5 days ago</p>
                </a>
                <a href="{{ route('announcement.detail', 5) }}" class="block bg-green-50 p-2 rounded-lg hover:bg-green-100 transition-colors border-l-4 border-green-500">
                    <p class="text-xs font-medium text-gray-900">Scholarship Opportunities</p>
                    <p class="text-xs text-gray-600">CMU Financial Aid</p>
                    <p class="text-xs text-green-600">Posted 1 week ago</p>
                </a>
            </div>
        </div>

        {{-- Logout --}}
        <div class="p-4 border-t border-gray-200">
            <a href="{{ route('logout') }}" class="flex items-center gap-3 px-3 py-2 text-gray-700 rounded-lg hover:bg-red-50 hover:text-red-700 transition-colors">
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