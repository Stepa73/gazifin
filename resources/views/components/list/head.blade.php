@aware(['grid' => ''])

{{-- Hlavička sloupců — jen na desktopu, na mobilu ji karty nepotřebují. --}}
<div {{ $attributes->merge(['class' => 'hidden gap-3 border-b border-gray-200 px-3 py-2 text-xs font-medium uppercase tracking-wide text-gray-500 lg:grid '.$grid]) }}>
    {{ $slot }}
</div>
