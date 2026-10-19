import { describe, expect, it } from 'vitest';
import {
    bandIndexFor,
    classicResult,
    marginalLevy,
    pausalInstallmentsLeft,
    pausalResult,
    remainingLevy,
    resolveTax,
    rulesFor,
} from '../../resources/js/calculator/tax.js';

const rules = rulesFor(2026);
const round = (value) => Math.round(value);

describe('pásma paušální daně', () => {
    it('IT živnost s 60% paušálem má první pásmo do 1,5 milionu', () => {
        expect(bandIndexFor(rules, 60, 1400000)).toBe(0);
        expect(bandIndexFor(rules, 60, 1600000)).toBe(1);
        expect(bandIndexFor(rules, 60, 1900000)).toBe(1);
    });

    it('svobodné povolání se 40% paušálem spadne do druhého pásma dřív', () => {
        expect(bandIndexFor(rules, 40, 1100000)).toBe(1);
        expect(bandIndexFor(rules, 40, 1600000)).toBe(2);
    });

    it('nad dva miliony už paušální režim není', () => {
        expect(bandIndexFor(rules, 80, 2000001)).toBe(-1);
    });
});

describe('paušální daň', () => {
    it('dvanáct záloh prvního pásma 2026', () => {
        const result = pausalResult(rules, 60, 900000);

        expect(result.monthly).toBe(9162);
        expect(result.totalDue).toBe(9162 * 12);
        expect(result.net).toBe(900000 - 109944);
    });

    it('kdo vstoupil v srpnu, platí pět záloh, ne dvanáct', () => {
        expect(pausalResult(rules, 60, 400000, 5).totalDue).toBe(9162 * 5);
    });
});

describe('klasické přiznání', () => {
    const base = { activity: 60, expenseMode: 'pausal', realExpenses: 0, sideActivity: false };

    it('milion příjmů na hlavní činnost', () => {
        const r = classicResult(rules, { ...base, income: 1000000 });

        expect(r.expenses).toBe(600000);
        expect(r.base).toBe(400000);
        // 15 % ze 400 000 = 60 000, minus sleva 30 840
        expect(round(r.incomeTax)).toBe(29160);
        // 55 % ze 400 000 = 220 000 > minimum 205 668
        expect(round(r.social)).toBe(round(220000 * 0.292));
        // 50 % ze 400 000 = 200 000 < minimum 293 802 → platí se minimum
        expect(r.healthAtMinimum).toBe(true);
        expect(round(r.health)).toBe(round(293802 * 0.135));
    });

    it('nízký zisk na hlavní činnost platí pojistné z minima, ne z nuly', () => {
        const r = classicResult(rules, { ...base, income: 200000 });

        expect(r.base).toBe(80000);
        expect(r.incomeTax).toBe(0);
        expect(r.socialAtMinimum).toBe(true);
        expect(r.healthAtMinimum).toBe(true);
        expect(round(r.social)).toBe(round(205668 * 0.292));
        expect(round(r.health)).toBe(round(293802 * 0.135));
    });

    it('paušální výdaje mají strop', () => {
        const r = classicResult(rules, { ...base, income: 2500000 });

        expect(r.expenses).toBe(1200000);
        expect(r.expensesCapped).toBe(true);
    });

    it('nad 36násobkem průměrné mzdy se daní 23 %', () => {
        const r = classicResult(rules, { ...base, income: 5000000 });
        const expected = 0.15 * 1762812 + 0.23 * (r.base - 1762812);

        expect(round(r.taxBeforeCredit)).toBe(round(expected));
    });

    it('sociální základ má strop 48násobku průměrné mzdy', () => {
        const r = classicResult(rules, { ...base, income: 12000000 });

        expect(r.socialAtCeiling).toBe(true);
        expect(round(r.socialBase)).toBe(2350416);
    });
});

describe('vedlejší činnost', () => {
    const side = { activity: 60, expenseMode: 'pausal', realExpenses: 0, sideActivity: true };

    it('pod rozhodnou částkou se sociální neplatí vůbec', () => {
        const r = classicResult(rules, { ...side, income: 250000 });

        expect(r.base).toBe(100000);
        expect(r.socialExempt).toBe(true);
        expect(r.social).toBe(0);
    });

    it('nad rozhodnou částkou se platí z celého základu, ne jen z částky nad ní', () => {
        const r = classicResult(rules, { ...side, income: 500000 });

        expect(r.base).toBe(200000);
        expect(r.socialExempt).toBe(false);
        // 55 % z 200 000 = 110 000, nad minimem vedlejší činnosti
        expect(round(r.social)).toBe(round(110000 * 0.292));
    });

    it('zdravotní u vedlejší činnosti minimum nemá', () => {
        const r = classicResult(rules, { ...side, income: 200000 });

        expect(r.healthAtMinimum).toBe(false);
        expect(round(r.health)).toBe(round(80000 * 0.5 * 0.135));
    });
});

