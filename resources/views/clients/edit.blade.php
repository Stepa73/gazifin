<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Upravit klienta</h2>
    </x-slot>

    <div class="hidden lg:block">
        <div class="py-12">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white p-6 shadow sm:rounded-lg">
                    @include('clients._form', ['action' => route('clients.update', $client), 'method' => 'PUT', 'client' => $client])
                </div>
            </div>
        </div>
    </div>

    <x-mobile.page-shell title="UPRAVIT KLIENTA" :back-url="route('clients.index')" active="clients">
        @include('clients._form', ['action' => route('clients.update', $client), 'method' => 'PUT', 'client' => $client])
    </x-mobile.page-shell>
</x-app-layout>
