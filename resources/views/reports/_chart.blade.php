@php
    $formatShort = function (float $amount): string {
        if ($amount >= 1000000) {
            return rtrim(rtrim(number_format($amount / 1000000, 1, ',', ' '), '0'), ',').' mil.';
        }
        if ($amount >= 1000) {
            return number_format($amount / 1000, 0, ',', ' ').' tis.';
        }

        return number_format($amount, 0, ',', ' ');
    };
    $formatMoney = fn (float $amount): string => number_format($amount, 2, ',', ' ').' Kč';
    $max = $summary['max_month'] > 0 ? $summary['max_month'] : 0;

    // Škála: 5 popisků od 0 po největší měsíc.
    $ticks = [];
    foreach (range(4, 0) as $i) {
        $fraction = $i / 4;
        $ticks[] = [
            'value' => $max * $fraction,
            'top' => (1 - $fraction) * 100,
        ];
    }
@endphp

@if ($max <= 0)
    <p class="py-8 text-center text-sm text-gray-500">Za rok {{ $year }} zatím nejsou žádné faktury.</p>
@else
    <div>
        <div class="mb-4 flex items-center gap-4 text-xs text-gray-500">
            <span class="inline-flex items-center gap-1.5">
                <span class="h-3 w-3 rounded-sm" style="background:#007aff"></span> Zaplaceno
            </span>
            <span class="inline-flex items-center gap-1.5">
                <span class="h-3 w-3 rounded-sm" style="background:#cfe4ff"></span> Nezaplacené
            </span>
        </div>

        <div class="flex gap-2">
            {{-- Y osa / škála --}}
            <div class="relative w-14 shrink-0" style="height: 220px;">
                @foreach ($ticks as $tick)
                    <div class="absolute right-0 -translate-y-1/2 text-right text-[10px] leading-none text-gray-400" style="top: {{ $tick['top'] }}%;">
                        {{ $formatShort($tick['value']) }}
                    </div>
                @endforeach
            </div>

            {{-- Graf --}}
            <div class="flex-1 overflow-x-auto">
                <div class="relative min-w-[520px] sm:min-w-0" style="height: 220px;">
                    {{-- Vodorovné linky škály --}}
                    @foreach ($ticks as $tick)
                        <div class="absolute inset-x-0 border-t {{ $tick['top'] >= 100 ? 'border-gray-300' : 'border-dashed border-gray-100' }}" style="top: {{ $tick['top'] }}%;"></div>
                    @endforeach

                    {{-- Sloupce --}}
                    <div class="relative flex h-full items-end gap-2">
                        @foreach ($chart as $bar)
                            @php
                                $totalPct = $max > 0 ? $bar['total'] / $max * 100 : 0;
                                $paidPct = $bar['total'] > 0 ? $bar['paid'] / $bar['total'] * 100 : 0;
                            @endphp
                            <div class="flex h-full flex-1 flex-col items-center justify-end" title="{{ $bar['label'] }}: {{ $formatMoney($bar['total']) }} (zaplaceno {{ $formatMoney($bar['paid']) }})">
                                @if ($bar['total'] > 0)
                                    <span class="mb-1 text-[10px] font-medium text-gray-500">{{ $formatShort($bar['total']) }}</span>
                                @endif
                                <div class="w-full max-w-[42px] rounded-t" style="height: {{ $bar['total'] > 0 ? max($totalPct, 1.5) : 0 }}%; background:#cfe4ff; position: relative;">
                                    <div class="rounded-t" style="position:absolute; bottom:0; left:0; right:0; height: {{ $paidPct }}%; background:#007aff;"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Popisky měsíců --}}
                <div class="mt-1 flex min-w-[520px] gap-2 sm:min-w-0">
                    @foreach ($chart as $bar)
                        <div class="flex-1 text-center text-[11px] font-medium text-gray-500">{{ $bar['label'] }}</div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
@endif
