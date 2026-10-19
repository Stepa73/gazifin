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
                {{-- Horní popisek osy přesahuje nad plochu grafu, proto to odsazení. --}}
                <div class="flex gap-1.5 pt-2 sm:gap-2">
                    {{-- Levá osa: kolik přijde za měsíc. --}}
                    <div class="relative hidden w-11 shrink-0 sm:block" style="height: 200px">
                        <template x-for="line in c.gridlines" :key="'l' + line.pct">
                            <div class="absolute right-0 translate-y-1/2 text-[10px] tabular-nums text-gray-400"
                                 :style="`bottom:${line.pct}%`" x-text="line.left"></div>
                        </template>
                    </div>

                    <div class="min-w-0 flex-1">
                        {{-- Plocha grafu je vlastní vrstva vysoká přesně 200 px, aby procenta
                             uvnitř nepočítala i s řádkem názvů měsíců pod ní. --}}
                        <div class="relative" style="height: 200px">
                            {{-- Měsíce, které ještě nenastaly, jsou odhad — ne fakt. --}}
                            <template x-if="c.nowX !== null">
                                <div class="absolute inset-y-0 right-0 bg-gray-50/70" :style="`left:${c.nowX}%`"></div>
                            </template>

                            <template x-for="line in c.gridlines" :key="'g' + line.pct">
                                <div class="absolute inset-x-0 border-t border-gray-100" :style="`bottom:${line.pct}%`"></div>
                            </template>

                            {{-- Kumulativ pod sloupci, ať je nepřekrývá. --}}
                            <svg class="pointer-events-none absolute inset-0 h-full w-full"
                                 viewBox="0 0 1200 100" preserveAspectRatio="none" aria-hidden="true">
                                <polygon :points="c.areaPoints" fill="#059669" fill-opacity="0.07" />
                                <polyline :points="c.linePoints" fill="none" stroke="#059669" stroke-width="2"
                                          stroke-linejoin="round" stroke-linecap="round" vector-effect="non-scaling-stroke" />
                                {{-- Bez <template>: uvnitř SVG se neparsuje jako šablona, takže by ho Alpine nedokázal naklonovat. --}}
                                <line x-show="c.ceilingVisible" x1="0" x2="1200" :y1="100 - c.ceilingPct" :y2="100 - c.ceilingPct"
                                      stroke="#ef4444" stroke-width="1" stroke-dasharray="6 4" vector-effect="non-scaling-stroke" />
                            </svg>

                            {{-- Sloupce --}}
                            <div class="relative grid h-full grid-cols-12">
                                <template x-for="bar in c.bars" :key="bar.index">
                                    <button type="button"
                                            class="relative flex h-full items-end justify-center gap-[2px] px-[1px]"
                                            :aria-label="`${monthName(bar.index)} ${year}`"
                                            :aria-pressed="activeMonth(hover) === bar.index"
                                            @mouseenter="hover = bar.index" @mouseleave="hover = null"
                                            @click="calendarMonth = bar.index; selectedDay = null">
                                        <div class="absolute inset-y-0 inset-x-[1px] rounded-sm transition"
                                             :class="activeMonth(hover) === bar.index ? 'bg-brand/10 ring-1 ring-brand/30' : ''"></div>

                                        <div class="relative w-full max-w-[13px] rounded-t transition-all"
                                             :class="bar.ahead ? 'bg-amber-300' : 'bg-amber-400'"
                                             :style="`height:${bar.planPct}%`"></div>

                                        <template x-if="c.hasActual">
                                            <div class="relative w-full max-w-[13px] overflow-hidden rounded-t bg-blue-200 transition-all"
                                                 :style="`height:${bar.actualPct}%`">
                                                <div class="absolute inset-x-0 bottom-0 bg-blue-500" :style="`height:${bar.receivedShare}%`"></div>
                                            </div>
                                        </template>

                                        {{-- Tečka na kumulativní čáře pro vybraný měsíc. --}}
                                        {{-- :style musí být objekt: řetězec by přepsal celý atribut
                                             včetně display:none, které tam zapíše x-show. --}}
                                        <div x-show="activeMonth(hover) === bar.index"
                                             class="pointer-events-none absolute left-1/2 h-2 w-2 -translate-x-1/2 translate-y-1/2 rounded-full border-2 border-white bg-emerald-600"
                                             :style="{ bottom: bar.cumulativePct + '%' }"></div>
                                    </button>
                                </template>
                            </div>

                            {{-- Štítek sedí přímo na čáře stropu, jinak by splynul s popiskem nejbližší mřížky. --}}
                            <div x-show="c.ceilingVisible"
                                 class="pointer-events-none absolute left-1 z-10 translate-y-1/2 rounded bg-white/90 px-1 text-[9px] font-semibold uppercase tracking-wide text-red-500"
                                 :style="{ bottom: c.ceilingPct + '%' }">strop 2 mil.</div>

                            <template x-if="c.nowX !== null">
                                <div class="pointer-events-none absolute inset-y-0 border-l border-dashed border-gray-300" :style="`left:${c.nowX}%`"></div>
                            </template>
                        </div>

                        {{-- Měsíce --}}
                        <div class="mt-1 grid grid-cols-12">
                            <template x-for="bar in c.bars" :key="'m' + bar.index">
                                <button type="button" class="py-1 text-center text-[10px] uppercase transition"
                                        :class="activeMonth(hover) === bar.index
                                            ? 'font-bold text-brand'
                                            : (bar.isNow ? 'font-semibold text-gray-700' : 'text-gray-400 hover:text-gray-700')"
                                        @click="calendarMonth = bar.index; selectedDay = null"
                                        x-text="bar.label"></button>
                            </template>
                        </div>
                    </div>

                    {{-- Pravá osa: kumulativní součet. Má jiný rozsah než měsíce, tak i vlastní popisky. --}}
                    <div class="relative hidden w-11 shrink-0 sm:block" style="height: 200px">
                        <template x-for="line in c.gridlines" :key="'r' + line.pct">
                            <div class="absolute left-0 translate-y-1/2 text-[10px] tabular-nums text-emerald-700/70"
                                 :style="`bottom:${line.pct}%`" x-text="line.right"></div>
                        </template>
                    </div>
                </div>

                {{-- Ukazatel vybraného měsíce. Drží se i bez myši, takže funguje i na mobilu. --}}
                <div class="mt-3 rounded-xl border border-gray-200 bg-gray-50 px-3 py-2">
                    <div class="flex flex-wrap items-baseline justify-between gap-x-3 gap-y-1">
                        <span class="text-sm font-semibold text-gray-900">
                            <span x-text="monthName(activeMonth(hover))"></span> <span class="tabular-nums" x-text="year"></span>
                        </span>
                        <span class="text-[10px] uppercase tracking-wide"
                              :class="c.bars[activeMonth(hover)].ahead ? 'text-gray-400' : 'text-gray-500'"
                              x-text="c.bars[activeMonth(hover)].ahead ? 'ještě nenastalo' : (c.bars[activeMonth(hover)].isNow ? 'probíhá' : 'proběhlo')"></span>
                    </div>

                    <div class="mt-2 grid gap-2" :class="c.hasActual ? 'grid-cols-3' : 'grid-cols-2'">
                        <div>
                            <div class="flex items-center gap-1.5 text-[10px] uppercase tracking-wide text-gray-400">
                                <i class="inline-block h-2 w-2 rounded-sm bg-amber-400"></i>plán
                            </div>
                            <div class="text-sm font-semibold tabular-nums text-gray-900" x-text="money(c.bars[activeMonth(hover)].plan)"></div>
                        </div>

                        <template x-if="c.hasActual">
                            <div>
                                <div class="flex items-center gap-1.5 text-[10px] uppercase tracking-wide text-gray-400">
                                    <i class="inline-block h-2 w-2 rounded-sm bg-blue-500"></i>z faktur
                                </div>
                                <div class="text-sm font-semibold tabular-nums text-blue-600" x-text="money(c.bars[activeMonth(hover)].actual)"></div>
                                <div class="text-[10px] tabular-nums"
                                     :class="c.bars[activeMonth(hover)].diff >= 0 ? 'text-emerald-600' : 'text-red-500'"
                                     x-text="signed(c.bars[activeMonth(hover)].diff) + ' proti plánu'"></div>
                            </div>
                        </template>

                        <div>
                            <div class="flex items-center gap-1.5 text-[10px] uppercase tracking-wide text-gray-400">
                                <i class="inline-block h-0.5 w-3 rounded bg-emerald-600"></i>od ledna
                            </div>
                            <div class="text-sm font-semibold tabular-nums text-emerald-700" x-text="money(c.bars[activeMonth(hover)].cumulative)"></div>
                            <div class="text-[10px] tabular-nums text-gray-400"
                                 x-text="percent(c.bars[activeMonth(hover)].cumulative / 2000000, 0) + ' stropu'"></div>
                        </div>
                    </div>

                    <div class="mt-2 border-t border-gray-200 pt-1.5 text-[10px] text-gray-400">
                        Klikni na měsíc — kalendář níž se přepne na stejný.
                        <span x-show="c.hasActual">Sytější modrá je už uhrazené, světlejší čeká na úhradu.</span>
                    </div>
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
