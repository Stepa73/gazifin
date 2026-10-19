{{-- Rozpad odvodů. Jiný pro paušální daň, jiný pro přiznání. --}}
<section>
    <h2 class="mb-2 text-xs font-bold uppercase tracking-wide text-gray-500">
        Odvody za rok <span x-text="year"></span>
    </h2>

    <x-panel>
        <template x-if="results && results.tax.mode === 'pausal'">
            <div>
                <div class="space-y-0 border-b border-gray-100 pb-1">
                    <x-calculator.row label="Příjem přijatý za rok" x-value="money(results.cash.total)" />
                    <x-calculator.row label="Paušální záloha měsíčně" tone="accent" x-value="money(results.tax.monthly)" />
                    <x-calculator.row sub x-label="results.tax.months + '× ročně, splatnost do 20. dne v měsíci'" />
                    <x-calculator.row label="Zaplatíš za rok" tone="accent" x-value="money(results.tax.totalDue)" />
                    <x-calculator.row sub label="z toho daň z příjmů" x-value="money(results.tax.incomeTax)" />
                    <x-calculator.row sub label="z toho sociální" x-value="money(results.tax.social)" />
                    <x-calculator.row sub label="z toho zdravotní" x-value="money(results.tax.health)" />
                </div>

                <div class="mt-3 flex items-baseline justify-between gap-3">
                    <span class="text-sm font-semibold text-gray-700">Zbyde</span>
                    <span class="text-xl font-bold tabular-nums text-emerald-600" x-text="money(results.tax.net)"></span>
                </div>

                <template x-if="results.tax.alternative">
                    <p class="mt-3 rounded-xl bg-gray-50 px-3 py-2 text-xs leading-relaxed text-gray-500">
                        V klasickém přiznání bys odvedl
                        <strong class="font-semibold text-gray-900" x-text="money(results.tax.alternative.totalDue)"></strong>
                        a zbylo by <strong class="font-semibold text-gray-900" x-text="money(results.tax.alternative.net)"></strong>
                        — tedy
                        <span x-text="results.tax.alternative.net > results.tax.net ? 'o ' + money(results.tax.alternative.net - results.tax.net) + ' víc' : 'o ' + money(results.tax.net - results.tax.alternative.net) + ' míň'"></span>.
                    </p>
                </template>
            </div>
        </template>

        <template x-if="results && results.tax.mode === 'classic'">
            <div>
                <div class="space-y-0 border-b border-gray-100 pb-1">
                    <x-calculator.row label="Příjem přijatý za rok" x-value="money(results.cash.total)" />
                    <x-calculator.row
                        x-label="results.tax.expenseMode === 'real' ? 'Skutečné výdaje' : ('Paušální výdaje ' + results.tax.activity + ' %')"
                        x-value="'−' + money(results.tax.expenses)" />
                    <template x-if="results.tax.expensesCapped">
                        <div><x-calculator.row sub x-label="'zastropováno na ' + money(results.tax.expenseCap)" /></div>
                    </template>
                    <x-calculator.row label="Základ daně" x-value="money(results.tax.base)" />

                    <x-calculator.row label="Daň z příjmů po slevě" tone="accent" x-value="money(results.tax.incomeTax)" />
                    <x-calculator.row sub x-label="'15 % do ' + number(results.tax.rules.incomeTax.upperThreshold) + ', pak 23 %'" x-value="money(results.tax.taxBeforeCredit)" />
                    <x-calculator.row sub label="sleva na poplatníka" x-value="'−' + money(results.tax.credit)" />

                    <x-calculator.row label="Sociální pojištění" tone="accent" x-value="money(results.tax.social)" />
                    <template x-if="results.tax.socialExempt">
                        <div><x-calculator.row sub x-label="'vedlejší činnost se ziskem pod ' + money(results.tax.rules.social.sideThreshold) + ' — neplatí se'" /></div>
                    </template>
                    <template x-if="results.tax.socialAtMinimum">
                        <div><x-calculator.row sub x-label="'platí se z minimálního základu ' + money(results.tax.socialBase) + ', ne ze skutečného zisku'" /></div>
                    </template>
                    <template x-if="results.tax.socialAtCeiling">
                        <div><x-calculator.row sub x-label="'základ zastropován na ' + money(results.tax.rules.social.maxBase)" /></div>
                    </template>

                    <x-calculator.row label="Zdravotní pojištění" tone="accent" x-value="money(results.tax.health)" />
                    <template x-if="results.tax.healthAtMinimum">
                        <div><x-calculator.row sub x-label="'platí se z minimálního základu ' + money(results.tax.healthBase)" /></div>
                    </template>

                    <x-calculator.row label="Odvody celkem" x-value="money(results.tax.totalDue)" />
                    <x-calculator.row label="Odvedeš z příjmu" x-value="results.cash.total > 0 ? percent(results.tax.effectiveRate) : '—'" />
                </div>

                <div class="mt-3 flex items-baseline justify-between gap-3">
                    <span class="text-sm font-semibold text-gray-700">Zbyde</span>
                    <span class="text-xl font-bold tabular-nums"
                          :class="results.tax.net < 0 ? 'text-red-600' : 'text-emerald-600'"
                          x-text="money(results.tax.net)"></span>
                </div>

                <p class="mt-3 rounded-xl bg-gray-50 px-3 py-2 text-xs leading-relaxed text-gray-500">
                    <template x-if="results.tax.forced">
                        <span>
                            <strong class="font-semibold text-gray-900">Ručně zvolené klasické přiznání</strong> i pod dvěma miliony.
                            V paušální dani bys odvedl
                            <strong class="font-semibold text-gray-900" x-text="money(results.tax.alternative.totalDue)"></strong>
                            a zbylo by <strong class="font-semibold text-gray-900" x-text="money(results.tax.alternative.net)"></strong>.
                        </span>
                    </template>
                    <template x-if="!results.tax.forced">
                        <span><strong class="font-semibold text-gray-900">Podáváš přiznání i oba přehledy.</strong> Zaplacené zálohy se do odvodů započtou.</span>
                    </template>
                    <span x-show="results.cash.total > results.tax.rules.vat.immediateThreshold">
                        Obrat přes <span x-text="money(results.tax.rules.vat.immediateThreshold)"></span> znamená, že plátcem DPH se stáváš hned další den po překročení.
                    </span>
                    <span x-show="results.cash.total > results.tax.rules.vat.yearlyThreshold && results.cash.total <= results.tax.rules.vat.immediateThreshold">
                        Plátcem DPH se stáváš od 1. ledna <span x-text="year + 1"></span>.
                    </span>
                </p>
            </div>
        </template>
    </x-panel>
