<div class="eve-sic">
  <h1>Skill Injector Calculator</h1>

  <!-- A) How many Injectors do I need? -->
  <section class="sic-section" id="section-need">
    <h2>How many Injectors do I need?</h2>

    <div class="sic-row">
      <label for="currentSP">Current SP</label>
      <input type="number" id="currentSP" min="0" step="1" inputmode="numeric" />
    </div>

    <div class="sic-row">
      <label for="injectSP">SP to add</label>
      <input type="number" id="injectSP" min="0" step="1" inputmode="numeric" />
    </div>

    <div class="sic-actions">
      <button id="btnCalcInjectors" type="button">Calculate Injectors</button>
    </div>

    <div id="result" class="sic-result is-hidden" aria-hidden="true">
      <div class="sic-row">
        <span>Large Injectors</span>
        <span id="resLarge"></span>
      </div>
      <div class="sic-row">
        <span>Small Injectors</span>
        <span id="resSmall"></span>
      </div>
      <div class="sic-note">
        <span id="resNote"></span>
      </div>
    </div>
  </section>

  <!-- B) SP gained from N injectors -->
  <section class="sic-section" id="section-gain">
    <h2>SP gained from Injectors</h2>

    <div class="sic-row">
      <label for="spGain">Current SP</label>
      <input type="number" id="spGain" min="0" step="1" inputmode="numeric" />
    </div>

    <div class="sic-row">
      <label for="injectorsGain">Number of Injectors</label>
      <input type="number" id="injectorsGain" min="0" step="1" inputmode="numeric" />
    </div>

    <div class="sic-actions">
      <button id="btnCalcSPGain" type="button">Calculate SP gain</button>
    </div>

    <div id="gainResult" class="sic-result is-hidden" aria-hidden="true">
      <div class="sic-row">
        <span>Using Large Injectors</span>
        <span id="gainLarge"></span>
      </div>
      <div class="sic-row">
        <span>Using Small Injectors</span>
        <span id="gainSmall"></span>
      </div>
      <!-- Script hides this row -->
      <div class="sic-row">
        <span>Total SP Gained</span>
        <span id="gainTotal"></span>
      </div>
      <div class="sic-note">
        <span id="gainNote"></span>
      </div>
    </div>
  </section>

  <!-- C) Injectors to reach a Goal -->
  <section class="sic-section" id="section-goal">
    <h2>Injectors to reach a Goal</h2>

    <div class="sic-row">
      <label for="spGoalCurrent">Current SP</label>
      <input type="number" id="spGoalCurrent" min="0" step="1" inputmode="numeric" />
    </div>

    <div class="sic-row">
      <label for="spGoalTarget">Target SP</label>
      <input type="number" id="spGoalTarget" min="0" step="1" inputmode="numeric" />
    </div>

    <div class="sic-actions">
      <button id="btnCalcGoal" type="button">Calculate injectors to goal</button>
    </div>

    <div id="goalResult" class="sic-result is-hidden" aria-hidden="true">
      <div class="sic-row">
        <span>Large Injectors</span>
        <span id="goalLarge"></span>
      </div>
      <div class="sic-row">
        <span>Small Injectors</span>
        <span id="goalSmall"></span>
      </div>
      <!-- Script hides this row -->
      <div class="sic-row">
        <span>Total Injectors</span>
        <span id="goalTotal"></span>
      </div>
      <div class="sic-note">
        <span id="goalNote"></span>
      </div>
    </div>
  </section>

  <!-- D) Skill Extractors Profit Check -->
  <section class="sic-section" id="section-extract">
    <h2>Skill Extractors Profit Check</h2>

    <div class="sic-row">
      <label for="spExtract">Current SP</label>
      <input type="number" id="spExtract" min="0" step="1" inputmode="numeric" />
    </div>

    <div class="sic-actions">
      <button id="btnCalcExtractors" type="button">Calculate Extractor Profit</button>
    </div>

    <div id="extractorResult" class="sic-result is-hidden" aria-hidden="true">

      <!-- Usable Extractors ABOVE the price grid -->
      <div class="sic-row">
        <span>Usable Extractors</span>
        <span id="extUsable"></span>
      </div>

      <!-- Compact 2×2 price grid (IDs preserved for JS) -->
      <div class="sic-mini">
        <table>
          <thead>
            <tr>
              <th></th>
              <th>Extractor</th>
              <th>Injector</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <th>Buy</th>
              <td id="extBuy"></td>
              <td id="injBuy"></td>
            </tr>
            <tr>
              <th>Sell</th>
              <td id="extSell"></td>
              <td id="injSell"></td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="sic-table">
        <table>
          <thead>
            <tr>
              <th>Scenario</th>
              <th>Profit (ISK)</th>
            </tr>
          </thead>
          <tbody id="profitBody"></tbody>
        </table>
      </div>

      <div class="sic-note">
        <span id="extNote"></span>
      </div>
    </div>
  </section>
</div>
