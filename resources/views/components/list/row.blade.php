@aware(['grid' => ''])

{{-- Řádek seznamu. Je to `div`, ne odkaz, aby v něm mohly být akční tlačítka —
     hlavní odkaz se přes `after:absolute after:inset-0` roztáhne přes celý řádek. --}}
<div {{ $attributes->merge(['class' => 'relative px-4 py-4 active:bg-gray-50 lg:grid lg:items-center lg:gap-3 lg:px-3 lg:py-3 lg:hover:bg-gray-50 '.$grid]) }}>
    {{ $slot }}
</div>
