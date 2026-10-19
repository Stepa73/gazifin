{{-- Co z roku zbyde: jedno velké číslo a tři čísla, která ho vysvětlují. --}}
<section>
    <x-panel>
        <template x-if="results">
            <div>
                {{-- Na mobilu průměr nesmí vypadat jako druhé hlavní číslo, proto jde pod ně na jeden řádek. --}}
                <div class="sm:flex sm:items-end sm:justify-between sm:gap-3">
                    <div class="min-w-0">
                        <div class="text-xs font-bold uppercase tracking-wide text-gray-500">
                            Čistého za rok <span x-text="year"></span>
                            <span x-show="isForecast" class="font-normal normal-case text-amber-600">· prognóza</span>
                        </div>
                        <div class="mt-0.5 text-3xl font-bold tabular-nums sm:mt-1 sm:text-4xl"
                             :class="results.tax.net < 0 ? 'text-red-600' : 'text-emerald-600'"
                             x-text="money(results.tax.net)"></div>
                    </div>
                    {{-- Na mobilu jeden řádek pod hlavním číslem, na desktopu sloupeček vpravo.
                         Jedno společné značkování by na jednom z nich vždycky vypadalo rozlámaně. --}}
                    <div class="mt-0.5 text-xs text-gray-500 sm:hidden">
                        v průměru
                        <span class="font-semibold tabular-nums text-gray-900" x-text="money(results.tax.net / 12)"></span>
                        měsíčně
                    </div>
                    <div class="hidden sm:block sm:text-right">
                        <div class="text-xs text-gray-400">v průměru měsíčně</div>
                        <div class="text-lg font-semibold tabular-nums text-gray-900" x-text="money(results.tax.net / 12)"></div>
                    </div>
                </div>

                <div class="mt-4 grid grid-cols-3 gap-x-3 border-t border-gray-100 pt-4">
                    <div>
                        <div class="text-[11px] uppercase tracking-wide text-gray-400">Na účet</div>
                        <div class="mt-0.5 text-[13px] font-semibold tabular-nums text-gray-900 sm:text-sm" x-text="money(results.cash.total)"></div>
                    </div>
                    <div>
                        <div class="text-[11px] uppercase tracking-wide text-gray-400">Odvody</div>
                        <div class="mt-0.5 text-[13px] font-semibold tabular-nums text-amber-600 sm:text-sm" x-text="'−' + money(results.tax.totalDue)"></div>
                    </div>
                    <div>
                        <div class="text-[11px] uppercase tracking-wide text-gray-400">Odvedeš</div>
                        <div class="mt-0.5 text-[13px] font-semibold tabular-nums text-gray-900 sm:text-sm"
                             x-text="results.cash.total > 0 ? percent(results.tax.effectiveRate) : '—'"></div>
                    </div>
                </div>

                <div class="mt-3 flex flex-wrap items-center gap-1.5">
                    <span class="rounded-full px-2.5 py-1 text-[11px] font-semibold"
                          :class="results.tax.mode === 'pausal' ? 'bg-brand-light text-brand' : 'bg-gray-100 text-gray-600'"
                          x-text="results.tax.mode === 'pausal'
                              ? ('Paušální daň · ' + results.tax.band.roman + '. pásmo')
                              : 'Klasické přiznání'"></span>
                    <span x-show="results.tax.overCap" class="rounded-full bg-red-50 px-2.5 py-1 text-[11px] font-semibold text-red-700">
                        Nad 2 miliony — paušál už nejde
                    </span>
                    <span x-show="results.tax.rules.estimated" class="rounded-full bg-amber-50 px-2.5 py-1 text-[11px] font-semibold text-amber-700">
                        Sazby <span x-text="year"></span> nejsou vyhlášené — počítáno podle <span x-text="results.tax.rules.year"></span>
                    </span>
                </div>
            </div>
        </template>
    </x-panel>
</section>

{{-- Kolik z letoška ještě dorazí. Ukazuje se jen u roku, který právě běží. --}}
<section x-show="year === years.base && results && !results.remaining.finished">
    <h2 class="mb-2 text-xs font-bold uppercase tracking-wide text-gray-500">Do konce roku ještě dorazí</h2>
    <x-panel>
        <template x-if="results">
            <div>
                <div class="sm:flex sm:items-end sm:justify-between sm:gap-3">
                    <div class="text-2xl font-bold tabular-nums sm:text-3xl"
                         :class="results.remaining.net < 0 ? 'text-red-600' : 'text-gray-900'"
                         x-text="money(results.remaining.net)"></div>
                    <div class="text-xs text-gray-400 sm:text-right">
                        čistého, po odvodech ze zbytku roku
                    </div>
                </div>

                <div class="mt-4 space-y-0 border-t border-gray-100 pt-1">
                    <x-calculator.row label="Dorazí na účet (hrubé)" x-value="money(results.remaining.gross)" />
                    <x-calculator.row label="Z toho odvody" tone="accent" x-value="'−' + money(results.remaining.levy)" />
                    <x-calculator.row sub
                        x-label="results.remaining.installments !== null
                            ? (results.remaining.installments + '× ' + money(results.remaining.monthly) + ', splatnost do 20. dne v měsíci')
                            : ('mezní sazba ' + percent(results.remaining.rate) + ' — poslední peníze roku se daní dráž')" />
                    <x-calculator.row label="Počet plateb, které ještě přijdou" x-value="String(results.remaining.upcoming.length)" />
                    <template x-if="results.remaining.last">
                        <div>
                            <x-calculator.row label="Poslední letošní platba" x-value="dateLabel(results.remaining.last.iso) + ' ' + year" />
                        </div>
                    </template>
                </div>

                <details class="mt-3" x-show="results.remaining.upcoming.length">
                    <summary class="cursor-pointer text-xs font-semibold text-brand">Rozpis plateb</summary>
                    <div class="mt-2">
                        <template x-for="payment in results.remaining.upcoming" :key="payment.iso + payment.name + payment.amount">
                            <div class="grid grid-cols-[3.5rem_1fr_auto] items-baseline gap-2 border-b border-gray-100 py-1.5 text-xs last:border-b-0">
                                <span class="tabular-nums text-gray-400" x-text="dateLabel(payment.iso)"></span>
                                <span class="truncate text-gray-500">
                                    <span x-text="payment.name"></span>
                                    <span x-show="payment.kind === 'work'" class="text-gray-400"
                                          x-text="'· práce ' + MONTHS_SHORT[payment.workMonth] + (payment.workYear !== year ? ' ' + payment.workYear : '')"></span>
                                    <span x-show="payment.kind === 'invoice'" class="text-gray-400">· faktura</span>
                                </span>
                                <span class="whitespace-nowrap font-semibold tabular-nums text-gray-900" x-text="money(payment.amount)"></span>
                            </div>
                        </template>
                    </div>
                </details>

                <p class="mt-3 rounded-xl bg-gray-50 px-3 py-2 text-xs leading-relaxed text-gray-500">
                    Počítá se od dneška podle data, kdy každá platba fakticky dorazí — tedy podle zpoždění fakturace i dne v měsíci, kdy klient platí.
                    <template x-if="results.remaining.afterYearEnd > 0">
                        <span>
                            Dalších <strong class="font-semibold text-gray-900" x-text="money(results.remaining.afterYearEnd)"></strong>
                            za letošní práci dorazí až po Novém roce, do letoška se proto nepočítá.
                        </span>
                    </template>
                </p>
            </div>
        </template>
    </x-panel>
</section>
