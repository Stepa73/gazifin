@php
    $formatMoney = fn (float $amount): string => number_format($amount, 2, ',', ' ').' Kč';
    $search = $search ?? '';
    $statusFilter = $statusFilter ?? 'all';
    $sort = $sort ?? 'created_at';
    $direction = $direction ?? 'desc';
    $sortedByIssueDate = $sort === 'issue_date';
    $sortParams = $sortedByIssueDate ? ['sort' => 'issue_date', 'direction' => $direction] : [];
    $issueDateSortUrl = $issueDateSortUrl ?? route('invoices.index', array_filter([
        'status' => $statusFilter !== 'all' ? $statusFilter : null,
        'q' => $search ?: null,
    ]) + [
        'sort' => 'issue_date',
        'direction' => $sortedByIssueDate && $direction === 'desc' ? 'asc' : 'desc',
    ]);

    $demoInvoices = [
        ['client' => 'Ukázkový klient a.s.', 'number' => '2026-0002', 'issue_date' => '12.02.2026', 'total' => 1000, 'status' => 'draft', 'url' => null],
        ['client' => 'Demo Solutions s.r.o.', 'number' => '2026-0001', 'issue_date' => '05.01.2026', 'total' => 459500, 'status' => 'sent', 'url' => null],
        ['client' => 'Test Company', 'number' => '2025-0042', 'issue_date' => '18.11.2025', 'total' => 12500, 'status' => 'paid', 'url' => null],
    ];

    $statusClasses = [
        'draft' => 'text-orange-500',
        'sent' => 'text-gray-500',
        'paid' => 'text-green-600',
    ];

    $statusLabels = [
        'draft' => 'Neodesláno',
        'sent' => 'Odesláno',
        'paid' => 'Uhrazeno',
    ];
@endphp

