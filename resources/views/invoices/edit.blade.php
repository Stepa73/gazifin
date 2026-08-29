<x-app-layout title="UPRAVIT FAKTURU" :back-url="route('invoices.show', $invoice)" active="documents">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Upravit fakturu {{ $invoice->number }}</h2>
    </x-slot>

    <x-page class="lg:max-w-5xl">
        <x-panel>
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
        </x-panel>
    </x-page>
</x-app-layout>
