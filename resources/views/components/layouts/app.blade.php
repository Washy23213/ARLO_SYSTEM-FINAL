<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'CMU OARL' }}</title>

    {{-- Tailwind CSS --}}
    <!-- Tailwind CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <link rel="stylesheet" href="{{ asset('resources/css/login.css') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="portal-flash-overlay fixed right-3 top-3 z-[100] sm:right-6 sm:top-6">
        @include('components.flash-messages')
    </div>

    {{ $slot }}

    <script>
        (function () {
            const isSkippableHref = (href) => {
                if (!href) return true;
                const value = href.trim().toLowerCase();
                return (
                    value.startsWith("#") ||
                    value.startsWith("mailto:") ||
                    value.startsWith("tel:") ||
                    value.startsWith("javascript:")
                );
            };

            document.querySelectorAll("a[href]").forEach((anchor) => {
                if (anchor.dataset.keepHref === "true") return;
                if (anchor.hasAttribute("download")) return;
                if (anchor.hasAttribute("target")) return;

                const originalHref = anchor.getAttribute("href");
                if (isSkippableHref(originalHref)) return;

                let resolvedUrl;
                try {
                    resolvedUrl = new URL(originalHref, window.location.origin);
                } catch (e) {
                    return;
                }

                if (resolvedUrl.origin !== window.location.origin) return;

                anchor.dataset.navUrl = resolvedUrl.toString();
                anchor.removeAttribute("href");
                anchor.style.cursor = "pointer";

                anchor.addEventListener("click", (event) => {
                    const url = anchor.dataset.navUrl;
                    if (!url) return;
                    if (event.metaKey || event.ctrlKey) {
                        window.open(url, "_blank", "noopener");
                        return;
                    }
                    window.location.assign(url);
                });

                anchor.addEventListener("auxclick", (event) => {
                    if (event.button !== 1) return;
                    const url = anchor.dataset.navUrl;
                    if (!url) return;
                    window.open(url, "_blank", "noopener");
                });

                anchor.addEventListener("keydown", (event) => {
                    if (event.key !== "Enter") return;
                    const url = anchor.dataset.navUrl;
                    if (!url) return;
                    event.preventDefault();
                    window.location.assign(url);
                });
            });
        })();
    </script>
</body>
</html>
