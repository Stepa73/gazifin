<x-app-layout title="NOVÁ POLOŽKA" :back-url="route('products.index')" active="products">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Nová položka</h2>
    </x-slot>

    <x-page class="lg:max-w-3xl">
        <x-panel>
            @include('products._form', ['action' => route('products.store'), 'method' => 'POST'])
        </x-panel>
    </x-page>
</x-app-layout>
