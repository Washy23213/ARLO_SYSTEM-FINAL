<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>CMU ARLO | @yield('title')</title>

    {{-- Google Font --}}
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">

    {{-- Vite --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @yield('styles')
</head>

<body class="font-[Inter] bg-gray-100 min-h-screen">

    {{-- Wrapper Center --}}
    <div class="min-h-screen flex items-center justify-center px-4 py-10">
        @yield('content')
    </div>

    @yield('scripts')
</body>
</html>
