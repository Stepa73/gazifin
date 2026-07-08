<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Ceník</h2>
            <a href="{{ route('products.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500">
                Nová položka
            </a>
        </div>
    </x-slot>

    <div class="hidden lg:block">
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <x-flash-messages />

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 overflow-x-auto">
                        @if ($products->isEmpty())
                            <p class="text-gray-500">Zatím nemáte žádné položky v ceníku.</p>
                        @else
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead>
                                    <tr>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Název</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Jednotka</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Cena</th>
                                        <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">Akce</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    @foreach ($products as $product)
                                        <tr>
                                            <td class="px-3 py-2">{{ $product->name }}</td>
                                            <td class="px-3 py-2">{{ $product->unit ?: '—' }}</td>
                                            <td class="px-3 py-2">{{ number_format($product->unit_price, 2, ',', ' ') }} Kč</td>
                                            <td class="px-3 py-2 text-right space-x-2 whitespace-nowrap">
                                                <a href="{{ route('products.edit', $product) }}" class="text-indigo-600 hover:underline">Upravit</a>
                                                <form action="{{ route('products.destroy', $product) }}" method="POST" class="inline" onsubmit="return confirm('Opravdu smazat položku?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-red-600 hover:underline">Smazat</button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            <div class="mt-4">{{ $products->links() }}</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('mobile.products')
</x-app-layout>
