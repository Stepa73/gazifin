<x-mobile.invoice-shell title="{{ $invoice->number }}" :back-url="route('invoices.index')">
    <x-slot:headerRight>
        <a href="{{ route('invoices.edit', $invoice) }}" class="flex h-10 w-10 items-center justify-center rounded-full text-brand hover:bg-brand-light" aria-label="Upravit fakturu">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m16 4 4 4-12 12H4v-4L16 4Z"/>
            </svg>
        </a>
    </x-slot:headerRight>

    <x-flash-messages />

    <div class="mb-4 flex gap-2 overflow-x-auto pb-1">
        <a href="{{ route('invoices.pdf', $invoice) }}" class="inline-flex shrink-0 items-center rounded-full border border-gray-200 bg-white px-4 py-2 text-xs font-semibold text-gray-700">
            Stáhnout PDF
        </a>
        @if ($invoice->client->email)
            <form method="POST" action="{{ route('invoices.send', $invoice) }}">
                @csrf
                <button type="submit" class="inline-flex shrink-0 items-center rounded-full bg-brand px-4 py-2 text-xs font-semibold text-white">
                    Odeslat emailem
                </button>
            </form>
        @endif
        <span @class([
            'inline-flex shrink-0 items-center rounded-full px-4 py-2 text-xs font-semibold',
            'bg-orange-50 text-orange-600' => $invoice->status === 'draft',
            'bg-blue-50 text-blue-600' => $invoice->status === 'sent',
            'bg-green-50 text-green-600' => $invoice->status === 'paid',
        ])>
            {{ $invoice->mobileStatusLabel() }}
        </span>
    </div>

    <p class="mb-3 text-xs text-gray-500">Náhled odpovídá PDF — na telefonu je automaticky zmenšený, ale proporce zůstávají stejné.</p>

    <x-invoice-preview :invoice="$invoice" :user="$user" :client="$client" :qr-code="$qrCode" :iban="$iban" />
</x-mobile.invoice-shell>
