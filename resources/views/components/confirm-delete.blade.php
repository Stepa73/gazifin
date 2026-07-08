@props([
    'action',
    'method' => 'DELETE',
    'title' => 'Smazat fakturu?',
    'message' => 'Opravdu chcete tuto fakturu trvale smazat? Tuto akci nelze vzít zpět.',
    'confirmLabel' => 'Smazat',
    'triggerClass' => '',
])

<div x-data="{ open: false }" {{ $attributes }}>
    <button type="button" @click="open = true" class="{{ $triggerClass }}">
        {{ $trigger }}
    </button>

    <template x-teleport="body">
        <div
            x-show="open"
            x-cloak
            @keydown.escape.window="open = false"
            class="fixed inset-0 z-[60] flex items-center justify-center px-4"
            role="dialog"
            aria-modal="true"
        >
            {{-- Overlay --}}
            <div
                x-show="open"
                x-transition:enter="ease-out duration-200"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in duration-150"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                @click="open = false"
                class="absolute inset-0 bg-gray-900/60"
            ></div>

            {{-- Dialog --}}
            <div
                x-show="open"
                x-transition:enter="ease-out duration-200"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="ease-in duration-150"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                class="relative w-full max-w-sm rounded-2xl bg-white p-6 shadow-xl"
            >
                <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-red-100">
                    <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 7h16M10 11v6M14 11v6M6 7l1 12a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2l1-12M9 7V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v3"/>
                    </svg>
                </div>

                <h3 class="text-center text-lg font-semibold text-gray-900">{{ $title }}</h3>
                <p class="mt-2 text-center text-sm text-gray-500">{{ $message }}</p>

                <div class="mt-6 flex gap-3">
                    <button type="button" @click="open = false" class="flex-1 rounded-xl border border-gray-200 bg-white py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                        Zrušit
                    </button>
                    <form method="POST" action="{{ $action }}" class="flex-1">
                        @csrf
                        @method($method)
                        <button type="submit" class="w-full rounded-xl bg-red-600 py-2.5 text-sm font-semibold text-white hover:bg-red-500">
                            {{ $confirmLabel }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </template>
</div>