<x-mobile.layout active="documents">
    <div class="pb-24">
        {{-- Top bar --}}
        <header class="sticky top-0 z-40 border-b border-gray-100 bg-white px-4 py-3">
            <div class="flex items-center justify-between gap-3">
                <a href="{{ route('profile.edit') }}" class="flex h-10 w-10 items-center justify-center rounded-full text-gray-600 hover:bg-gray-50" aria-label="Nastavení">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 15.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7Z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2.1 2.1 0 0 1-2.9 2.9l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2.1 2.1 0 0 1-4.2 0v-.1a1.7 1.7 0 0 0-1-1.5 1.7 1.7 0 0 0-1.8.3l-.1.1a2.1 2.1 0 0 1-2.9-2.9l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2.1 2.1 0 0 1 0-4.2h.1a1.7 1.7 0 0 0 1.5-1 1.7 1.7 0 0 0-.3-1.8l-.1-.1a2.1 2.1 0 0 1 2.9-2.9l.1.1a1.7 1.7 0 0 0 1.8.3 1.7 1.7 0 0 0 1-1.5V3a2.1 2.1 0 0 1 4.2 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.8-.3l.1-.1a2.1 2.1 0 0 1 2.9 2.9l-.1.1a1.7 1.7 0 0 0-.3 1.8 1.7 1.7 0 0 0 1.5 1H21a2.1 2.1 0 0 1 0 4.2h-.1a1.7 1.7 0 0 0-1.5 1Z"/>
                    </svg>
                </a>

                <h1 class="text-base font-bold tracking-wide text-gray-900">DOKUMENTY</h1>

                <a href="{{ route('invoices.create') }}" class="flex h-10 w-10 items-center justify-center rounded-full text-brand hover:bg-brand-light" aria-label="Nový dokument">
                    <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v14M5 12h14"/>
                    </svg>
                </a>
            </div>
        </header>

        <x-flash-messages />

        {{-- Search --}}
        <form method="GET" action="{{ route('invoices.index') }}" class="px-4 pt-4">
            <div class="relative">
                <svg class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m21 21-4.3-4.3M10.5 18a7.5 7.5 0 1 1 0-15 7.5 7.5 0 0 1 0 15Z"/>
                </svg>
                <input
                    type="search"
                    name="q"
                    value="{{ $search }}"
                    placeholder="Vyhledat"
                    class="w-full rounded-xl border-gray-200 bg-gray-50 py-3 pl-10 pr-4 text-sm text-gray-900 placeholder:text-gray-400 focus:border-brand focus:ring-brand"
                >
                @if ($statusFilter !== 'all')
                    <input type="hidden" name="status" value="{{ $statusFilter }}">
                @endif
                @if ($sortedByIssueDate)
                    <input type="hidden" name="sort" value="issue_date">
                    <input type="hidden" name="direction" value="{{ $direction }}">
                @endif
            </div>
        </form>

        {{-- Quick filters --}}
        <div class="mt-4 flex gap-2 overflow-x-auto border-t border-gray-100 px-4 py-3">
            @foreach (['all' => 'Všechny', 'overdue' => 'Po splatnosti', 'unpaid' => 'Neuhrazené', 'unsent' => 'Neodesláno', 'paid' => 'Uhrazené'] as $value => $label)
                <a
                    href="{{ route('invoices.index', array_filter(['status' => $value !== 'all' ? $value : null, 'q' => $search ?: null]) + $sortParams) }}"
                    @class([
                        'shrink-0 rounded-full border px-3 py-1.5 text-xs font-medium',
                        'border-brand bg-brand-light text-brand' => $statusFilter === $value,
                        'border-gray-200 bg-white text-gray-600' => $statusFilter !== $value,
                    ])
                >
                    {{ $label }}
                </a>
            @endforeach
        </div>

        {{-- Sorting --}}
        <div class="flex items-center justify-end border-t border-gray-100 px-4 py-2">
            <a
                href="{{ $issueDateSortUrl }}"
                @class([
                    'inline-flex items-center gap-1 rounded-full border px-3 py-1.5 text-xs font-medium',
                    'border-brand bg-brand-light text-brand' => $sortedByIssueDate,
                    'border-gray-200 bg-white text-gray-600' => ! $sortedByIssueDate,
                ])
            >
                Datum vystavení
                @if ($sortedByIssueDate)
                    <span aria-hidden="true">{{ $direction === 'asc' ? '↑' : '↓' }}</span>
                    <span class="sr-only">{{ $direction === 'asc' ? 'seřazeno vzestupně' : 'seřazeno sestupně' }}</span>
                @else
                    <span class="text-gray-300" aria-hidden="true">↕</span>
                @endif
            </a>
        </div>

        {{-- Invoice list --}}
        <div class="divide-y divide-gray-100 border-t border-gray-100">
            @forelse ($invoices as $invoice)
                <a href="{{ route('invoices.show', $invoice) }}" class="block px-4 py-4 active:bg-gray-50">
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0">
                            <div class="truncate text-base font-semibold text-gray-900">{{ $invoice->client->name }}</div>
                            <div class="mt-1 text-sm text-gray-500">{{ $invoice->number }} · {{ $invoice->issue_date->format('d.m.Y') }}</div>
                        </div>
                        <div class="shrink-0 text-right">
                            <div class="text-base font-bold text-gray-900">{{ $formatMoney((float) $invoice->total) }}</div>
                            <div class="mt-1 text-sm font-medium {{ $invoice->mobileStatusClass() }}">{{ $invoice->mobileStatusLabel() }}</div>
                        </div>
                    </div>
                </a>
            @empty
                @if ($isFiltered ?? false)
                    <div class="px-4 py-10 text-center">
                        <p class="text-sm text-gray-500">Žádné faktury neodpovídají zadanému filtru.</p>
                        <a href="{{ route('invoices.index') }}" class="mt-3 inline-flex items-center text-sm font-semibold text-brand">Zrušit filtr</a>
                    </div>
                @else
                    @foreach ($demoInvoices as $demo)
                        <div class="px-4 py-4 opacity-80">
                            <div class="flex items-start justify-between gap-4">
                                <div class="min-w-0">
                                    <div class="truncate text-base font-semibold text-gray-900">{{ $demo['client'] }}</div>
                                    <div class="mt-1 text-sm text-gray-500">{{ $demo['number'] }} · {{ $demo['issue_date'] }}</div>
                                </div>
                                <div class="shrink-0 text-right">
                                    <div class="text-base font-bold text-gray-900">{{ $formatMoney((float) $demo['total']) }}</div>
                                    <div class="mt-1 text-sm font-medium {{ $statusClasses[$demo['status']] }}">{{ $statusLabels[$demo['status']] }}</div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                @endif
            @endforelse
        </div>

        @if ($invoices->hasPages())
            <div class="px-4 py-4">
                {{ $invoices->links() }}
            </div>
        @endif
    </div>
</x-mobile.layout>
