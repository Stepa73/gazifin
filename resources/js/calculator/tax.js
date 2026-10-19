/**
 * Daňová pravidla OSVČ, zvlášť pro každý rok.
 *
 * Sazby se mění každý rok, takže tu nejsou rozsypané v kódu, ale pohromadě
 * v tabulce. Rok, pro který pravidla nemáme, se počítá podle posledního
 * známého — a řekne si o to příznakem `estimated`, aby si výsledek mohl
 * označit jako odhad.
 */

const M = 1e6;

/** Průměrná mzda pro rok 2026, ze které se odvozují minima a stropy. */
const AVG_WAGE_2026 = 48967;

const RULES_2026 = {
    year: 2026,
    estimated: false,

    /** Měsíční paušální daň. Od 1. 7. 2026 se I. pásmo zpětně snížilo z 9 984 na 9 162 Kč. */
    bands: [
        { roman: 'I', monthly: 9162, tax: 100, social: 5756, health: 3306 },
        { roman: 'II', monthly: 16745, tax: 4963, social: 8191, health: 3591 },
        { roman: 'III', monthly: 27139, tax: 9320, social: 12527, health: 5292 },
    ],

    /** Strop příjmu pro vstup do pásma podle toho, z čeho plyne aspoň 75 % příjmů. */
    bandCeilings: {
        80: [2 * M, 2 * M, 2 * M],
        60: [1.5 * M, 2 * M, 2 * M],
        40: [1 * M, 1.5 * M, 2 * M],
    },

    /** Horní hranice příjmů pro paušální režim vůbec. */
    pausalIncomeCap: 2 * M,

    /** Paušální výdaje: procento a jeho strop. */
    expenses: {
        80: { rate: 0.8, cap: 1600000 },
        60: { rate: 0.6, cap: 1200000 },
        40: { rate: 0.4, cap: 800000 },
    },

    incomeTax: {
        lowerRate: 0.15,
        upperRate: 0.23,
        /** 36násobek průměrné mzdy. */
        upperThreshold: 1762812,
        /** Sleva na poplatníka. */
        credit: 30840,
    },

    social: {
        rate: 0.292,
        /** Vyměřovací základ je 55 % daňového základu. */
        share: 0.55,
        /** 48násobek průměrné mzdy. */
        maxBase: 48 * AVG_WAGE_2026,
        /** 12 × 35 % průměrné mzdy — minimum od 1. 7. 2026 zpětně na celý rok. */
        minBaseMain: 205668,
        /** 12 × 11 % průměrné mzdy; platí až po překročení rozhodné částky. */
        minBaseSide: 64680,
        /** Rozhodná částka pro vedlejší činnost. Pod ní se sociální neplatí vůbec. */
        sideThreshold: 117521,
    },

    health: {
        rate: 0.135,
        share: 0.5,
        /** 12 × 50 % průměrné mzdy. Vedlejší činnost minimum nemá. */
        minBaseMain: 293802,
    },

    vat: {
        /** Od 1. ledna následujícího roku. */
        yearlyThreshold: 2 * M,
        /** Hned následující den po překročení. */
        immediateThreshold: 2536500,
    },
};

const RULES = {
    2025: {
        ...RULES_2026,
        year: 2025,
        estimated: false,
        bands: [
            { roman: 'I', monthly: 8716, tax: 100, social: 5335, health: 3281 },
            { roman: 'II', monthly: 16745, tax: 4963, social: 8191, health: 3591 },
            { roman: 'III', monthly: 27139, tax: 9320, social: 12527, health: 5292 },
        ],
        incomeTax: { ...RULES_2026.incomeTax, upperThreshold: 1676052 },
        social: {
            ...RULES_2026.social,
            maxBase: 2234736,
            minBaseMain: 195276,
            minBaseSide: 61404,
            sideThreshold: 111736,
        },
        health: { ...RULES_2026.health, minBaseMain: 279312 },
    },
    2026: RULES_2026,
};

/** Poslední rok, pro který máme vyhlášené sazby. */
export const LATEST_KNOWN_YEAR = 2026;

export function rulesFor(year) {
    if (RULES[year]) {
        return RULES[year];
    }

    // Sazby pro další rok se vyhlašují až na podzim. Do té doby je nejlepší
    // možný odhad poslední známý rok — označený jako odhad.
    return { ...RULES[LATEST_KNOWN_YEAR], year, estimated: true };
}