</section>

{{-- Pásma paušální daně + postup ke stropu. --}}
<section>
    <h2 class="mb-2 text-xs font-bold uppercase tracking-wide text-gray-500">Pásmo paušální daně</h2>
    <x-panel :padded="false" class="overflow-hidden">
        <template x-if="results">
            <div>
                <div class="px-4 pb-3 pt-4">
                    <div class="flex items-baseline justify-between gap-2 text-xs">
                        <span class="text-gray-500">Postup ke stropu paušálu</span>
                        <span class="font-semibold tabular-nums"
                              :class="results.cash.total > 2000000 ? 'text-red-600' : 'text-gray-900'"
                              x-text="money(results.cash.total) + ' / ' + money(2000000)"></span>
                    </div>
                    <div class="mt-2 h-2.5 overflow-hidden rounded-full bg-gray-100">
                        <div class="h-full rounded-full transition-all"
                             :class="results.cash.total > 2000000 ? 'bg-red-500' : 'bg-emerald-500'"
                             :style="`width:${ceilingProgress()}%`"></div>
                    </div>
                </div>

                <div class="divide-y divide-gray-100 border-t border-gray-100">
                    <template x-for="(band, index) in results.tax.rules.bands" :key="band.roman">
                        <div class="flex items-center gap-3 px-4 py-3 transition"
                             :class="{ 'bg-brand-light': bandState(index) === 'active', 'opacity-40': bandState(index) === 'blocked' }">
                            <div class="w-8 shrink-0 border-r border-gray-200 pr-3 text-center text-sm font-bold"
                                 :class="bandState(index) === 'active' ? 'text-brand' : 'text-gray-400'"
                                 x-text="band.roman"></div>
                            <div class="flex-1 text-xs" :class="bandState(index) === 'active' ? 'text-gray-900' : 'text-gray-500'">
                                Příjem do <span x-text="compact(results.ceilings[index])"></span> Kč
                            </div>
                            <div class="whitespace-nowrap text-sm font-bold tabular-nums"
                                 :class="bandState(index) === 'active' ? 'text-brand' : 'text-gray-500'"
                                 x-text="number(band.monthly) + ' Kč'"></div>
                        </div>
                    </template>
                </div>
            </div>
        </template>
    </x-panel>
</section>
