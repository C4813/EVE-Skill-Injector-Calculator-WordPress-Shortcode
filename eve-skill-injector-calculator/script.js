/* EVE Skill Injector Calculator – script.js
 * Vanilla JS. No innerHTML. No inline styles.
 */

(function () {
  "use strict";

  // ===== Utilities =====
  const $ = (id) => document.getElementById(id);

  const setText = (id, text) => {
    const el = $(id);
    if (el) el.textContent = String(text ?? "");
  };

  const show = (id) => {
    const el = $(id);
    if (!el) return;
    el.classList.remove("is-hidden");
    el.setAttribute("aria-hidden", "false");
  };

  const hideRow = (valueId) => {
    const el = $(valueId);
    if (!el) return;
    const row = el.closest("div");
    if (row) {
      row.classList.add("is-hidden");
      row.setAttribute("aria-hidden", "true");
    }
  };

  const getInt = (id) => {
    const el = $(id);
    if (!el) return NaN;
    const n = parseInt(el.value, 10);
    return Number.isFinite(n) ? n : NaN;
  };

  const clearChildren = (el) => {
    if (!el) return;
    while (el.firstChild) el.removeChild(el.firstChild);
  };

  // Create a table row with two cells; supports "\n" in left cell
  const appendTwoColRow = (tbody, leftText, rightText) => {
    if (!tbody) return;
    const tr = document.createElement("tr");
    const td1 = document.createElement("td");
    const td2 = document.createElement("td");

    const parts = String(leftText).split("\n");
    parts.forEach((part, idx) => {
      td1.appendChild(document.createTextNode(part));
      if (idx < parts.length - 1) td1.appendChild(document.createElement("br"));
    });

    td2.textContent = rightText;
    tr.appendChild(td1);
    tr.appendChild(td2);
    tbody.appendChild(tr);
  };

  // Stylable Buy/Sell note block
  const setBuySellNote = (id, buyISK, sellISK) => {
    const el = $(id);
    if (!el) return;

    clearChildren(el);
    el.classList.add("note-buy-sell");

    const buyLabel = document.createElement("span");
    buyLabel.className = "note-label";
    buyLabel.textContent = "Buy: ";

    const buyValue = document.createElement("span");
    buyValue.className = "note-value";
    buyValue.textContent = `${fmtNum(buyISK)} ISK`;

    const sep = document.createTextNode(" · ");

    const sellLabel = document.createElement("span");
    sellLabel.className = "note-label";
    sellLabel.textContent = "Sell: ";

    const sellValue = document.createElement("span");
    sellValue.className = "note-value";
    sellValue.textContent = `${fmtNum(sellISK)} ISK`;

    el.appendChild(buyLabel);
    el.appendChild(buyValue);
    el.appendChild(sep);
    el.appendChild(sellLabel);
    el.appendChild(sellValue);
  };

  // Restrict inputs to digits (extra guard for paste, IME, etc.)
  const restrictNumberInputs = () => {
    const inputs = document.querySelectorAll("input[type='number']");
    inputs.forEach((input) => {
      input.addEventListener("input", () => {
        // Keep digits only; allow empty string so user can clear
        input.value = input.value.replace(/[^0-9]/g, "");
      });
    });
  };

  // Format large ISK/SP numbers with spaces
  const fmtNum = (n) => {
    if (!Number.isFinite(n)) return "0";
    return Math.round(n).toString().replace(/\B(?=(\d{3})+(?!\d))/g, " ");
  };

  // ===== EVE rules: SP per injector based on current SP =====
  function gainLargeBySP(currentSP) {
    if (currentSP < 5_000_000) return 500_000;
    if (currentSP < 50_000_000) return 400_000;
    if (currentSP < 80_000_000) return 300_000;
    return 150_000;
  }
  function gainSmallBySP(currentSP) {
    return Math.floor(gainLargeBySP(currentSP) / 5);
  }

  function sumGain(currentSP, injectors, perInjectorFn) {
    let sp = currentSP;
    let total = 0;
    for (let i = 0; i < injectors; i++) {
      const g = perInjectorFn(sp);
      total += g;
      sp += g;
    }
    return total;
  }

  function injectorsToTarget(currentSP, targetSP) {
    let sp = currentSP;
    let remaining = Math.max(0, targetSP - sp);
    let large = 0;
    let small = 0;

    while (remaining > 0) {
      const gL = gainLargeBySP(sp);
      const gS = gainSmallBySP(sp);

      if (remaining >= gL) {
        large++; sp += gL; remaining -= gL;
      } else if (remaining >= gS) {
        small++; sp += gS; remaining -= gS;
      } else {
        small++; sp += gS; remaining = 0;
      }
    }
    return { large, small, total: large + small };
  }

  function usableExtractors(currentSP) {
    if (!Number.isFinite(currentSP) || currentSP <= 5_000_000) return 0;
    return Math.max(0, Math.floor((currentSP - 5_000_000) / 500_000));
  }

  // ===== Prices via REST (localized in PHP into window.EVE_SIC_DATA) =====
  async function fetchPrices() {
    try {
      const url = (window.EVE_SIC_DATA && EVE_SIC_DATA.rest && EVE_SIC_DATA.rest.url) || "";
      if (!url) return null;

      const resp = await fetch(url, {
        method: "GET",
        headers: {
          Accept: "application/json",
          "X-WP-Nonce": (EVE_SIC_DATA && EVE_SIC_DATA.rest && EVE_SIC_DATA.rest.nonce) || "",
        },
        credentials: "same-origin",
        cache: "no-cache",
      });

      if (!resp.ok) return null;
      const data = await resp.json();
      if (!data || typeof data !== "object") return null;

      const out = {};
      for (const k of Object.keys(data)) {
        out[Number(k)] = {
          buy: Number(data[k]?.buy || 0),
          sell: Number(data[k]?.sell || 0),
        };
      }
      return out;
    } catch (e) {
      console.error("Price fetch failed:", e);
      return null;
    }
  }

  // ===== Section A: How many Injectors do I need? =====
  async function calculateRequiredInjectors() {
    const currentSP = getInt("currentSP");
    const injectSP = getInt("injectSP");

    show("result");
    setText("resNote", "");

    if (!Number.isFinite(currentSP) || currentSP < 1 || !Number.isFinite(injectSP) || injectSP < 1) {
      setText("resLarge", "");
      setText("resSmall", "");
      setText("resNote", "Please enter valid numbers.");
      return;
    }

    const targetSP = currentSP + injectSP;
    const plan = injectorsToTarget(currentSP, targetSP);

    setText("resLarge", String(plan.large));
    setText("resSmall", String(plan.small));

    const prices = await fetchPrices();
    if (prices) {
      const L = prices[40520] || { buy: 0, sell: 0 };
      const S = prices[45635] || { buy: 0, sell: 0 };
      const totalBuyISK  = plan.large * L.sell + plan.small * S.sell;
      const totalSellISK = plan.large * L.buy  + plan.small * S.buy;
      setBuySellNote("resNote", totalBuyISK, totalSellISK);
    } else {
      setText("resNote", "Buy: — · Sell: —");
    }
  }

  // ===== Section B: SP gained from N injectors =====
  function calculateSPGain() {
    const spBase = getInt("spGain");
    const count = getInt("injectorsGain");

    show("gainResult");

    if (!Number.isFinite(spBase) || spBase < 1 || !Number.isFinite(count) || count < 1) {
      setText("gainLarge", "");
      setText("gainSmall", "");
      setText("gainNote", "Please enter valid numbers.");
      return;
    }

    const totalLarge = sumGain(spBase, count, gainLargeBySP);
    const totalSmall = sumGain(spBase, count, gainSmallBySP);

    setText("gainLarge", `${fmtNum(totalLarge)} SP`);
    setText("gainSmall", `${fmtNum(totalSmall)} SP`);

    hideRow("gainTotal");
    setText("gainTotal", "");
    setText("gainNote", "");
  }

  // ===== Section C: Injectors needed to reach a Goal =====
  async function calculateGoalInjectors() {
    show("goalResult");

    const currentSP = getInt("spGoalCurrent");
    const targetSP = getInt("spGoalTarget");

    if (!Number.isFinite(currentSP) || currentSP < 1 || !Number.isFinite(targetSP) || targetSP < 1) {
      setText("goalLarge", "");
      setText("goalSmall", "");
      setText("goalNote", "Please enter valid numbers.");
      return;
    }

    if (targetSP <= currentSP) {
      setText("goalLarge", "0");
      setText("goalSmall", "0");
      setText("goalNote", "You are already at or above the target.");
      hideRow("goalTotal");
      setText("goalTotal", "");
      return;
    }

    const plan = injectorsToTarget(currentSP, targetSP);

    setText("goalLarge", String(plan.large));
    setText("goalSmall", String(plan.small));

    hideRow("goalTotal");
    setText("goalTotal", "");

    const prices = await fetchPrices();
    if (prices) {
      const L = prices[40520] || { buy: 0, sell: 0 };
      const S = prices[45635] || { buy: 0, sell: 0 };
      const totalBuyISK  = plan.large * L.sell + plan.small * S.sell;
      const totalSellISK = plan.large * L.buy  + plan.small * S.buy;
      setBuySellNote("goalNote", totalBuyISK, totalSellISK);
    } else {
      setText("goalNote", "Buy: — · Sell: —");
    }
  }

  // ===== Section D: Skill Extractors Profit Check (four scenarios, break after "Value),") =====
  async function calculateExtractorProfitability() {
    const sp = getInt("spExtract");
    show("extractorResult");

    const tbody = $("profitBody");
    clearChildren(tbody);

    if (!Number.isFinite(sp) || sp < 1) {
      setText("extUsable", "0");
      setText("extBuy", "");
      setText("injBuy", "");
      setText("extSell", "");
      setText("injSell", "");
      setText("extNote", "");
      return;
    }

    const prices = await fetchPrices();
    if (!prices) {
      setText("extUsable", "0");
      setText("extBuy", "");
      setText("injBuy", "");
      setText("extSell", "");
      setText("injSell", "");
      setText("extNote", "");
      return;
    }

    const usable = usableExtractors(sp);
    setText("extUsable", String(usable));

    const EXT = prices[40519] || { buy: 0, sell: 0 }; // Extractor
    const INJ = prices[40520] || { buy: 0, sell: 0 }; // Large Injector

    setText("extBuy",  `${fmtNum(EXT.buy)} ISK`);
    setText("injBuy",  `${fmtNum(INJ.buy)} ISK`);
    setText("extSell", `${fmtNum(EXT.sell)} ISK`);
    setText("injSell", `${fmtNum(INJ.sell)} ISK`);

    const per_buyExt_buyInj   = INJ.buy  - EXT.buy;
    const per_buyExt_sellInj  = INJ.sell - EXT.buy;
    const per_sellExt_sellInj = INJ.sell - EXT.sell;
    const per_sellExt_buyInj  = INJ.buy  - EXT.sell;

    const tot_buyExt_buyInj   = per_buyExt_buyInj   * usable;
    const tot_buyExt_sellInj  = per_buyExt_sellInj  * usable;
    const tot_sellExt_sellInj = per_sellExt_sellInj * usable;
    const tot_sellExt_buyInj  = per_sellExt_buyInj  * usable;

    appendTwoColRow(
      tbody,
      "Buy Extractors (Buy Value),\nSell Injectors (Buy Value)",
      `${fmtNum(tot_buyExt_buyInj)} ISK`
    );
    appendTwoColRow(
      tbody,
      "Buy Extractors (Buy Value),\nSell Injectors (Sell Value)",
      `${fmtNum(tot_buyExt_sellInj)} ISK`
    );
    appendTwoColRow(
      tbody,
      "Buy Extractors (Sell Value),\nSell Injectors (Sell Value)",
      `${fmtNum(tot_sellExt_sellInj)} ISK`
    );
    appendTwoColRow(
      tbody,
      "Buy Extractors (Sell Value),\nSell Injectors (Buy Value)",
      `${fmtNum(tot_sellExt_buyInj)} ISK`
    );

    setText("extNote", "");
  }

  // ===== Init =====
  function onReady(fn) {
    if (document.readyState === "loading") {
      document.addEventListener("DOMContentLoaded", fn, { once: true });
    } else {
      fn();
    }
  }

  onReady(() => {
    restrictNumberInputs();

    const wiring = [
      ["btnCalcInjectors",  "click", () => calculateRequiredInjectors()],
      ["btnCalcSPGain",     "click", calculateSPGain],
      ["btnCalcGoal",       "click", () => calculateGoalInjectors()],
      ["btnCalcExtractors", "click", () => calculateExtractorProfitability()],
    ];

    for (const [id, type, handler] of wiring) {
      const el = $(id);
      if (el) el.addEventListener(type, handler, { passive: true });
    }
  });
})();
