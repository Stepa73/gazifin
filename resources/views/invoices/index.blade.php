@php
    $formatMoney = fn (float $amount): string => number_format($amount, 2, ',', ' ').' Kč';
    $direction = $direction ?? 'desc';
    $sortQuery = collect(request()->query())->except(['page', 'direction'])->all();
    $issueDateSortUrl = route('invoices.index', $sortQuery + ['direction' => $direction === 'desc' ? 'asc' : 'desc']);

    $statusOptions = ['all' => 'Všechny', 'overdue' => 'Po splatnosti', 'unpaid' => 'Neuhrazené', 'unsent' => 'Neodesláno', 'paid' => 'Uhrazené'];

    $demoInvoices = [
        ['client' => 'Ukázkový klient a.s.', 'number' => '2026-0002', 'issue_date' => '12.02.2026', 'total' => 1000, 'label' => 'Neodesláno', 'class' => 'text-orange-500'],
        ['client' => 'Demo Solutions s.r.o.', 'number' => '2026-0001', 'issue_date' => '05.01.2026', 'total' => 459500, 'label' => 'Odesláno', 'class' => 'text-gray-500'],
        ['client' => 'Test Company', 'number' => '2025-0042', 'issue_date' => '18.11.2025', 'total' => 12500, 'label' => 'Uhrazeno', 'class' => 'text-green-600'],
    ];
@endphp

