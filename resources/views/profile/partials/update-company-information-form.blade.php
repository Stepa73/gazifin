<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">Firemní údaje</h2>
        <p class="mt-1 text-sm text-gray-600">Údaje dodavatele zobrazené na faktuře.</p>
    </header>

    <form method="post" action="{{ route('profile.company.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="company_name" value="Název firmy" />
            <x-text-input id="company_name" name="company_name" type="text" class="mt-1 block w-full" :value="old('company_name', $user->company_name)" />
            <x-input-error class="mt-2" :messages="$errors->get('company_name')" />
        </div>

        <div>
            <x-input-label for="address" value="Adresa" />
            <textarea id="address" name="address" rows="3" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('address', $user->address) }}</textarea>
            <x-input-error class="mt-2" :messages="$errors->get('address')" />
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <x-input-label for="ico" value="IČO" />
                <x-text-input id="ico" name="ico" type="text" class="mt-1 block w-full" :value="old('ico', $user->ico)" />
                <x-input-error class="mt-2" :messages="$errors->get('ico')" />
            </div>
            <div>
                <x-input-label for="dic" value="DIČ" />
                <x-text-input id="dic" name="dic" type="text" class="mt-1 block w-full" :value="old('dic', $user->dic)" />
                <x-input-error class="mt-2" :messages="$errors->get('dic')" />
            </div>
        </div>

        <div>
            <x-input-label for="bank_account" value="Číslo účtu" />
            <x-text-input id="bank_account" name="bank_account" type="text" class="mt-1 block w-full" placeholder="123456789/0100" :value="old('bank_account', $user->bank_account)" />
            <x-input-error class="mt-2" :messages="$errors->get('bank_account')" />
        </div>

        <div>
            <label class="inline-flex items-center">
                <input type="hidden" name="is_vat_payer" value="0">
                <input type="checkbox" name="is_vat_payer" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" @checked(old('is_vat_payer', $user->is_vat_payer))>
                <span class="ms-2 text-sm text-gray-600">Jsem plátce DPH</span>
            </label>
            <x-input-error class="mt-2" :messages="$errors->get('is_vat_payer')" />
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>Uložit firemní údaje</x-primary-button>
        </div>
    </form>
</section>
