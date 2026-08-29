<x-app-layout title="KLIENTI" active="clients">
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Klienti</h2>
            <a href="{{ route('clients.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500">
                Nový klient
            </a>
        </div>
    </x-slot>

    <x-slot name="mobileRight">
        <a href="{{ route('clients.create') }}" class="flex h-10 w-10 items-center justify-center rounded-full text-brand hover:bg-brand-light" aria-label="Nový klient">
            <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v14M5 12h14"/>
            </svg>
        </a>
    </x-slot>

    <x-page>
        <x-panel :padded="false" class="overflow-hidden">
            <x-list grid="lg:grid-cols-[2fr_2fr_1fr_7rem]">
                @if ($clients->isNotEmpty())
                    <x-list.head>
                        <div>Název</div>
                        <div>E-mail</div>
                        <div>IČO</div>
                        <div class="text-right">Akce</div>
                    </x-list.head>
                @endif

                @forelse ($clients as $client)
                    <x-list.row>
                        <div class="flex items-start justify-between gap-4 lg:contents">
                            <div class="min-w-0 lg:contents">
                                <div class="min-w-0">
                                    <a href="{{ route('clients.edit', $client) }}" class="block truncate text-base font-semibold text-gray-900 after:absolute after:inset-0 lg:text-sm lg:font-medium">{{ $client->name }}</a>
                                </div>
                                <div class="mt-1 truncate text-sm text-gray-500 lg:mt-0 lg:text-gray-900">{{ $client->email }}</div>
                            </div>

                            <div class="shrink-0 text-right lg:contents">
                                <div class="text-sm font-medium text-gray-700 lg:text-left lg:font-normal lg:text-gray-900">
                                    @if ($client->ico)
                                        <span class="lg:hidden">IČO </span>{{ $client->ico }}
                                    @endif
                                </div>
                                @if ($client->phone)
                                    <div class="mt-1 text-sm text-gray-500 lg:hidden">{{ $client->phone }}</div>
                                @endif
                            </div>
                        </div>

                        <div class="relative z-10 hidden justify-end gap-2 whitespace-nowrap text-sm lg:flex">
                            <a href="{{ route('clients.edit', $client) }}" class="text-indigo-600 hover:underline">Upravit</a>
                            <x-confirm-delete
                                class="inline-block"
                                :action="route('clients.destroy', $client)"
                                title="Smazat klienta {{ $client->name }}?"
                                trigger-class="text-red-600 hover:underline"
                            >
                                <x-slot:trigger>Smazat</x-slot:trigger>
                            </x-confirm-delete>
                        </div>
                    </x-list.row>
                @empty
                    <div class="px-4 py-10 text-center">
                        <p class="text-sm text-gray-500">Zatím nemáte žádné klienty.</p>
                        <a href="{{ route('clients.create') }}" class="mt-4 inline-flex items-center rounded-full bg-brand px-5 py-2.5 text-sm font-semibold text-white">
                            Přidat klienta
                        </a>
                    </div>
                @endforelse
            </x-list>

            @if ($clients->hasPages())
                <div class="px-4 py-4">{{ $clients->links() }}</div>
            @endif
        </x-panel>
    </x-page>
</x-app-layout>
