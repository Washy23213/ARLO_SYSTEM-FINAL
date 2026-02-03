<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'CMU ARLO' }}</title>

    {{-- Tailwind CSS --}}
    <!-- Tailwind CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <link rel="stylesheet" href="{{ asset('resources/css/login.css') }}">

    {{-- Fonts --}}
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">

    {{ $slot }}

    {{-- Optional JS --}}
    @vite('resources/js/app.js')
</body>
</html>
