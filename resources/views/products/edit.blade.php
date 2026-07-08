<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Upravit položku</h2>
    </x-slot>

    <div class="hidden lg:block">
        <div class="py-12">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white p-6 shadow sm:rounded-lg">
                    @include('products._form', ['action' => route('products.update', $product), 'method' => 'PUT'])
                </div>
            </div>
        </div>
    </div>

    <x-mobile.page-shell title="UPRAVIT POLOŽKU" :back-url="route('products.index')" active="products">
        @include('products._form', ['action' => route('products.update', $product), 'method' => 'PUT'])
    </x-mobile.page-shell>
</x-app-layout>
