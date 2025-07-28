<script>
const largeSell = <?php echo json_encode($largeSell); ?>;
const largeBuy = <?php echo json_encode($largeBuy); ?>;
const smallSell = <?php echo json_encode($smallSell); ?>;
const smallBuy = <?php echo json_encode($smallBuy); ?>;
const extractorSell = <?php echo json_encode($extractorSell); ?>;
const extractorBuy = <?php echo json_encode($extractorBuy); ?>;

function getLargeGain(sp) {
    if (sp < 5000000) return 500000;
    if (sp < 50000000) return 400000;
    if (sp < 80000000) return 300000;
    return 150000;
}

function getSmallGain(sp) {
    if (sp < 5000000) return 100000;
    if (sp < 50000000) return 80000;
    if (sp < 80000000) return 60000;
    return 30000;
}

function calculateInjectors() {
    let currentSP = parseInt(document.getElementById("currentSP").value);
    let injectSP = parseInt(document.getElementById("injectSP").value);
    let remaining = injectSP;
    let largeInjectors = 0;
    let smallInjectors = 0;

    if (isNaN(currentSP) || isNaN(injectSP) || currentSP < 0 || injectSP <= 0) {
        document.getElementById("result").innerText = "Please enter valid numbers.";
        return;
    }

    while (remaining > 0) {
        let gain = getLargeGain(currentSP);
        if (remaining >= gain) {
            largeInjectors++;
            currentSP += gain;
            remaining -= gain;
        } else {
            let smallGain = getSmallGain(currentSP);
            if (remaining <= smallGain) {
                smallInjectors++;
                currentSP += smallGain;
                remaining = 0;
            } else {
                largeInjectors++;
                break;
            }
        }
    }

    let sell = (largeInjectors * largeSell) + (smallInjectors * smallSell);
    let buy = (largeInjectors * largeBuy) + (smallInjectors * smallBuy);

    document.getElementById("result").innerHTML = `
        <span>Large:</span> <span style="font-weight:normal">${largeInjectors}</span><br>
        <span>Small:</span> <span style="font-weight:normal">${smallInjectors}</span><br>
        <span>Jita Buy:</span> <span style="font-weight:normal">${buy.toLocaleString()} ISK</span><br>
        <span>Jita Sell:</span> <span style="font-weight:normal">${sell.toLocaleString()} ISK</span><br>
        <small>(${largeInjectors} Large @ ${largeBuy.toLocaleString()} buy / ${largeSell.toLocaleString()} sell${smallInjectors ? `, ${smallInjectors} Small @ ${smallBuy.toLocaleString()} buy / ${smallSell.toLocaleString()} sell` : ''})</small>`;
}

function calculateSPGain() {
    let sp = parseInt(document.getElementById("spOwned").value);
    let large = parseInt(document.getElementById("largeInjectors").value) || 0;
    let small = parseInt(document.getElementById("smallInjectors").value) || 0;
    let gain = 0;

    for (let i = 0; i < large; i++) {
        let g = getLargeGain(sp);
        gain += g;
        sp += g;
    }
    for (let i = 0; i < small; i++) {
        let g = getSmallGain(sp);
        gain += g;
        sp += g;
    }

    document.getElementById("spGainResult").innerHTML = `<span>SP Gained:</span> <span style="font-weight:normal">${gain.toLocaleString()} SP</span>`;
}

