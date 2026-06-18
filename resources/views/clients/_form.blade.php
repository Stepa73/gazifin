@php($client = $client ?? null)

<form
    method="POST"
    action="{{ $action }}"
    class="space-y-4"
    x-data="{
        loading: false,
        error: null,
        async fetchFromAres() {
            const ico = this.$refs.icoInput.value.trim();
            this.error = null;

            if (! ico) {
                this.error = 'Nejdřív vyplňte IČO.';
                return;
            }

            this.loading = true;

            try {
                const response = await fetch('{{ route('clients.lookup-ico') }}?ico=' + encodeURIComponent(ico), {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                const payload = await response.json();

                if (! response.ok) {
                    this.error = payload.message ?? 'Nepodařilo se načíst údaje z ARES.';
                    return;
                }

                this.$refs.nameInput.value = payload.name ?? '';
                this.$refs.addressInput.value = payload.address ?? '';
                this.$refs.icoInput.value = payload.ico ?? ico;
                this.$refs.dicInput.value = payload.dic ?? '';
            } catch (error) {
                this.error = 'Nepodařilo se načíst údaje z ARES.';
            } finally {
                this.loading = false;
            }
        },
    }"
>
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="md:col-span-2">
            <x-input-label for="ico" value="IČO" />
            <div class="mt-1 flex gap-2">
                <x-text-input
                    id="ico"
                    name="ico"
                    class="block w-full"
                    x-ref="icoInput"
                    :value="old('ico', $client?->ico)"
                />
                <button
                    type="button"
                    class="inline-flex shrink-0 items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50 disabled:opacity-50"
                    @click="fetchFromAres()"
                    :disabled="loading"
                >
                    <span x-show="! loading">Načíst z ARES</span>
                    <span x-show="loading" x-cloak>Načítám…</span>
                </button>
            </div>
            <p x-show="error" x-text="error" x-cloak class="mt-2 text-sm text-red-600"></p>
            <x-input-error :messages="$errors->get('ico')" class="mt-2" />
        </div>
    </div>

    <div>
        <x-input-label for="name" value="Název" />
        <x-text-input id="name" name="name" class="mt-1 block w-full" x-ref="nameInput" :value="old('name', $client?->name)" required />
        <x-input-error :messages="$errors->get('name')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="email" value="E-mail" />
        <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $client?->email)" />
        <x-input-error :messages="$errors->get('email')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="phone" value="Telefon" />
        <x-text-input id="phone" name="phone" class="mt-1 block w-full" :value="old('phone', $client?->phone)" />
        <x-input-error :messages="$errors->get('phone')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="address" value="Adresa" />
        <textarea id="address" name="address" rows="3" x-ref="addressInput" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('address', $client?->address) }}</textarea>
        <x-input-error :messages="$errors->get('address')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="dic" value="DIČ" />
        <x-text-input id="dic" name="dic" class="mt-1 block w-full" x-ref="dicInput" :value="old('dic', $client?->dic)" />
        <x-input-error :messages="$errors->get('dic')" class="mt-2" />
    </div>

    <div class="flex justify-end gap-2">
        <a href="{{ route('clients.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50">Zrušit</a>
        <x-primary-button>Uložit</x-primary-button>
    </div>
</form>
