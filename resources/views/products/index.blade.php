@php
    $formatMoney = fn (float $amount): string => number_format($amount, 2, ',', ' ').' Kč';
@endphp

<x-app-layout title="CENÍK" active="products">
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Ceník</h2>
            <a href="{{ route('products.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500">
                Nová položka
            </a>
        </div>
    </x-slot>

    <x-slot name="mobileRight">
        <a href="{{ route('products.create') }}" class="flex h-10 w-10 items-center justify-center rounded-full text-brand hover:bg-brand-light" aria-label="Nová položka">
            <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v14M5 12h14"/>
            </svg>
        </a>
    </x-slot>

    <x-page>
        <x-panel :padded="false" class="overflow-hidden">
            <x-list grid="lg:grid-cols-[3fr_1fr_1fr_7rem]">
                @if ($products->isNotEmpty())
                    <x-list.head>
                        <div>Název</div>
                        <div>Jednotka</div>
                        <div>Cena</div>
                        <div class="text-right">Akce</div>
                    </x-list.head>
                @endif

                @forelse ($products as $product)
                    <x-list.row>
                        <div class="flex items-start justify-between gap-4 lg:contents">
                            <div class="min-w-0 lg:contents">
                                <div class="min-w-0">
                                    <a href="{{ route('products.edit', $product) }}" class="block truncate text-base font-semibold text-gray-900 after:absolute after:inset-0 lg:text-sm lg:font-medium">{{ $product->name }}</a>
                                </div>
                                <div class="mt-1 text-sm text-gray-500 lg:mt-0 lg:text-gray-900">
                                    @if ($product->unit)
                                        <span class="lg:hidden">za </span>{{ $product->unit }}
                                    @else
                                        <span class="hidden lg:inline">—</span>
                                    @endif
                                </div>
                            </div>

                            <div class="shrink-0 text-right lg:contents">
                                <div class="text-base font-bold text-gray-900 lg:text-left lg:text-sm lg:font-normal">{{ $formatMoney((float) $product->unit_price) }}</div>
                            </div>
                        </div>

                        <div class="relative z-10 hidden justify-end gap-2 whitespace-nowrap text-sm lg:flex">
                            <a href="{{ route('products.edit', $product) }}" class="text-indigo-600 hover:underline">Upravit</a>
                            <x-confirm-delete
                                class="inline-block"
                                :action="route('products.destroy', $product)"
                                title="Smazat položku {{ $product->name }}?"
                                trigger-class="text-red-600 hover:underline"
                            >
                                <x-slot:trigger>Smazat</x-slot:trigger>
                            </x-confirm-delete>
                        </div>
                    </x-list.row>
                @empty
                    <div class="px-4 py-10 text-center">
                        <p class="text-sm text-gray-500">Zatím nemáte žádné položky v ceníku.</p>
                        <a href="{{ route('products.create') }}" class="mt-4 inline-flex items-center rounded-full bg-brand px-5 py-2.5 text-sm font-semibold text-white">
                            Přidat položku
                        </a>
                    </div>
                @endforelse
            </x-list>

            @if ($products->hasPages())
                <div class="px-4 py-4">{{ $products->links() }}</div>
            @endif
        </x-panel>
    </x-page>
</x-app-layout>
