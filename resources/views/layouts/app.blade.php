<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-100" x-data="{ openChangePassword: false }" x-init="openChangePassword = @js(session()->has('show_change_password_modal') || $errors->updatePassword->any())">
            @include('layouts.navigation')

            <!-- Page Heading -->
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

                    if ($routeName === 'personnel.edit' && auth()->user()?->isUser()) {
                        $routeUser = request()->route('user');
                        $routeUserId = is_object($routeUser) && method_exists($routeUser, 'getKey') ? (int) $routeUser->getKey() : (int) $routeUser;
                        if ((int) auth()->id() === $routeUserId) {
                            $backHref = $dashboardHref;
                        }
                    }
                    $backHref = $backHref ?: (url()->previous() ?: $dashboardHref);
                @endphp
                <header class="bg-white shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
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

            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>

            @auth
                <div x-show="openChangePassword" x-transition x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <div class="absolute inset-0 bg-black/40" @click="openChangePassword = false"></div>
                    <div class="relative w-[33.33vw] max-w-[95vw] bg-white rounded-lg shadow-lg flex flex-col max-h-[33.33vh]">
                        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between shrink-0">
                            <div class="text-lg font-semibold text-gray-900">Change Password</div>
                            <button type="button" @click="openChangePassword = false" class="text-gray-500 hover:text-gray-700">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                        <div class="p-6 overflow-y-auto flex-1">
                            @include('profile.partials.update-password-form')
                        </div>
                    </div>
                </div>
            @endauth
        </div>
    </body>
</html>
