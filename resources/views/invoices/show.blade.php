<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Faktura {{ $invoice->number }}</h2>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('invoices.pdf', $invoice) }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50">
                    Stáhnout PDF
                </a>
                @if ($invoice->client->email)
                    <form method="POST" action="{{ route('invoices.send', $invoice) }}">
                        @csrf
                        <x-primary-button>Odeslat emailem</x-primary-button>
                    </form>
                @endif
                <a href="{{ route('invoices.edit', $invoice) }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50">
                    Upravit
                </a>
            </div>
        </div>
    </x-slot>

    <div class="hidden lg:block">
        <div class="py-12">
            <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <x-flash-messages />
                <x-invoice-preview :invoice="$invoice" :user="$user" :client="$client" :qr-code="$qrCode" :iban="$iban" />
            </div>
        </div>
    </div>

    <div class="lg:hidden">
        @include('mobile.invoice-show')
    </div>
</x-app-layout>
