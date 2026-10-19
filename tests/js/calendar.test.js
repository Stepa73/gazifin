import { describe, expect, it } from 'vitest';
import {
    easterSunday,
    holidayName,
    isWorkingDay,
    toISO,
    weekdayIndex,
    dayInMonth,
} from '../../resources/js/calculator/calendar.js';

describe('velikonoce', () => {
    it.each([
        [2025, '2025-04-20'],
        [2026, '2026-04-05'],
        [2027, '2027-03-28'],
        [2030, '2030-04-21'],
    ])('neděle %i vychází na %s', (year, iso) => {
        expect(toISO(easterSunday(year))).toBe(iso);
    });

    it('velký pátek a pondělí jsou svátky i v roce, na který nikdo nemyslel', () => {
        expect(holidayName(new Date(2031, 3, 11))).toBe('Velký pátek');
        expect(holidayName(new Date(2031, 3, 14))).toBe('Velikonoční pondělí');
    });
});

describe('pracovní dny', () => {
    it('víkend ani svátek nejsou pracovní den', () => {
        expect(isWorkingDay(new Date(2026, 0, 3))).toBe(false); // sobota
        expect(isWorkingDay(new Date(2026, 0, 1))).toBe(false); // Nový rok
        expect(isWorkingDay(new Date(2026, 0, 2))).toBe(true);
    });

    it('17. listopadu 2026 je svátek, ačkoli padá na úterý', () => {
        expect(holidayName(new Date(2026, 10, 17))).toBe('Den boje za svobodu');
    });

    it('pondělí je index 0', () => {
        expect(weekdayIndex(new Date(2026, 0, 5))).toBe(0);
        expect(weekdayIndex(new Date(2026, 0, 11))).toBe(6);
    });
});

describe('den v měsíci', () => {
    it('31. den se v kratším měsíci ořízne na poslední', () => {
        expect(toISO(dayInMonth(2026, 1, 31))).toBe('2026-02-28');
        expect(toISO(dayInMonth(2028, 1, 31))).toBe('2028-02-29');
        expect(toISO(dayInMonth(2026, 3, 31))).toBe('2026-04-30');
    });
});
