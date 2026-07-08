@php
    $formatMoney = fn (float $amount): string => number_format($amount, 2, ',', ' ').' Kč';
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Reporty {{ $year }}</h2>
            <form method="GET" action="{{ route('reports') }}">
                <select name="year" onchange="this.form.submit()" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                    @foreach ($availableYears as $availableYear)
                        <option value="{{ $availableYear }}" @selected($availableYear === $year)>{{ $availableYear }}</option>
                    @endforeach
                </select>
            </form>
        </div>
    </x-slot>

    {{-- Desktop --}}
    <div class="hidden lg:block">
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="bg-white p-6 shadow sm:rounded-lg">
                        <div class="text-sm text-gray-500">Vyfakturováno celkem</div>
                        <div class="text-3xl font-semibold text-gray-900">{{ $formatMoney($summary['year_total']) }}</div>
                    </div>
                    <div class="bg-white p-6 shadow sm:rounded-lg">
                        <div class="text-sm text-gray-500">Zaplaceno</div>
                        <div class="text-3xl font-semibold text-green-600">{{ $formatMoney($summary['paid_total']) }}</div>
                    </div>
                    <div class="bg-white p-6 shadow sm:rounded-lg">
                        <div class="text-sm text-gray-500">Počet faktur</div>
                        <div class="text-3xl font-semibold text-gray-900">{{ $summary['count'] }}</div>
                    </div>
                </div>

                <div class="bg-white p-6 shadow sm:rounded-lg">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Fakturace po měsících ({{ $year }})</h3>
                    @include('reports._chart')
                </div>
            </div>
        </div>
    </div>

    {{-- Mobile --}}
    <div class="lg:hidden">
        <x-mobile.layout active="home">
            <div class="pb-24">
                <header class="sticky top-0 z-40 border-b border-gray-100 bg-white px-4 py-3">
                    <div class="flex items-center justify-between gap-3">
                        <a href="{{ route('dashboard') }}" class="flex h-10 w-10 items-center justify-center rounded-full text-gray-600 hover:bg-gray-50" aria-label="Zpět">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 18 9 12l6-6"/>
                            </svg>
                        </a>
                        <h1 class="text-base font-bold tracking-wide text-gray-900">REPORTY</h1>
                        <form method="GET" action="{{ route('reports') }}" class="shrink-0">
                            <select name="year" onchange="this.form.submit()" class="rounded-lg border-gray-200 bg-gray-50 py-1.5 text-sm text-gray-900 focus:border-brand focus:ring-brand">
                                @foreach ($availableYears as $availableYear)
                                    <option value="{{ $availableYear }}" @selected($availableYear === $year)>{{ $availableYear }}</option>
                                @endforeach
                            </select>
                        </form>
                    </div>
                </header>

                <div class="space-y-3 px-4 pt-4">
                    <div class="rounded-2xl border border-gray-200 bg-gray-50/80 px-4 py-4">
                        <div class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">Vyfakturováno celkem</div>
                        <div class="mt-1 text-2xl font-bold text-gray-900">{{ $formatMoney($summary['year_total']) }}</div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="rounded-2xl border border-gray-200 bg-gray-50/80 px-4 py-4">
                            <div class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">Zaplaceno</div>
                            <div class="mt-1 text-lg font-bold text-green-600">{{ $formatMoney($summary['paid_total']) }}</div>
                        </div>
                        <div class="rounded-2xl border border-gray-200 bg-gray-50/80 px-4 py-4">
                            <div class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">Počet faktur</div>
                            <div class="mt-1 text-lg font-bold text-gray-900">{{ $summary['count'] }}</div>
                        </div>
                    </div>
                </div>

                <div class="px-4 pt-5">
                    <h2 class="mb-3 text-xs font-bold tracking-wide text-gray-500">FAKTURACE PO MĚSÍCÍCH</h2>
                    <div class="rounded-2xl border border-gray-200 bg-white p-4">
                        @include('reports._chart')
                    </div>
                </div>
            </div>
        </x-mobile.layout>
    </div>
</x-app-layout>