<x-app-layout title="DOKUMENTY" active="documents">
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Faktury</h2>
            <a href="{{ route('invoices.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500">
                Nová faktura
            </a>
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

    <x-slot name="mobileRight">
        <a href="{{ route('invoices.create') }}" class="flex h-10 w-10 items-center justify-center rounded-full text-brand hover:bg-brand-light" aria-label="Nová faktura">
            <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v14M5 12h14"/>
            </svg>
        </a>
    </x-slot>

    <x-page>
        {{-- Hledání --}}
        <form method="GET" action="{{ route('invoices.index') }}" class="px-4 pt-4 lg:px-0 lg:pt-0">
            <div class="flex flex-col gap-2 lg:flex-row lg:items-center">
                <div class="relative lg:w-96">
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m21 21-4.3-4.3M10.5 18a7.5 7.5 0 1 1 0-15 7.5 7.5 0 0 1 0 15Z"/>
                    </svg>
                    <input
                        type="search"
                        name="q"
                        value="{{ $search }}"
                        placeholder="Hledat podle čísla nebo klienta"
                        class="w-full rounded-xl border-gray-200 bg-gray-50 py-3 pl-10 pr-4 text-sm text-gray-900 placeholder:text-gray-400 focus:border-brand focus:ring-brand lg:rounded-md lg:border-gray-300 lg:bg-white lg:py-2"
                    >
                </div>

                @if ($statusFilter !== 'all')
                    <input type="hidden" name="status" value="{{ $statusFilter }}">
                @endif
                <input type="hidden" name="direction" value="{{ $direction }}">

                <button type="submit" class="hidden items-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white hover:bg-indigo-500 lg:inline-flex">
                    Hledat
                </button>
            </div>
        </form>

        {{-- Filtry a řazení --}}
        {{-- Na mobilu je řazení na vlastním řádku, aby nepřekrývalo odrolované filtry. --}}
        <div class="mt-4 flex flex-col gap-2 border-t border-gray-100 px-4 py-3 lg:mt-4 lg:flex-row lg:items-center lg:border-0 lg:px-0 lg:py-0">
            <div class="flex gap-2 overflow-x-auto lg:flex-wrap lg:overflow-visible">
                @foreach ($statusOptions as $value => $label)
                    <a
                        href="{{ route('invoices.index', array_filter(['status' => $value !== 'all' ? $value : null, 'q' => $search ?: null]) + ['direction' => $direction]) }}"
                        @class([
                            'shrink-0 rounded-full border px-3 py-1.5 text-xs font-medium',
                            'border-brand bg-brand-light text-brand' => $statusFilter === $value,
                            'border-gray-200 bg-white text-gray-600 hover:bg-gray-50' => $statusFilter !== $value,
                        ])
                    >
                        {{ $label }}
                    </a>
                @endforeach
            </div>

            <a href="{{ $issueDateSortUrl }}" class="inline-flex shrink-0 items-center gap-1 self-end rounded-full border border-brand bg-brand-light px-3 py-1.5 text-xs font-medium text-brand lg:ml-auto lg:self-auto" title="Přepnout směr řazení podle data vystavení">
                Datum vystavení
                <span aria-hidden="true">{{ $direction === 'asc' ? '↑' : '↓' }}</span>
                <span class="sr-only">{{ $direction === 'asc' ? 'seřazeno vzestupně' : 'seřazeno sestupně' }}</span>
            </a>
        </div>

        <x-panel :padded="false" class="mt-0 overflow-hidden lg:mt-4">
            <x-list grid="lg:grid-cols-[2fr_1fr_1fr_1fr_1fr_1fr_7rem]">
                @if ($invoices->isNotEmpty())
                    <x-list.head>
                        <div>Klient</div>
                        <div>Číslo</div>
                        <div>Vystaveno</div>
                        <div>Splatnost</div>
                        <div>Částka</div>
                        <div>Stav</div>
                        <div class="text-right">Akce</div>
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
                                    <div class="hidden lg:block lg:text-gray-900">{{ $invoice->due_date->format('d.m.Y') }}</div>
                                </div>
                            </div>

                            <div class="shrink-0 text-right lg:contents">
                                <div class="text-base font-bold text-gray-900 lg:text-left lg:text-sm lg:font-normal">{{ $formatMoney((float) $invoice->total) }}</div>
                                <div class="mt-1 text-sm font-medium lg:mt-0 lg:text-left {{ $invoice->mobileStatusClass() }} lg:text-gray-900">{{ $invoice->mobileStatusLabel() }}</div>
                            </div>
                        </div>

                        <div class="relative z-10 hidden justify-end gap-2 whitespace-nowrap text-sm lg:flex">
                            <a href="{{ route('invoices.edit', $invoice) }}" class="text-indigo-600 hover:underline">Upravit</a>
                            <x-confirm-delete
                                class="inline-block"
                                :action="route('invoices.destroy', $invoice)"
                                title="Smazat fakturu {{ $invoice->number }}?"
                                trigger-class="text-red-600 hover:underline"
                            >
                                <x-slot:trigger>Smazat</x-slot:trigger>
                            </x-confirm-delete>
                        </div>
                    </x-list.row>
                @empty
                    @if ($isFiltered ?? false)
                        <div class="px-4 py-10 text-center">
                            <p class="text-sm text-gray-500">Žádné faktury neodpovídají zadanému filtru.</p>
                            <a href="{{ route('invoices.index') }}" class="mt-3 inline-flex items-center text-sm font-semibold text-brand">Zrušit filtr</a>
                        </div>
                    @else
                        @foreach ($demoInvoices as $demo)
                            <x-list.row class="opacity-80">
                                <div class="flex items-start justify-between gap-4 lg:contents">
                                    <div class="min-w-0 lg:contents">
                                        <div class="min-w-0 truncate text-base font-semibold text-gray-900 lg:text-sm lg:font-medium">{{ $demo['client'] }}</div>
                                        <div class="mt-1 flex items-center text-sm text-gray-500 lg:mt-0 lg:contents">
                                            <div class="lg:text-gray-900">{{ $demo['number'] }}</div>
                                            <div class="before:mx-1 before:content-['·'] lg:before:content-none lg:text-gray-900">{{ $demo['issue_date'] }}</div>
                                            <div class="hidden lg:block">—</div>
                                        </div>
                                    </div>
                                    <div class="shrink-0 text-right lg:contents">
                                        <div class="text-base font-bold text-gray-900 lg:text-left lg:text-sm lg:font-normal">{{ $formatMoney((float) $demo['total']) }}</div>
                                        <div class="mt-1 text-sm font-medium lg:mt-0 lg:text-left {{ $demo['class'] }}">{{ $demo['label'] }}</div>
                                    </div>
                                </div>
                                <div class="hidden lg:block"></div>
                            </x-list.row>
                        @endforeach
                    @endif
                @endforelse
            </x-list>

            @if ($invoices->hasPages())
                <div class="px-4 py-4">{{ $invoices->links() }}</div>
            @endif
        </x-panel>
    </x-page>
</x-app-layout>
