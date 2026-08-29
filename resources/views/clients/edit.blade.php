<x-app-layout title="UPRAVIT KLIENTA" :back-url="route('clients.index')" active="clients">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Upravit klienta</h2>
    </x-slot>

    <x-page class="lg:max-w-3xl">
        <x-panel>
            @include('clients._form', ['action' => route('clients.update', $client), 'method' => 'PUT', 'client' => $client])
        </x-panel>
    </x-page>
</x-app-layout>
