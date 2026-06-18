<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Upravit fakturu {{ $invoice->number }}</h2>
    </x-slot>

    <div class="hidden lg:block">
        <div class="py-12">
            <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white p-6 shadow sm:rounded-lg">
                    @include('invoices._form', [
                        'action' => route('invoices.update', $invoice),
                        'method' => 'PUT',
                        'clients' => $clients,
                        'invoice' => $invoice,
                        'invoiceNumber' => $invoice->number,
                        'suggestedNumber' => $suggestedNumber,
                        'variableSymbol' => $invoice->variable_symbol ?? $invoice->effectiveVariableSymbol(),
                        'isVatPayer' => $isVatPayer,
                    ])
                </div>
            </div>
        </div>
    </div>

    <x-mobile.invoice-shell title="UPRAVIT FAKTURU" :back-url="route('invoices.show', $invoice)">
        @include('invoices._form', [
            'action' => route('invoices.update', $invoice),
            'method' => 'PUT',
            'clients' => $clients,
            'invoice' => $invoice,
            'invoiceNumber' => $invoice->number,
            'suggestedNumber' => $suggestedNumber,
            'variableSymbol' => $invoice->variable_symbol ?? $invoice->effectiveVariableSymbol(),
            'isVatPayer' => $isVatPayer,
        ])
    </x-mobile.invoice-shell>
</x-app-layout>
