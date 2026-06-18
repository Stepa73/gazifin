<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">Gmail propojení</h2>
        <p class="mt-1 text-sm text-gray-600">Pro odesílání faktur e-mailem propojte svůj Google účet.</p>
    </header>

    <div class="mt-6">
        @if ($user->hasGmailConnected())
            <p class="text-sm text-green-700">Gmail propojen: {{ $user->email }}</p>
        @else
            <a href="{{ route('auth.google.connect') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50">
                Propojit Gmail
            </a>
        @endif

        @if (session('status') === 'gmail-connected')
            <p class="mt-2 text-sm text-green-600">Gmail byl úspěšně propojen.</p>
        @endif
    </div>
</section>
