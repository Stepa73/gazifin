<x-mobile.layout active="more">
    <div class="pb-24">
        <header class="sticky top-0 z-40 border-b border-gray-100 bg-white px-4 py-3">
            <div class="flex items-center justify-center">
                <h1 class="text-base font-bold tracking-wide text-gray-900">VÍCE</h1>
            </div>
        </header>

        <x-flash-messages />

        <div class="space-y-4 px-4 py-4">
            <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white">
                <div class="border-b border-gray-100 px-4 py-3">
                    <h2 class="text-xs font-bold tracking-wide text-gray-500">PROFIL</h2>
                </div>
                <div class="p-4 sm:p-6">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </section>

            <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white">
                <div class="border-b border-gray-100 px-4 py-3">
                    <h2 class="text-xs font-bold tracking-wide text-gray-500">FIREMNÍ ÚDAJE</h2>
                </div>
                <div class="p-4 sm:p-6">
                    @include('profile.partials.update-company-information-form')
                </div>
            </section>

            <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white">
                <div class="border-b border-gray-100 px-4 py-3">
                    <h2 class="text-xs font-bold tracking-wide text-gray-500">GMAIL</h2>
                </div>
                <div class="p-4 sm:p-6">
                    @include('profile.partials.gmail-connection-form')
                </div>
            </section>

            <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white">
                <div class="border-b border-gray-100 px-4 py-3">
                    <h2 class="text-xs font-bold tracking-wide text-gray-500">HESLO</h2>
                </div>
                <div class="p-4 sm:p-6">
                    @include('profile.partials.update-password-form')
                </div>
            </section>

            <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white">
                <div class="border-b border-gray-100 px-4 py-3">
                    <h2 class="text-xs font-bold tracking-wide text-gray-500">SMAZAT ÚČET</h2>
                </div>
                <div class="p-4 sm:p-6">
                    @include('profile.partials.delete-user-form')
                </div>
            </section>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="w-full rounded-2xl border border-gray-200 bg-white px-4 py-4 text-left text-base font-semibold text-gray-900 active:bg-gray-50">
                    Odhlásit se
                </button>
            </form>
        </div>
    </div>
</x-mobile.layout>
