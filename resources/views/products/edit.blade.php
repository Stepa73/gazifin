<x-app-layout title="UPRAVIT POLOŽKU" :back-url="route('products.index')" active="products">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Upravit položku</h2>
    </x-slot>

    <x-page class="lg:max-w-3xl">
        <x-panel>
            @include('products._form', ['action' => route('products.update', $product), 'method' => 'PUT', 'product' => $product])
        </x-panel>
    </x-page>
</x-app-layout>
