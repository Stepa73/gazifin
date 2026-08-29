<x-app-layout title="NOVÁ FAKTURA" :back-url="route('invoices.index')" active="documents">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Nová faktura</h2>
    </x-slot>

    <x-page class="lg:max-w-5xl">
        <x-panel>
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
        </x-panel>
    </x-page>
</x-app-layout>
