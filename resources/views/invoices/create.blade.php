<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Nová faktura</h2>
    </x-slot>

    <div class="hidden lg:block">
        <div class="py-12">
            <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white p-6 shadow sm:rounded-lg">
                    @include('invoices._form', [
                        'action' => route('invoices.store'),
                        'method' => 'POST',
                        'clients' => $clients,
                        'invoiceNumber' => $invoiceNumber,
                        'suggestedNumber' => $suggestedNumber,
                        'variableSymbol' => $variableSymbol,
                        'issueDate' => $issueDate,
                        'defaultDueDate' => $defaultDueDate,
                        'isVatPayer' => $isVatPayer,
                    ])
                </div>
            </div>
        </div>
    </div>

    <x-mobile.invoice-shell title="NOVÁ FAKTURA" :back-url="route('invoices.index')">
        @include('invoices._form', [
            'action' => route('invoices.store'),
            'method' => 'POST',
            'clients' => $clients,
            'invoiceNumber' => $invoiceNumber,
            'suggestedNumber' => $suggestedNumber,
            'variableSymbol' => $variableSymbol,
            'issueDate' => $issueDate,
            'defaultDueDate' => $defaultDueDate,
            'isVatPayer' => $isVatPayer,
        ])
    </x-mobile.invoice-shell>
</x-app-layout>
