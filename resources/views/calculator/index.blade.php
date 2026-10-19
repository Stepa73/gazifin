@php
    $boot = [
        'clients' => $clients,
        'actuals' => $actuals,
        'state' => $state,
        'years' => $years,
        'saveUrl' => route('calculator.update'),
        'csrf' => csrf_token(),
    ];
@endphp

<x-app-layout title="KALKULAČKA" :back-url="route('dashboard')" active="home">
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Kalkulačka příjmů</h2>
            <span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold uppercase tracking-wide text-amber-700">Beta</span>
        </div>
    </x-slot>

    <div x-data="incomeCalculator(@js($boot))" x-cloak>
        {{-- Přepínač roku: první věc na stránce a drží se nahoře, protože mění úplně všechno pod sebou. --}}
        <div class="sticky top-[57px] z-30 border-b border-gray-200 bg-white/95 backdrop-blur lg:top-0">
            <div class="mx-auto w-full max-w-7xl px-4 py-3 lg:px-8">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-2">
                        <div class="inline-flex rounded-xl bg-gray-100 p-1" role="group" aria-label="Rok">
                            @foreach (['prev', 'base', 'next'] as $key)
                                <button type="button"
                                        @click="setYear({{ $years[$key] }})"
                                        :aria-pressed="year === {{ $years[$key] }}"
                                        :class="year === {{ $years[$key] }}
                                            ? 'bg-white text-brand shadow-sm'
                                            : 'text-gray-500 hover:text-gray-900'"
                                        class="rounded-lg px-4 py-2 text-sm font-semibold tabular-nums transition">
                                    {{ $years[$key] }}
                                </button>
                            @endforeach
                        </div>
                        <span class="hidden text-xs text-gray-400 sm:inline"
                              x-text="year === {{ $years['base'] }} ? 'letošek' : (year > {{ $years['base'] }} ? 'prognóza' : 'loňsko')"></span>
                    </div>

                    <div class="flex items-center gap-3 text-xs">
                        <span class="text-gray-400" x-text="saveStatus"></span>
                        <span class="rounded-full bg-amber-100 px-2 py-0.5 font-semibold uppercase tracking-wide text-amber-700 lg:hidden">Beta</span>
                    </div>
                </div>
            </div>
        </div>

        <x-page class="space-y-4 px-4 py-4 lg:space-y-6 lg:py-8">
            @include('calculator._summary')
            @include('calculator._chart')
            @include('calculator._calendar')
            @include('calculator._sources')
            @include('calculator._result')
            @include('calculator._settings')
            @include('calculator._notes')
        </x-page>
    </div>
</x-app-layout>
