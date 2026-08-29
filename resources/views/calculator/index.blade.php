@php
    $input = 'mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm';
    $heading = 'text-xs font-bold uppercase tracking-wide text-gray-500';
@endphp

<x-app-layout title="KALKULAČKA" :back-url="route('dashboard')" active="home">
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Kalkulačka příjmů</h2>
            <span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold uppercase tracking-wide text-amber-700">Beta</span>
        </div>
    </x-slot>

    <x-page class="space-y-4 px-4 py-4 lg:space-y-6 lg:py-12">
        <x-panel>
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <h1 class="text-lg font-semibold text-gray-900">Příjmy v čase a co z nich zbyde</h1>
                    <p class="mt-1 text-sm text-gray-500">
                        Plán se ukládá k tvému účtu. Ke každému zdroji můžeš přiřadit klienta z fakturace — pak se vedle plánu ukazuje i to, co jsi u něj skutečně vyfakturoval.
                    </p>
                </div>
                <span class="shrink-0 rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-amber-700 lg:hidden">Beta</span>
            </div>
            <div class="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-gray-100 pt-3">
                <span class="text-xs text-gray-500" id="saveStatus">Načteno</span>
                <button type="button" id="resetBtn" class="text-xs font-medium text-brand hover:underline">Začít znovu podle klientů</button>
            </div>
        </x-panel>

        {{-- Zdroje příjmu --}}
        <section>
            <h2 class="{{ $heading }} mb-2">Zdroje příjmu</h2>
            <div id="sources" class="space-y-3"></div>
            <button type="button" id="addSrc" class="mt-3 w-full rounded-2xl border border-dashed border-gray-300 bg-white px-4 py-3 text-sm font-semibold text-brand active:bg-gray-50 lg:rounded-lg lg:hover:bg-gray-50">
                + Přidat zdroj
            </button>
        </section>

        {{-- Kolik ještě dorazí --}}
        <section>
            <h2 class="{{ $heading }} mb-2" id="remainingLabel">Čistého ještě dorazí do konce roku</h2>
            <x-panel>
                <div class="text-3xl font-bold tabular-nums text-gray-900" id="remainingBig">—</div>
                <div id="remainingRows"></div>
                <div id="remainingPlan"></div>
                <p class="mt-3 rounded-xl bg-gray-50 px-3 py-2 text-xs leading-relaxed text-gray-500" id="remainingNote"></p>
            </x-panel>
        </section>

        {{-- Přechod z minulého roku --}}
        <section>
            <h2 class="{{ $heading }} mb-2">Přechod z roku {{ $years['prev'] }}</h2>
            <x-panel>
                <p class="rounded-xl bg-gray-50 px-3 py-2 text-xs leading-relaxed text-gray-500" id="autoCarryNote">—</p>
                <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div>
                        <x-input-label for="carryAmount" value="Ruční korekce (+ nebo −)" />
                        <input type="number" id="carryAmount" value="0" step="1000" class="{{ $input }}">
                    </div>
                    <div>
                        <x-input-label for="carryMonth" value="Kdy dorazí" />
                        <select id="carryMonth" class="{{ $input }}">
                            <option value="0" selected>Leden {{ $years['base'] }}</option>
                            <option value="1">Únor {{ $years['base'] }}</option>
                            <option value="2">Březen {{ $years['base'] }}</option>
                        </select>
                    </div>
                </div>
                <p class="mt-3 text-xs leading-relaxed text-gray-500">
                    Automaticky se přenáší jen práce, kterou zdroj podle svého data „Od“ opravdu odvedl na konci roku {{ $years['prev'] }}. Když zdroj startuje až v roce {{ $years['base'] }}, přičti si sem ručně, co ti ještě dorazí za prosinec {{ $years['prev'] }}.
                </p>
            </x-panel>
        </section>

        {{-- Rok po měsících --}}
        <section>
            <div class="mb-2 flex items-center justify-between gap-2">
                <h2 class="{{ $heading }}" id="calLabel">Rok po měsících</h2>
                <div class="flex gap-2" id="yearToggle">
                    <button type="button" data-year="{{ $years['base'] }}" aria-pressed="true" class="shrink-0 rounded-full border px-3 py-1.5 text-xs font-medium">{{ $years['base'] }}</button>
                    <button type="button" data-year="{{ $years['next'] }}" aria-pressed="false" class="shrink-0 rounded-full border px-3 py-1.5 text-xs font-medium">{{ $years['next'] }} · prognóza</button>
                </div>
            </div>
            <x-panel>
                <div id="cal" class="divide-y divide-gray-100"></div>
                <div class="mt-3 flex flex-wrap gap-x-4 gap-y-1 text-[11px] text-gray-500">
                    <span class="inline-flex items-center gap-1.5"><i class="inline-block h-2.5 w-2.5 rounded-sm bg-amber-400"></i>plán ten měsíc</span>
                    <span class="inline-flex items-center gap-1.5"><i class="inline-block h-2.5 w-2.5 rounded-sm bg-emerald-500"></i>kumulativně (strop 2 mil.)</span>
                    <span class="inline-flex items-center gap-1.5"><i class="inline-block h-2.5 w-2.5 rounded-sm bg-blue-500"></i>vyfakturováno ten měsíc</span>
                </div>
                <div id="reality"></div>
                <div id="cross"></div>
                <div id="overflow"></div>
            </x-panel>
        </section>

        {{-- Nastavení --}}
        <section>
            <h2 class="{{ $heading }} mb-2">Nastavení činnosti a výdajů</h2>
            <x-panel>
                <div>
                    <x-input-label value="Daňový režim" />
                    <div class="mt-1 flex flex-wrap gap-2" id="regimeToggle">
                        <button type="button" data-regime="auto"   aria-pressed="true"  class="shrink-0 rounded-full border px-3 py-1.5 text-xs font-medium">Automaticky</button>
                        <button type="button" data-regime="pausal" aria-pressed="false" class="shrink-0 rounded-full border px-3 py-1.5 text-xs font-medium">Paušální daň</button>
                        <button type="button" data-regime="klasik" aria-pressed="false" class="shrink-0 rounded-full border px-3 py-1.5 text-xs font-medium">Klasické přiznání</button>
                    </div>
                </div>

                <label class="mt-4 flex cursor-pointer items-center gap-3 text-sm text-gray-700" for="sideAct">
                    <input type="checkbox" id="sideAct" class="h-5 w-5 rounded border-gray-300 text-brand focus:ring-brand">
                    <span>Vedlejší činnost (důchodce, student, rodičovská, zaměstnání vedle OSVČ)</span>
                </label>

                <div class="mt-4">
                    <x-input-label for="activity" value="Aspoň 75 % příjmů plyne z" />
                    <select id="activity" class="{{ $input }}">
                        <option value="80">Řemeslná živnost, zemědělství — paušál 80 %</option>
                        <option value="60" selected>Volná či vázaná živnost, IT — paušál 60 %</option>
                        <option value="40">Svobodné povolání, autorské honoráře — paušál 40 %</option>
                    </select>
                </div>

                <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div>
                        <x-input-label for="expMode" value="Výdaje uplatním" />
                        <select id="expMode" class="{{ $input }}">
                            <option value="pausal" selected>Procentem z příjmů</option>
                            <option value="real">Skutečné</option>
                        </select>
                    </div>
                    <div id="expWrap" class="hidden">
                        <x-input-label for="expReal" value="Skutečné výdaje za rok" />
                        <input type="number" id="expReal" value="0" min="0" step="10000" class="{{ $input }}">
                    </div>
                </div>

                <p class="mt-3 text-xs leading-relaxed text-gray-500">
                    Výdaje se použijí jen v klasickém přiznání. „Automaticky“ drží paušální daň do 2 milionů a nad stropem přepne na klasické přiznání.
                </p>
            </x-panel>
        </section>

        {{-- Pásmo paušální daně --}}
        <section id="bandSection">
            <h2 class="{{ $heading }} mb-2">Pásmo paušální daně</h2>
            <x-panel :padded="false" class="overflow-hidden">
                <div id="ladder" class="divide-y divide-gray-100"></div>
            </x-panel>
        </section>

        {{-- Výsledek --}}
        <section>
            <h2 class="{{ $heading }} mb-2" id="resultLabel">Zbyde ti za rok</h2>
            <x-panel id="result">
                <div class="text-3xl font-bold tabular-nums text-gray-900" id="netYear">—</div>
                <div id="rows"></div>
                <p class="mt-3 rounded-xl bg-gray-50 px-3 py-2 text-xs leading-relaxed text-gray-500" id="resultNote"></p>
            </x-panel>
        </section>

        <details class="rounded-2xl border border-gray-200 bg-white px-4 py-3 lg:rounded-lg lg:border-0 lg:shadow-sm lg:px-6">
            <summary class="cursor-pointer text-sm font-semibold text-gray-700">Jak se to počítá a co je zatím v betě</summary>
            <div class="mt-3 space-y-3 text-xs leading-relaxed text-gray-500">
                <p><span class="font-medium text-gray-700">Beta:</span> výpočet i napojení na fakturaci se ještě usazují. Ber čísla jako orientační, není to daňové poradenství.</p>
                <p><span class="font-medium text-gray-700">Ukládání:</span> plán se ukládá k tvému účtu na server, takže ho najdeš i na jiném zařízení. Faktury se z něj nijak nemění — kalkulačka z nich jen čte.</p>
                <p><span class="font-medium text-gray-700">Skutečnost vs. plán:</span> „skutečnost“ jsou tvoje faktury a počítají se do měsíce, kdy byly <em>vystavené</em> — to je měsíc, za který sis vydělal. Podle splatnosti to nejde: aplikace ji sama nastavuje na 15. den následujícího měsíce, takže by červnová faktura spadla do července. Plán oproti tomu sleduje, kdy peníze dorazí na účet, takže se po měsících liší o zpoždění platby.</p>
                <p><span class="font-medium text-gray-700">Kdy se příjem počítá:</span> rozhoduje datum, kdy peníze fakticky dorazí na účet — ne datum faktury. Každý zdroj má proto zpoždění platby a den v měsíci, kdy platba chodí.</p>
                <p><span class="font-medium text-gray-700">Od / Do:</span> „Do“ je nepovinné — když ho necháš prázdné, spolupráce běží dál i do dalších let a prognóza s ní počítá celý rok.</p>
                <p><span class="font-medium text-gray-700">Prognóza {{ $years['next'] }}:</span> předpokládá stejnou sazbu, harmonogram i dovolenou jako v {{ $years['base'] }}. Jednorázové faktury se neopakují. Oficiální sazby paušální daně pro {{ $years['next'] }} zatím nejsou zveřejněné, použity jsou částky {{ $years['base'] }} jako odhad.</p>
                <p><span class="font-medium text-gray-700">Pracovní dny se počítají samy</span> podle víkendů a státních svátků ({{ $years['prev'] }}–{{ $years['next'] }}, včetně posunutých Velikonoc). Dovolenou u klienta zadáš ručně po měsících.</p>
                <p><span class="font-medium text-gray-700">Paušální daň {{ $years['base'] }}:</span> 1. pásmo 9 162 Kč, 2. pásmo 16 745 Kč, 3. pásmo 27 139 Kč měsíčně, splatnost do 20. dne měsíce.</p>
                <p><span class="font-medium text-gray-700">Nad 2 miliony:</span> daň 15 % a nad 1 762 812 Kč základu 23 %, sleva na poplatníka 30 840 Kč, sociální 29,2 % z 55 % základu (strop 2 350 416 Kč), zdravotní 13,5 % z 50 % základu. Paušální výdaje mají strop — 1,2 mil. u 60 %, 1,6 mil. u 80 %, 800 tis. u 40 %.</p>
                <p><span class="font-medium text-gray-700">Vedlejší činnost:</span> u klasického přiznání se sociální pojištění platí jen ze zisku nad rozhodnou částku 111 736 Kč ({{ $years['base'] }}); pod ní je sociální nulové. Paušální daň je určená jen pro hlavní činnost.</p>
            </div>
        </details>
    </x-page>

    @include('calculator._script')
</x-app-layout>
