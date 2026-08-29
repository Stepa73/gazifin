@php
    $button = 'flex w-full items-center justify-center gap-2 rounded-xl py-3 text-sm font-semibold lg:w-auto lg:rounded-md lg:px-4 lg:py-2 lg:text-xs lg:uppercase lg:tracking-widest';
    $neutralButton = $button.' border border-gray-200 bg-white text-gray-700 active:bg-gray-50 lg:border-gray-300 lg:hover:bg-gray-50';
@endphp

<x-app-layout :title="$invoice->number" :back-url="route('invoices.index')" active="documents">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Faktura {{ $invoice->number }}</h2>
    </x-slot>

    <x-slot name="mobileRight">
        <a href="{{ route('invoices.edit', $invoice) }}" class="flex h-10 w-10 items-center justify-center rounded-full text-brand hover:bg-brand-light" aria-label="Upravit fakturu">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m16 4 4 4-12 12H4v-4L16 4Z"/>
            </svg>
        </a>
    </x-slot>

    <x-page class="lg:max-w-5xl lg:space-y-6">
        <div class="px-4 py-4 lg:px-0 lg:py-0">
            {{-- Stav faktury --}}
            <div class="mb-4 flex items-center justify-between lg:justify-start lg:gap-3">
                <span class="text-sm text-gray-500">
                    Stav faktury
                    @if ($invoice->status === 'paid' && $invoice->paid_at)
                        <span class="block text-xs text-gray-400 lg:inline lg:ml-1">· uhrazeno {{ $invoice->paid_at->format('d.m.Y') }}</span>
                    @endif
                </span>
                <span @class([
                    'inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold',
                    'bg-orange-50 text-orange-600' => $invoice->status === 'draft',
                    'bg-blue-50 text-blue-600' => $invoice->status === 'sent',
                    'bg-green-50 text-green-600' => $invoice->status === 'paid',
                ])>
                    {{ $invoice->mobileStatusLabel() }}
                </span>
            </div>

            {{-- Akce --}}
            <div class="flex flex-col gap-2 lg:flex-row lg:flex-wrap lg:items-center">
                @if ($invoice->status === 'paid')
                    <form method="POST" action="{{ route('invoices.mark-unpaid', $invoice) }}" class="lg:w-auto">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="{{ $neutralButton }}">
                            <svg class="h-5 w-5 lg:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 4v6h6M20 20v-6h-6"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 10a8 8 0 0 1 14-4M20 14a8 8 0 0 1-14 4"/>
                            </svg>
                            Zrušit úhradu
                        </button>
                    </form>
                @else
                    <form method="POST" action="{{ route('invoices.mark-paid', $invoice) }}" class="lg:w-auto">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="{{ $button }} bg-green-600 text-white active:bg-green-700 lg:hover:bg-green-500">
                            <svg class="h-5 w-5 lg:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M5 13l4 4L19 7"/>
                            </svg>
                            Označit jako zaplacenou
                        </button>
                    </form>
                @endif

                @if ($invoice->client->email)
                    <form method="POST" action="{{ route('invoices.send', $invoice) }}" class="lg:w-auto">
                        @csrf
                        <button type="submit" class="{{ $button }} bg-brand text-white active:opacity-90 lg:hover:bg-brand-dark">
                            <svg class="h-5 w-5 lg:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 6h16v12H4z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m4 7 8 6 8-6"/>
                            </svg>
                            Odeslat emailem
                        </button>
                    </form>
                @endif

                <a href="{{ route('invoices.pdf', $invoice) }}" class="{{ $neutralButton }}">
                    <svg class="h-5 w-5 lg:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 3v12m0 0-4-4m4 4 4-4M5 21h14"/>
                    </svg>
                    Stáhnout PDF
                </a>

                <a href="{{ route('invoices.edit', $invoice) }}" class="{{ $neutralButton }} hidden lg:flex">
                    Upravit
                </a>

                <x-confirm-delete
                    class="w-full lg:w-auto"
                    :action="route('invoices.destroy', $invoice)"
                    title="Smazat fakturu {{ $invoice->number }}?"
                    trigger-class="{{ $button }} border border-red-200 bg-white text-red-600 active:bg-red-50 lg:border-red-300 lg:hover:bg-red-50"
                >
                    <x-slot:trigger>
                        <svg class="h-5 w-5 lg:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 7h16M10 11v6M14 11v6M6 7l1 12a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2l1-12M9 7V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v3"/>
                        </svg>
                        Smazat fakturu
                    </x-slot:trigger>
                </x-confirm-delete>
            </div>

            <p class="mb-3 mt-5 text-xs text-gray-500 lg:hidden">Náhled odpovídá PDF — na telefonu je automaticky zmenšený, ale proporce zůstávají stejné.</p>
        </div>

        <div class="px-4 pb-4 lg:px-0 lg:pb-0">
            <x-invoice-preview :invoice="$invoice" :user="$user" :client="$client" :qr-code="$qrCode" :iban="$iban" />
        </div>
    </x-page>
</x-app-layout>
