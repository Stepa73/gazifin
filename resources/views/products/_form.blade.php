@php($product = $product ?? null)

<form method="POST" action="{{ $action }}" class="space-y-4">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div>
        <x-input-label for="name" value="Název položky" />
        <x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name', $product?->name)" required />
        <x-input-error :messages="$errors->get('name')" class="mt-2" />
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <x-input-label for="unit_price" value="Cena za jednotku (Kč)" />
            <x-text-input id="unit_price" name="unit_price" type="number" step="0.01" min="0" class="mt-1 block w-full" :value="old('unit_price', $product?->unit_price)" required />
            <x-input-error :messages="$errors->get('unit_price')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="unit" value="Jednotka (nepovinné)" />
            <x-text-input id="unit" name="unit" class="mt-1 block w-full" :value="old('unit', $product?->unit)" placeholder="ks, hod, kg…" />
            <x-input-error :messages="$errors->get('unit')" class="mt-2" />
        </div>
    </div>

    <div class="flex flex-col gap-2 pt-2 sm:flex-row sm:justify-end">
        <a href="{{ route('products.index') }}" class="inline-flex w-full items-center justify-center px-4 py-2.5 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50 sm:w-auto sm:py-2">Zrušit</a>
        <x-primary-button class="w-full justify-center py-2.5 sm:w-auto sm:py-2">Uložit</x-primary-button>
    </div>
</form>
