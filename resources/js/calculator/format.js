/** Formátování čísel a částek — na jednom místě, ať se to nerozchází. */

const nbsp = ' ';

export const formatNumber = (value) =>
    Math.round(value || 0).toLocaleString('cs-CZ').replace(/\s/g, nbsp);

export const formatMoney = (value) => `${formatNumber(value)}${nbsp}Kč`;

/**
 * Částka bez zaokrouhlení.
 *
 * V kalendáři se ukazuje, co přesně ten den vyděláš, takže se nesmí nic
 * schovat — ani do „tis.“, ani zaokrouhlením na koruny. Haléře se vypíšou
 * jen tehdy, když tam opravdu jsou: u rozpuštěného měsíčního paušálu den
 * obvykle na celé koruny nevyjde.
 */
export function formatExact(value) {
    const number = value || 0;
    const hasFraction = Math.abs(number - Math.round(number)) > 0.004;

    return number
        .toLocaleString('cs-CZ', {
            minimumFractionDigits: hasFraction ? 2 : 0,
            maximumFractionDigits: hasFraction ? 2 : 0,
        })
        .replace(/\s/g, nbsp);
}

export const formatMoneyExact = (value) => `${formatExact(value)}${nbsp}Kč`;

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

/**
 * Horní hranice osy zaokrouhlená nahoru na čitelné číslo.
 *
 * Bez toho popisky osy ukazují náhodné hodnoty, které v datech nikde nejsou —
 * „187 433“ místo „200 tis.“ Krok se volí podle řádu, aby čtvrtiny osy vyšly
 * taky na kulato.
 */
export function niceCeiling(value) {
    const number = Math.max(1, value);
    const magnitude = 10 ** Math.floor(Math.log10(number));

    for (const step of [1, 1.2, 1.6, 2, 2.4, 4, 6, 8, 10]) {
        if (number <= step * magnitude) {
            return step * magnitude;
        }
    }

    return 10 * magnitude;
}