/** Stropy pásem pro zvolenou činnost. */
export const bandCeilings = (rules, activity) => rules.bandCeilings[activity] ?? rules.bandCeilings[60];

/** Do kterého pásma příjem spadá. -1 znamená, že je nad paušálním režimem. */
export function bandIndexFor(rules, activity, income) {
    const ceilings = bandCeilings(rules, activity);
    const idx = ceilings.findIndex((ceiling) => income <= ceiling);

    return income > rules.pausalIncomeCap ? -1 : (idx === -1 ? -1 : idx);
}

/**
 * Výpočet podle paušální daně.
 *
 * Platí se za každý měsíc v režimu, ne vždy dvanáctkrát — kdo vstoupil v srpnu,
 * zaplatí pět záloh.
 */
export function pausalResult(rules, activity, income, months = 12) {
    const idx = bandIndexFor(rules, activity, income);

    if (idx === -1) {
        return null;
    }

    const band = rules.bands[idx];
    const paidMonths = Math.max(0, Math.min(12, Math.round(months)));
    const total = band.monthly * paidMonths;

    return {
        mode: 'pausal',
        bandIndex: idx,
        band,
        months: paidMonths,
        monthly: band.monthly,
        incomeTax: band.tax * paidMonths,
        social: band.social * paidMonths,
        health: band.health * paidMonths,
        expenses: 0,
        base: 0,
        totalDue: total,
        net: income - total,
        effectiveRate: income > 0 ? total / income : 0,
    };
}

/** Základ daně se zaokrouhluje dolů na celé stokoruny. */
const roundBaseDown = (value) => Math.max(0, Math.floor(value / 100) * 100);

/**
 * Výpočet podle klasického přiznání.
 *
 * Oproti původní verzi respektuje minimální vyměřovací základy. Bez nich
 * vycházely nízké příjmy výrazně lépe, než ve skutečnosti dopadnou — OSVČ
 * na hlavní činnost platí pojistné z minima i v roce, kdy skoro nevydělá.
 */
export function classicResult(rules, { income, activity, expenseMode, realExpenses, sideActivity }) {
    const scheme = rules.expenses[activity] ?? rules.expenses[60];
    const expenses = expenseMode === 'real'
        ? Math.max(0, realExpenses)
        : Math.min(income * scheme.rate, scheme.cap);

    const capped = expenseMode !== 'real' && income * scheme.rate > scheme.cap;
    const profit = Math.max(0, income - expenses);
    const base = roundBaseDown(profit);

    // Daň z příjmů
    const taxBeforeCredit =
        rules.incomeTax.lowerRate * Math.min(base, rules.incomeTax.upperThreshold) +
        rules.incomeTax.upperRate * Math.max(0, base - rules.incomeTax.upperThreshold);
    const incomeTax = Math.max(0, taxBeforeCredit - rules.incomeTax.credit);

    // Sociální pojištění
    // U vedlejší činnosti se pod rozhodnou částkou neplatí nic. Nad ní se ale
    // platí z celého vyměřovacího základu, ne jen z částky nad hranicí —
    // rozhodná částka je spínač, ne odpočet.
    const socialExempt = sideActivity && base <= rules.social.sideThreshold;
    const socialMinBase = sideActivity ? rules.social.minBaseSide : rules.social.minBaseMain;
    const socialRawBase = base * rules.social.share;
    const socialBase = socialExempt
        ? 0
        : Math.min(Math.max(socialRawBase, socialMinBase), rules.social.maxBase);
    const social = socialBase * rules.social.rate;
    const socialAtMinimum = !socialExempt && socialRawBase < socialMinBase;
    const socialAtCeiling = socialRawBase > rules.social.maxBase;

    // Zdravotní pojištění — minimum má jen hlavní činnost, strop nemá žádná.
    const healthRawBase = base * rules.health.share;
    const healthBase = sideActivity ? healthRawBase : Math.max(healthRawBase, rules.health.minBaseMain);
    const health = healthBase * rules.health.rate;
    const healthAtMinimum = !sideActivity && healthRawBase < rules.health.minBaseMain;

    const totalDue = incomeTax + social + health;

    return {
        mode: 'classic',
        activity,
        expenseMode,
        expenses,
        expensesCapped: capped,
        expenseCap: scheme.cap,
        profit,
        base,
        taxBeforeCredit,
        credit: rules.incomeTax.credit,
        incomeTax,
        social,
        socialBase,
        socialExempt,
        socialAtMinimum,
        socialAtCeiling,
        health,
        healthBase,
        healthAtMinimum,
        sideActivity,
        totalDue,
        // Skutečné výdaje z příjmu opravdu odtečou; paušální jsou jen daňová fikce.
        net: income - (expenseMode === 'real' ? Math.max(0, realExpenses) : 0) - totalDue,
        effectiveRate: income > 0 ? totalDue / income : 0,
    };
}

