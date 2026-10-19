/**
 * Alpine komponenta kalkulačky příjmů.
 *
 * Stav drží jedno místo a po každé změně se celý rok přepočítá nanovo —
 * výpočet nad třemi roky trvá jednotky milisekund, takže není důvod držet
 * mezivýsledky a riskovat, že se rozejdou s tím, co je na obrazovce.
 */

import {
    MONTHS,
    MONTHS_IN,
    MONTHS_SHORT,
    WEEKDAYS_SHORT,
    formatDayLong,
    fromISO,
    monthDays,
    toISO,
    weekdayIndex,
} from './calendar.js';
import {
    DEFAULT_WORK_DAYS,
    cashYear,
    collectPayments,
    crossingMonth,
    dailyBreakdown,
    sourceMonthAmount,
} from './engine.js';
import { M, bandCeilings, remainingLevy, resolveTax, rulesFor } from './tax.js';
import { formatCompact, formatHours, formatMoney, formatNumber, formatPercent, formatSigned, plural } from './format.js';

const todayISO = () => toISO(new Date());

export default function incomeCalculator(boot) {
    return {
        // ---- data ze serveru ----
        clients: boot.clients ?? [],
        actuals: boot.actuals ?? { byClient: {}, totals: {} },
        years: boot.years,
        saveUrl: boot.saveUrl,
        csrf: boot.csrf,

        // ---- stav ----
        sources: [],
        regime: 'auto',
        sideActivity: false,
        activity: '60',
        pausalMonths: 12,
        expMode: 'pausal',
        expReal: 0,
        carryAmount: 0,
        carryMonth: 0,

        // ---- ovládání pohledu ----
        year: boot.years.base,
        calendarMonth: new Date().getFullYear() === boot.years.base ? new Date().getMonth() : 0,
        selectedDay: null,
        vacationMode: false,
        openSource: null,
        showSettings: false,
        compareBasis: 'cash',

        // ---- odvozené ----
        results: null,
        saveStatus: 'Načteno',
        saveTimer: null,
        ready: false,

        // ---- konstanty pro šablonu ----
        MONTHS,
        MONTHS_SHORT,
        WEEKDAYS_SHORT,
        today: todayISO(),

        init() {
            this.sources = (boot.state.sources ?? []).map((source) => this.normalizeSource(source));
            this.regime = boot.state.regime ?? 'auto';
            this.sideActivity = !!boot.state.sideActivity;
            this.activity = String(boot.state.activity ?? '60');
            this.pausalMonths = boot.state.pausalMonths ?? 12;
            this.expMode = boot.state.expMode ?? 'pausal';
            this.expReal = boot.state.expReal ?? 0;
            this.carryAmount = boot.state.carryAmount ?? 0;
            this.carryMonth = boot.state.carryMonth ?? 0;

            if (!this.sources.length) {
                this.addSource();
            }

            this.recalc();
            this.ready = true;
        },

        // ================= zdroje =================

        normalizeSource(source) {
            return {
                id: source.id ?? Math.random().toString(36).slice(2),
                // Hodnoty <select> jsou řetězce, takže i nenapojený zdroj drží řetězec —
                // jinak by x-model nenašel shodu a vybral by se první klient v seznamu.
                clientId: source.clientId == null ? '' : String(source.clientId),
                name: source.name ?? 'Zdroj',
                mode: source.mode ?? 'rate',
                rate: +source.rate || 0,
                unit: source.unit ?? 'h',
                hoursPerDay: +source.hoursPerDay || 8,
                workDays: (source.workDays ?? DEFAULT_WORK_DAYS).map(Number),
                // Zpoždění 0 je platná hodnota, takže se nedá použít ||.
                lag: source.lag == null ? 2 : Math.max(0, Math.min(3, +source.lag || 0)),
                payDay: +source.payDay || 15,
                from: source.from ?? '',
                to: source.to ?? '',
                fixed: +source.fixed || 0,
                date: source.date ?? '',
                amount: +source.amount || 0,
                vacationDays: [...(source.vacationDays ?? [])],
            };
        },

        addSource(preset = {}) {
            const index = this.sources.length;

            this.sources.push(this.normalizeSource({
                id: `new-${Date.now()}-${index}`,
                name: `Zdroj ${index + 1}`,
                mode: 'rate',
                rate: 1200,
                unit: 'h',
                hoursPerDay: 8,
                lag: 2,
                payDay: 15,
                from: `${this.years.base}-01-01`,
                to: '',
                fixed: 40000,
                date: `${this.years.base}-06-15`,
                amount: 50000,
                vacationDays: [],
                ...preset,
            }));

            this.openSource = this.sources[this.sources.length - 1].id;
            this.touch();
        },

        removeSource(uid) {
            this.sources = this.sources.filter((s) => s.id !== uid);

            if (!this.sources.length) {
                this.addSource();
                return;
            }

            this.touch();
        },

        duplicateSource(uid) {
            const original = this.sources.find((s) => s.id === uid);

            if (!original) return;

            const copy = this.normalizeSource({
                ...original,
                id: `new-${Date.now()}`,
                name: `${original.name} (kopie)`,
            });

            this.sources.splice(this.sources.indexOf(original) + 1, 0, copy);
            this.touch();
        },

        moveSource(uid, offset) {
            const index = this.sources.findIndex((s) => s.id === uid);
            const target = index + offset;

            if (index < 0 || target < 0 || target >= this.sources.length) return;

            const [source] = this.sources.splice(index, 1);
            this.sources.splice(target, 0, source);
            this.touch();
        },

        resetToClients() {
            this.sources = [];

            if (this.clients.length) {
                this.clients.forEach((client, index) => {
                    this.sources.push(this.normalizeSource({
                        id: `new-${index}`,
                        clientId: client.id,
                        name: client.name,
                        rate: 1200,
                        from: `${this.years.base}-01-01`,
                    }));
                });
            } else {
                this.sources.push(this.normalizeSource({ id: 'new-0', name: 'Klient A', rate: 1200, from: `${this.years.base}-01-01` }));
            }

            this.regime = 'auto';
            this.sideActivity = false;
            this.activity = '60';
            this.pausalMonths = 12;
            this.expMode = 'pausal';
            this.expReal = 0;
            this.carryAmount = 0;
            this.carryMonth = 0;
            this.openSource = null;
            this.touch();
        },

        toggleWorkDay(source, day) {
            const days = new Set(source.workDays);

            days.has(day) ? days.delete(day) : days.add(day);

            // Zdroj bez jediného pracovního dne by tiše vydělával nulu.
            if (days.size) {
                source.workDays = [...days].sort((a, b) => a - b);
                this.touch();
            }
        },

        sourceLabel(source) {
            if (source.mode === 'invoice') return 'Jednorázová faktura';
            if (source.mode === 'fixed') return 'Pevná měsíční částka';

            return source.unit === 'h' ? 'Hodinová sazba' : 'Denní sazba (MD)';
        },

        clientName(clientId) {
            return this.clients.find((c) => String(c.id) === String(clientId))?.name ?? null;
        },

        // ================= dovolená =================

        isVacation(source, iso) {
            return source.vacationDays.includes(iso);
        },

        toggleVacation(source, iso) {
            const index = source.vacationDays.indexOf(iso);

            index === -1 ? source.vacationDays.push(iso) : source.vacationDays.splice(index, 1);
            source.vacationDays.sort();
            this.touch();
        },

        /** Dovolená všech zdrojů v daném roce — pro přehled nad kalendářem. */
        vacationCount(year) {
            const days = new Set();

            for (const source of this.sources) {
                for (const day of source.vacationDays) {
                    if (day.startsWith(`${year}-`)) days.add(day);
                }
            }

            return days.size;
        },

        clearVacation(year) {
            for (const source of this.sources) {
                source.vacationDays = source.vacationDays.filter((day) => !day.startsWith(`${year}-`));
            }

            this.touch();
        },

        // ================= přepočet =================

        taxOptions(year, income) {
            return {
                year,
                income,
                regime: this.regime,
                activity: +this.activity,
                expenseMode: this.expMode,
                realExpenses: +this.expReal || 0,
                sideActivity: this.sideActivity,
                pausalMonths: +this.pausalMonths,
            };
        },

        recalc() {
            const { prev, base, next } = this.years;
            const span = [prev, base, next];
            const carry = +this.carryAmount
                ? { amount: +this.carryAmount, year: base, month: +this.carryMonth }
                : null;

            const payments = collectPayments(this.sources, span, carry);
            const cash = cashYear(this.year, payments);
            const breakdown = dailyBreakdown(this.sources, this.year);
            const tax = resolveTax(this.taxOptions(this.year, cash.total));
            const ceilings = bandCeilings(rulesFor(this.year), +this.activity);

            this.results = {
                payments,
                cash,
                breakdown,
                tax,
                ceilings,
                crossing: crossingMonth(cash.cumulative, M * 2),
                actual: this.actualSeries(this.year),
                remaining: this.remaining(payments, base),
                autoCarry: payments
                    .filter((p) => p.kind === 'work' && p.workYear === prev && p.year === base)
                    .reduce((sum, p) => sum + p.amount, 0),
            };
        },

        /** Skutečnost z faktur po měsících — v obou pohledech. */
        actualSeries(year) {
            const received = new Array(12).fill(0);
            const expected = new Array(12).fill(0);
            const issued = new Array(12).fill(0);

            for (let month = 0; month < 12; month++) {
                const bucket = this.actuals.totals[`${year}-${month}`];

                if (!bucket) continue;

                received[month] = bucket.received ?? 0;
                expected[month] = bucket.expected ?? 0;
                issued[month] = bucket.issued ?? 0;
            }

            const sum = (list) => list.reduce((a, b) => a + b, 0);

            return {
                received,
                expected,
                issued,
                cash: received.map((value, i) => value + expected[i]),
                totals: {
                    received: sum(received),
                    expected: sum(expected),
                    issued: sum(issued),
                    cash: sum(received) + sum(expected),
                },
                any: sum(received) + sum(expected) + sum(issued) > 0,
            };
        },

        /** Co z letoška ještě dorazí a kolik z toho zbyde po odvodech. */
        remaining(payments, year) {
            const today = this.today;
            const upcoming = payments.filter((p) => p.year === year && p.iso >= today);
            const gross = upcoming.reduce((sum, p) => sum + p.amount, 0);
            const already = payments
                .filter((p) => p.year === year && p.iso < today)
                .reduce((sum, p) => sum + p.amount, 0);

            const { levy, rate, installments, monthly } = remainingLevy(this.taxOptions(year, 0), already, gross, today);
            const afterYearEnd = payments
                .filter((p) => p.workYear <= year && p.year > year)
                .reduce((sum, p) => sum + p.amount, 0);

            return {
                finished: today > `${year}-12-31`,
                upcoming,
                gross,
                already,
                levy,
                rate,
                installments,
                monthly,
                net: gross - levy,
                afterYearEnd,
                last: upcoming[upcoming.length - 1] ?? null,
            };
        },

        touch() {
            this.recalc();

            if (this.ready) {
                this.scheduleSave();
            }
        },

        setYear(year) {
            this.year = +year;
            this.selectedDay = null;
            this.calendarMonth = new Date().getFullYear() === this.year ? new Date().getMonth() : 0;
            this.recalc();
        },

        // ================= kalendář =================

        /** Mřížka měsíce: doplněná prázdnými buňkami tak, aby týden začínal v pondělí. */
        calendarGrid() {
            if (!this.results) return [];

            const cells = [];
            const first = new Date(this.year, this.calendarMonth, 1);

            for (let i = 0; i < weekdayIndex(first); i++) {
                cells.push(null);
            }

            for (const date of monthDays(this.year, this.calendarMonth)) {
                cells.push(this.results.breakdown.days.get(toISO(date)));
            }

            while (cells.length % 7 !== 0) {
                cells.push(null);
            }

            return cells;
        },

        monthBucket() {
            return this.results?.breakdown.months[this.calendarMonth] ?? null;
        },

        /** Nejsilnější den v měsíci — podle něj se škáluje sytost buněk. */
        monthPeak() {
            const bucket = this.monthBucket();

            return bucket ? Math.max(1, ...bucket.days.map((d) => d.amount)) : 1;
        },

        dayShade(day) {
            if (!day || !day.amount) return 0;

            return Math.min(1, day.amount / this.monthPeak());
        },

        dayClasses(day) {
            if (!day) return 'invisible';

            const classes = ['relative', 'rounded-lg', 'border', 'text-left', 'transition'];

            if (day.iso === this.selectedDay) {
                classes.push('border-brand', 'ring-2', 'ring-brand/30');
            } else if (day.iso === this.today) {
                classes.push('border-brand/60');
            } else {
                classes.push('border-transparent');
            }

            if (day.onVacation) {
                classes.push('bg-amber-100');
            } else if (day.amount > 0) {
                classes.push('bg-emerald-50');
            } else if (day.holiday) {
                classes.push('bg-rose-50');
            } else if (day.weekend) {
                classes.push('bg-gray-50');
            } else {
                classes.push('bg-white');
            }

            return classes.join(' ');
        },

        selectDay(day) {
            if (!day) return;

            this.selectedDay = this.selectedDay === day.iso ? null : day.iso;
        },

        selectedDayData() {
            return this.selectedDay ? this.results?.breakdown.days.get(this.selectedDay) ?? null : null;
        },

        selectedDayTitle() {
            const day = this.selectedDayData();

            return day ? formatDayLong(day.date) : '';
        },

        /** Kdy a s čím se za tenhle den dostaneš k penězům. */
        selectedDayPayment(entry) {
            const source = this.sources.find((s) => s.id === entry.id);

            if (!source || source.mode === 'invoice') return null;

            const payment = this.results.payments.find(
                (p) => p.sourceId === entry.id && p.workYear === this.year && p.workMonth === this.calendarMonth,
            );

            return payment ?? null;
        },

        shiftMonth(offset) {
            const next = this.calendarMonth + offset;

            if (next < 0 || next > 11) return;

            this.calendarMonth = next;
            this.selectedDay = null;
        },

        dayStatusLabel(status) {
            return {
                work: 'pracovní den',
                vacation: 'dovolená',
                holiday: 'státní svátek',
                off: 'nepracovní den',
                outside: 'mimo spolupráci',
            }[status] ?? status;
        },

        // ================= graf =================

        /**
         * Sloupcový graf měsíců.
         *
         * Plán i skutečnost se měří stejnou osou a stojí vedle sebe, ne přes
         * sebe — tři různě škálované pruhy v jednom řádku nešlo porovnat.
         * Kumulativní součet jde zvlášť jako čára, protože má úplně jiný rozsah
         * než jednotlivé měsíce.
         */
        chart() {
            if (!this.results) return null;

            const plan = this.results.cash.byMonth;
            const actual = this.results.actual.cash;
            const received = this.results.actual.received;
            const hasActual = this.results.actual.totals.cash > 0;
            const peak = Math.max(1, ...plan, ...actual);
            const cumulative = this.results.cash.cumulative;
            const cumulativePeak = Math.max(2 * M, ...cumulative);
            const nowMonth = new Date().getFullYear() === this.year ? new Date().getMonth() : -1;

            const bars = plan.map((value, index) => ({
                index,
                label: MONTHS_SHORT[index],
                plan: value,
                actual: actual[index],
                received: received[index],
                expected: this.results.actual.expected[index],
                planPct: (value / peak) * 100,
                actualPct: (actual[index] / peak) * 100,
                receivedShare: actual[index] > 0 ? (received[index] / actual[index]) * 100 : 0,
                cumulative: cumulative[index],
                isNow: index === nowMonth,
            }));

            // Čára jde přes vlastní SVG s viewBoxem 0 0 1200 100 a
            // preserveAspectRatio="none", takže se roztáhne přesně na šířku mřížky.
            const linePoints = cumulative
                .map((value, index) => {
                    const x = ((index + 0.5) / 12) * 1200;
                    const y = 100 - (value / cumulativePeak) * 100;

                    return `${x.toFixed(1)},${y.toFixed(2)}`;
                })
                .join(' ');

            return {
                bars,
                hasActual,
                peak,
                cumulativePeak,
                linePoints,
                ceilingPct: (2 * M / cumulativePeak) * 100,
                ceilingVisible: cumulativePeak >= 2 * M,
                gridlines: [1, 0.75, 0.5, 0.25].map((fraction) => ({
                    pct: fraction * 100,
                    label: formatCompact(peak * fraction),
                })),
            };
        },

        /** Postup k hranici dvou milionů — vodorovný pruh nad pásmy. */
        ceilingProgress() {
            if (!this.results) return 0;

            return Math.min(100, (this.results.cash.total / (2 * M)) * 100);
        },

        bandState(index) {
            if (!this.results) return 'ok';

            const tax = this.results.tax;

            if (tax.mode !== 'pausal') return 'blocked';
            if (index === tax.bandIndex) return 'active';

            return this.results.cash.total > this.results.ceilings[index] ? 'blocked' : 'ok';
        },

        // ================= ukládání =================

        serialize() {
            return {
                regime: this.regime,
                sideActivity: this.sideActivity,
                activity: String(this.activity),
                pausalMonths: Math.max(0, Math.min(12, Math.round(+this.pausalMonths || 0))),
                expMode: this.expMode,
                expReal: +this.expReal || 0,
                carryAmount: +this.carryAmount || 0,
                carryMonth: Math.max(0, Math.min(11, Math.round(+this.carryMonth || 0))),
                sources: this.sources.map((source) => ({
                    clientId: source.clientId === '' || source.clientId == null ? null : +source.clientId,
                    name: String(source.name || 'Zdroj').slice(0, 255),
                    mode: source.mode,
                    rate: +source.rate || 0,
                    unit: source.unit === 'md' ? 'md' : 'h',
                    hoursPerDay: Math.max(0.5, Math.min(24, +source.hoursPerDay || 8)),
                    workDays: source.workDays.length ? source.workDays : DEFAULT_WORK_DAYS,
                    lag: Math.max(0, Math.min(3, Math.round(+source.lag || 0))),
                    payDay: Math.max(1, Math.min(31, Math.round(+source.payDay || 15))),
                    from: source.from || null,
                    to: source.to || null,
                    fixed: +source.fixed || 0,
                    date: source.date || null,
                    amount: +source.amount || 0,
                    vacationDays: [...new Set(source.vacationDays)].sort().slice(0, 400),
                })),
            };
        },

        scheduleSave() {
            clearTimeout(this.saveTimer);
            this.saveStatus = 'Ukládání…';
            this.saveTimer = setTimeout(() => this.save(), 600);
        },

        async save() {
            try {
                const response = await fetch(this.saveUrl, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': this.csrf,
                    },
                    body: JSON.stringify(this.serialize()),
                });

                if (response.status === 422) {
                    this.saveStatus = 'Neuloženo — zkontroluj zadané hodnoty';
                    return;
                }

                // Vypršelá session končí přesměrováním na přihlášení. To vypadá
                // jako úspěch, dokud se člověk nepodívá, že odpověď není JSON.
                if (!response.ok || response.redirected || !(response.headers.get('content-type') ?? '').includes('application/json')) {
                    this.saveStatus = 'Neuloženo — načti stránku znovu a přihlas se';
                    return;
                }

                this.saveStatus = `Uloženo v ${(await response.json()).at ?? ''}`;
            } catch {
                this.saveStatus = 'Neuloženo — jsi offline?';
            }
        },

        // ================= formátování pro šablonu =================

        money: formatMoney,
        plural,

        /** „1 den dovolené“, ne „1 dnů dovolené“. */
        days(count) {
            return `${count} ${plural(count, 'den', 'dny', 'dnů')}`;
        },

        number: formatNumber,
        signed: formatSigned,
        compact: formatCompact,
        percent: formatPercent,
        hours: formatHours,

        monthName(index) {
            return MONTHS[index];
        },

        monthNameIn(index) {
            return MONTHS_IN[index];
        },

        /** Kolik zdroj vydělá za celý zvolený rok. */
        sourceYearTotal(source) {
            let total = 0;

            for (let month = 0; month < 12; month++) {
                total += sourceMonthAmount(source, this.year, month);
            }

            return total;
        },

        dateLabel(iso) {
            if (!iso) return '—';
            const date = fromISO(iso);
            return `${date.getDate()}. ${date.getMonth() + 1}.`;
        },

        get isForecast() {
            return this.year > this.years.base;
        },

        get yearRules() {
            return rulesFor(this.year);
        },
    };
}
