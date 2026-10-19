import { describe, expect, it } from 'vitest';
import {
    cashYear,
    collectPayments,
    crossingMonth,
    dailyBreakdown,
    dayForSource,
    sourceMonthAmount,
} from '../../resources/js/calculator/engine.js';

const source = (overrides = {}) => ({
    id: 1,
    name: 'Klient',
    mode: 'rate',
    rate: 1000,
    unit: 'h',
    hoursPerDay: 8,
    workDays: [1, 2, 3, 4, 5],
    lag: 2,
    payDay: 15,
    from: '2026-01-01',
    to: '',
    vacationDays: [],
    fixed: 0,
    date: '',
    amount: 0,
    ...overrides,
});

describe('den u zdroje', () => {
    it('běžný pracovní den vydělá sazbu krát hodiny', () => {
        const day = dayForSource(source(), new Date(2026, 0, 5));

        expect(day.status).toBe('work');
        expect(day.amount).toBe(8000);
    });

    it('víkend, svátek a dovolená se rozlišují', () => {
        expect(dayForSource(source(), new Date(2026, 0, 3)).status).toBe('off');
        expect(dayForSource(source(), new Date(2026, 0, 1)).status).toBe('holiday');
        expect(dayForSource(source({ vacationDays: ['2026-07-14'] }), new Date(2026, 6, 14)).status).toBe('vacation');
    });

    it('den mimo rozsah spolupráce se nepočítá', () => {
        const s = source({ from: '2026-03-01', to: '2026-06-30' });

        expect(dayForSource(s, new Date(2026, 1, 20)).status).toBe('outside');
        expect(dayForSource(s, new Date(2026, 6, 1)).status).toBe('outside');
        expect(dayForSource(s, new Date(2026, 3, 1)).status).toBe('work');
    });

    it('sazba za MD nekouká na hodiny denně', () => {
        const day = dayForSource(source({ unit: 'md', rate: 9000, hoursPerDay: 6 }), new Date(2026, 0, 5));

        expect(day.amount).toBe(9000);
    });

    it('zkrácený úvazek počítá jen zvolené dny v týdnu', () => {
        const s = source({ workDays: [1, 3] });

        expect(dayForSource(s, new Date(2026, 0, 5)).status).toBe('work'); // pondělí
        expect(dayForSource(s, new Date(2026, 0, 6)).status).toBe('off'); // úterý
        expect(dayForSource(s, new Date(2026, 0, 7)).status).toBe('work'); // středa
    });
});

describe('měsíční paušál', () => {
    it('celý měsíc dá přesně sjednanou částku', () => {
        const s = source({ mode: 'fixed', fixed: 60000 });

        expect(Math.round(sourceMonthAmount(s, 2026, 3))).toBe(60000);
    });

    it('dovolená částku nesnižuje — paušál se jen rozprostře na zbylé dny', () => {
        const s = source({ mode: 'fixed', fixed: 60000, vacationDays: ['2026-04-07', '2026-04-08'] });

        expect(Math.round(sourceMonthAmount(s, 2026, 3))).toBe(60000);
    });

    it('spolupráce od poloviny měsíce dá jen poměrnou část', () => {
        const s = source({ mode: 'fixed', fixed: 60000, from: '2026-04-16' });
        const amount = sourceMonthAmount(s, 2026, 3);

        expect(amount).toBeGreaterThan(20000);
        expect(amount).toBeLessThan(40000);
    });
});

describe('dovolená', () => {
    it('ubere přesně dny, které si zadal — ne nějaký počet za měsíc', () => {
        const plain = sourceMonthAmount(source(), 2026, 6);
        const withLeave = sourceMonthAmount(source({ vacationDays: ['2026-07-14', '2026-07-15'] }), 2026, 6);

        expect(plain - withLeave).toBe(16000);
    });

    it('přidání dne do stejného pole se projeví hned', () => {
        // Regrese: Set dovolené se cachoval podle identity pole, takže po
        // zaškrtnutí dne v kalendáři zůstal výdělek nezměněný.
        const s = source();
        const before = sourceMonthAmount(s, 2026, 6);

        s.vacationDays.push('2026-07-14');

        expect(sourceMonthAmount(s, 2026, 6)).toBe(before - 8000);

        s.vacationDays.splice(0, 1);

        expect(sourceMonthAmount(s, 2026, 6)).toBe(before);
    });

    it('dovolená na svátek neubere nic, protože ten den se stejně nepracuje', () => {
        const plain = sourceMonthAmount(source(), 2026, 6);
        const withLeave = sourceMonthAmount(source({ vacationDays: ['2026-07-06'] }), 2026, 6);

        expect(plain).toBe(withLeave);
    });

    it('dovolená z letoška se nepřenáší do dalšího roku', () => {
        const withLeave = source({ vacationDays: ['2026-07-14'], to: '' });
        const without = source({ to: '' });

        // V roce 2026 ten den ubere, v roce 2027 už se k němu nikdo nevrací.
        expect(sourceMonthAmount(withLeave, 2026, 6)).toBe(sourceMonthAmount(without, 2026, 6) - 8000);
        expect(sourceMonthAmount(withLeave, 2027, 6)).toBe(sourceMonthAmount(without, 2027, 6));
    });
});