/**
 * Co se na daný příjem použije podle zvoleného režimu.
 *
 * `auto` drží paušální daň, dokud se vejde do stropu, a nad ním přepne na
 * klasické přiznání — protože tam už jiná volba není.
 */
export function resolveTax(options) {
    const rules = rulesFor(options.year);
    const income = Math.max(0, options.income);
    const activity = options.activity ?? 60;
    const regime = options.regime ?? 'auto';

    const pausal = regime === 'klasik' ? null : pausalResult(rules, activity, income, options.pausalMonths ?? 12);
    const classic = classicResult(rules, {
        income,
        activity,
        expenseMode: options.expenseMode ?? 'pausal',
        realExpenses: options.realExpenses ?? 0,
        sideActivity: !!options.sideActivity,
    });

    const pausalAvailable = bandIndexFor(rules, activity, income) !== -1;

    const chosen = pausal ? { ...pausal } : { ...classic };
    chosen.rules = rules;
    chosen.income = income;
    chosen.pausalAvailable = pausalAvailable;
    // Kolik by vyšlo to druhé — ať je vidět, jestli se volba vyplácí.
    chosen.alternative = pausal
        ? classic
        : (pausalAvailable ? pausalResult(rules, activity, income, options.pausalMonths ?? 12) : null);
    chosen.forced = regime === 'klasik' && pausalAvailable;
    chosen.overCap = !pausalAvailable;

    return chosen;
}

/**
 * Kolik odvodů připadá na peníze, které v roce teprve dorazí.
 *
 * Průměrná sazba by lhala: odvody nerostou lineárně, takže poslední
 * stotisícovka roku se daní jinak než první. Proto se počítá rozdíl mezi
 * celým rokem a tím, co už přišlo.
 */
export function marginalLevy(options, received, remaining) {
    if (remaining <= 0) {
        return { levy: 0, rate: 0 };
    }

    const whole = resolveTax({ ...options, income: received + remaining });
    const soFar = resolveTax({ ...options, income: received });
    const levy = Math.max(0, whole.totalDue - soFar.totalDue);

    return { levy, rate: levy / remaining };
}

/**
 * Kolik záloh paušální daně ještě letos zaplatíš.
 *
 * Záloha je splatná do 20. dne v měsíci, takže se počítají měsíce, jejichž
 * dvacátý den ještě nenastal.
 */
export function pausalInstallmentsLeft(year, todayISO, months = 12) {
    const paid = Math.max(0, Math.min(12, Math.round(months)));
    let count = 0;

    for (let month = 0; month < paid; month++) {
        if (`${year}-${String(month + 1).padStart(2, '0')}-20` >= todayISO) {
            count++;
        }
    }

    return count;
}

/**
 * Odvody ze zbytku roku.
 *
 * V paušálu nezávisí na příjmu — platíš pevné zálohy, které ještě nejsou
 * splatné. Jinde se počítá mezní sazba, protože poslední peníze roku se daní
 * dráž než ty první.
 */
export function remainingLevy(options, received, remaining, todayISO) {
    const profile = resolveTax({ ...options, income: received + remaining });

    if (profile.mode === 'pausal') {
        const installments = pausalInstallmentsLeft(options.year, todayISO, options.pausalMonths ?? 12);
        const levy = installments * profile.monthly;

        return { levy, rate: remaining > 0 ? levy / remaining : 0, installments, monthly: profile.monthly };
    }

    return { ...marginalLevy(options, received, remaining), installments: null, monthly: null };
}

export { M };
