import { describe, expect, it } from 'vitest';
import { formatCompact, formatExact, formatMoneyExact, formatNumber, plural } from '../../resources/js/calculator/format.js';

const plain = (value) => value.replace(/ /g, ' ');

describe('přesná částka', () => {
    it('nic nezaokrouhluje ani nezkracuje', () => {
        expect(plain(formatExact(9600))).toBe('9 600');
        expect(plain(formatExact(201600))).toBe('201 600');
        expect(plain(formatExact(1234567))).toBe('1 234 567');
    });

    it('haléře vypíše jen tehdy, když tam opravdu jsou', () => {
        // Měsíční paušál 60 000 rozpuštěný do 21 dnů na celé koruny nevyjde.
        expect(plain(formatExact(60000 / 21))).toBe('2 857,14');
        expect(plain(formatExact(8000))).toBe('8 000');
    });

    it('drobnou odchylku z dělení nevydává za haléře', () => {
        expect(plain(formatExact(9600.0000001))).toBe('9 600');
    });

    it('s měnou', () => {
        expect(plain(formatMoneyExact(9600))).toBe('9 600 Kč');
    });

    it('zkrácený zápis zůstává pro osy grafu', () => {
        expect(plain(formatCompact(9600))).toBe('10 tis.');
        expect(plain(formatNumber(9600))).toBe('9 600');
    });
});

describe('skloňování', () => {
    it.each([
        [0, 'dnů'],
        [1, 'den'],
        [2, 'dny'],
        [4, 'dny'],
        [5, 'dnů'],
        [21, 'dnů'],
    ])('%i → %s', (count, expected) => {
        expect(plural(count, 'den', 'dny', 'dnů')).toBe(expected);
    });
});
