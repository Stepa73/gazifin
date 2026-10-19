{{-- Rok po měsících. Plán a skutečnost vedle sebe na jedné ose, kumulativ zvlášť čarou. --}}
<section>
    <div class="mb-2 flex items-baseline justify-between gap-2">
        <h2 class="text-xs font-bold uppercase tracking-wide text-gray-500">
            Rok <span x-text="year"></span> po měsících
        </h2>
        <span class="text-[11px] text-gray-400">kdy peníze dorazí na účet</span>
    </div>

    <x-panel>
        <template x-if="chart()">
            <div x-data="{ c: chart(), hover: null }" x-effect="c = chart()">
                <div class="flex gap-2">
                    {{-- Popisky osy. Vedle mřížky, ne v ní, ať se sloupce nezužují. --}}
                    <div class="relative hidden w-12 shrink-0 sm:block" style="height: 190px">
                        <template x-for="line in c.gridlines" :key="line.pct">
                            <div class="absolute right-0 -translate-y-1/2 text-[10px] tabular-nums text-gray-400"
                                 :style="`bottom:${line.pct}%`" x-text="line.label"></div>
                        </template>
                    </div>

                    <div class="relative min-w-0 flex-1">
                        {{-- Vodorovné linky --}}
                        <div class="absolute inset-x-0 top-0" style="height: 190px">
                            <template x-for="line in c.gridlines" :key="line.pct">
                                <div class="absolute inset-x-0 border-t border-dashed border-gray-100" :style="`bottom:${line.pct}%`"></div>
                            </template>
                        </div>

                        {{-- Sloupce --}}
                        <div class="relative grid grid-cols-12" style="height: 190px">
                            <template x-for="bar in c.bars" :key="bar.index">
                                <div class="relative flex h-full items-end justify-center gap-[2px] px-[2px]"
                                     @mouseenter="hover = bar.index" @mouseleave="hover = null"
                                     @click="calendarMonth = bar.index; selectedDay = null">
                                    <div class="absolute inset-0 rounded-sm transition"
                                         :class="hover === bar.index ? 'bg-gray-50' : (bar.isNow ? 'bg-brand-light/40' : '')"></div>

                                    <div class="relative w-full max-w-[14px] rounded-t bg-amber-400 transition-all"
                                         :style="`height:${bar.planPct}%`"
                                         :title="`Plán ${MONTHS_SHORT[bar.index]}: ${money(bar.plan)}`"></div>

                                    <template x-if="c.hasActual">
                                        <div class="relative w-full max-w-[14px] overflow-hidden rounded-t bg-blue-200 transition-all"
                                             :style="`height:${bar.actualPct}%`"
                                             :title="`Skutečnost ${MONTHS_SHORT[bar.index]}: ${money(bar.actual)}`">
                                            <div class="absolute inset-x-0 bottom-0 bg-blue-500" :style="`height:${bar.receivedShare}%`"></div>
                                        </div>
                                    </template>
                                </div>
                            </template>
                        </div>

                        {{-- Kumulativní čára přes sloupce. Vlastní měřítko, proto zvlášť. --}}
                        <svg class="pointer-events-none absolute inset-x-0 top-0 w-full" style="height: 190px"
                             viewBox="0 0 1200 100" preserveAspectRatio="none" aria-hidden="true">
                            {{-- Bez <template>: uvnitř SVG se neparsuje jako šablona, takže by ho Alpine nedokázal naklonovat. --}}
                            <line x-show="c.ceilingVisible" x1="0" x2="1200" :y1="100 - c.ceilingPct" :y2="100 - c.ceilingPct"
                                  stroke="#ef4444" stroke-width="1" stroke-dasharray="6 4" vector-effect="non-scaling-stroke" />
                            <polyline :points="c.linePoints" fill="none" stroke="#059669" stroke-width="2"
                                      stroke-linejoin="round" stroke-linecap="round" vector-effect="non-scaling-stroke" />
                        </svg>

                        {{-- Měsíce --}}
                        <div class="mt-1 grid grid-cols-12">
                            <template x-for="bar in c.bars" :key="bar.index">
                                <button type="button" class="py-1 text-center text-[10px] uppercase transition"
                                        :class="calendarMonth === bar.index ? 'font-bold text-brand' : (bar.isNow ? 'font-semibold text-gray-700' : 'text-gray-400 hover:text-gray-700')"
                                        @click="calendarMonth = bar.index; selectedDay = null"
                                        x-text="bar.label"></button>
                            </template>
                        </div>
                    </div>
                </div>

                {{-- Co je pod kurzorem, nebo součet za rok. --}}
                <div class="mt-3 rounded-xl bg-gray-50 px-3 py-2">
                    <template x-if="hover !== null">
                        <div class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1 text-xs">
                            <span class="font-semibold text-gray-900" x-text="monthName(hover) + ' ' + year"></span>
                            <span class="tabular-nums text-amber-600" x-text="'plán ' + money(c.bars[hover].plan)"></span>
                            <span x-show="c.hasActual" class="tabular-nums text-blue-600" x-text="'skutečnost ' + money(c.bars[hover].actual)"></span>
                            <span class="tabular-nums text-emerald-600" x-text="'kumulativně ' + money(c.bars[hover].cumulative)"></span>
                        </div>
                    </template>
                    <template x-if="hover === null">
                        <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-[11px] text-gray-500">
                            <span class="inline-flex items-center gap-1.5"><i class="inline-block h-2.5 w-2.5 rounded-sm bg-amber-400"></i>plán</span>
                            <span x-show="c.hasActual" class="inline-flex items-center gap-1.5"><i class="inline-block h-2.5 w-2.5 rounded-sm bg-blue-500"></i>uhrazené faktury</span>
                            <span x-show="c.hasActual" class="inline-flex items-center gap-1.5"><i class="inline-block h-2.5 w-2.5 rounded-sm bg-blue-200"></i>čeká na úhradu</span>
                            <span class="inline-flex items-center gap-1.5"><i class="inline-block h-0.5 w-3 rounded bg-emerald-600"></i>kumulativně</span>
                            <span x-show="c.ceilingVisible" class="inline-flex items-center gap-1.5"><i class="inline-block h-0.5 w-3 rounded bg-red-500"></i>2 miliony</span>
                        </div>
                    </template>
                </div>

                {{-- Poznámky k přelivům mezi roky. --}}
                <template x-if="results.crossing >= 0">
                    <div class="mt-3 rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-xs leading-relaxed text-red-700">
                        Dva miliony na účtu překročíš v
                        <strong class="font-semibold" x-text="monthNameIn(results.crossing)"></strong>.
                        Od té chvíle paušální režim pro rok <span x-text="year"></span> neplatí.
                    </div>
                </template>

                <template x-if="results.cash.deferred > 0 || results.cash.carriedIn > 0">
                    <div class="mt-3 rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-xs leading-relaxed text-gray-600">
                        <span x-show="results.cash.carriedIn > 0">
                            Zahrnuje <strong class="font-semibold text-gray-900" x-text="money(results.cash.carriedIn)"></strong>
                            přenesených z konce roku <span x-text="year - 1"></span>.
                        </span>
                        <span x-show="results.cash.deferred > 0">
                            Práce za <strong class="font-semibold text-gray-900" x-text="money(results.cash.deferred)"></strong>
                            se zaplatí až v roce <span x-text="year + 1"></span>, do tohoto roku se nepočítá.
                        </span>
                    </div>
                </template>
            </div>
        </template>
    </x-panel>
