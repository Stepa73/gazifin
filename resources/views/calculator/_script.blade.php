<script>
(function(){
  // ---- data ze serveru ----
  const BOOT     = @json($state);
  const ACTUALS  = @json($actuals);
  const CLIENTS  = @json($clients);
  const YEARS    = @json($years);
  const SAVE_URL = @json(route('calculator.update'));
  const CSRF     = document.querySelector('meta[name="csrf-token"]').content;

  const BASE_YEAR = YEARS.base, NEXT_YEAR = YEARS.next, PREV_YEAR = YEARS.prev;
  const WORK_YEARS = [PREV_YEAR, BASE_YEAR, NEXT_YEAR];
  const M = 1e6;
  const BANDS = [{n:1,m:9162,r:"I"},{n:2,m:16745,r:"II"},{n:3,m:27139,r:"III"}];
  const MONTHS = ["Led","Úno","Bře","Dub","Kvě","Čvn","Čvc","Srp","Zář","Říj","Lis","Pro"];
  const SOC_RATE=.292, SOC_SHARE=.55, SOC_CAP=2350416;
  const HEALTH_RATE=.135, HEALTH_SHARE=.5;
  const TAX_STEP=1762812, SLEVA=30840;
  const SOC_SIDE_THRESHOLD=111736;
  const PAUSAL_CAP={80:1600000,60:1200000,40:800000};

  const HOLIDAYS_BY_YEAR = {
    2025: new Set(["2025-01-01","2025-04-18","2025-04-21","2025-05-01","2025-05-08",
                   "2025-07-05","2025-07-06","2025-09-28","2025-10-28","2025-11-17",
                   "2025-12-24","2025-12-25","2025-12-26"]),
    2026: new Set(["2026-01-01","2026-04-03","2026-04-06","2026-05-01","2026-05-08",
                   "2026-07-05","2026-07-06","2026-09-28","2026-10-28","2026-11-17",
                   "2026-12-24","2026-12-25","2026-12-26"]),
    2027: new Set(["2027-01-01","2027-03-26","2027-03-29","2027-05-01","2027-05-08",
                   "2027-07-05","2027-07-06","2027-09-28","2027-10-28","2027-11-17",
                   "2027-12-24","2027-12-25","2027-12-26"])
  };
  const HOLIDAY_MD_FIXED = new Set(["01-01","05-01","05-08","07-05","07-06","09-28","10-28","11-17","12-24","12-25","12-26"]);

  const fmt = n => Math.round(n).toLocaleString('cs-CZ').replace(/ /g,' ');
  const kc  = n => fmt(n) + ' Kč';
  const el  = id => document.getElementById(id);
  const pad = n => String(n).padStart(2,'0');
  const esc = v => String(v == null ? '' : v).replace(/[&<>"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));

  // ---- třídy sdílené se zbytkem aplikace ----
  const INPUT  = 'mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm';
  const LABEL  = 'block text-xs font-medium text-gray-500';
  const CHIP   = 'shrink-0 rounded-full border px-3 py-1.5 text-xs font-medium';
  const CHIP_ON  = 'border-brand bg-brand-light text-brand';
  const CHIP_OFF = 'border-gray-200 bg-white text-gray-600 hover:bg-gray-50';

  function paintChips(container){
    container.querySelectorAll('button[aria-pressed]').forEach(b => {
      b.className = CHIP + ' ' + (b.getAttribute('aria-pressed') === 'true' ? CHIP_ON : CHIP_OFF);
    });
  }

  const TONES = {plain:'text-gray-900', accent:'text-amber-600', real:'text-blue-600', muted:'text-gray-400'};
  function rowHTML(label, value, opts){
    opts = opts || {};
    const labelCls = opts.sub ? 'pl-3 text-xs text-gray-400' : 'text-sm text-gray-500';
    const valueCls = opts.sub ? 'text-xs font-normal text-gray-400' : 'text-sm font-semibold';
    return `<div class="flex items-baseline justify-between gap-3 border-b border-gray-100 py-2 last:border-b-0">
      <span class="${labelCls}">${label}</span>
      <span class="whitespace-nowrap tabular-nums ${valueCls} ${opts.sub ? '' : (TONES[opts.tone] || TONES.plain)}">${value}</span>
    </div>`;
  }
  const rowsWrap = inner => `<div class="mt-4 border-t border-gray-100 pt-1">${inner}</div>`;
  const noteBox = inner => `<div class="mt-3 rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-xs leading-relaxed text-gray-600">${inner}</div>`;
  const warnBox = inner => `<div class="mt-3 rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-xs leading-relaxed text-red-700">${inner}</div>`;
  const toISO = d => `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}`;
  const parseISO = iso => iso ? new Date(iso+'T00:00:00') : null;
  const fmtDate = iso => { if(!iso) return '—'; const [y,m,d]=iso.split('-'); return `${+d}. ${+m}. ${y}`; };
  const lastDay = (y,m) => new Date(y, m+1, 0).getDate();
  const payDate = (y,m,day) => new Date(y, m, Math.min(Math.max(1, day||15), lastDay(y,m)));

  const NOW = new Date();
  const TODAY = new Date(NOW.getFullYear(), NOW.getMonth(), NOW.getDate());

  function monthRange(mi, year){ return [new Date(year,mi,1), new Date(year,mi+1,0)]; }
  function daysIncl(a,b){ return Math.round((b-a)/86400000)+1; }
  function isHoliday(d){
    const set = HOLIDAYS_BY_YEAR[d.getFullYear()];
    if (set) return set.has(toISO(d));
    return HOLIDAY_MD_FIXED.has(`${pad(d.getMonth()+1)}-${pad(d.getDate())}`);
  }
  function countWorkdays(start,end){
    let count=0;
    let cur = new Date(start.getFullYear(), start.getMonth(), start.getDate());
    const last = new Date(end.getFullYear(), end.getMonth(), end.getDate());
    while(cur <= last){
      const dow = cur.getDay();
      if(dow!==0 && dow!==6 && !isHoliday(cur)) count++;
      cur.setDate(cur.getDate()+1);
    }
    return count;
  }

  function overlap(s, mi, year){
    const [mFirst,mLast] = monthRange(mi, year);
    const fromD = parseISO(s.from);
    const toD   = parseISO(s.to);
    const start = (fromD && fromD > mFirst) ? fromD : mFirst;
    const end   = (toD   && toD   < mLast)  ? toD   : mLast;
    return end < start ? null : [start, end, mFirst, mLast];
  }
  function workdaysFor(s, mi, year){
    const ov = overlap(s, mi, year);
    return ov ? countWorkdays(ov[0], ov[1]) : 0;
  }
  function amountFor(s, mi, year){
    if (s.mode === 'invoice') return 0;
    const ov = overlap(s, mi, year); if (!ov) return 0;
    const [start,end,mFirst,mLast] = ov;
    if (s.mode === 'fixed'){
      return (+s.fixed || 0) * (daysIncl(start,end) / daysIncl(mFirst,mLast));
    }
    const wd = countWorkdays(start, end);
    const vac = Math.min(+(s.vacation && s.vacation[mi]) || 0, wd);
    const billable = Math.max(0, wd - vac);
    const rate = +s.rate || 0, hpd = +s.hoursPerDay || 8;
    return s.unit === 'h' ? billable*hpd*rate : billable*rate;
  }

  // ---- skutečnost z faktur ----
  function actualFor(clientId, year, mi){
    if (clientId == null || clientId === '') return null;
    const byClient = ACTUALS.byClient[clientId];
    return byClient ? (byClient[`${year}-${mi}`] || null) : null;
  }
  function actualYear(clientId, year){
    let paid = 0, open = 0;
    for (let i=0;i<12;i++){ const a = actualFor(clientId, year, i); if (a){ paid += a.paid; open += a.open; } }
    return {paid, open};
  }
  function actualMonthlyAll(year){
    const paid = new Array(12).fill(0), open = new Array(12).fill(0);
    for (let i=0;i<12;i++){
      const a = ACTUALS.totals[`${year}-${i}`];
      if (a){ paid[i] = a.paid; open[i] = a.open; }
    }
    return {paid, open};
  }
  function linkedClientIds(){
    return [...new Set(sources.map(s => s.clientId).filter(v => v != null && v !== ''))];
  }

  let uid = 0;
  let sources = [];
  let activeYear = BASE_YEAR;
  let regime = 'auto';
  let sideActivity = false;
  let loaded = false;

  function blankSource(preset){
    return Object.assign({
      id:++uid, clientId:null, name:'Klient '+String.fromCharCode(64+sources.length+1),
      mode:'rate', rate:1200, unit:'h', hoursPerDay:8, lag:2, payDay:15,
      from:BASE_YEAR+'-01-01', to:'',
      vacation:new Array(12).fill(0),
      fixed:40000,
      date:BASE_YEAR+'-06-15', amount:50000
    }, preset||{});
  }
  function addSource(preset){ sources.push(blankSource(preset)); }

  function srcCardHTML(s){
    const vacGrid = MONTHS.map((mn,i) => `
      <div class="flex flex-col items-center gap-1">
        <span class="text-[10px] uppercase text-gray-400">${mn}</span>
        <input type="number" min="0" max="31" step="1" data-vac="${i}" value="${+s.vacation[i]||0}" aria-label="Dovolená ${mn}"
               class="w-full rounded-md border-gray-300 px-1 py-1 text-center text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
      </div>`).join('');

    const lagOpts = [0,1,2,3].map(v => `<option value="${v}" ${+s.lag===v?'selected':''}>${
      v===0 ? 'Ve stejném měsíci, kdy pracuju' : (v===1 ? 'O měsíc později' : (v===2 ? 'O 2 měsíce později (fakturuji až příští měsíc)' : 'O 3 měsíce později'))
    }</option>`).join('');

    const dayOpts = [1,5,10,14,15,20,25,31].map(v => `<option value="${v}" ${+s.payDay===v?'selected':''}>${
      v===31 ? 'poslední den v měsíci' : v+'. den'
    }</option>`).join('');

    const clientOpts = ['<option value="">Nenapojeno na fakturaci</option>']
      .concat(CLIENTS.map(c => `<option value="${c.id}" ${String(s.clientId)===String(c.id)?'selected':''}>${esc(c.name)}</option>`))
      .join('');

    const modeChip = (mode, label) => `<button type="button" data-mode="${mode}" aria-pressed="${s.mode===mode}" class="${CHIP} ${s.mode===mode?CHIP_ON:CHIP_OFF}">${label}</button>`;

    return `
      <div class="src rounded-2xl border border-gray-200 bg-white p-4 lg:rounded-lg lg:shadow-sm" data-id="${s.id}">
        <div class="flex items-center gap-2">
          <input type="text" data-f="name" value="${esc(s.name)}" aria-label="Název zdroje"
                 class="block w-full flex-1 rounded-md border-gray-300 text-sm font-medium shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
          <button type="button" data-del="1" aria-label="Smazat zdroj"
                  class="h-9 w-9 shrink-0 rounded-md border border-gray-300 text-lg leading-none text-gray-400 hover:border-red-300 hover:text-red-600">×</button>
        </div>

        <div class="mt-3">
          <span class="${LABEL}">Klient z fakturace</span>
          <select data-f="clientId" class="${INPUT}">${clientOpts}</select>
        </div>

        <div class="mt-3 flex flex-wrap gap-2">
          ${modeChip('rate','Sazba')}${modeChip('fixed','Napevno')}${modeChip('invoice','Faktura')}
        </div>

        <div class="${s.mode==='rate'?'':'hidden'} mt-3" data-g="rateCore">
          <div class="grid grid-cols-2 gap-3">
            <div>
              <span class="${LABEL}">Sazba za jednotku</span>
              <input type="number" data-f="rate" value="${s.rate}" min="0" step="50" class="${INPUT}">
            </div>
            <div>
              <span class="${LABEL}">Jednotka</span>
              <select data-f="unit" class="${INPUT}">
                <option value="h"  ${s.unit==='h'?'selected':''}>hodina</option>
                <option value="md" ${s.unit==='md'?'selected':''}>MD (den)</option>
              </select>
            </div>
            <div class="${s.unit==='h'?'':'hidden'}" data-g="hpd">
              <span class="${LABEL}">Hodin denně</span>
              <input type="number" data-f="hoursPerDay" value="${s.hoursPerDay}" min="1" max="24" step="0.5" class="${INPUT}">
            </div>
          </div>
          <div class="mt-3">
            <span class="${LABEL}">Dovolená u klienta (dny / měsíc)</span>
            <div class="mt-1 grid grid-cols-6 gap-1.5">${vacGrid}</div>
          </div>
        </div>

        <div class="${s.mode==='fixed'?'':'hidden'} mt-3" data-g="fixedCore">
          <span class="${LABEL}">Částka za měsíc</span>
          <input type="number" data-f="fixed" value="${s.fixed}" min="0" step="1000" class="${INPUT}">
        </div>

        <div class="${s.mode==='invoice'?'':'hidden'} mt-3" data-g="invoiceCore">
          <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
            <div>
              <span class="${LABEL}">Datum připsání na účet</span>
              <input type="date" data-f="date" value="${s.date||''}" min="${PREV_YEAR}-01-01" max="${NEXT_YEAR}-12-31" class="${INPUT}">
            </div>
            <div>
              <span class="${LABEL}">Částka</span>
              <input type="number" data-f="amount" value="${s.amount}" min="0" step="1000" class="${INPUT}">
            </div>
          </div>
        </div>

        <div class="${s.mode==='invoice'?'hidden':''} mt-3" data-g="daterange">
          <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
            <div>
              <span class="${LABEL}">Od</span>
              <input type="date" data-f="from" value="${s.from||''}" min="${PREV_YEAR}-01-01" max="${NEXT_YEAR}-12-31" class="${INPUT}">
            </div>
            <div>
              <span class="${LABEL}">Do — nepovinné</span>
              <input type="date" data-f="to" value="${s.to||''}" min="${PREV_YEAR}-01-01" max="${NEXT_YEAR}-12-31" class="${INPUT}">
            </div>
          </div>
          <div class="mt-3">
            <span class="${LABEL}">Zpoždění platby za odvedenou práci</span>
            <select data-f="lag" class="${INPUT}">${lagOpts}</select>
          </div>
          <div class="mt-3">
            <span class="${LABEL}">Den v měsíci, kdy platba dorazí</span>
            <select data-f="payDay" class="${INPUT}">${dayOpts}</select>
          </div>
        </div>

        <div class="mt-3 border-t border-gray-100 pt-3 text-right" data-sum></div>
      </div>`;
  }

  function renderSources(){
    el('sources').innerHTML = sources.map(srcCardHTML).join('');
    compute();
  }

  function refreshCard(card, s){
    const hpdWrap = card.querySelector('[data-g="hpd"]');
    if (hpdWrap) hpdWrap.classList.toggle('hidden', s.unit !== 'h');
    if (s.mode === 'rate'){
      card.querySelectorAll('[data-vac]').forEach(inp => {
        const mi = +inp.dataset.vac;
        inp.disabled = workdaysFor(s, mi, activeYear) === 0;
      });
    }
  }

  el('sources').addEventListener('click', e => {
    const card = e.target.closest('.src'); if (!card) return;
    const s = sources.find(x => x.id === +card.dataset.id);
    if (e.target.dataset.del){
      sources = sources.filter(x => x.id !== s.id);
      renderSources(); return;
    }
    const m = e.target.dataset.mode;
    if (m && m !== s.mode){
      s.mode = m;
      renderSources();
    }
  });

  el('sources').addEventListener('input', e => {
    const card = e.target.closest('.src'); if (!card) return;
    const s = sources.find(x => x.id === +card.dataset.id);
    if (e.target.dataset.vac !== undefined){
      s.vacation[+e.target.dataset.vac] = Math.max(0, +e.target.value || 0);
      compute(); return;
    }
    const f = e.target.dataset.f; if (!f) return;
    s[f] = e.target.value;
    refreshCard(card, s);
    compute();
  });

  el('addSrc').addEventListener('click', () => { addSource(); renderSources(); });
  el('activity').addEventListener('change', compute);
  el('expMode').addEventListener('change', () => {
    el('expWrap').classList.toggle('hidden', el('expMode').value !== 'real');
    compute();
  });
  el('expReal').addEventListener('input', compute);
  el('carryAmount').addEventListener('input', compute);
  el('carryMonth').addEventListener('input', compute);

  el('yearToggle').addEventListener('click', e => {
    const y = e.target.dataset.year; if (!y) return;
    activeYear = +y;
    el('yearToggle').querySelectorAll('button').forEach(b => b.setAttribute('aria-pressed', String(+b.dataset.year === activeYear)));
    paintChips(el('yearToggle'));
    compute();
  });

  el('regimeToggle').addEventListener('click', e => {
    const r = e.target.dataset.regime; if (!r) return;
    regime = r;
    el('regimeToggle').querySelectorAll('button').forEach(b => b.setAttribute('aria-pressed', String(b.dataset.regime === regime)));
    paintChips(el('regimeToggle'));
    compute();
  });

  el('sideAct').addEventListener('change', e => {
    sideActivity = e.target.checked;
    compute();
  });

  function ceilings(){
    const a = el('activity').value;
    return [ a==='80' ? 2*M : (a==='60' ? 1.5*M : 1*M),
             (a==='80'||a==='60') ? 2*M : 1.5*M,
             2*M ];
  }

  function collectPayments(){
    const list = [];
    sources.forEach(s => {
      if (s.mode === 'invoice'){
        const d = parseISO(s.date); if (!d) return;
        list.push({date:d, y:d.getFullYear(), m:d.getMonth(), amount:+s.amount||0,
                   name:s.name, kind:'invoice', workYear:d.getFullYear()});
        return;
      }
      const lag = Math.max(0, +s.lag || 0);
      const day = +s.payDay || 15;
      WORK_YEARS.forEach(wy => {
        for (let mi=0; mi<12; mi++){
          const amt = amountFor(s, mi, wy);
          if (!amt) continue;
          const t = mi + lag, ty = wy + Math.floor(t/12), tm = ((t%12)+12)%12;
          list.push({date:payDate(ty,tm,day), y:ty, m:tm, amount:amt,
                     name:s.name, kind:'work', workYear:wy, workMonth:mi});
        }
      });
    });

    const carryAmt = +el('carryAmount').value || 0;
    if (carryAmt){
      const cm = Math.max(0, Math.min(11, +el('carryMonth').value || 0));
      list.push({date:payDate(BASE_YEAR,cm,15), y:BASE_YEAR, m:cm, amount:carryAmt,
                 name:'Ruční korekce', kind:'carry', workYear:PREV_YEAR});
    }
    return list.sort((a,b) => a.date - b.date);
  }

  function yearData(year, payments){
    const cash = new Array(12).fill(0);
    let carriedIn = 0;
    payments.forEach(p => {
      if (p.y !== year) return;
      cash[p.m] += p.amount;
      if (p.workYear < year) carriedIn += p.amount;
    });
    const overflow = payments
      .filter(p => p.workYear === year && p.y > year)
      .reduce((a,p) => a + p.amount, 0);
    return {cash, carriedIn, overflow, total: cash.reduce((a,b)=>a+b,0)};
  }

  function taxProfile(total){
    const caps = ceilings();
    let idx = caps.findIndex(c => total <= c);
    if (idx === -1) idx = 2;

    const canPausal = total <= 2*M;
    const usePausal = canPausal && (regime === 'pausal' || regime === 'auto');

    if (usePausal){
      const band = BANDS[idx];
      const yearDue = band.m * 12;
      return {mode:'pausal', idx, band, monthly:band.m, yearDue, forced: regime==='pausal',
              net: total - yearDue, effRate: total>0 ? yearDue/total : 0};
    }
    const a = el('activity').value;
    const realMode = el('expMode').value === 'real';
    const realExp = Math.max(0, +el('expReal').value || 0);
    const expenses = realMode ? realExp : Math.min(total * (+a/100), PAUSAL_CAP[a]);
    const base = Math.max(0, Math.floor((total - expenses)/100)*100);
    const socBase = Math.min(base*SOC_SHARE, SOC_CAP);
    const socAssess = sideActivity ? Math.max(0, base - SOC_SIDE_THRESHOLD) * SOC_SHARE : socBase;
    const socBaseFinal = sideActivity ? Math.min(socAssess, SOC_CAP) : socBase;
    const soc = SOC_RATE * socBaseFinal;
    const socExempt = sideActivity && base <= SOC_SIDE_THRESHOLD;
    const health = HEALTH_RATE * base * HEALTH_SHARE;
    const taxRaw = .15*Math.min(base, TAX_STEP) + .23*Math.max(0, base - TAX_STEP);
    const tax = Math.max(0, taxRaw - SLEVA);
    const totalDue = tax + soc + health;
    return {mode:'over', idx, a, realMode, realExp, expenses, base, soc, health,
            socExempt, side: sideActivity,
            taxRaw, tax, totalDue, forced: canPausal && regime==='klasik',
            capped: !realMode && total*(+a/100) > PAUSAL_CAP[a],
            net: total - (realMode ? realExp : 0) - totalDue,
            effRate: total>0 ? totalDue/total : 0};
  }

  function renderRemaining(payments, profile){
    const yearEnd = new Date(BASE_YEAR, 11, 31);
    el('remainingLabel').textContent = `Čistého ještě dorazí do 31. 12. ${BASE_YEAR}`;

    if (TODAY > yearEnd){
      el('remainingBig').innerHTML = '0 <span class="text-base font-normal text-gray-400">Kč</span>';
      el('remainingRows').innerHTML = '';
      el('remainingPlan').innerHTML = '';
      el('remainingNote').textContent = `Rok ${BASE_YEAR} už skončil.`;
      return;
    }

    const upcoming = payments.filter(p => p.y === BASE_YEAR && p.date >= TODAY);
    const gross = upcoming.reduce((a,p) => a + p.amount, 0);

    let rows = rowHTML('Dorazí na účet (hrubé)', kc(gross));
    let net, dueLeft = 0;

    if (profile.mode === 'pausal'){
      let count = 0;
      for (let m=0; m<12; m++) if (new Date(BASE_YEAR, m, 20) >= TODAY) count++;
      dueLeft = profile.monthly * count;
      net = gross - dueLeft;
      rows += rowHTML('Zbývající paušální zálohy', '−'+kc(dueLeft), {tone:'accent'})
            + rowHTML(`${count}× ${kc(profile.monthly)}, splatnost do 20.`, '', {sub:true});
    } else {
      dueLeft = gross * profile.effRate;
      net = gross - dueLeft;
      rows += rowHTML(`Odložit na odvody (${(profile.effRate*100).toFixed(1).replace('.',',')} %)`, '−'+kc(dueLeft), {tone:'accent'})
            + rowHTML(`vyúčtuje se až v roce ${BASE_YEAR+1}`, '', {sub:true});
    }

    rows += rowHTML('Počet plateb, které ještě přijdou', String(upcoming.length));
    if (upcoming.length){
      const last = upcoming[upcoming.length-1];
      rows += rowHTML('Poslední letošní platba', `${last.date.getDate()}. ${last.date.getMonth()+1}. ${last.y}`);
    }

    el('remainingBig').innerHTML = fmt(net) + ' <span class="text-base font-normal text-gray-400">Kč</span>';
    el('remainingBig').className = 'text-3xl font-bold tabular-nums ' + (net < 0 ? 'text-red-600' : 'text-gray-900');
    el('remainingRows').innerHTML = rowsWrap(rows);

    el('remainingPlan').innerHTML = upcoming.length
      ? `<div class="mt-3 border-t border-gray-100 pt-2">
          <div class="mb-1 text-[11px] font-semibold uppercase tracking-wide text-gray-400">Rozpis plateb</div>` +
        upcoming.map(p => `<div class="grid grid-cols-[3.5rem_1fr_auto] items-baseline gap-2 border-b border-gray-100 py-1.5 text-xs last:border-b-0">
            <span class="tabular-nums text-gray-400">${p.date.getDate()}. ${p.date.getMonth()+1}.</span>
            <span class="truncate text-gray-500">${esc(p.name)}${p.kind==='work' ? ` · práce ${MONTHS[p.workMonth]}${p.workYear!==BASE_YEAR?' '+p.workYear:''}` : (p.kind==='invoice'?' · faktura':'')}</span>
            <span class="whitespace-nowrap font-semibold tabular-nums text-gray-900">${kc(p.amount)}</span>
          </div>`).join('') + '</div>'
      : '';

    const df = TODAY.toLocaleDateString('cs-CZ',{day:'numeric',month:'numeric'});
    const afterYear = payments.filter(p => p.workYear <= BASE_YEAR && p.y > BASE_YEAR)
                              .reduce((a,p) => a + p.amount, 0);
    let note = `Počítá se od ${df} podle konkrétního data připsání každé platby — tedy zpoždění fakturace i dne v měsíci, kdy klient platí.`;
    if (afterYear > 0) note += ` Dalších <strong class="font-semibold text-gray-900">${kc(afterYear)}</strong> za letošní práci dorazí až po Novém roce, do letoška se proto nepočítá.`;
    el('remainingNote').innerHTML = note;
  }

  function renderYearView(year, data){
    const perMonth = data.cash;
    const total = data.total;
    const real = actualMonthlyAll(year);

    let cum = 0, crossMonth = -1;
    const peak = Math.max(2*M, total);
    const maxMonth = Math.max(1, ...perMonth.map(v => Math.abs(v)), ...real.paid.map((v,i) => v + real.open[i]));
    const nowMonth = (TODAY.getFullYear() === year) ? TODAY.getMonth() : -1;

    el('cal').innerHTML = perMonth.map((v, i) => {
      cum += v;
      if (crossMonth < 0 && cum > 2*M) crossMonth = i;
      const over = cum > 2*M;
      const monthTxt = v > 0 ? '+'+fmt(v) : (v < 0 ? fmt(v) : '—');
      const realTotal = real.paid[i] + real.open[i];
      const isNow = i === nowMonth;
      return `<div class="grid grid-cols-[2.25rem_1fr_6.5rem] items-center gap-3 py-2">
        <div class="text-[11px] uppercase ${isNow ? 'font-bold text-brand' : 'font-medium text-gray-400'}">${MONTHS[i]}</div>
        <div class="relative h-8 overflow-hidden rounded-md bg-gray-100">
          <div class="absolute left-0 top-1 h-2 rounded-full ${over ? 'bg-red-500' : 'bg-emerald-500'}" style="width:${Math.max(0, Math.min(100, cum/peak*100))}%"></div>
          <div class="absolute left-0 top-3.5 h-2 rounded-full ${v<0 ? 'bg-red-400' : 'bg-amber-400'}" style="width:${Math.min(100, Math.abs(v)/maxMonth*100)}%"></div>
          <div class="absolute bottom-1 left-0 h-2 rounded-full ${real.open[i]>0 ? 'bg-blue-300' : 'bg-blue-500'}" style="width:${Math.min(100, realTotal/maxMonth*100)}%"></div>
        </div>
        <div class="text-right leading-tight">
          <b class="block text-xs font-bold tabular-nums ${v!==0 ? 'text-gray-900' : 'text-gray-300'}">${monthTxt}</b>
          <small class="block text-[10px] tabular-nums text-gray-400">Σ ${fmt(cum)}</small>
          <small class="block text-[10px] tabular-nums ${realTotal>0 ? 'text-blue-600' : 'text-gray-300'}">${realTotal>0 ? 'fakt. '+fmt(realTotal) : '—'}</small>
        </div>
      </div>`;
    }).join('');

    el('calLabel').textContent = `Rok ${year} po měsících — kdy peníze fakticky dorazí`;

    renderReality(year, total, real);

    el('cross').innerHTML = crossMonth >= 0
      ? warnBox(`Dva miliony na účtu překročíš v měsíci <strong class="font-semibold">${MONTHS[crossMonth]}</strong>. Od té chvíle paušální režim pro rok ${year} neplatí.`)
      : '';

    const msgs = [];
    if (data.overflow > 0) msgs.push(`Práce v hodnotě přibližně <strong class="font-semibold text-gray-900">${kc(data.overflow)}</strong> se zaplatí až v roce ${year+1} — do příjmů a pásma roku ${year} se nepočítá.`);
    if (data.carriedIn > 0) msgs.push(`Zahrnuje <strong class="font-semibold text-gray-900">${kc(data.carriedIn)}</strong> přenesených z konce roku ${year-1}.`);
    el('overflow').innerHTML = msgs.length ? noteBox(msgs.join(' ')) : '';

    const caps = ceilings();
    const profile = taxProfile(total);
    const over = profile.mode === 'over';
    el('ladder').innerHTML = BANDS.map((b, i) => {
      const state = over ? 'blocked' : (i === profile.idx ? 'active' : (total > caps[i] ? 'blocked' : 'ok'));
      const active = state === 'active';
      return `<div class="flex items-center gap-3 px-4 py-3 ${active ? 'bg-brand-light' : ''} ${state==='blocked' ? 'opacity-40' : ''}">
        <div class="w-8 shrink-0 border-r border-gray-200 pr-3 text-center text-sm font-bold ${active ? 'text-brand' : 'text-gray-400'}">${b.r}</div>
        <div class="flex-1 text-xs ${active ? 'text-gray-900' : 'text-gray-500'}">Příjem do ${(caps[i]/M).toLocaleString('cs-CZ',{maximumFractionDigits:1})} mil. Kč</div>
        <div class="whitespace-nowrap text-sm font-bold tabular-nums ${active ? 'text-brand' : 'text-gray-500'}">${fmt(b.m)} Kč</div>
      </div>`;
    }).join('');
    el('bandSection').classList.toggle('opacity-50', over);

    over ? renderOver(total, year, profile) : renderPausal(total, year, profile);
  }

  // ---- plán vs. skutečnost ----
  function renderReality(year, planTotal, real){
    const paid = real.paid.reduce((a,b)=>a+b,0);
    const open = real.open.reduce((a,b)=>a+b,0);

    if (paid === 0 && open === 0){
      el('reality').innerHTML = noteBox(`Za rok ${year} zatím nejsou žádné faktury, se kterými by šlo plán porovnat. Přiřaď zdrojům klienty a skutečnost se doplní sama.`);
      return;
    }

    const linked = linkedClientIds();
    let linkedPaid = 0, linkedOpen = 0;
    linked.forEach(id => { const a = actualYear(id, year); linkedPaid += a.paid; linkedOpen += a.open; });

    const diff = (paid + open) - planTotal;
    const diffTxt = diff >= 0 ? '+'+fmt(diff) : fmt(diff);

    let rows = rowHTML(`Plán na rok ${year}`, kc(planTotal))
             + rowHTML(`Vyfakturováno v roce ${year}`, kc(paid + open), {tone:'real'})
             + rowHTML('z toho uhrazeno', kc(paid), {sub:true})
             + rowHTML('čeká na úhradu', kc(open), {sub:true})
             + rowHTML('Vyfakturováno proti plánu', diffTxt + ' Kč', {tone:'accent'});

    if (linked.length && Math.round(linkedPaid + linkedOpen) !== Math.round(paid + open)){
      rows += rowHTML('z toho u klientů napojených na zdroje', kc(linkedPaid + linkedOpen), {sub:true});
    }
    if (!linked.length){
      rows += rowHTML('žádný zdroj nemá přiřazeného klienta — porovnává se proti všem fakturám', '', {sub:true});
    }

    el('reality').innerHTML = `<div class="mt-3 rounded-xl border border-gray-200 bg-gray-50 px-3 py-1">
      <div class="mb-1 pt-2 text-[11px] font-semibold uppercase tracking-wide text-gray-400">Plán vs. skutečnost</div>${rows}
      <p class="pb-2 pt-2 text-[11px] leading-relaxed text-gray-400">
        Faktura se počítá do měsíce, kdy byla vystavená. Plán oproti tomu sleduje, kdy peníze dorazí na účet, takže se po měsících posune o zpoždění platby.
      </p></div>`;
  }

  function renderPausal(total, year, p){
    const proj = year === NEXT_YEAR ? ' (prognóza)' : '';
    el('resultLabel').textContent = `Zbyde ti za rok ${year}${proj}`;
    el('netYear').innerHTML = fmt(p.net) + ' <span class="text-base font-normal text-gray-400">Kč</span>';
    el('netYear').className = 'text-3xl font-bold tabular-nums ' + (p.net < 0 ? 'text-red-600' : 'text-emerald-600');
    el('rows').innerHTML = rowsWrap(
        rowHTML('Příjem přijatý za rok', kc(total))
      + rowHTML('Paušální záloha měsíčně', kc(p.monthly), {tone:'accent'})
      + rowHTML('Paušální záloha za rok', kc(p.yearDue), {tone:'accent'})
      + rowHTML('Zbyde měsíčně v průměru', kc(p.net/12))
      + rowHTML('Odvedeš z příjmu', total>0?(p.effRate*100).toFixed(1).replace('.',',')+' %':'—'));
    let note = total > 1.8*M
      ? `<strong class="font-semibold text-gray-900">Zbývá ti ${kc(2*M-total)} do stropu.</strong> Pak končí paušál i osvobození od DPH.`
      : 'Zálohy platíš do 20. dne každého měsíce, jedna platba pokrývá daň i obojí pojistné.';
    if (year === NEXT_YEAR) note += ' Prognóza předpokládá stejné klienty, sazby i harmonogram jako letos a sazby paušální daně beze změny.';
    el('resultNote').innerHTML = note;
  }

  function renderOver(total, year, p){
    const proj = year === NEXT_YEAR ? ' (prognóza)' : '';
    el('resultLabel').textContent = `Zbyde ti za rok ${year}${proj} po klasickém přiznání`;
    el('netYear').innerHTML = fmt(p.net) + ' <span class="text-base font-normal text-gray-400">Kč</span>';
    el('netYear').className = 'text-3xl font-bold tabular-nums ' + (p.net < 0 ? 'text-red-600' : 'text-emerald-600');

    el('rows').innerHTML = rowsWrap(
        rowHTML('Příjem přijatý za rok', kc(total))
      + rowHTML(p.realMode?'Skutečné výdaje':'Paušální výdaje '+p.a+' %', '−'+kc(p.expenses))
      + (p.capped ? rowHTML(`zastropováno na ${kc(PAUSAL_CAP[p.a])}`, '', {sub:true}) : '')
      + rowHTML('Základ daně', kc(p.base))
      + rowHTML('Daň z příjmů po slevě', kc(p.tax), {tone:'accent'})
      + rowHTML(`15 % do ${fmt(TAX_STEP)}, pak 23 %`, kc(p.taxRaw), {sub:true})
      + rowHTML('sleva na poplatníka', '−'+kc(SLEVA), {sub:true})
      + rowHTML('Sociální pojištění', kc(p.soc), {tone:'accent'})
      + (p.side ? rowHTML(p.socExempt?`vedlejší činnost — zisk pod ${fmt(SOC_SIDE_THRESHOLD)}, sociální 0`:`vedlejší činnost — jen ze zisku nad ${fmt(SOC_SIDE_THRESHOLD)}`, '', {sub:true}) : '')
      + rowHTML('Zdravotní pojištění', kc(p.health), {tone:'accent'})
      + rowHTML('Odvody celkem', kc(p.totalDue))
      + rowHTML('Odvedeš z příjmu', (p.effRate*100).toFixed(1).replace('.',',')+' %')
      + rowHTML('Zbyde měsíčně v průměru', kc(p.net/12)));

    const dph = total > 2536500
      ? 'Obrat přes 2 536 500 Kč znamená, že plátcem DPH se stáváš hned další den po překročení.'
      : `Plátcem DPH se stáváš od 1. ledna ${year+1}, pokud nepřekročíš i hranici 2 536 500 Kč.`;
    let note = p.forced
      ? `<strong class="font-semibold text-gray-900">Ručně zvolené klasické přiznání</strong> i pod 2 miliony. Podáváš přiznání i oba přehledy. `
      : `<strong class="font-semibold text-gray-900">Podáváš přiznání i oba přehledy.</strong> Zaplacené paušální zálohy se ti do odvodů započtou. `;
    if (p.forced){
      const alt = BANDS[p.idx];
      const altNet = total - alt.m*12;
      note += `Pro srovnání: v paušální dani bys odvedl(a) ${kc(alt.m*12)} a zbylo by ${kc(altNet)}. ${dph}`;
    } else {
      note += dph;
    }
    if (year === NEXT_YEAR) note += ' Prognóza předpokládá stejné klienty, sazby i harmonogram jako letos.';
    el('resultNote').innerHTML = note;
  }

  function sourceSummary(s, year){
    let plan;
    if (s.mode === 'invoice'){
      const d = parseISO(s.date);
      const inYear = d && d.getFullYear() === year;
      plan = `<span class="text-sm font-semibold tabular-nums text-gray-900">${kc(inYear ? (+s.amount||0) : 0)}</span><span class="block text-[11px] text-gray-400">jednorázově — dorazí ${fmtDate(s.date)}${inYear?'':` (mimo rok ${year})`}</span>`;
    } else {
      const dayTxt = (+s.payDay===31) ? 'poslední den' : (+s.payDay||15)+'.';
      const lagTxt = +s.lag>0 ? `platba +${s.lag} měs., ${dayTxt}` : `platba týž měsíc, ${dayTxt}`;

      if (s.mode === 'fixed'){
        let yearTotal = 0;
        for (let i=0;i<12;i++) yearTotal += amountFor(s,i,year);
        plan = `<span class="text-sm font-semibold tabular-nums text-gray-900">${kc(yearTotal)}</span><span class="block text-[11px] text-gray-400">odpracováno v ${year} · ${kc(s.fixed)} / měsíc · ${lagTxt}</span>`;
      } else {
        let units = 0, wdTotal = 0, vacTotal = 0, yearTotal = 0;
        for (let i=0;i<12;i++){
          const wd = workdaysFor(s,i,year), vac = Math.min(+(s.vacation[i])||0, wd), billable = Math.max(0, wd - vac);
          wdTotal += wd; vacTotal += vac;
          units += s.unit === 'h' ? billable*(+s.hoursPerDay||8) : billable;
          yearTotal += amountFor(s,i,year);
        }
        const unitLabel = s.unit === 'h' ? 'h' : 'MD';
        plan = `<span class="text-sm font-semibold tabular-nums text-gray-900">${kc(yearTotal)}</span><span class="block text-[11px] text-gray-400">odpracováno v ${year} · ${fmt(wdTotal)} prac. dní − ${fmt(vacTotal)} dovolená → ${fmt(units)} ${unitLabel} · ${lagTxt}</span>`;
      }
    }

    if (s.clientId == null || s.clientId === '') return plan;

    const a = actualYear(s.clientId, year);
    const real = (a.paid === 0 && a.open === 0)
      ? `<span class="font-semibold text-gray-400">Vyfakturováno v ${year}: nic</span><span class="block text-[11px] text-gray-400">u tohoto klienta zatím žádné faktury</span>`
      : `<span class="font-semibold text-blue-600">Vyfakturováno v ${year}: ${kc(a.paid + a.open)}</span><span class="block text-[11px] text-gray-400">z toho ${kc(a.paid)} uhrazeno${a.open>0?` · ${kc(a.open)} čeká`:''}</span>`;
    return plan + `<span class="mt-2 block border-t border-dashed border-gray-200 pt-2 text-xs">${real}</span>`;
  }

  function compute(){
    document.querySelectorAll('.src').forEach(card => {
      const s = sources.find(x => x.id === +card.dataset.id);
      if (!s) return;
      refreshCard(card, s);
      card.querySelector('[data-sum]').innerHTML = sourceSummary(s, activeYear);
    });

    const payments = collectPayments();

    const autoCarry = payments
      .filter(p => p.kind === 'work' && p.workYear === PREV_YEAR && p.y === BASE_YEAR)
      .reduce((a,p) => a + p.amount, 0);
    el('autoCarryNote').innerHTML = autoCarry > 0
      ? `Podle dat „Od“ tvých zdrojů se z práce odvedené v roce ${PREV_YEAR} přenáší na začátek roku ${BASE_YEAR} <strong class="font-semibold text-gray-900">${kc(autoCarry)}</strong>.`
      : `Z roku ${PREV_YEAR} se automaticky nepřenáší nic — žádný zdroj nemá datum „Od“ před 1. 1. ${BASE_YEAR}.`;

    const y26 = yearData(BASE_YEAR, payments);
    const yActive = activeYear === BASE_YEAR ? y26 : yearData(activeYear, payments);

    renderYearView(activeYear, yActive);
    renderRemaining(payments, taxProfile(y26.total));

    if (loaded) scheduleSave();
  }

  // ---- ukládání na server ----
  function setStatus(t){ const s = el('saveStatus'); if (s) s.textContent = t; }

  let saveTimer = null;
  function scheduleSave(){
    clearTimeout(saveTimer);
    setStatus('Ukládání…');
    saveTimer = setTimeout(doSave, 700);
  }

  function serialize(){
    return {
      regime,
      sideActivity,
      activity: el('activity').value,
      expMode: el('expMode').value,
      expReal: +el('expReal').value || 0,
      carryAmount: +el('carryAmount').value || 0,
      carryMonth: Math.max(0, Math.min(11, +el('carryMonth').value || 0)),
      sources: sources.map(s => ({
        clientId: (s.clientId === '' || s.clientId == null) ? null : +s.clientId,
        name: String(s.name || 'Zdroj').slice(0, 255),
        mode: s.mode,
        rate: +s.rate || 0,
        unit: s.unit === 'md' ? 'md' : 'h',
        hoursPerDay: Math.max(0.5, Math.min(24, +s.hoursPerDay || 8)),
        lag: Math.max(0, Math.min(3, Math.round(+s.lag || 0))),
        payDay: Math.max(1, Math.min(31, Math.round(+s.payDay || 15))),
        from: s.from || null,
        to: s.to || null,
        fixed: +s.fixed || 0,
        date: s.date || null,
        amount: +s.amount || 0,
        vacation: Array.from({length:12}, (_,i) => Math.max(0, Math.min(31, Math.round(+(s.vacation||[])[i] || 0))))
      }))
    };
  }

  async function doSave(){
    try{
      const res = await fetch(SAVE_URL, {
        method: 'PUT',
        headers: {'Content-Type':'application/json', 'Accept':'application/json', 'X-CSRF-TOKEN': CSRF},
        body: JSON.stringify(serialize())
      });
      if (!res.ok){
        if (res.status === 422) setStatus('Uložení se nepodařilo — zkontroluj zadané hodnoty');
        else if (res.status === 401 || res.status === 419) setStatus('Uložení se nepodařilo — načti stránku znovu a přihlas se');
        else setStatus('Uložení se nepodařilo');
        return;
      }
      // Vypršelá session končí přesměrováním na přihlášení — to se tváří jako úspěch,
      // dokud se člověk nepodívá, že odpověď není JSON.
      if (res.redirected || !(res.headers.get('content-type') || '').includes('application/json')){
        setStatus('Uložení se nepodařilo — načti stránku znovu a přihlas se');
        return;
      }
      const data = await res.json();
      setStatus('Uloženo v ' + (data.at || ''));
    }catch(err){
      setStatus('Uložení se nepodařilo — jsi offline?');
    }
  }

  el('resetBtn').addEventListener('click', () => {
    sources = []; uid = 0;
    if (CLIENTS.length){
      CLIENTS.forEach(c => addSource({clientId:c.id, name:c.name}));
    } else {
      addSource({name:'Klient A'});
    }
    regime = 'auto';
    el('regimeToggle').querySelectorAll('button').forEach(b => b.setAttribute('aria-pressed', String(b.dataset.regime === 'auto')));
    paintChips(el('regimeToggle'));
    sideActivity = false;
    el('sideAct').checked = false;
    el('activity').value = '60';
    el('expMode').value = 'pausal';
    el('expWrap').classList.add('hidden');
    el('expReal').value = 0;
    el('carryAmount').value = 0;
    el('carryMonth').value = 0;
    renderSources();
    scheduleSave();
  });

  // ---- start ----
  sources = (BOOT.sources || []).map(s => Object.assign(blankSource(), s));
  uid = sources.reduce((m,s) => Math.max(m, +s.id || 0), 0);
  if (!sources.length) addSource({name:'Klient A'});

  regime = BOOT.regime || 'auto';
  el('regimeToggle').querySelectorAll('button').forEach(b => b.setAttribute('aria-pressed', String(b.dataset.regime === regime)));
  paintChips(el('regimeToggle'));
  paintChips(el('yearToggle'));
  sideActivity = !!BOOT.sideActivity;
  el('sideAct').checked = sideActivity;
  el('activity').value = BOOT.activity || '60';
  el('expMode').value = BOOT.expMode || 'pausal';
  el('expWrap').classList.toggle('hidden', el('expMode').value !== 'real');
  el('expReal').value = BOOT.expReal ?? 0;
  el('carryAmount').value = BOOT.carryAmount ?? 0;
  el('carryMonth').value = BOOT.carryMonth ?? 0;

  renderSources();
  loaded = true;
})();
</script>
