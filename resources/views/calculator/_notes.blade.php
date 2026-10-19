<details class="rounded-2xl border border-gray-200 bg-white px-4 py-3 lg:rounded-lg lg:border-0 lg:px-6 lg:shadow-sm">
    <summary class="cursor-pointer text-sm font-semibold text-gray-700">Jak se to počítá</summary>

    <div class="mt-3 space-y-3 text-xs leading-relaxed text-gray-500">
        <p>
            <span class="font-medium text-gray-700">Beta:</span>
            čísla ber jako orientační, není to daňové poradenství. Plán se ukládá k tvému účtu, faktury kalkulačka jen čte a nijak je nemění.
        </p>
        <p>
            <span class="font-medium text-gray-700">Základní jednotka je den.</span>
            Každý den se spočítá zvlášť podle toho, jestli pro daný zdroj pracuješ — rozhodují dny v týdnu, rozsah spolupráce, státní svátky a dovolená.
            Měsíc i rok jsou pak jen součty, takže se v kalendáři dá rozkliknout, z čeho se které číslo skládá.
        </p>
        <p>
            <span class="font-medium text-gray-700">Dovolená sedí na konkrétní datum.</span>
            Zadává se klikáním v kalendáři, ne jako počet dnů za měsíc. Díky tomu se do dalšího roku nepřenáší — rok {{ $years['next'] }} si naplánuješ zvlášť.
        </p>
        <p>
            <span class="font-medium text-gray-700">Příjem se počítá podle dne, kdy peníze dorazí na účet</span>, ne podle data faktury.
            Každý zdroj má proto zpoždění platby a den v měsíci, kdy klient platí. Práce z prosince se tak obvykle započte až do dalšího roku.
        </p>
        <p>
            <span class="font-medium text-gray-700">Plán vs. skutečnost</span> se dá porovnat dvěma způsoby.
            „Podle peněz“ řadí uhrazené faktury podle data úhrady a neuhrazené podle splatnosti — to je přímo srovnatelné s plánem.
            „Podle vystavení“ ukazuje, za který měsíc sis vydělal; proti plánu je posunuté o zpoždění platby.
        </p>
        <p>
            <span class="font-medium text-gray-700">Svátky se počítají</span>, ne vypisují — včetně pohyblivých Velikonoc, takže kalendář sedí i v letech, na které nikdo předem nemyslel.
        </p>
        <p>
            <span class="font-medium text-gray-700">Paušální daň {{ $years['base'] }}:</span>
            I. pásmo 9 162 Kč, II. pásmo 16 745 Kč, III. pásmo 27 139 Kč měsíčně, splatnost do 20. dne v měsíci.
            První pásmo se od 1. 7. 2026 zpětně snížilo z 9 984 Kč, takže za první pololetí vznikl přeplatek.
        </p>
        <p>
            <span class="font-medium text-gray-700">Klasické přiznání {{ $years['base'] }}:</span>
            daň 15 % a nad 1 762 812 Kč základu 23 %, sleva na poplatníka 30 840 Kč.
            Sociální 29,2 % z 55 % základu, zdravotní 13,5 % z 50 % základu.
            Paušální výdaje mají strop — 1,6 mil. u 80 %, 1,2 mil. u 60 %, 800 tis. u 40 %.
        </p>
        <p>
            <span class="font-medium text-gray-700">Minimální pojistné.</span>
            Na hlavní činnost se platí z minimálního vyměřovacího základu i v roce, kdy skoro nevyděláš —
            sociální z 205 668 Kč a zdravotní z 293 802 Kč ročně. Bez toho by nízké příjmy vycházely líp, než ve skutečnosti dopadnou.
        </p>
        <p>
            <span class="font-medium text-gray-700">Vedlejší činnost.</span>
            Sociální se platí až od zisku nad rozhodnou částku 117 521 Kč ({{ $years['base'] }}). Nad ní se ale platí z celého vyměřovacího základu, ne jen z částky nad hranicí —
            rozhodná částka je spínač, ne odpočet. Zdravotní u vedlejší činnosti minimum nemá. Paušální daň je určená jen pro hlavní činnost.
        </p>
        <p>
            <span class="font-medium text-gray-700">Prognóza {{ $years['next'] }}.</span>
            Předpokládá stejné sazby i harmonogram jako letos, jednorázové faktury se neopakují.
            Oficiální sazby pro {{ $years['next'] }} zatím nejsou vyhlášené, použité jsou částky {{ $years['base'] }} jako odhad — kalkulačka na to u výsledku upozorní.
        </p>
    </div>
</details>
