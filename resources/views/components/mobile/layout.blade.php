@props(['active' => 'home'])

<div {{ $attributes->merge(['class' => 'min-h-screen bg-white text-gray-900 lg:hidden']) }}>
    {{ $slot }}

    <x-mobile.bottom-nav :active="$active" />
</div>
