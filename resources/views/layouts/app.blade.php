<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <link rel="icon" type="image/svg+xml" href="{{ asset('images/logo.svg') }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-white text-gray-900 lg:bg-gray-100">
            <div class="hidden lg:block">
                @include('layouts.navigation')
            </div>

            @if ($title !== null)
                <header class="sticky top-0 z-40 border-b border-gray-100 bg-white px-4 py-3 lg:hidden">
                    <div class="flex items-center justify-between gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center">
                            @if (isset($mobileLeft))
                                {{ $mobileLeft }}
                            @elseif ($backUrl)
                                <a href="{{ $backUrl }}" class="flex h-10 w-10 items-center justify-center rounded-full text-gray-600 hover:bg-gray-50" aria-label="Zpět">
                                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 18 9 12l6-6"/>
                                    </svg>
                                </a>
                            @endif
                        </div>

                        <h1 class="truncate text-base font-bold tracking-wide text-gray-900">{{ $title }}</h1>

                        <div class="flex h-10 w-10 shrink-0 items-center justify-center">
                            @if (isset($mobileRight))
                                {{ $mobileRight }}
                            @endif
                        </div>
                    </div>
                </header>
            @endif

            @isset($header)
                <header class="hidden bg-white shadow lg:block">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <main class="pb-24 lg:pb-0">
                @if (session('success') || session('error'))
                    <div class="px-4 pt-4 lg:mx-auto lg:max-w-7xl lg:px-8 lg:pt-8">
                        <x-flash-messages />
                    </div>
                @endif

                {{ $slot }}
            </main>

            @if ($active)
                <x-bottom-nav :active="$active" />
            @endif
        </div>
    </body>
</html>
