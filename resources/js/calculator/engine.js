/**
 * Výpočet příjmů po jednotlivých dnech.
 *
 * Původní verze počítala rovnou měsíční součty, takže se nedalo ukázat, z čeho
 * se měsíc skládá, a dovolená se zadávala jako „počet dnů“ bez vazby na datum.
 * Tady je základní jednotka den: měsíc i rok jsou jen jeho součty, kalendář má
 * co zobrazit a dovolená sedí na konkrétní datum.
 */

import { dayInMonth, daysInMonth, fromISO, holidayName, isWorkingDay, toISO, weekdayIndex } from './calendar.js';

export const DEFAULT_WORK_DAYS = [1, 2, 3, 4, 5];

/** Pracuje se pro tento zdroj v tenhle den v týdnu? Ukládá se 1 = pondělí … 7 = neděle. */
const worksOnWeekday = (source, date) => source.workDays.has(weekdayIndex(date) + 1);

const withinRange = (source, iso) => {
    if (source.from && iso < source.from) return false;
    if (source.to && iso > source.to) return false;
    return true;
};

/**
 * Zdroj připravený pro rychlé čtení po dnech.
 *
 * Dovolená i pracovní dny se převádějí na Set jednou za průchod, ne pro každý
 * den zvlášť. Dřív se Set cachoval na samotném zdroji podle identity pole —
 * jenže dovolená se přidává mutací téhož pole, takže cache zůstala stará
 * a výdělek se po zaškrtnutí dne nezměnil.
 */
export function compileSource(source) {
    if (source.__compiled) {
        return source;
    }

    return {
        __compiled: true,
        id: source.id,
        name: source.name,
        mode: source.mode,
        rate: +source.rate || 0,
        unit: source.unit,
        hoursPerDay: +source.hoursPerDay || 8,
        fixed: +source.fixed || 0,
        amount: +source.amount || 0,
        date: source.date,
        from: source.from,
        to: source.to,
        lag: source.lag,
        payDay: source.payDay,
        workDays: new Set(source.workDays ?? DEFAULT_WORK_DAYS),
        vacation: new Set(source.vacationDays ?? []),
    };
}

/**
 * Stav jednoho dne pro jeden zdroj.
 *
 * `status` říká, proč se ten den nevydělává — ať se v kalendáři dá rozlišit
 * dovolená od svátku a víkend od dne mimo spolupráci.
 */
export function dayForSource(input, date) {
    const source = compileSource(input);
    const iso = toISO(date);

    if (source.mode === 'invoice') {
        return { status: 'none', amount: 0, hours: 0 };
    }

    if (!withinRange(source, iso)) {
        return { status: 'outside', amount: 0, hours: 0 };
    }

    if (source.vacation.has(iso)) {
        return { status: 'vacation', amount: 0, hours: 0 };
    }

    const holiday = holidayName(date);

    if (holiday) {
        return { status: 'holiday', amount: 0, hours: 0, holiday };
    }

    if (!worksOnWeekday(source, date)) {
        return { status: 'off', amount: 0, hours: 0 };
    }

    if (source.mode === 'fixed') {
        // Paušál za měsíc se rozpouští do dnů, kdy se pro klienta pracuje —
        // takhle sedí jak měsíční součet, tak detail dne.
        const billable = billableDaysInMonth(source, date.getFullYear(), date.getMonth());

        return {
            status: 'work',
            amount: billable > 0 ? (+source.fixed || 0) / billable : 0,
            hours: +source.hoursPerDay || 0,
            prorated: true,
        };
    }

    const rate = +source.rate || 0;
    const hours = +source.hoursPerDay || 8;

    return {
        status: 'work',
        amount: source.unit === 'h' ? hours * rate : rate,
        hours: source.unit === 'h' ? hours : 1,
    };
}

/**
 * Kolik dnů v měsíci se pro zdroj reálně pracuje — ignoruje rozsah spolupráce,
 * protože slouží k rozpuštění měsíčního paušálu na celý měsíc.
 */
function billableDaysInMonth(source, year, month) {
    let count = 0;

    for (let d = 1; d <= daysInMonth(year, month); d++) {
        const date = new Date(year, month, d);

        if (isWorkingDay(date) && worksOnWeekday(source, date) && !source.vacation.has(toISO(date))) {
            count++;
        }
    }

    return count;
}

/**
 * Rozpad celého roku na dny.
 *
 * @return {{days: Map<string, object>, months: Array<object>, total: number}}
 */
