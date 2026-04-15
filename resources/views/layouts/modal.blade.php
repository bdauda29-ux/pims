<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-gray-100">
        <div class="min-h-screen">
            @isset($header)
                @php
                    $dashboardHref = auth()->check() && ! auth()->user()->isUser() && ! auth()->user()->isPersonnel()
                        ? route('admin.portal')
                        : route('dashboard');
                    $showBack = ! request()->routeIs('dashboard') && ! request()->routeIs('admin.portal');
                    
                    $routeName = request()->route()?->getName();
                    $backHref = null;
                    if ($routeName) {
                        if (str_ends_with($routeName, '.create') || str_ends_with($routeName, '.edit') || str_ends_with($routeName, '.show')) {
                            $base = substr($routeName, 0, strrpos($routeName, '.'));
                            if (Route::has("$base.index")) {
                                $backHref = route("$base.index");
                            }
                        } elseif (str_ends_with($routeName, '.index')) {
                            $backHref = $dashboardHref;
                        }
                    }
                    $backHref = $backHref ?: (url()->previous() ?: $dashboardHref);
                @endphp
                <header class="bg-white shadow">
                    <div class="max-w-7xl mx-auto py-4 px-4 sm:px-6 lg:px-8">
                        <div class="flex items-center gap-4">
                            @if($showBack)
                                <a href="{{ $backHref }}" class="h-9 inline-flex items-center px-3 border border-gray-300 rounded-md bg-white font-semibold text-gray-700">
                                    Back
                                </a>
                            @endif
                            <div class="flex-1">
                                {{ $header }}
                            </div>
                        </div>
                    </div>
                </header>
            @endisset

            <main>
                {{ $slot }}
            </main>
        </div>
        <script>
            window.addEventListener('load', () => {
                const observer = new ResizeObserver((entries) => {
                    for (let entry of entries) {
                        const height = entry.target.scrollHeight;
                        window.parent.postMessage({ type: 'modalHeight', height: height }, '*');
                    }
                });
                observer.observe(document.body);
            });

            document.addEventListener('click', (e) => {
                const a = e.target && e.target.closest ? e.target.closest('a') : null;
                if (!a) return;
                if (!a.href) return;
                let url;
                try {
                    url = new URL(a.href, window.location.origin);
                } catch (err) {
                    return;
                }
                if (url.origin !== window.location.origin) return;
                if (!url.searchParams.has('modal')) {
                    url.searchParams.set('modal', '1');
                    a.href = url.toString();
                }
            });
        </script>
    </body>
</html>
