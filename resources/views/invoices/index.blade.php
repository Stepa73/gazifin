@php
    $direction = $direction ?? 'desc';
    $issueDateSortDirection = $direction === 'desc' ? 'asc' : 'desc';
    $sortQuery = collect(request()->query())->except(['page', 'direction'])->all();
    $issueDateSortUrl = route('invoices.index', $sortQuery + ['direction' => $issueDateSortDirection]);
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Faktury</h2>
            <a href="{{ route('invoices.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500">
                Nová faktura
            </a>
        </div>
    </x-slot>

    <div class="hidden lg:block">
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <x-flash-messages />

                <form method="GET" action="{{ route('invoices.index') }}" class="mb-4 flex flex-wrap items-center gap-2">
                    <input type="hidden" name="direction" value="{{ $direction }}">
                    <input
                        type="search"
                        name="q"
                        value="{{ $search }}"
                        placeholder="Hledat podle čísla nebo klienta"
                        class="w-72 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm"
                    >
                    <select name="status" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                        @foreach (['all' => 'Všechny', 'overdue' => 'Po splatnosti', 'unpaid' => 'Neuhrazené', 'unsent' => 'Neodesláno', 'paid' => 'Uhrazené'] as $value => $label)
                            <option value="{{ $value }}" @selected($statusFilter === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500">
                        Hledat
                    </button>
                    @if ($search !== '' || $statusFilter !== 'all')
                        <a href="{{ route('invoices.index') }}" class="text-sm text-gray-500 hover:underline">Zrušit</a>
                    @endif
                </form>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 overflow-x-auto">
                        @if ($invoices->isEmpty())
                            <p class="text-gray-500">{{ ($isFiltered ?? false) ? 'Žádné faktury neodpovídají zadanému filtru.' : 'Zatím nemáte žádné faktury.' }}</p>
                        @else
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead>
                                    <tr>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Číslo</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Klient</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">
                                            <a href="{{ $issueDateSortUrl }}" class="inline-flex items-center gap-1 hover:text-gray-700" title="Seřadit podle data vystavení">
                                                Vystaveno
                                                <span aria-hidden="true">{{ $direction === 'asc' ? '↑' : '↓' }}</span>
                                                <span class="sr-only">{{ $direction === 'asc' ? 'seřazeno vzestupně' : 'seřazeno sestupně' }}</span>
                                            </a>
                                        </th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Splatnost</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Částka</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Stav</th>
                                        <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">Akce</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    @foreach ($invoices as $invoice)
                                        <tr>
                                            <td class="px-3 py-2">
                                                <a href="{{ route('invoices.show', $invoice) }}" class="text-indigo-600 hover:underline">{{ $invoice->number }}</a>
                                            </td>
                                            <td class="px-3 py-2">{{ $invoice->client->name }}</td>
                                            <td class="px-3 py-2">{{ $invoice->issue_date->format('d.m.Y') }}</td>
                                            <td class="px-3 py-2">{{ $invoice->due_date->format('d.m.Y') }}</td>
                                            <td class="px-3 py-2">{{ number_format($invoice->total, 2, ',', ' ') }} Kč</td>
                                            <td class="px-3 py-2">{{ $invoice->statusLabel() }}</td>
                                            <td class="px-3 py-2 text-right space-x-2 whitespace-nowrap">
                                                <a href="{{ route('invoices.edit', $invoice) }}" class="text-indigo-600 hover:underline">Upravit</a>
                                                <x-confirm-delete
                                                    class="inline-block"
                                                    :action="route('invoices.destroy', $invoice)"
                                                    title="Smazat fakturu {{ $invoice->number }}?"
                                                    trigger-class="text-red-600 hover:underline"
                                                >
                                                    <x-slot:trigger>Smazat</x-slot:trigger>
                                                </x-confirm-delete>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            <div class="mt-4">{{ $invoices->links() }}</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('mobile.documents')
</x-app-layout>
