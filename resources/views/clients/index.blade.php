<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Klienti</h2>
            <a href="{{ route('clients.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500">
                Nový klient
            </a>
        </div>
    </x-slot>

    <div class="hidden lg:block">
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <x-flash-messages />

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 overflow-x-auto">
                        @if ($clients->isEmpty())
                            <p class="text-gray-500">Zatím nemáte žádné klienty.</p>
                        @else
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead>
                                    <tr>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Název</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">E-mail</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">IČO</th>
                                        <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">Akce</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    @foreach ($clients as $client)
                                        <tr>
                                            <td class="px-3 py-2">{{ $client->name }}</td>
                                            <td class="px-3 py-2">{{ $client->email }}</td>
                                            <td class="px-3 py-2">{{ $client->ico }}</td>
                                            <td class="px-3 py-2 text-right space-x-2">
                                                <a href="{{ route('clients.edit', $client) }}" class="text-indigo-600 hover:underline">Upravit</a>
                                                <form action="{{ route('clients.destroy', $client) }}" method="POST" class="inline" onsubmit="return confirm('Opravdu smazat klienta?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-red-600 hover:underline">Smazat</button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            <div class="mt-4">{{ $clients->links() }}</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('mobile.clients')
</x-app-layout>
