{{-- Kalendář. Den je základní jednotka výpočtu, takže se dá i rozkliknout. --}}
<section x-data="{ vacationTarget: 'all' }">
    <div class="mb-2 flex items-baseline justify-between gap-2">
        <h2 class="text-xs font-bold uppercase tracking-wide text-gray-500">Kalendář výdělku</h2>
        <span class="text-[11px] text-gray-400">klikni na den pro rozbor</span>
    </div>

    <x-panel>
        <template x-if="results">
            <div>
                {{-- Navigace po měsících --}}
                <div class="flex items-center justify-between gap-2">
                    <button type="button" @click="shiftMonth(-1)" :disabled="calendarMonth === 0"
                            class="flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 text-gray-500 transition disabled:opacity-30 hover:bg-gray-50"
                            aria-label="Předchozí měsíc">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 18 9 12l6-6"/></svg>
                    </button>

                    <div class="text-center">
                        <div class="text-sm font-semibold text-gray-900">
                            <span x-text="monthName(calendarMonth)"></span> <span class="tabular-nums" x-text="year"></span>
                        </div>
                        <div class="text-xs tabular-nums text-gray-500" x-text="moneyExact(monthBucket()?.amount ?? 0)"></div>
                    </div>

                    <button type="button" @click="shiftMonth(1)" :disabled="calendarMonth === 11"
                            class="flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 text-gray-500 transition disabled:opacity-30 hover:bg-gray-50"
                            aria-label="Další měsíc">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 18 6-6-6-6"/></svg>
                    </button>
                </div>

                {{-- Souhrn měsíce --}}
                <div class="mt-3 grid grid-cols-3 gap-2 border-y border-gray-100 py-3 text-center">
                    <div>
                        <div class="text-sm font-semibold tabular-nums text-gray-900" x-text="monthBucket()?.workDays ?? 0"></div>
                        <div class="text-[10px] uppercase tracking-wide text-gray-400">odpracovaných dnů</div>
                    </div>
                    <div>
                        <div class="text-sm font-semibold tabular-nums text-gray-900" x-text="hours(monthBucket()?.hours ?? 0)"></div>
                        <div class="text-[10px] uppercase tracking-wide text-gray-400">hodin</div>
                    </div>
                    <div>
                        <div class="text-sm font-semibold tabular-nums"
                             :class="(monthBucket()?.vacationDays ?? 0) > 0 ? 'text-amber-600' : 'text-gray-900'"
                             x-text="monthBucket()?.vacationDays ?? 0"></div>
                        <div class="text-[10px] uppercase tracking-wide text-gray-400">dnů dovolené</div>
                    </div>
                </div>

                {{-- Režim zadávání dovolené --}}
                <div class="mt-3 flex flex-wrap items-center gap-2">
                    <button type="button" @click="vacationMode = !vacationMode; selectedDay = null"
                            :class="vacationMode ? 'border-amber-400 bg-amber-50 text-amber-700' : 'border-gray-200 bg-white text-gray-600 hover:bg-gray-50'"
                            class="rounded-full border px-3 py-1.5 text-xs font-medium transition">
                        <span x-text="vacationMode ? 'Hotovo' : 'Zadat dovolenou'"></span>
                    </button>

                    <template x-if="vacationMode && sources.filter(s => s.mode !== 'invoice').length > 1">
                        <select x-model="vacationTarget"
                                class="rounded-full border-gray-200 py-1.5 pl-3 pr-8 text-xs shadow-sm focus:border-brand focus:ring-brand">
                            <option value="all">U všech zdrojů</option>
                            <template x-for="source in sources.filter(s => s.mode !== 'invoice')" :key="source.id">
                                <option :value="source.id" x-text="source.name"></option>
                            </template>
                        </select>
                    </template>

                    <span class="text-[11px] text-gray-400"
                          x-text="days(vacationCount(year)) + ' dovolené v roce ' + year"></span>

                    <button type="button" x-show="vacationCount(year) > 0" @click="clearVacation(year)"
                            class="text-[11px] font-medium text-gray-400 underline hover:text-red-600">
                        vymazat rok
                    </button>
                </div>

                <p x-show="vacationMode" class="mt-2 rounded-xl bg-amber-50 px-3 py-2 text-xs leading-relaxed text-amber-800">
                    Klikáním na dny zapínáš a vypínáš dovolenou. Dovolená platí pro konkrétní datum, takže se do dalšího roku nepřenáší — ten si naplánuješ zvlášť.
                </p>

                {{-- Mřížka --}}
                <div class="mt-3 grid grid-cols-7 gap-1 text-center">
                    <template x-for="day in WEEKDAYS_SHORT" :key="day">
                        <div class="pb-1 text-[10px] font-semibold uppercase tracking-wide text-gray-400" x-text="day"></div>
                    </template>

                    <template x-for="(cell, index) in calendarGrid()" :key="cell ? cell.iso : 'pad-' + index">
                        <button type="button"
                                :class="dayClasses(cell)"
                                :disabled="!cell"
                                @click="cell && (vacationMode
                                    ? sources
                                        .filter(s => s.mode !== 'invoice' && (vacationTarget === 'all' || s.id === vacationTarget))
                                        .forEach(s => toggleVacation(s, cell.iso))
                                    : selectDay(cell))"
                                class="min-h-[3.25rem] p-1">
                            <template x-if="cell">
                                <div class="flex h-full flex-col justify-between">
                                    <div class="flex items-start justify-between gap-0.5">
                                        <span class="text-[11px] font-semibold tabular-nums"
                                              :class="cell.iso === today ? 'text-brand' : (cell.weekend || cell.holiday ? 'text-gray-400' : 'text-gray-700')"
                                              x-text="cell.date.getDate()"></span>
                                        <span x-show="cell.onVacation" class="text-[9px] leading-none text-amber-600" title="dovolená">★</span>
                                    </div>

                                    <div class="text-left">
                                        <template x-if="cell.amount > 0">
                                            {{-- Přesná částka se na mobilu do buňky vejde jen natěsno, proto nezalomená a drobným písmem. --}}
                                            <span class="block whitespace-nowrap text-[9px] font-semibold leading-tight tabular-nums text-emerald-700 sm:text-[10px]"
                                                  x-text="exact(cell.amount)"></span>
                                        </template>
                                        <template x-if="cell.amount === 0 && cell.holiday">
                                            <span class="block truncate text-[8px] leading-tight text-rose-500" :title="cell.holiday" x-text="cell.holiday"></span>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </button>
                    </template>
                </div>

                {{-- Rozbor vybraného dne --}}
                <template x-if="selectedDayData()">
                    <div class="mt-3 rounded-xl border border-brand/30 bg-brand-light/40 p-3">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <div class="text-sm font-semibold text-gray-900" x-text="selectedDayTitle()"></div>
                                <div class="text-xs text-gray-500">
                                    <span x-show="selectedDayData().holiday" class="text-rose-600" x-text="selectedDayData().holiday"></span>
                                    <span x-show="!selectedDayData().holiday && selectedDayData().weekend">víkend</span>
                                    <span x-show="selectedDayData().onVacation" class="text-amber-700">dovolená</span>
                                    <span x-show="selectedDayData().working" x-text="hours(selectedDayData().hours) + ' práce'"></span>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="text-xl font-bold tabular-nums text-gray-900" x-text="moneyExact(selectedDayData().amount)"></div>
                                <button type="button" @click="selectedDay = null" class="text-[11px] text-gray-400 underline">zavřít</button>
                            </div>
                        </div>

                        <template x-if="selectedDayData().sources.length">
                            <div class="mt-3 border-t border-brand/20 pt-2">
                                <template x-for="entry in selectedDayData().sources" :key="entry.id">
                                    <div class="border-b border-brand/10 py-1.5 last:border-b-0">
                                        <div class="flex items-baseline justify-between gap-2">
                                            <span class="truncate text-xs font-medium text-gray-700" x-text="entry.name"></span>
                                            <span class="whitespace-nowrap text-xs font-semibold tabular-nums"
                                                  :class="entry.amount > 0 ? 'text-emerald-700' : 'text-gray-400'"
                                                  x-text="entry.amount > 0 ? moneyExact(entry.amount) : dayStatusLabel(entry.status)"></span>
                                        </div>
                                        <template x-if="entry.amount > 0 && selectedDayPayment(entry)">
                                            <div class="text-[11px] text-gray-500">
                                                připíše se
                                                <span class="font-medium" x-text="dateLabel(selectedDayPayment(entry).iso) + ' ' + selectedDayPayment(entry).year"></span>
                                                spolu se zbytkem měsíce
                                                <span x-text="'(' + moneyExact(selectedDayPayment(entry).amount) + ')'"></span>
                                            </div>
                                        </template>
                                        <template x-if="entry.prorated">
                                            <div class="text-[11px] text-gray-400">podíl z měsíčního paušálu</div>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </template>

                        <template x-if="!selectedDayData().sources.length">
                            <p class="mt-2 text-xs text-gray-500">Tenhle den nespadá do žádné spolupráce.</p>
                        </template>
                    </div>
                </template>

                <div class="mt-3 flex flex-wrap gap-x-4 gap-y-1 text-[11px] text-gray-500">
                    <span class="inline-flex items-center gap-1.5"><i class="inline-block h-2.5 w-2.5 rounded-sm bg-emerald-50 ring-1 ring-emerald-200"></i>vyděláváš</span>
                    <span class="inline-flex items-center gap-1.5"><i class="inline-block h-2.5 w-2.5 rounded-sm bg-amber-100 ring-1 ring-amber-300"></i>dovolená</span>
                    <span class="inline-flex items-center gap-1.5"><i class="inline-block h-2.5 w-2.5 rounded-sm bg-rose-50 ring-1 ring-rose-200"></i>svátek</span>
                    <span class="inline-flex items-center gap-1.5"><i class="inline-block h-2.5 w-2.5 rounded-sm bg-gray-50 ring-1 ring-gray-200"></i>víkend</span>
                </div>
            </div>
        </template>
    </x-panel>
</section>
