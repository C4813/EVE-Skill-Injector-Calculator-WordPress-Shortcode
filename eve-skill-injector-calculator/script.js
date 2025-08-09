(function(){
  'use strict';

  const REST = (window.EVE_SIC_DATA && window.EVE_SIC_DATA.rest) || { url:'', nonce:'' };

  function enforcePositiveIntegerInput(el) {
    el.setAttribute('min','1'); el.setAttribute('step','1');
    el.setAttribute('inputmode','numeric'); el.setAttribute('pattern','\\d*');
    el.addEventListener('input', () => {
      const digits = el.value.replace(/[^\d]/g, '');
      el.value = digits === '' || digits === '0' ? '1' : digits.replace(/^0+/, '') || '1';
    });
    el.addEventListener('paste', (e) => {
      const t = (e.clipboardData || window.clipboardData).getData('text');
      if (!/^[1-9]\d*$/.test(t)) e.preventDefault();
    });
    el.addEventListener('blur', () => {
      let n = parseInt(el.value, 10); if (!Number.isFinite(n) || n < 1) n = 1; el.value = String(n);
    });
    el.addEventListener('keydown', (e) => {
      const allowed = ['Backspace','Delete','ArrowLeft','ArrowRight','ArrowUp','ArrowDown','Home','End','Tab','Enter'];
      if (allowed.includes(e.key) || /^\d$/.test(e.key)) return; e.preventDefault();
    });
  }

  function $(id){ return document.getElementById(id); }
  function setText(id, text){ const el = $(id); if (el) el.textContent = text; }
  function show(id){ const el = $(id); if (el){ el.classList.remove('is-hidden'); el.setAttribute('aria-hidden','false'); } }
  function toISK(n){ return Number(n||0).toLocaleString(); }
  function clearChildren(node){ while (node && node.firstChild) node.removeChild(node.firstChild); }

  function getGain(sp, thresholds, gains){ for (let i=0;i<thresholds.length;i++) if (sp < thresholds[i]) return gains[i]; return gains[gains.length-1]; }
  function getLargeGain(sp){ return getGain(sp, [5000000, 50000000, 80000000], [500000, 400000, 300000, 150000]); }
  function getSmallGain(sp){ return getGain(sp, [5000000, 50000000, 80000000], [100000, 80000, 60000, 30000]); }

  async function fetchPrices(){
    if (!REST.url) return null;
    try{
      const res = await fetch(REST.url, { headers: { 'X-WP-Nonce': REST.nonce || '' } });
      if (!res.ok) return null;
      return await res.json();
    }catch(_){ return null; }
  }

  async function calculateInjectors(){
    const prices = await fetchPrices();
    show('result');
    if (!prices){ setText('resNote','Prices unavailable. Please try again.'); return; }

    let currentSP = parseInt($('currentSP').value,10);
    let injectSP  = parseInt($('injectSP').value,10);
    if (!Number.isFinite(currentSP) || !Number.isFinite(injectSP) || currentSP < 1 || injectSP < 1){
      setText('resNote','Please enter valid numbers.'); return;
    }

    let remaining = injectSP, largeInjectors = 0, smallInjectors = 0;
    while (remaining > 0){
      const gainLarge = getLargeGain(currentSP);
      const gainSmall = getSmallGain(currentSP);
      if (remaining >= gainLarge){ largeInjectors++; currentSP += gainLarge; remaining -= gainLarge; }
      else if (remaining >= gainSmall){ smallInjectors++; currentSP += gainSmall; remaining -= gainSmall; }
      else { smallInjectors++; currentSP += gainSmall; remaining = 0; }
    }

    const L = prices[40520] || prices['40520'] || {buy:0,sell:0};
    const S = prices[45635] || prices['45635'] || {buy:0,sell:0};
    const totalBuy  = largeInjectors*(L.buy||0)  + smallInjectors*(S.buy||0);
    const totalSell = largeInjectors*(L.sell||0) + smallInjectors*(S.sell||0);

    setText('resLarge', String(largeInjectors));
    setText('resSmall', String(smallInjectors));
    setText('resBuy',   `${toISK(totalBuy)} ISK`);
    setText('resSell',  `${toISK(totalSell)} ISK`);
    setText('resNote', `${largeInjectors} Large @ ${toISK(L.buy)} buy / ${toISK(L.sell)} sell` +
                       (smallInjectors ? `, ${smallInjectors} Small @ ${toISK(S.buy)} buy / ${toISK(S.sell)} sell` : ''));
  }

  function calculateSPGain(){
    show('spGainResult');
    let sp = parseInt($('spOwned').value,10);
    let large = parseInt($('largeInjectors').value,10) || 0;
    let small = parseInt($('smallInjectors').value,10) || 0;
    if (!Number.isFinite(sp) || sp < 1 || (large < 1 && small < 1)){ setText('resSPGained',''); return; }

    let gain = 0;
    for (let i=0;i<large;i++){ const g = getLargeGain(sp); gain += g; sp += g; }
    for (let i=0;i<small;i++){ const g = getSmallGain(sp); gain += g; sp += g; }

    setText('resSPGained', `${toISK(gain)} SP`);
  }

  async function calculateGoalInjectors(){
    const prices = await fetchPrices();
    show('goalResult');
    if (!prices){ setText('goalNote','Prices unavailable. Please try again.'); return; }

    let currentSP = parseInt($('currentSPGoal').value,10);
    let targetSP  = parseInt($('targetSPGoal').value,10);
    if (!Number.isFinite(currentSP) || !Number.isFinite(targetSP) || currentSP < 1 || targetSP < 1 || targetSP <= currentSP){
      setText('goalNote','Please enter valid numbers.'); return;
    }

    let remaining = targetSP - currentSP, largeInjectors = 0, smallInjectors = 0;
    while (remaining > 0){
      const gainLarge = getLargeGain(currentSP);
      const gainSmall = getSmallGain(currentSP);
      if (remaining >= gainLarge){ largeInjectors++; currentSP += gainLarge; remaining -= gainLarge; }
      else if (remaining >= gainSmall){ smallInjectors++; currentSP += gainSmall; remaining -= gainSmall; }
      else { smallInjectors++; currentSP += gainSmall; remaining = 0; }
    }

    const L = prices[40520] || prices['40520'] || {buy:0,sell:0};
    const S = prices[45635] || prices['45635'] || {buy:0,sell:0};
    const totalBuy  = largeInjectors*(L.buy||0)  + smallInjectors*(S.buy||0);
    const totalSell = largeInjectors*(L.sell||0) + smallInjectors*(S.sell||0);

    setText('goalLarge', String(largeInjectors));
    setText('goalSmall', String(smallInjectors));
    setText('goalBuy',   `${toISK(totalBuy)} ISK`);
    setText('goalSell',  `${toISK(totalSell)} ISK`);
    setText('goalNote', `${largeInjectors} Large @ ${toISK(L.buy)} buy / ${toISK(L.sell)} sell` +
                        (smallInjectors ? `, ${smallInjectors} Small @ ${toISK(S.buy)} buy / ${toISK(S.sell)} sell` : ''));
  }

  async function calculateExtractors(){
    const prices = await fetchPrices();
    show('extractorResult');
    if (!prices){ setText('extNote','Prices unavailable. Please try again.'); return; }

    let sp = parseInt($('spExtract').value,10);
    if (!Number.isFinite(sp) || sp < 5500000){
      const body = $('profitBody'); clearChildren(body);
      setText('extUsable',''); setText('extBuy',''); setText('extSell',''); setText('injBuy',''); setText('injSell','');
      setText('extNote','Must have at least 5,500,000 SP to use skill extractors.');
      return;
    }

    const L = prices[40520] || prices['40520'] || {buy:0,sell:0};
    const X = prices[40519] || prices['40519'] || {buy:0,sell:0};

    const usable = Math.floor((sp - 5500000) / 500000) + 1;
    const extractorBuyTotal  = usable * (X.buy || 0);
    const extractorSellTotal = usable * (X.sell|| 0);
    const injectorBuyTotal   = usable * (L.buy || 0);
    const injectorSellTotal  = usable * (L.sell|| 0);

    setText('extUsable', String(usable));
    setText('extBuy',  `${toISK(extractorBuyTotal)} ISK`);
    setText('extSell', `${toISK(extractorSellTotal)} ISK`);
    setText('injBuy',  `${toISK(injectorBuyTotal)} ISK`);
    setText('injSell', `${toISK(injectorSellTotal)} ISK`);

    const scenarios = [
      ['Buy Extractors (Buy Value), Sell Injectors (Buy Value)',  injectorBuyTotal - extractorBuyTotal],
      ['Buy Extractors (Buy Value), Sell Injectors (Sell Value)', injectorSellTotal - extractorBuyTotal],
      ['Buy Extractors (Sell Value), Sell Injectors (Sell Value)',injectorSellTotal - extractorSellTotal],
      ['Buy Extractors (Sell Value), Sell Injectors (Buy Value)', injectorBuyTotal - extractorSellTotal],
    ];
    const body = $('profitBody'); clearChildren(body);
    scenarios.forEach(([label, profit]) => {
      const tr = document.createElement('tr');
      const td1 = document.createElement('td');
      const td2 = document.createElement('td');

      const idx = label.indexOf(',');
      if (idx !== -1){
        td1.appendChild(document.createTextNode(label.slice(0, idx + 1)));
        td1.appendChild(document.createElement('br'));
        td1.appendChild(document.createTextNode(label.slice(idx + 2)));
      } else {
        td1.textContent = label;
      }

      td2.textContent = `${toISK(profit)} ISK`;
      tr.appendChild(td1); tr.appendChild(td2); body.appendChild(tr);
    });

    setText('extNote','');
  }

  document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('#eve-skill-calculators input[type="number"]').forEach(enforcePositiveIntegerInput);
    $('btnCalcInjectors')?.addEventListener('click', calculateInjectors);
    $('btnCalcSPGain')?.addEventListener('click', calculateSPGain);
    $('btnCalcGoal')?.addEventListener('click', calculateGoalInjectors);
    $('btnCalcExtractors')?.addEventListener('click', calculateExtractors);
  });
})();
