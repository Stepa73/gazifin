{{-- Obsahový kontejner stránky: na mobilu na celou šířku, na desktopu vycentrovaný s odsazením. --}}
<div {{ $attributes->merge(['class' => 'mx-auto w-full max-w-7xl lg:px-8 lg:py-12']) }}>
    {{ $slot }}
</div>