export function dailyBreakdown(rawSources, year) {
    const sources = rawSources.map(compileSource);
    const days = new Map();
    const months = [];
    let total = 0;

    for (let month = 0; month < 12; month++) {
        const bucket = {
            month,
            year,
            amount: 0,
            workDays: 0,
            vacationDays: 0,
            holidayDays: 0,
            hours: 0,
            days: [],
        };

        for (let d = 1; d <= daysInMonth(year, month); d++) {
            const date = new Date(year, month, d);
            const iso = toISO(date);
            const entry = {
                iso,
                date,
                month,
                year,
                amount: 0,
                hours: 0,
                sources: [],
                holiday: holidayName(date),
                weekend: !isWorkingDay(date) && !holidayName(date),
                working: false,
                onVacation: false,
            };

            for (const source of sources) {
                const result = dayForSource(source, date);

                if (result.status === 'work') {
                    entry.amount += result.amount;
                    entry.hours += result.hours;
                    entry.working = true;
                } else if (result.status === 'vacation') {
                    entry.onVacation = true;
                }

                if (result.status !== 'none' && result.status !== 'outside') {
                    entry.sources.push({
                        id: source.id,
                        name: source.name,
                        status: result.status,
                        amount: result.amount,
                        hours: result.hours,
                        prorated: !!result.prorated,
                    });
                }
            }

            bucket.amount += entry.amount;
            bucket.hours += entry.hours;
            if (entry.working) bucket.workDays++;
            if (entry.onVacation) bucket.vacationDays++;
            if (entry.holiday) bucket.holidayDays++;

            bucket.days.push(entry);
            days.set(iso, entry);
        }

        total += bucket.amount;
        months.push(bucket);
    }

    return { days, months, total };
}

/** Kolik zdroj vydělá v daném měsíci. */
export function sourceMonthAmount(rawSource, year, month) {
    const source = compileSource(rawSource);
    let amount = 0;

    for (let d = 1; d <= daysInMonth(year, month); d++) {
        const result = dayForSource(source, new Date(year, month, d));

        if (result.status === 'work') {
            amount += result.amount;
        }
    }

    return amount;
}

/**
 * Kdy peníze dorazí na účet.
 *
 * Odvedená práce se fakturuje v měsíčním cyklu, takže se sečte za měsíc
 * a připíše se o `lag` měsíců později na zvolený den.
 */
export function collectPayments(rawSources, years, carry = null) {
    const payments = [];

    for (const source of rawSources.map(compileSource)) {
        if (source.mode === 'invoice') {
            const date = fromISO(source.date);

            if (!date) continue;

            payments.push({
                date,
                iso: toISO(date),
                year: date.getFullYear(),
                month: date.getMonth(),
                amount: +source.amount || 0,
                sourceId: source.id,
                name: source.name,
                kind: 'invoice',
                workYear: date.getFullYear(),
                workMonth: date.getMonth(),
            });

            continue;
        }

        const lag = Math.max(0, Math.min(3, +source.lag || 0));
        const payDay = +source.payDay || 15;

        for (const workYear of years) {
            for (let workMonth = 0; workMonth < 12; workMonth++) {
                const amount = sourceMonthAmount(source, workYear, workMonth);

                if (!amount) continue;

                const shifted = workMonth + lag;
                const year = workYear + Math.floor(shifted / 12);
                const month = ((shifted % 12) + 12) % 12;
                const date = dayInMonth(year, month, payDay);

                payments.push({
                    date,
                    iso: toISO(date),
                    year,
                    month,
                    amount,
                    sourceId: source.id,
                    name: source.name,
                    kind: 'work',
                    workYear,
                    workMonth,
                });
            }
        }
    }

    if (carry && carry.amount) {
        const date = dayInMonth(carry.year, carry.month, 15);

        payments.push({
            date,
            iso: toISO(date),
            year: carry.year,
            month: carry.month,
            amount: carry.amount,
            sourceId: null,
            name: 'Ruční korekce',
            kind: 'carry',
            workYear: carry.year - 1,
            workMonth: 11,
        });
    }

    return payments.sort((a, b) => a.date - b.date);
}

/** Co z plateb spadne do daného roku a co z něj naopak vypadne. */
export function cashYear(year, payments) {
    const byMonth = new Array(12).fill(0);
    let carriedIn = 0;
    let total = 0;

    for (const payment of payments) {
        if (payment.year !== year) continue;

        byMonth[payment.month] += payment.amount;
        total += payment.amount;

        if (payment.workYear < year) {
            carriedIn += payment.amount;
        }
    }

    const deferred = payments
        .filter((p) => p.workYear === year && p.year > year)
        .reduce((sum, p) => sum + p.amount, 0);

    const cumulative = [];
    let running = 0;

    for (const value of byMonth) {
        running += value;
        cumulative.push(running);
    }

    return { year, byMonth, cumulative, carriedIn, deferred, total };
}

/** Ve kterém měsíci kumulativní příjem překročí hranici. -1 když vůbec. */
export function crossingMonth(cumulative, threshold) {
    return cumulative.findIndex((value) => value > threshold);
}
