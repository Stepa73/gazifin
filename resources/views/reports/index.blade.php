@php
    $formatMoney = fn (float $amount): string => number_format($amount, 2, ',', ' ').' Kč';
@endphp

<x-app-layout title="REPORTY" :back-url="route('dashboard')" active="home">
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

    <x-slot name="mobileRight">
        <form method="GET" action="{{ route('reports') }}" class="shrink-0">
            <select name="year" onchange="this.form.submit()" class="rounded-lg border-gray-200 bg-gray-50 py-1.5 text-sm text-gray-900 focus:border-brand focus:ring-brand">
                @foreach ($availableYears as $availableYear)
                    <option value="{{ $availableYear }}" @selected($availableYear === $year)>{{ $availableYear }}</option>
                @endforeach
            </select>
        </form>
    </x-slot>

    <x-page class="lg:space-y-6">
        <div class="space-y-3 px-4 pt-4 lg:grid lg:grid-cols-3 lg:gap-4 lg:space-y-0 lg:px-0 lg:pt-0">
            <div class="rounded-2xl border border-gray-200 bg-gray-50/80 px-4 py-4 lg:rounded-lg lg:border-0 lg:bg-white lg:p-6 lg:shadow">
                <div class="text-[11px] font-semibold uppercase tracking-wide text-gray-500 lg:text-sm lg:normal-case lg:tracking-normal">Vyfakturováno celkem</div>
                <div class="mt-1 text-2xl font-bold text-gray-900 lg:text-3xl lg:font-semibold">{{ $formatMoney($summary['year_total']) }}</div>
            </div>
            <div class="grid grid-cols-2 gap-3 lg:contents">
                <div class="rounded-2xl border border-gray-200 bg-gray-50/80 px-4 py-4 lg:rounded-lg lg:border-0 lg:bg-white lg:p-6 lg:shadow">
                    <div class="text-[11px] font-semibold uppercase tracking-wide text-gray-500 lg:text-sm lg:normal-case lg:tracking-normal">Zaplaceno</div>
                    <div class="mt-1 text-lg font-bold text-green-600 lg:text-3xl lg:font-semibold">{{ $formatMoney($summary['paid_total']) }}</div>
                </div>
                <div class="rounded-2xl border border-gray-200 bg-gray-50/80 px-4 py-4 lg:rounded-lg lg:border-0 lg:bg-white lg:p-6 lg:shadow">
                    <div class="text-[11px] font-semibold uppercase tracking-wide text-gray-500 lg:text-sm lg:normal-case lg:tracking-normal">Počet faktur</div>
                    <div class="mt-1 text-lg font-bold text-gray-900 lg:text-3xl lg:font-semibold">{{ $summary['count'] }}</div>
                </div>
            </div>
        </div>

        <div class="px-4 pt-5 lg:px-0 lg:pt-0">
            <h2 class="mb-3 text-xs font-bold tracking-wide text-gray-500 lg:hidden">FAKTURACE PO MĚSÍCÍCH</h2>
            <div class="rounded-2xl border border-gray-200 bg-white p-4 lg:rounded-lg lg:border-0 lg:p-6 lg:shadow">
                <h3 class="mb-4 hidden text-lg font-medium text-gray-900 lg:block">Fakturace po měsících ({{ $year }})</h3>
                @include('reports._chart')
            </div>
        </div>
    </x-page>
</x-app-layout>
