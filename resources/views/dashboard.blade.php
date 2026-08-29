@php
    $formatMoney = fn (float $amount): string => number_format($amount, 2, ',', ' ').' Kč';
@endphp

<x-app-layout :title="$user->displayCompanyName()" active="home">
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Dashboard</h2>
            <div class="flex gap-2">
                <a href="{{ route('reports') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50">
                    Reporty
                </a>
                <a href="{{ route('invoices.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500">
                    Nová faktura
                </a>
                <a href="{{ route('clients.create') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50">
                    Přidat klienta
                </a>
            </div>
        </div>
    </x-slot>

    <x-slot name="mobileLeft">
        <a href="{{ route('profile.edit') }}" class="flex h-10 w-10 items-center justify-center rounded-full text-gray-600 hover:bg-gray-50" aria-label="Nastavení">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 15.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7Z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2.1 2.1 0 0 1-2.9 2.9l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2.1 2.1 0 0 1-4.2 0v-.1a1.7 1.7 0 0 0-1-1.5 1.7 1.7 0 0 0-1.8.3l-.1.1a2.1 2.1 0 0 1-2.9-2.9l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2.1 2.1 0 0 1 0-4.2h.1a1.7 1.7 0 0 0 1.5-1 1.7 1.7 0 0 0-.3-1.8l-.1-.1a2.1 2.1 0 0 1 2.9-2.9l.1.1a1.7 1.7 0 0 0 1.8.3 1.7 1.7 0 0 0 1-1.5V3a2.1 2.1 0 0 1 4.2 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.8-.3l.1-.1a2.1 2.1 0 0 1 2.9 2.9l-.1.1a1.7 1.7 0 0 0-.3 1.8 1.7 1.7 0 0 0 1.5 1H21a2.1 2.1 0 0 1 0 4.2h-.1a1.7 1.7 0 0 0-1.5 1Z"/>
            </svg>
        </a>
    </x-slot>

    <x-page class="lg:space-y-6">
        {{-- Přehled --}}
        <section class="px-4 pt-5 lg:px-0 lg:pt-0">
            <div class="mb-3 flex items-center justify-between">
                <h2 class="text-xs font-bold tracking-wide text-gray-500">PŘEHLED</h2>
                <a href="{{ route('reports') }}" class="inline-flex items-center gap-1 text-sm font-medium text-brand">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 20V10M10 20V4M16 20v-8M22 20H2"/>
                    </svg>
                    Reporty
                </a>
            </div>

            <div class="space-y-3 lg:grid lg:grid-cols-4 lg:gap-4 lg:space-y-0">
                @foreach ($overviewCards as $card)
                    <a href="{{ route('invoices.index', ['status' => $card['status']]) }}" class="block rounded-2xl border border-gray-200 bg-gray-50/80 px-4 py-4 active:bg-gray-100 lg:bg-white lg:transition lg:hover:shadow-md">
                        <div class="mb-2 flex items-center justify-between gap-2">
                            <span class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">{{ $card['label'] }}</span>
                            <span class="rounded-md border border-gray-200 bg-white px-2 py-0.5 text-xs font-medium text-gray-500">{{ $card['count'] }}</span>
                        </div>
                        <div class="flex items-center justify-between gap-2">
                            <div class="text-2xl font-bold text-gray-900">{{ $formatMoney($card['total']) }}</div>
                            <svg class="h-5 w-5 shrink-0 text-gray-400 lg:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m9 6 6 6-6 6"/>
                            </svg>
                        </div>
                    </a>
                @endforeach
            </div>
        </section>

        {{-- Poslední faktury --}}
        {{-- Spodní odsazení na mobilu drží poslední řádek nad plovoucím tlačítkem. --}}
        <section class="mt-6 pb-8 lg:mt-0 lg:pb-0">
            <h2 class="mb-3 px-4 text-xs font-bold tracking-wide text-gray-500 lg:hidden">POSLEDNÍ FAKTURY</h2>

            <x-panel :padded="false" class="overflow-hidden">
                <h3 class="hidden px-6 pt-6 text-lg font-medium text-gray-900 lg:block">Poslední faktury</h3>

                <x-list grid="lg:grid-cols-[2fr_1fr_1fr_1fr_1fr]" class="lg:mt-4">
                    @if ($invoices->isNotEmpty())
                        <x-list.head>
                            <div>Klient</div>
                            <div>Číslo</div>
                            <div>Datum</div>
                            <div>Částka</div>
                            <div>Stav</div>
                        </x-list.head>
                    @endif

                    @forelse ($invoices as $invoice)
                        <x-list.row>
                            <div class="flex items-start justify-between gap-4 lg:contents">
                                <div class="min-w-0 lg:contents">
                                    <div class="min-w-0">
                                        <a href="{{ route('invoices.show', $invoice) }}" class="block truncate text-base font-semibold text-gray-900 after:absolute after:inset-0 lg:text-sm lg:font-medium">{{ $invoice->client->name }}</a>
                                    </div>
                                    <div class="mt-1 flex items-center text-sm text-gray-500 lg:mt-0 lg:contents">
                                        <div class="lg:text-gray-900">{{ $invoice->number }}</div>
                                        <div class="before:mx-1 before:content-['·'] lg:before:content-none lg:text-gray-900">{{ $invoice->issue_date->format('d.m.Y') }}</div>
                                    </div>
                                </div>

                                <div class="shrink-0 text-right lg:contents">
                                    <div class="text-base font-bold text-gray-900 lg:text-left lg:text-sm lg:font-normal">{{ $formatMoney((float) $invoice->total) }}</div>
                                    <div class="mt-1 text-sm font-medium lg:mt-0 lg:text-left {{ $invoice->mobileStatusClass() }} lg:text-gray-900">{{ $invoice->mobileStatusLabel() }}</div>
                                </div>
                            </div>
                        </x-list.row>
                    @empty
                        <div class="px-4 py-10 text-center lg:px-6">
                            <p class="text-sm text-gray-500">Zatím nemáte žádné faktury.</p>
                        </div>
                    @endforelse
                </x-list>
            </x-panel>
        </section>

        {{-- Rychlá akce jen na mobilu, na desktopu je tlačítko v hlavičce --}}
        <a
            href="{{ route('invoices.create') }}"
            class="fixed bottom-[calc(4.5rem+env(safe-area-inset-bottom))] right-4 z-40 inline-flex items-center gap-2 rounded-full bg-brand px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-brand/30 lg:hidden"
        >
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v14M5 12h14"/>
            </svg>
            Vytvořit nové
        </a>
    </x-page>
</x-app-layout>
