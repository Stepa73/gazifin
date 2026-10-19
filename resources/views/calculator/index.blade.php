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
            <div class="mx-auto w-full max-w-7xl px-4 py-2 lg:px-8 lg:py-3">
                <div class="flex items-center justify-between gap-2">
                    <div class="flex min-w-0 items-center gap-2">
                        {{-- Přepínač nese celý zbytek stránky, takže je na mobilu roztažený přes volné místo. --}}
                        <div class="inline-flex rounded-xl bg-gray-100 p-0.5 lg:p-1" role="group" aria-label="Rok">
                            @foreach (['prev', 'base', 'next'] as $key)
                                <button type="button"
                                        @click="setYear({{ $years[$key] }})"
                                        :aria-pressed="year === {{ $years[$key] }}"
                                        :class="year === {{ $years[$key] }}
                                            ? 'bg-white text-brand shadow-sm'
                                            : 'text-gray-500 hover:text-gray-900'"
                                        class="rounded-lg px-3 py-1.5 text-sm font-semibold tabular-nums transition lg:px-4 lg:py-2">
                                    {{ $years[$key] }}
                                </button>
                            @endforeach
                        </div>
                        <span class="hidden text-xs text-gray-400 sm:inline"
                              x-text="year === {{ $years['base'] }} ? 'letošek' : (year > {{ $years['base'] }} ? 'prognóza' : 'loňsko')"></span>
                    </div>

                    <div class="flex shrink-0 items-center gap-2">
                        <span class="truncate text-[11px] text-gray-400 lg:text-xs" x-text="saveStatus"></span>
                        <span class="rounded-full bg-amber-100 px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-amber-700 lg:hidden">Beta</span>
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
