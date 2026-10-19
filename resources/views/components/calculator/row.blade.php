@props(['label' => null, 'tone' => 'plain', 'sub' => false])

@php
    $tones = [
        'plain' => 'text-gray-900',
        'accent' => 'text-amber-600',
        'real' => 'text-blue-600',
        'good' => 'text-emerald-600',
    ];
@endphp

<div class="flex items-baseline justify-between gap-3 border-b border-gray-100 py-2 last:border-b-0">
    <span class="{{ $sub ? 'pl-3 text-xs text-gray-400' : 'text-sm text-gray-500' }}"
          @isset($attributes['x-label']) x-text="{{ $attributes['x-label'] }}" @endisset>{{ $label }}</span>
    <span class="whitespace-nowrap tabular-nums {{ $sub ? 'text-xs font-normal text-gray-400' : 'text-sm font-semibold '.($tones[$tone] ?? $tones['plain']) }}"
          @isset($attributes['x-value']) x-text="{{ $attributes['x-value'] }}" @endisset>{{ $slot }}</span>
</div>
