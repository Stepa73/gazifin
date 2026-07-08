@props(['active' => 'home'])

<nav class="fixed bottom-0 inset-x-0 z-50 border-t border-gray-200 bg-white pb-[env(safe-area-inset-bottom)]">
    <div class="grid grid-cols-5">
        <a
            href="{{ route('dashboard') }}"
            @class([
                'flex flex-col items-center justify-center gap-1 py-2 text-[11px] font-medium transition-colors',
                'text-brand' => $active === 'home',
                'text-gray-500' => $active !== 'home',
            ])
        >
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 10.5 12 3l9 7.5V20a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1v-9.5Z"/>
            </svg>
            Domov
        </a>

        <a
            href="{{ route('invoices.index') }}"
            @class([
                'flex flex-col items-center justify-center gap-1 py-2 text-[11px] font-medium transition-colors',
                'text-brand' => $active === 'documents',
                'text-gray-500' => $active !== 'documents',
            ])
        >
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 3h7l5 5v13a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1Z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M14 3v5h5"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 13h6M9 17h6"/>
            </svg>
            Dokumenty
        </a>

        <a
            href="{{ route('clients.index') }}"
            @class([
                'flex flex-col items-center justify-center gap-1 py-2 text-[11px] font-medium transition-colors',
                'text-brand' => $active === 'clients',
                'text-gray-500' => $active !== 'clients',
            ])
        >
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 11a4 4 0 1 0-8 0M4 20a8 8 0 0 1 16 0"/>
            </svg>
            Klienti
        </a>

        <a
            href="{{ route('products.index') }}"
            @class([
                'flex flex-col items-center justify-center gap-1 py-2 text-[11px] font-medium transition-colors',
                'text-brand' => $active === 'products',
                'text-gray-500' => $active !== 'products',
            ])
        >
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 7h5l9 9a2 2 0 0 1 0 3l-3 3a2 2 0 0 1-3 0L4 13V7Z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 11a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z"/>
            </svg>
            Ceník
        </a>

        <a
            href="{{ route('profile.edit') }}"
            @class([
                'flex flex-col items-center justify-center gap-1 py-2 text-[11px] font-medium transition-colors',
                'text-brand' => $active === 'more',
                'text-gray-500' => $active !== 'more',
            ])
        >
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 7h16M4 12h16M4 17h16"/>
            </svg>
            Více
        </a>
    </div>
</nav>
