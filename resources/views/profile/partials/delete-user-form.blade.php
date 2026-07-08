<section class="space-y-6">
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            Smazat účet
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            Po smazání účtu budou trvale odstraněna všechna jeho data. Než účet smažete, stáhněte si prosím veškeré údaje, které si chcete ponechat.
        </p>
    </header>

    <x-danger-button
        x-data=""
        x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
    >Smazat účet</x-danger-button>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="p-6">
            @csrf
            @method('delete')

            <h2 class="text-lg font-medium text-gray-900">
                Opravdu chcete smazat svůj účet?
            </h2>

            <p class="mt-1 text-sm text-gray-600">
                Po smazání účtu budou trvale odstraněna všechna jeho data. Pro potvrzení trvalého smazání účtu zadejte prosím své heslo.
            </p>

            <div class="mt-6">
                <x-input-label for="password" value="Heslo" class="sr-only" />

                <x-text-input
                    id="password"
                    name="password"
                    type="password"
                    class="mt-1 block w-3/4"
                    placeholder="Heslo"
                />

                <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-2" />
            </div>

            <div class="mt-6 flex justify-end">
                <x-secondary-button x-on:click="$dispatch('close')">
                    Zrušit
                </x-secondary-button>

                <x-danger-button class="ms-3">
                    Smazat účet
                </x-danger-button>
            </div>
        </form>
    </x-modal>
</section>
