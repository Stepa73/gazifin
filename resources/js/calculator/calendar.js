/**
 * Český pracovní kalendář.
 *
 * Svátky se počítají, ne vypisují — hardcoded seznam platí vždy jen pro roky,
 * na které někdo myslel, a kalkulačka se dívá i do budoucna.
 */

export const MONTHS = ['Leden', 'Únor', 'Březen', 'Duben', 'Květen', 'Červen', 'Červenec', 'Srpen', 'Září', 'Říjen', 'Listopad', 'Prosinec'];
/** 6. pád — „překročíš v lednu“, ne „v leden“. */
export const MONTHS_IN = ['lednu', 'únoru', 'březnu', 'dubnu', 'květnu', 'červnu', 'červenci', 'srpnu', 'září', 'říjnu', 'listopadu', 'prosinci'];
export const MONTHS_SHORT = ['Led', 'Úno', 'Bře', 'Dub', 'Kvě', 'Čvn', 'Čvc', 'Srp', 'Zář', 'Říj', 'Lis', 'Pro'];
export const WEEKDAYS_SHORT = ['Po', 'Út', 'St', 'Čt', 'Pá', 'So', 'Ne'];
export const WEEKDAYS = ['Pondělí', 'Úterý', 'Středa', 'Čtvrtek', 'Pátek', 'Sobota', 'Neděle'];

/** Pevné svátky jako 'MM-DD'. */
const FIXED = {
    '01-01': 'Nový rok',
    '05-01': 'Svátek práce',
    '05-08': 'Den vítězství',
    '07-05': 'Cyril a Metoděj',
    '07-06': 'Jan Hus',
    '09-28': 'Den české státnosti',
    '10-28': 'Vznik Československa',
    '11-17': 'Den boje za svobodu',
    '12-24': 'Štědrý den',
    '12-25': '1. svátek vánoční',
    '12-26': '2. svátek vánoční',
};

export const pad = (n) => String(n).padStart(2, '0');

/** Datum jako 'YYYY-MM-DD' v místním čase — toISOString() by ujel o časové pásmo. */
export const toISO = (date) => `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;

export const fromISO = (iso) => (iso ? new Date(`${iso}T00:00:00`) : null);

/** Pondělí = 0 … neděle = 6. Date.getDay() má týden od neděle, což se tu plete. */
export const weekdayIndex = (date) => (date.getDay() + 6) % 7;

/** Velikonoční neděle podle gregoriánského výpočtu (Meeus/Jones/Butcher). */
export function easterSunday(year) {
    const a = year % 19;
    const b = Math.floor(year / 100);
    const c = year % 100;
    const d = Math.floor(b / 4);
    const e = b % 4;
    const f = Math.floor((b + 8) / 25);
    const g = Math.floor((b - f + 1) / 3);
    const h = (19 * a + b - d - g + 15) % 30;
    const i = Math.floor(c / 4);
    const k = c % 4;
    const l = (32 + 2 * e + 2 * i - h - k) % 7;
    const m = Math.floor((a + 11 * h + 22 * l) / 451);
    const month = Math.floor((h + l - 7 * m + 114) / 31);
    const day = ((h + l - 7 * m + 114) % 31) + 1;

    return new Date(year, month - 1, day);
}

const holidayCache = new Map();

/** @return {Map<string, string>} datum 'YYYY-MM-DD' → název svátku */
export function holidaysOf(year) {
    if (holidayCache.has(year)) {
        return holidayCache.get(year);
    }

    const map = new Map();

    for (const [md, name] of Object.entries(FIXED)) {
        map.set(`${year}-${md}`, name);
    }

    const easter = easterSunday(year);
    const goodFriday = new Date(easter);
    goodFriday.setDate(easter.getDate() - 2);
    const easterMonday = new Date(easter);
    easterMonday.setDate(easter.getDate() + 1);

    map.set(toISO(goodFriday), 'Velký pátek');
    map.set(toISO(easterMonday), 'Velikonoční pondělí');

    holidayCache.set(year, map);

    return map;
}

export const holidayName = (date) => holidaysOf(date.getFullYear()).get(toISO(date)) ?? null;
export const isHoliday = (date) => holidaysOf(date.getFullYear()).has(toISO(date));
export const isWeekend = (date) => date.getDay() === 0 || date.getDay() === 6;

/** Pracovní den podle kalendáře — bez ohledu na to, jestli zrovna pro někoho pracuješ. */
export const isWorkingDay = (date) => !isWeekend(date) && !isHoliday(date);

export const daysInMonth = (year, month) => new Date(year, month + 1, 0).getDate();

/** Všechny dny měsíce jako pole Date. */
export function monthDays(year, month) {
    const total = daysInMonth(year, month);
    const days = [];

    for (let d = 1; d <= total; d++) {
        days.push(new Date(year, month, d));
    }

    return days;
}

/** Všechny dny roku jako pole Date. */
export function yearDays(year) {
    const days = [];

    for (let m = 0; m < 12; m++) {
        days.push(...monthDays(year, m));
    }

    return days;
}

/** Den v měsíci, osekaný na jeho délku — 31. v únoru je 28. nebo 29. */
export function dayInMonth(year, month, day) {
    return new Date(year, month, Math.min(Math.max(1, day), daysInMonth(year, month)));
}

export const formatDate = (iso) => {
    if (!iso) return '—';
    const [y, m, d] = iso.split('-');
    return `${+d}. ${+m}. ${y}`;
};

export const formatDayLong = (date) =>
    `${WEEKDAYS[weekdayIndex(date)]} ${date.getDate()}. ${date.getMonth() + 1}. ${date.getFullYear()}`;
