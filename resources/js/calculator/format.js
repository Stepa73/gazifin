/** Formátování čísel a částek — na jednom místě, ať se to nerozchází. */

const nbsp = ' ';

export const formatNumber = (value) =>
    Math.round(value || 0).toLocaleString('cs-CZ').replace(/\s/g, nbsp);

export const formatMoney = (value) => `${formatNumber(value)}${nbsp}Kč`;

export const formatSigned = (value) =>
    `${value > 0 ? '+' : ''}${formatNumber(value)}${nbsp}Kč`;

/** Krátký zápis pro osy grafů: 1,2 mil. / 850 tis. */
export function formatCompact(value) {
    const abs = Math.abs(value);

    if (abs >= 1e6) {
        return `${(value / 1e6).toLocaleString('cs-CZ', { maximumFractionDigits: 1 })}${nbsp}mil.`;
    }

    if (abs >= 1000) {
        return `${Math.round(value / 1000).toLocaleString('cs-CZ')}${nbsp}tis.`;
    }

    return formatNumber(value);
}

export const formatPercent = (value, digits = 1) =>
    `${(value * 100).toFixed(digits).replace('.', ',')}${nbsp}%`;

export const formatHours = (value) =>
    `${value.toLocaleString('cs-CZ', { maximumFractionDigits: 1 })}${nbsp}h`;

/** České skloňování po číslovce: 1 den, 2–4 dny, 0 a 5+ dnů. */
export function plural(count, one, few, many) {
    const n = Math.abs(Math.round(count));

    if (n === 1) return one;
    if (n >= 2 && n <= 4) return few;

    return many;
}
