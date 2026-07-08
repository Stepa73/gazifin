@php
    $formatMoney = fn (float $amount): string => number_format($amount, 2, ',', ' ').' Kč';
    $companyName = $user->displayCompanyName();
@endphp

<x-mobile.layout active="home">
    <div class="pb-28">
        {{-- Top bar --}}
        <header class="sticky top-0 z-40 border-b border-gray-100 bg-white px-4 py-3">
            <div class="flex items-center justify-between gap-3">
                <a href="{{ route('profile.edit') }}" class="flex h-10 w-10 items-center justify-center rounded-full text-gray-600 hover:bg-gray-50" aria-label="Nastavení">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 15.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7Z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2.1 2.1 0 0 1-2.9 2.9l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2.1 2.1 0 0 1-4.2 0v-.1a1.7 1.7 0 0 0-1-1.5 1.7 1.7 0 0 0-1.8.3l-.1.1a2.1 2.1 0 0 1-2.9-2.9l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2.1 2.1 0 0 1 0-4.2h.1a1.7 1.7 0 0 0 1.5-1 1.7 1.7 0 0 0-.3-1.8l-.1-.1a2.1 2.1 0 0 1 2.9-2.9l.1.1a1.7 1.7 0 0 0 1.8.3 1.7 1.7 0 0 0 1-1.5V3a2.1 2.1 0 0 1 4.2 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.8-.3l.1-.1a2.1 2.1 0 0 1 2.9 2.9l-.1.1a1.7 1.7 0 0 0-.3 1.8 1.7 1.7 0 0 0 1.5 1H21a2.1 2.1 0 0 1 0 4.2h-.1a1.7 1.7 0 0 0-1.5 1Z"/>
                    </svg>
                </a>

                <div class="flex min-w-0 flex-1 items-center justify-center gap-1 text-base font-semibold text-gray-900">
                    <span class="truncate">{{ $companyName }}</span>
                </div>

                <div class="h-10 w-10 shrink-0" aria-hidden="true"></div>
            </div>
        </header>

        <x-flash-messages />

        {{-- Overview --}}
        <section class="px-4 pt-5">
            <div class="mb-3 flex items-center justify-between">
                <h2 class="text-xs font-bold tracking-wide text-gray-500">PŘEHLED</h2>
                <a href="{{ route('reports') }}" class="inline-flex items-center gap-1 text-sm font-medium text-brand">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 20V10M10 20V4M16 20v-8M22 20H2"/>
                    </svg>
                    Reporty
                </a>
            </div>

            <div class="space-y-3">
                @foreach ($overviewCards as $card)
                    <a href="{{ route('invoices.index', ['status' => $card['status']]) }}" class="block rounded-2xl border border-gray-200 bg-gray-50/80 px-4 py-4 active:bg-gray-100">
                        <div class="mb-2 flex items-center justify-between gap-2">
                            <span class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">{{ $card['label'] }}</span>
                            <span class="rounded-md border border-gray-200 bg-white px-2 py-0.5 text-xs font-medium text-gray-500">{{ $card['count'] }}</span>
                        </div>
                        <div class="flex items-center justify-between gap-2">
                            <div class="text-2xl font-bold text-gray-900">{{ $formatMoney($card['total']) }}</div>
                            <svg class="h-5 w-5 shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m9 6 6 6-6 6"/>
                            </svg>
                        </div>
                    </a>
                @endforeach
            </div>
        </section>

        {{-- FAB --}}
        <a
            href="{{ route('invoices.create') }}"
            class="fixed bottom-[calc(4.5rem+env(safe-area-inset-bottom))] right-4 z-40 inline-flex items-center gap-2 rounded-full bg-brand px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-brand/30"
        >
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v14M5 12h14"/>
            </svg>
            Vytvořit nové
        </a>
    </div>
</x-mobile.layout>
