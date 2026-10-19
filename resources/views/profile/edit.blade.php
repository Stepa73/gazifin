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
        <a href="{{ route('calculator.index') }}" class="flex items-center justify-between gap-3 rounded-2xl border border-gray-200 bg-white px-4 py-4 active:bg-gray-50 lg:rounded-lg lg:border-0 lg:px-8 lg:shadow">
            <span class="flex items-center gap-2 text-base font-semibold text-gray-900">
                Kalkulačka příjmů
                <span class="rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-amber-700">Beta</span>
            </span>
            <svg class="h-5 w-5 shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m9 6 6 6-6 6"/>
            </svg>
        </a>

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
