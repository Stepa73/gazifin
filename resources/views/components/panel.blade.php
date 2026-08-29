@props(['padded' => true])

{{-- Na mobilu splývá se stránkou, na desktopu je to bílá karta. --}}
<div {{ $attributes->merge(['class' => 'bg-white lg:rounded-lg lg:shadow-sm'.($padded ? ' px-4 py-4 lg:p-6' : '')]) }}>
    {{ $slot }}
</div>
