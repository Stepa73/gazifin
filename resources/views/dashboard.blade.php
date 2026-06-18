<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Dashboard</h2>
            <div class="flex gap-2">
                <a href="{{ route('invoices.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500">
                    Nová faktura
                </a>
                <a href="{{ route('clients.create') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50">
                    Přidat klienta
                </a>
            </div>
        </div>
    </x-slot>

    <div class="hidden lg:block">
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <x-flash-messages />

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="bg-white p-6 shadow sm:rounded-lg">
                        <div class="text-sm text-gray-500">Faktury celkem</div>
                        <div class="text-3xl font-semibold text-gray-900">{{ $stats['invoices_total'] }}</div>
                    </div>
                    <div class="bg-white p-6 shadow sm:rounded-lg">
                        <div class="text-sm text-gray-500">Nezaplacené</div>
                        <div class="text-3xl font-semibold text-gray-900">{{ $stats['invoices_unpaid'] }}</div>
                    </div>
                    <div class="bg-white p-6 shadow sm:rounded-lg">
                        <div class="text-sm text-gray-500">Klienti</div>
                        <div class="text-3xl font-semibold text-gray-900">{{ $stats['clients_total'] }}</div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Poslední faktury</h3>
                        @if ($invoices->isEmpty())
                            <p class="text-gray-500">Zatím nemáte žádné faktury.</p>
                        @else
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead>
                                        <tr>
                                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Číslo</th>
                                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Klient</th>
                                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Datum</th>
                                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Částka</th>
                                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Stav</th>
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
                                                <td class="px-3 py-2">{{ number_format($invoice->total, 2, ',', ' ') }} Kč</td>
                                                <td class="px-3 py-2">{{ $invoice->statusLabel() }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('mobile.dashboard')
</x-app-layout>
