@props(['grid' => ''])

{{-- Seznam záznamů: na mobilu karty pod sebou, na desktopu mřížka ve stylu tabulky.
     Prop `grid` si přes @aware přebírají x-list.head i x-list.row, aby sloupce seděly. --}}
<div {{ $attributes->merge(['class' => 'divide-y divide-gray-100 border-t border-gray-100 lg:divide-gray-200 lg:border-t-0']) }}>
    {{ $slot }}
</div>