function calculateGoalInjectors() {
    let currentSP = parseInt(document.getElementById("currentSPGoal").value);
    let targetSP = parseInt(document.getElementById("targetSPGoal").value);
    let remaining = targetSP - currentSP;
    let largeInjectors = 0;
    let smallInjectors = 0;

    if (isNaN(currentSP) || isNaN(targetSP) || remaining <= 0) {
        document.getElementById("goalResult").innerText = "Please enter valid numbers.";
        return;
    }

    while (remaining > 0) {
        let gain = getLargeGain(currentSP);
        if (remaining >= gain) {
            largeInjectors++;
            currentSP += gain;
            remaining -= gain;
        } else {
            let smallGain = getSmallGain(currentSP);
            if (remaining <= smallGain) {
                smallInjectors++;
                currentSP += smallGain;
                remaining = 0;
            } else {
                largeInjectors++;
                break;
            }
        }
    }

    let sell = (largeInjectors * largeSell) + (smallInjectors * smallSell);
    let buy = (largeInjectors * largeBuy) + (smallInjectors * smallBuy);

    document.getElementById("goalResult").innerHTML = `
        <span>Large:</span> <span style="font-weight:normal">${largeInjectors}</span><br>
        <span>Small:</span> <span style="font-weight:normal">${smallInjectors}</span><br>
        <span>Jita Buy:</span> <span style="font-weight:normal">${buy.toLocaleString()} ISK</span><br>
        <span>Jita Sell:</span> <span style="font-weight:normal">${sell.toLocaleString()} ISK</span><br>
        <small>(${largeInjectors} Large @ ${largeBuy.toLocaleString()} buy / ${largeSell.toLocaleString()} sell${smallInjectors ? `, ${smallInjectors} Small @ ${smallBuy.toLocaleString()} buy / ${smallSell.toLocaleString()} sell` : ''})</small>`;
}

function calculateExtractors() {
    let sp = parseInt(document.getElementById("spExtract").value);
    if (isNaN(sp) || sp < 5500000) {
        document.getElementById("extractorResult").innerText = "Must have at least 5,500,000 SP to use skill extractors.";
        return;
    }

    let usable = Math.floor((sp - 5500000) / 500000) + 1;
    let extractorBuyTotal = usable * extractorBuy;
    let extractorSellTotal = usable * extractorSell;
    let injectorBuyTotal = usable * largeBuy;
    let injectorSellTotal = usable * largeSell;
    let profitBuySide = injectorBuyTotal - extractorSellTotal;
    let profitSellSide = injectorSellTotal - extractorBuyTotal;

    document.getElementById("extractorResult").innerHTML = `
        <span>Usable Extractors:</span> <span style="font-weight:normal">${usable}</span><br><br>
        <span>Extractor Jita Buy:</span> <span style="font-weight:normal">${extractorBuyTotal.toLocaleString()} ISK</span><br>
        <span>Extractor Jita Sell:</span> <span style="font-weight:normal">${extractorSellTotal.toLocaleString()} ISK</span><br><br>
        <span>Injector Jita Buy:</span> <span style="font-weight:normal">${injectorBuyTotal.toLocaleString()} ISK</span><br>
        <span>Injector Jita Sell:</span> <span style="font-weight:normal">${injectorSellTotal.toLocaleString()} ISK</span><br><br>
        <table style='width:100%; border-collapse: collapse; margin-top: 10px;'>
          <tr><th style='text-align:center; border-bottom: 1px solid #ccc; font-weight:bold;'>Scenario</th><th style='text-align:center; border-bottom: 1px solid #ccc; font-weight:bold;'>Profit</th></tr>
          <tr><td style='padding: 4px 0; text-align:center;'>Buy Extractors (Buy Value),<br>Sell Injectors (Buy Value)</td><td style='text-align:center;'>${(injectorBuyTotal - extractorBuyTotal).toLocaleString()} ISK</td></tr>
          <tr><td style='padding: 4px 0; text-align:center;'>Buy Extractors (Buy Value),<br>Sell Injectors (Sell Value)</td><td style='text-align:center;'>${(injectorSellTotal - extractorBuyTotal).toLocaleString()} ISK</td></tr>
          <tr><td style='padding: 4px 0; text-align:center;'>Buy Extractors (Sell Value),<br>Sell Injectors (Sell Value)</td><td style='text-align:center;'>${(injectorSellTotal - extractorSellTotal).toLocaleString()} ISK</td></tr>
          <tr><td style='padding: 4px 0; text-align:center;'>Buy Extractors (Sell Value),<br>Sell Injectors (Buy Value)</td><td style='text-align:center;'>${(injectorBuyTotal - extractorSellTotal).toLocaleString()} ISK</td></tr>
        </table>`;
}
</script>