</section>

{{-- Plán vs. skutečnost. Dva pohledy, protože faktura a peníze na účtu nejsou totéž. --}}
<section x-show="results && results.actual.any">
    <h2 class="mb-2 text-xs font-bold uppercase tracking-wide text-gray-500">Plán vs. skutečnost</h2>
    <x-panel>
        <template x-if="results">
            <div>
                <div class="inline-flex rounded-lg bg-gray-100 p-0.5 text-xs">
                    <button type="button" @click="compareBasis = 'cash'"
                            :class="compareBasis === 'cash' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500'"
                            class="rounded-md px-3 py-1.5 font-medium transition">Podle peněz</button>
                    <button type="button" @click="compareBasis = 'issued'"
                            :class="compareBasis === 'issued' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500'"
                            class="rounded-md px-3 py-1.5 font-medium transition">Podle vystavení</button>
                </div>

                <div class="mt-3 space-y-0 border-t border-gray-100 pt-1">
                    <x-calculator.row x-label="'Plán na rok ' + year" x-value="money(results.cash.total)" tone="accent" />

                    <template x-if="compareBasis === 'cash'">
                        <div>
                            <x-calculator.row label="Z faktur dorazí na účet" tone="real" x-value="money(results.actual.totals.cash)" />
                            <x-calculator.row sub label="už uhrazeno" x-value="money(results.actual.totals.received)" />
                            <x-calculator.row sub label="čeká na úhradu, počítáno ke splatnosti" x-value="money(results.actual.totals.expected)" />
                            <x-calculator.row label="Rozdíl proti plánu"
                                :tone="'plain'"
                                x-value="signed(results.actual.totals.cash - results.cash.total)" />
                        </div>
                    </template>

                    <template x-if="compareBasis === 'issued'">
                        <div>
                            <x-calculator.row label="Vyfakturováno (podle data vystavení)" tone="real" x-value="money(results.actual.totals.issued)" />
                            <x-calculator.row label="Rozdíl proti plánu" x-value="signed(results.actual.totals.issued - results.cash.total)" />
                        </div>
                    </template>
                </div>

                <p class="mt-3 rounded-xl bg-gray-50 px-3 py-2 text-xs leading-relaxed text-gray-500">
                    <template x-if="compareBasis === 'cash'">
                        <span>Plán sleduje, kdy peníze dorazí na účet. Tenhle pohled dělá totéž s fakturami — uhrazené podle data úhrady, neuhrazené podle splatnosti. Tyhle dvě čísla jdou porovnávat přímo.</span>
                    </template>
                    <template x-if="compareBasis === 'issued'">
                        <span>Tenhle pohled řadí faktury podle data vystavení, tedy podle měsíce, <em>za který</em> sis vydělal. Proti plánu je posunutý o zpoždění platby, takže rozdíl sám o sobě nic neznamená.</span>
                    </template>
                </p>
            </div>
        </template>
    </x-panel>
</section>
