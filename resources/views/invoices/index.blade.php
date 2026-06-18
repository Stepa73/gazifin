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

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 overflow-x-auto">
                        @if ($invoices->isEmpty())
                            <p class="text-gray-500">Zatím nemáte žádné faktury.</p>
                        @else
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead>
                                    <tr>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Číslo</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Klient</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Vystaveno</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Splatnost</th>
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
                                            <td class="px-3 py-2">{{ $invoice->due_date->format('d.m.Y') }}</td>
                                            <td class="px-3 py-2">{{ number_format($invoice->total, 2, ',', ' ') }} Kč</td>
                                            <td class="px-3 py-2">{{ $invoice->statusLabel() }}</td>
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
