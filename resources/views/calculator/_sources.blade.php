@php
    $input = 'mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-brand focus:ring-brand';
    $label = 'block text-xs font-medium text-gray-500';
@endphp

{{-- Zdroje příjmu. Každý se rozbaluje zvlášť, ať je seznam přehledný i při deseti klientech. --}}
<section>
    <div class="mb-2 flex items-baseline justify-between gap-2">
        <h2 class="text-xs font-bold uppercase tracking-wide text-gray-500">Zdroje příjmu</h2>
        <button type="button" @click="resetToClients()" class="text-[11px] font-medium text-gray-400 underline hover:text-brand">
            začít znovu podle klientů
        </button>
    </div>

    <div class="space-y-2">
        <template x-for="(source, index) in sources" :key="source.id">
            <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white lg:rounded-lg lg:shadow-sm">
                {{-- Hlavička: shrnutí, dokud není rozbaleno --}}
                <div class="flex items-center gap-2 p-3">
                    <button type="button" class="min-w-0 flex-1 text-left"
                            @click="openSource = openSource === source.id ? null : source.id">
                        <div class="flex items-center gap-2">
                            <span class="truncate text-sm font-semibold text-gray-900" x-text="source.name || 'Beze jména'"></span>
                            <template x-if="source.clientId">
                                <span class="shrink-0 rounded-full bg-blue-50 px-2 py-0.5 text-[10px] font-medium text-blue-700">napojeno</span>
                            </template>
                        </div>
                        <div class="mt-0.5 flex flex-wrap items-baseline gap-x-2 text-[11px] text-gray-500">
                            <span x-text="sourceLabel(source)"></span>
                            <span class="text-gray-300">·</span>
                            <span class="font-semibold tabular-nums text-gray-700"
                                  x-text="money(sourceYearTotal(source)) + ' / ' + year"></span>
                        </div>
                    </button>

                    {{-- Ve sbalené hlavičce zůstává jen rozbalení. Přesouvání a mazání
                         je uvnitř, kde se na ně dá na mobilu pořádně trefit. --}}
                    <button type="button" @click="openSource = openSource === source.id ? null : source.id"
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-md text-gray-400 transition hover:bg-gray-50"
                            :aria-expanded="openSource === source.id" aria-label="Rozbalit zdroj">
                        <svg class="h-5 w-5 transition-transform" :class="openSource === source.id ? 'rotate-180' : ''"
                             fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m6 9 6 6 6-6"/></svg>
                    </button>
                </div>

                {{-- Detail --}}
                <div x-show="openSource === source.id" x-collapse>
                    <div class="space-y-3 border-t border-gray-100 p-3">
                        <div>
                            <span class="{{ $label }}">Název zdroje</span>
                            <input type="text" x-model="source.name" @input="touch()" aria-label="Název zdroje"
                                   class="{{ $input }} font-medium">
                        </div>

                        <div>
                            <span class="{{ $label }}">Klient z fakturace</span>
                            <select x-model="source.clientId" @change="touch()" class="{{ $input }}">
                                {{-- Prázdný řetězec, ne :value="null" — to by do modelu uložilo text „null“. --}}
                                <option value="">Nenapojeno na fakturaci</option>
                                @foreach ($clients as $client)
                                    <option value="{{ $client->id }}">{{ $client->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Způsob odměny --}}
                        <div class="flex flex-wrap gap-2">
                            @foreach (['rate' => 'Sazba', 'fixed' => 'Napevno', 'invoice' => 'Jednorázovka'] as $mode => $modeLabel)
                                <button type="button" @click="source.mode = '{{ $mode }}'; touch()"
                                        :aria-pressed="source.mode === '{{ $mode }}'"
                                        :class="source.mode === '{{ $mode }}' ? 'border-brand bg-brand-light text-brand' : 'border-gray-200 bg-white text-gray-600 hover:bg-gray-50'"
                                        class="shrink-0 rounded-full border px-3 py-1.5 text-xs font-medium transition">{{ $modeLabel }}</button>
                            @endforeach
                        </div>

                        {{-- Sazba --}}
                        <div x-show="source.mode === 'rate'" class="space-y-3">
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <span class="{{ $label }}">Sazba za jednotku</span>
                                    <input type="number" x-model.number="source.rate" @input="touch()" min="0" step="50" class="{{ $input }}">
                                </div>
                                <div>
                                    <span class="{{ $label }}">Jednotka</span>
                                    <select x-model="source.unit" @change="touch()" class="{{ $input }}">
                                        <option value="h">hodina</option>
                                        <option value="md">MD (den)</option>
                                    </select>
                                </div>
                            </div>
                            <div x-show="source.unit === 'h'">
                                <span class="{{ $label }}">Hodin denně</span>
                                <input type="number" x-model.number="source.hoursPerDay" @input="touch()" min="0.5" max="24" step="0.5" class="{{ $input }}">
                            </div>
                        </div>

                        {{-- Pevná částka --}}
                        <div x-show="source.mode === 'fixed'">
                            <span class="{{ $label }}">Částka za měsíc</span>
                            <input type="number" x-model.number="source.fixed" @input="touch()" min="0" step="1000" class="{{ $input }}">
                            <p class="mt-1 text-[11px] text-gray-400">Rozpustí se do dnů, kdy pro klienta pracuješ — dovolená ji nesnižuje.</p>
                        </div>

                        {{-- Jednorázová faktura --}}
                        <div x-show="source.mode === 'invoice'" class="space-y-3">
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <div>
                                    <span class="{{ $label }}">Datum připsání na účet</span>
                                    <input type="date" x-model="source.date" @input="touch()"
                                           min="{{ $years['prev'] }}-01-01" max="{{ $years['next'] }}-12-31" class="{{ $input }}">
                                </div>
                                <div>
                                    <span class="{{ $label }}">Částka</span>
                                    <input type="number" x-model.number="source.amount" @input="touch()" min="0" step="1000" class="{{ $input }}">
                                </div>
                            </div>
                            <p class="rounded-xl bg-gray-50 px-3 py-2 text-[11px] leading-relaxed text-gray-500">
                                Na účet dorazí celá částka v jeden den. V kalendáři se ale rozpočítá na pracovní dny toho měsíce,
                                aby bylo vidět, za co sis ji vydělal.
                            </p>
                        </div>

                        {{-- Rozsah, týden a platba --}}
                        <div x-show="source.mode !== 'invoice'" class="space-y-3">
                            <div>
                                <span class="{{ $label }}">Které dny v týdnu pracuješ</span>
                                <div class="mt-1 flex gap-1">
                                    <template x-for="(name, index) in WEEKDAYS_SHORT" :key="index">
                                        <button type="button" @click="toggleWorkDay(source, index + 1)"
                                                :aria-pressed="source.workDays.includes(index + 1)"
                                                :class="source.workDays.includes(index + 1)
                                                    ? 'border-brand bg-brand-light text-brand'
                                                    : 'border-gray-200 bg-white text-gray-400 hover:bg-gray-50'"
                                                class="h-9 flex-1 rounded-md border text-xs font-semibold transition"
                                                x-text="name"></button>
                                    </template>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <div>
                                    <span class="{{ $label }}">Od</span>
                                    <input type="date" x-model="source.from" @input="touch()"
                                           min="{{ $years['prev'] }}-01-01" max="{{ $years['next'] }}-12-31" class="{{ $input }}">
                                </div>
                                <div>
                                    <span class="{{ $label }}">Do — nepovinné</span>
                                    <input type="date" x-model="source.to" @input="touch()"
                                           min="{{ $years['prev'] }}-01-01" max="{{ $years['next'] }}-12-31" class="{{ $input }}">
                                </div>
                            </div>

                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <div>
                                    <span class="{{ $label }}">Zpoždění platby</span>
                                    <select x-model.number="source.lag" @change="touch()" class="{{ $input }}">
                                        <option value="0">Ve stejném měsíci, kdy pracuju</option>
                                        <option value="1">O měsíc později</option>
                                        <option value="2">O 2 měsíce (fakturuji až příští měsíc)</option>
                                        <option value="3">O 3 měsíce později</option>
                                    </select>
                                </div>
                                <div>
                                    <span class="{{ $label }}">Den, kdy platba dorazí</span>
                                    <select x-model.number="source.payDay" @change="touch()" class="{{ $input }}">
                                        @foreach ([1, 5, 10, 14, 15, 20, 25, 31] as $day)
                                            <option value="{{ $day }}">{{ $day === 31 ? 'poslední den v měsíci' : $day.'. den' }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="rounded-xl bg-gray-50 px-3 py-2 text-[11px] leading-relaxed text-gray-500">
                                Dovolená se zadává v kalendáři výše — klikáním na konkrétní dny.
                                <span class="font-medium text-gray-700"
                                      x-text="days(source.vacationDays.filter(d => d.startsWith(year + '-')).length) + ' v roce ' + year"></span>.
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-2 border-t border-gray-100 pt-3">
                            <button type="button" @click="moveSource(source.id, -1)" :disabled="index === 0"
                                    class="flex h-10 min-w-10 flex-1 items-center justify-center gap-1.5 rounded-md border border-gray-200 px-3 text-xs font-medium text-gray-600 transition disabled:opacity-30 hover:bg-gray-50 sm:flex-none">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m18 15-6-6-6 6"/></svg>
                                Nahoru
                            </button>
                            <button type="button" @click="moveSource(source.id, 1)" :disabled="index === sources.length - 1"
                                    class="flex h-10 min-w-10 flex-1 items-center justify-center gap-1.5 rounded-md border border-gray-200 px-3 text-xs font-medium text-gray-600 transition disabled:opacity-30 hover:bg-gray-50 sm:flex-none">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m6 9 6 6 6-6"/></svg>
                                Dolů
                            </button>
                            <button type="button" @click="duplicateSource(source.id)"
                                    class="flex h-10 flex-1 items-center justify-center rounded-md border border-gray-200 px-3 text-xs font-medium text-gray-600 transition hover:bg-gray-50 sm:flex-none">
                                Duplikovat
                            </button>
                            <button type="button" @click="removeSource(source.id)"
                                    class="flex h-10 flex-1 items-center justify-center rounded-md border border-gray-200 px-3 text-xs font-medium text-red-600 transition hover:border-red-300 hover:bg-red-50 sm:flex-none">
                                Smazat
                            </button>
                        </div>

                        {{-- Skutečnost u napojeného klienta --}}
                        <template x-if="source.clientId && results">
                            <div class="rounded-xl border border-dashed border-blue-200 bg-blue-50/50 px-3 py-2 text-[11px] leading-relaxed text-gray-600">
                                <span class="font-semibold text-blue-700">
                                    Faktury u klienta <span x-text="clientName(source.clientId)"></span> v roce <span x-text="year"></span>:
                                </span>
                                <span x-text="(() => {
                                    const months = actuals.byClient[source.clientId] ?? {};
                                    let received = 0, expected = 0, issued = 0;
                                    for (let m = 0; m < 12; m++) {
                                        const b = months[year + '-' + m];
                                        if (!b) continue;
                                        received += b.received ?? 0; expected += b.expected ?? 0; issued += b.issued ?? 0;
                                    }
                                    return issued === 0
                                        ? 'zatím žádné'
                                        : money(issued) + ' vystaveno · ' + money(received) + ' uhrazeno' + (expected > 0 ? ' · ' + money(expected) + ' čeká' : '');
                                })()"></span>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <button type="button" @click="addSource()"
            class="mt-2 w-full rounded-2xl border border-dashed border-gray-300 bg-white px-4 py-3 text-sm font-semibold text-brand transition active:bg-gray-50 lg:rounded-lg lg:hover:bg-gray-50">
        + Přidat zdroj
    </button>
</section>
