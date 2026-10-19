@php
    $input = 'mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-brand focus:ring-brand';
@endphp

{{-- Nastavení. Zabalené, protože se mění párkrát za rok, ne při každém pohledu. --}}
<section>
    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white lg:rounded-lg lg:border-0 lg:shadow-sm">
        <button type="button" @click="showSettings = !showSettings"
                class="flex w-full items-center justify-between gap-3 px-4 py-3 text-left lg:px-6"
                :aria-expanded="showSettings">
            <div class="min-w-0">
                <div class="text-sm font-semibold text-gray-900">Daňový režim a výdaje</div>
                <div class="mt-0.5 truncate text-[11px] text-gray-500">
                    <span x-text="{ auto: 'Automaticky', pausal: 'Paušální daň', klasik: 'Klasické přiznání' }[regime]"></span>
                    · paušál <span x-text="activity"></span> %
                    <span x-show="sideActivity">· vedlejší činnost</span>
                    <span x-show="expMode === 'real'">· skutečné výdaje</span>
                </div>
            </div>
            <svg class="h-5 w-5 shrink-0 text-gray-400 transition-transform" :class="showSettings ? 'rotate-180' : ''"
                 fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m6 9 6 6 6-6"/></svg>
        </button>

        <div x-show="showSettings" x-collapse>
            <div class="space-y-4 border-t border-gray-100 px-4 py-4 lg:px-6">
                <div>
                    <span class="block text-sm font-medium text-gray-700">Daňový režim</span>
                    <div class="mt-1 flex flex-wrap gap-2">
                        @foreach (['auto' => 'Automaticky', 'pausal' => 'Paušální daň', 'klasik' => 'Klasické přiznání'] as $value => $regimeLabel)
                            <button type="button" @click="regime = '{{ $value }}'; touch()"
                                    :aria-pressed="regime === '{{ $value }}'"
                                    :class="regime === '{{ $value }}' ? 'border-brand bg-brand-light text-brand' : 'border-gray-200 bg-white text-gray-600 hover:bg-gray-50'"
                                    class="shrink-0 rounded-full border px-3 py-1.5 text-xs font-medium transition">{{ $regimeLabel }}</button>
                        @endforeach
                    </div>
                    <p class="mt-1 text-[11px] text-gray-400">„Automaticky“ drží paušální daň do dvou milionů a nad stropem přepne na přiznání.</p>
                </div>

                <div x-show="regime !== 'klasik'">
                    <span class="block text-sm font-medium text-gray-700">Měsíců v paušálním režimu</span>
                    <input type="number" x-model.number="pausalMonths" @input="touch()" min="0" max="12" step="1" class="{{ $input }}">
                    <p class="mt-1 text-[11px] text-gray-400">Paušál se platí za každý měsíc v režimu. Kdo vstoupil v srpnu, zaplatí pět záloh, ne dvanáct.</p>
                </div>

                <label class="flex cursor-pointer items-start gap-3 text-sm text-gray-700">
                    <input type="checkbox" x-model="sideActivity" @change="touch()"
                           class="mt-0.5 h-5 w-5 rounded border-gray-300 text-brand focus:ring-brand">
                    <span>
                        Vedlejší činnost
                        <span class="block text-[11px] text-gray-400">důchodce, student, rodičovská, zaměstnání vedle OSVČ</span>
                    </span>
                </label>

                <div>
                    <span class="block text-sm font-medium text-gray-700">Aspoň 75 % příjmů plyne z</span>
                    <select x-model="activity" @change="touch()" class="{{ $input }}">
                        <option value="80">Řemeslná živnost, zemědělství — paušál 80 %</option>
                        <option value="60">Volná či vázaná živnost, IT — paušál 60 %</option>
                        <option value="40">Svobodné povolání, autorské honoráře — paušál 40 %</option>
                    </select>
                </div>

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div>
                        <span class="block text-sm font-medium text-gray-700">Výdaje uplatním</span>
                        <select x-model="expMode" @change="touch()" class="{{ $input }}">
                            <option value="pausal">Procentem z příjmů</option>
                            <option value="real">Skutečné</option>
                        </select>
                    </div>
                    <div x-show="expMode === 'real'">
                        <span class="block text-sm font-medium text-gray-700">Skutečné výdaje za rok</span>
                        <input type="number" x-model.number="expReal" @input="touch()" min="0" step="10000" class="{{ $input }}">
                    </div>
                </div>
                <p class="text-[11px] text-gray-400">Výdaje se uplatní jen v klasickém přiznání, paušální daň s nimi nepracuje.</p>
            </div>
        </div>
    </div>
</section>

{{-- Přechod z minulého roku. --}}
<section>
    <h2 class="mb-2 text-xs font-bold uppercase tracking-wide text-gray-500">
        Přechod z roku {{ $years['prev'] }}
    </h2>
    <x-panel>
        <p class="rounded-xl bg-gray-50 px-3 py-2 text-xs leading-relaxed text-gray-500">
            <template x-if="results && results.autoCarry > 0">
                <span>
                    Z práce odvedené v roce {{ $years['prev'] }} se podle dat „Od“ přenáší na začátek roku {{ $years['base'] }}
                    <strong class="font-semibold text-gray-900" x-text="money(results.autoCarry)"></strong>.
                </span>
            </template>
            <template x-if="results && results.autoCarry === 0">
                <span>Z roku {{ $years['prev'] }} se automaticky nepřenáší nic — žádný zdroj nemá datum „Od“ před 1. 1. {{ $years['base'] }}.</span>
            </template>
        </p>

        <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
            <div>
                <span class="block text-sm font-medium text-gray-700">Ruční korekce (+ nebo −)</span>
                <input type="number" x-model.number="carryAmount" @input="touch()" step="1000" class="{{ $input }}">
            </div>
            <div>
                <span class="block text-sm font-medium text-gray-700">Kdy dorazí</span>
                <select x-model.number="carryMonth" @change="touch()" class="{{ $input }}">
                    @foreach (['Leden', 'Únor', 'Březen'] as $index => $monthName)
                        <option value="{{ $index }}">{{ $monthName }} {{ $years['base'] }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </x-panel>
</section>