<div id="eve-skill-calculators" style="max-width: 420px; margin: auto; font-family: sans-serif;">
    <h2 style="text-align:center;">Skill Injector Calculator</h2>

    <!-- Calculator 1 -->
    <div>
        <h4 style="text-align: center;">a. How many Injectors do I need?</h3>
        <label>Current Skill Points<br><input type="number" id="currentSP" class="input-field no-spinner"></label><br>
        <label>Skill Points to inject<br><input type="number" id="injectSP" class="input-field no-spinner"></label><br>
        <button onclick="calculateInjectors()" class="calc-button">Calculate Injectors Needed</button>
        <div id="result" class="result"></div>
    </div>
    <hr>

    <!-- Calculator 2 -->
    <div>
        <h4 style="text-align: center;">b. How much SP will I gain?</h3>
        <label>Current Skill Points<br><input type="number" id="spOwned" class="input-field no-spinner"></label><br>
        <label>Large Injectors<br><input type="number" id="largeInjectors" class="input-field no-spinner"></label><br>
        <label>Small Injectors<br><input type="number" id="smallInjectors" class="input-field no-spinner"></label><br>
        <button onclick="calculateSPGain()" class="calc-button">Calculate SP Gained</button>
        <div id="spGainResult" class="result"></div>
    </div>
    <hr>

    <!-- Calculator 3 -->
    <div>
        <h4 style="text-align: center;">c. I want to reach X SP total</h3>
        <label>Current Skill Points<br><input type="number" id="currentSPGoal" class="input-field no-spinner"></label><br>
        <label>Target Skill Points<br><input type="number" id="targetSPGoal" class="input-field no-spinner"></label><br>
        <button onclick="calculateGoalInjectors()" class="calc-button">Calculate Injectors to Reach Goal</button>
        <div id="goalResult" class="result"></div>
    </div>
    <hr>
    <!-- Calculator 4 -->
    <div>
        <h4 style="text-align: center;">d. Skill Extractors Profit Check</h3>
        <label>Current Skill Points<br><input type="number" id="spExtract" class="input-field no-spinner"></label><br>
        <button onclick="calculateExtractors()" class="calc-button">Calculate Extractors</button>
        <div id="extractorResult" class="result"></div>
    </div>
    <hr>
</div>
<style>
/* Center labels and reduce bottom margin */
label {
  display: block;
  text-align: center;
}

/* Style input fields: width, padding, margin, no spinner arrows */
.input-field {
  width: 50%;
  padding: 6px;
  font-size: 1rem;
  margin: auto;
  display: block;
  box-sizing: border-box;
}

/* Remove number input arrows for Chrome, Safari, Edge */
.input-field.no-spinner::-webkit-outer-spin-button,
.input-field.no-spinner::-webkit-inner-spin-button {
  -webkit-appearance: none;
  margin: 0;
}

/* Remove number input arrows for Firefox */
.input-field.no-spinner {
  -moz-appearance: textfield;
}

/* Style buttons: width, padding, margin, font size */
.calc-button {
  width: 50%;
  padding: 10px;
  font-size: 1.3rem;
  margin: auto;
  display: block;
  background: #f0f0f0;
  color: #000;
  border: 1px solid #ccc;
  border-radius: 4px;
  cursor: pointer;
  transition: background 0.3s ease;
  appearance: none;
}

.calc-button:hover {
  background: #e0e0e0;
  color: #000;
}

/* Result text */
.result {
  margin-top: 10px;
  font-weight: bold;
  text-align: center; /* add this */
}
</style>
