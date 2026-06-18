<x-mobile.layout active="clients">
    <div class="pb-24">
        <header class="sticky top-0 z-40 border-b border-gray-100 bg-white px-4 py-3">
            <div class="flex items-center justify-between gap-3">
                <div class="h-10 w-10" aria-hidden="true"></div>

                <h1 class="text-base font-bold tracking-wide text-gray-900">KLIENTI</h1>

                <a href="{{ route('clients.create') }}" class="flex h-10 w-10 items-center justify-center rounded-full text-brand hover:bg-brand-light" aria-label="Nový klient">
                    <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v14M5 12h14"/>
                    </svg>
                </a>
            </div>
        </header>

        <x-flash-messages />

        <div class="divide-y divide-gray-100 border-t border-gray-100">
            @forelse ($clients as $client)
                <a href="{{ route('clients.edit', $client) }}" class="block px-4 py-4 active:bg-gray-50">
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0">
                            <div class="truncate text-base font-semibold text-gray-900">{{ $client->name }}</div>
                            @if ($client->email)
                                <div class="mt-1 truncate text-sm text-gray-500">{{ $client->email }}</div>
                            @endif
                        </div>
                        <div class="shrink-0 text-right">
                            @if ($client->ico)
                                <div class="text-sm font-medium text-gray-700">IČO {{ $client->ico }}</div>
                            @endif
                            @if ($client->phone)
                                <div class="mt-1 text-sm text-gray-500">{{ $client->phone }}</div>
                            @endif
                        </div>
                    </div>
                </a>
            @empty
                <div class="px-4 py-10 text-center">
                    <p class="text-sm text-gray-500">Zatím nemáte žádné klienty.</p>
                    <a href="{{ route('clients.create') }}" class="mt-4 inline-flex items-center rounded-full bg-brand px-5 py-2.5 text-sm font-semibold text-white">
                        Přidat klienta
                    </a>
                </div>
            @endforelse
        </div>

        @if ($clients->hasPages())
            <div class="px-4 py-4">
                {{ $clients->links() }}
            </div>
        @endif
    </div>
</x-mobile.layout>
