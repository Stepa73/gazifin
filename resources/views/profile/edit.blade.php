@php
    $sections = [
        ['title' => 'PROFIL', 'partial' => 'profile.partials.update-profile-information-form'],
        ['title' => 'FIREMNÍ ÚDAJE', 'partial' => 'profile.partials.update-company-information-form'],
        ['title' => 'GMAIL', 'partial' => 'profile.partials.gmail-connection-form'],
        ['title' => 'HESLO', 'partial' => 'profile.partials.update-password-form'],
        ['title' => 'SMAZAT ÚČET', 'partial' => 'profile.partials.delete-user-form'],
    ];
@endphp

<x-app-layout title="VÍCE" active="more">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Profile') }}</h2>
    </x-slot>

    <x-page class="space-y-4 px-4 py-4 lg:space-y-6 lg:py-12">
        @foreach ($sections as $section)
            <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white lg:rounded-lg lg:border-0 lg:shadow">
                <div class="border-b border-gray-100 px-4 py-3 lg:px-8 lg:pt-6">
                    <h2 class="text-xs font-bold tracking-wide text-gray-500">{{ $section['title'] }}</h2>
                </div>
                <div class="p-4 lg:p-8">
                    <div class="max-w-xl">
                        @include($section['partial'])
                    </div>
                </div>
            </section>
        @endforeach

        {{-- Na desktopu se odhlašuje z rozbalovacího menu v navigaci. --}}
        <form method="POST" action="{{ route('logout') }}" class="lg:hidden">
            @csrf
            <button type="submit" class="w-full rounded-2xl border border-gray-200 bg-white px-4 py-4 text-left text-base font-semibold text-gray-900 active:bg-gray-50">
                Odhlásit se
            </button>
        </form>
    </x-page>
</x-app-layout>
