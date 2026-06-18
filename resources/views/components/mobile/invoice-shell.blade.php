@props([
    'title',
    'backUrl',
])

<x-mobile.layout active="documents">
    <div class="pb-24">
        <header class="sticky top-0 z-40 border-b border-gray-100 bg-white px-4 py-3">
            <div class="flex items-center justify-between gap-3">
                <a href="{{ $backUrl }}" class="flex h-10 w-10 items-center justify-center rounded-full text-gray-600 hover:bg-gray-50" aria-label="Zpět">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 18 9 12l6-6"/>
                    </svg>
                </a>

                <h1 class="truncate text-base font-bold tracking-wide text-gray-900">{{ $title }}</h1>

                @isset($headerRight)
                    <div class="flex h-10 w-10 items-center justify-center">
                        {{ $headerRight }}
                    </div>
                @else
                    <div class="h-10 w-10" aria-hidden="true"></div>
                @endisset
            </div>
        </header>

        <div class="px-4 py-4">
            {{ $slot }}
        </div>
    </div>
</x-mobile.layout>