describe('volba režimu', () => {
    const options = { year: 2026, activity: 60, expenseMode: 'pausal', realExpenses: 0, sideActivity: false };

    it('automaticky drží paušál, dokud se vejde do stropu', () => {
        expect(resolveTax({ ...options, regime: 'auto', income: 1500000 }).mode).toBe('pausal');
        expect(resolveTax({ ...options, regime: 'auto', income: 2100000 }).mode).toBe('classic');
    });

    it('ručně zvolené přiznání nabídne srovnání s paušálem', () => {
        const r = resolveTax({ ...options, regime: 'klasik', income: 1000000 });

        expect(r.mode).toBe('classic');
        expect(r.forced).toBe(true);
        expect(r.alternative.mode).toBe('pausal');
    });

    it('nad stropem se paušál nenabízí ani jako alternativa', () => {
        const r = resolveTax({ ...options, regime: 'pausal', income: 3000000 });

        expect(r.mode).toBe('classic');
        expect(r.overCap).toBe(true);
        expect(r.alternative).toBeNull();
    });

    it('neznámý rok se počítá podle posledního známého a označí se jako odhad', () => {
        expect(resolveTax({ ...options, regime: 'auto', income: 1000000, year: 2031 }).rules.estimated).toBe(true);
    });
});

describe('odvody z peněz, které teprve dorazí', () => {
    const options = { year: 2026, regime: 'klasik', activity: 60, expenseMode: 'pausal', realExpenses: 0, sideActivity: false };

    it('sazba na zbytek roku je mezní, ne průměrná', () => {
        const { rate } = marginalLevy(options, 1700000, 300000);
        const average = resolveTax({ ...options, income: 2000000 }).effectiveRate;

        // Poslední peníze roku se daní dráž než ty první — průměr by podstřelil.
        expect(rate).toBeGreaterThan(average);
    });

    it('v paušálu zbylé zálohy na příjmu nezávisí', () => {
        const pausal = { ...options, regime: 'pausal' };

        expect(marginalLevy(pausal, 500000, 300000).levy).toBe(0);
    });

    it('bez zbylých peněz se neodvádí nic', () => {
        expect(marginalLevy(options, 1000000, 0)).toEqual({ levy: 0, rate: 0 });
    });
});

describe('zbytek roku v paušálu', () => {
    const options = { year: 2026, regime: 'pausal', activity: 60, expenseMode: 'pausal', realExpenses: 0, sideActivity: false, pausalMonths: 12 };

    it('počítá zálohy, které ještě nejsou splatné', () => {
        // 20. října ještě nenastal, takže zbývají zálohy za říjen, listopad a prosinec.
        expect(pausalInstallmentsLeft(2026, '2026-10-19')).toBe(3);
        // Den po splatnosti už se říjnová nepočítá.
        expect(pausalInstallmentsLeft(2026, '2026-10-21')).toBe(2);
        expect(pausalInstallmentsLeft(2026, '2026-01-01')).toBe(12);
        expect(pausalInstallmentsLeft(2027, '2026-10-19')).toBe(12);
    });

    it('kdo je v režimu jen půl roku, má po červnu nulu', () => {
        expect(pausalInstallmentsLeft(2026, '2026-10-19', 6)).toBe(0);
    });

    it('odvody ze zbytku roku jsou zbylé zálohy, ne nula', () => {
        const { levy, installments, monthly } = remainingLevy(options, 1500000, 400000, '2026-10-19');

        expect(installments).toBe(3);
        expect(monthly).toBe(16745);
        expect(levy).toBe(3 * 16745);
    });

    it('nad stropem paušálu se vrací mezní sazba', () => {
        const { installments, levy } = remainingLevy(options, 1900000, 400000, '2026-10-19');

        expect(installments).toBeNull();
        expect(levy).toBeGreaterThan(0);
    });
});