describe('rozpad roku na dny', () => {
    it('součet dnů sedí na součet měsíců i na rok', () => {
        const { days, months, total } = dailyBreakdown([source()], 2026);
        const fromDays = [...days.values()].reduce((sum, d) => sum + d.amount, 0);
        const fromMonths = months.reduce((sum, m) => sum + m.amount, 0);

        expect(Math.round(fromDays)).toBe(Math.round(total));
        expect(Math.round(fromMonths)).toBe(Math.round(total));
    });

    it('leden 2026 má 21 pracovních dnů (22 všedních minus Nový rok)', () => {
        const { months } = dailyBreakdown([source()], 2026);

        expect(months[0].workDays).toBe(21);
        expect(months[0].amount).toBe(21 * 8000);
    });

    it('den zná všechny zdroje, které do něj sáhly', () => {
        const a = source({ id: 1, name: 'Alfa' });
        const b = source({ id: 2, name: 'Beta', rate: 500 });
        const { days } = dailyBreakdown([a, b], 2026);
        const day = days.get('2026-01-05');

        expect(day.sources.map((s) => s.name)).toEqual(['Alfa', 'Beta']);
        expect(day.amount).toBe(8000 + 4000);
    });
});

describe('kdy peníze dorazí', () => {
    it('práce za leden se zpožděním 2 dorazí 15. března', () => {
        const payments = collectPayments([source()], [2026]);
        const january = payments.find((p) => p.workMonth === 0);

        expect(january.iso).toBe('2026-03-15');
    });

    it('práce z konce roku přeteče do dalšího roku', () => {
        const payments = collectPayments([source({ to: '2026-12-31' })], [2026]);
        const december = payments.find((p) => p.workMonth === 11);

        expect(december.year).toBe(2027);
        expect(december.month).toBe(1);
    });

    it('platba na 31. se v kratším měsíci posune na poslední den', () => {
        const payments = collectPayments([source({ payDay: 31, lag: 1 })], [2026]);
        const january = payments.find((p) => p.workMonth === 0);

        expect(january.iso).toBe('2026-02-28');
    });

    it('jednorázová faktura dorazí ve svém datu', () => {
        const payments = collectPayments([source({ mode: 'invoice', date: '2026-09-09', amount: 50000 })], [2026]);

        expect(payments).toHaveLength(1);
        expect(payments[0]).toMatchObject({ iso: '2026-09-09', amount: 50000, kind: 'invoice' });
    });
});

describe('rok v hotovosti', () => {
    it('odděluje přenesené z loňska od letošního výdělku', () => {
        const payments = collectPayments([source({ from: '2025-11-01' })], [2025, 2026]);
        const year = cashYear(2026, payments);

        expect(year.carriedIn).toBeGreaterThan(0);
        expect(year.byMonth[0]).toBeGreaterThan(0); // listopadová práce, platba v lednu
    });

    it('práce z prosince, která se zaplatí až příští rok, se do roku nepočítá', () => {
        const payments = collectPayments([source({ to: '2026-12-31' })], [2026]);
        const year = cashYear(2026, payments);

        expect(year.deferred).toBeGreaterThan(0);
        expect(year.byMonth[0]).toBe(0); // leden 2026 kryje práci z listopadu 2025, ta tu není
    });

    it('kumulativní řada je průběžný součet měsíců', () => {
        const payments = collectPayments([source()], [2026]);
        const { byMonth, cumulative, total } = cashYear(2026, payments);

        expect(cumulative[11]).toBeCloseTo(total, 6);
        expect(cumulative[3]).toBeCloseTo(byMonth.slice(0, 4).reduce((a, b) => a + b, 0), 6);
    });
});

describe('překročení stropu', () => {
    it('najde měsíc, kdy kumulativní příjem přeleze dva miliony', () => {
        expect(crossingMonth([0, 1e6, 1.9e6, 2.1e6], 2e6)).toBe(3);
        expect(crossingMonth([0, 1e6, 1.5e6], 2e6)).toBe(-1);
    });
});
