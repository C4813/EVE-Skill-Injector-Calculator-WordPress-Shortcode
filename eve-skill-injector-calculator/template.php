<?php defined('ABSPATH') || exit; ?>
<div id="eve-skill-calculators" class="eve-sic">
  <h2 class="eve-sic__title">Skill Injector Calculator</h2>

  <section class="eve-sic__panel">
    <h3 class="eve-sic__panel-title">a. How many Injectors do I need?</h3>
    <label class="eve-sic__label">Current Skill Points
      <input type="number" id="currentSP" class="eve-sic__input no-spinner" min="1" step="1" inputmode="numeric" pattern="\d*">
    </label>
    <label class="eve-sic__label">Skill Points to inject
      <input type="number" id="injectSP" class="eve-sic__input no-spinner" min="1" step="1" inputmode="numeric" pattern="\d*">
    </label>
    <button id="btnCalcInjectors" type="button" class="eve-sic__btn">Calculate Required Injectors</button>

    <div id="result" class="eve-sic__result is-hidden" aria-hidden="true">
      <div><span>Large:</span> <span id="resLarge" class="eve-sic__value"></span></div>
      <div><span>Medium:</span> <span id="resMedium" class="eve-sic__value"></span></div>
      <div><span>Small:</span> <span id="resSmall" class="eve-sic__value"></span></div>
      <div class="eve-sic__note"><span id="resNote"></span></div>
    </div>
  </section>

  <hr>

  <section class="eve-sic__panel">
    <h3 class="eve-sic__panel-title">b. Skill Points gained from Injectors</h3>
    <label class="eve-sic__label">Current Skill Points
      <input type="number" id="spGain" class="eve-sic__input no-spinner" min="1" step="1" inputmode="numeric" pattern="\d*">
    </label>
    <label class="eve-sic__label">Number of Injectors
      <input type="number" id="injectorsGain" class="eve-sic__input no-spinner" min="1" step="1" inputmode="numeric" pattern="\d*">
    </label>
    <button id="btnCalcSPGain" type="button" class="eve-sic__btn">Calculate SP Gain</button>

    <div id="gainResult" class="eve-sic__result is-hidden" aria-hidden="true">
      <div><span>Large Injectors:</span> <span id="gainLarge" class="eve-sic__value"></span></div>
      <div><span>Medium Injectors:</span> <span id="gainMedium" class="eve-sic__value"></span></div>
      <div><span>Small Injectors:</span> <span id="gainSmall" class="eve-sic__value"></span></div>
      <div><span>Total SP Gained:</span> <span id="gainTotal" class="eve-sic__value"></span></div>
      <div class="eve-sic__note"><span id="gainNote"></span></div>
    </div>
  </section>

  <hr>

  <section class="eve-sic__panel">
    <h3 class="eve-sic__panel-title">c. Injectors needed to reach a Goal</h3>
    <label class="eve-sic__label">Current Skill Points
      <input type="number" id="spGoalCurrent" class="eve-sic__input no-spinner" min="1" step="1" inputmode="numeric" pattern="\d*">
    </label>
    <label class="eve-sic__label">Target Skill Points
      <input type="number" id="spGoalTarget" class="eve-sic__input no-spinner" min="1" step="1" inputmode="numeric" pattern="\d*">
    </label>
    <button id="btnCalcGoal" type="button" class="eve-sic__btn">Calculate Injectors to Goal</button>

    <div id="goalResult" class="eve-sic__result is-hidden" aria-hidden="true">
      <div><span>Large:</span> <span id="goalLarge" class="eve-sic__value"></span></div>
      <div><span>Medium:</span> <span id="goalMedium" class="eve-sic__value"></span></div>
      <div><span>Small:</span> <span id="goalSmall" class="eve-sic__value"></span></div>
      <div><span>Total Injectors:</span> <span id="goalTotal" class="eve-sic__value"></span></div>
      <div class="eve-sic__note"><span id="goalNote"></span></div>
    </div>
  </section>

  <hr>

  <section class="eve-sic__panel">
    <h3 class="eve-sic__panel-title">d. Skill Extractors Profit Check</h3>
    <label class="eve-sic__label">Current Skill Points
      <input type="number" id="spExtract" class="eve-sic__input no-spinner" min="1" step="1" inputmode="numeric" pattern="\d*">
    </label>
    <button id="btnCalcExtractors" type="button" class="eve-sic__btn">Calculate Character Profitability</button>

    <div id="extractorResult" class="eve-sic__result is-hidden" aria-hidden="true">
      <div><strong>Usable Extractors:</strong> <span id="extUsable" class="eve-sic__value"></span></div>

      <div class="eve-sic__grid">
        <div class="eve-sic__metric">
          <div><strong>Extractor Buy:</strong></div>
          <div id="extBuy" class="eve-sic__value"></div>
        </div>
        <div class="eve-sic__metric">
          <div><strong>Injector Buy:</strong></div>
          <div id="injBuy" class="eve-sic__value"></div>
        </div>
        <div class="eve-sic__metric">
          <div><strong>Extractor Sell:</strong></div>
          <div id="extSell" class="eve-sic__value"></div>
        </div>
        <div class="eve-sic__metric">
          <div><strong>Injector Sell:</strong></div>
          <div id="injSell" class="eve-sic__value"></div>
        </div>
      </div>

      <h4 class="eve-sic__subhead">Profit Scenarios</h4>
      <table class="eve-sic__table" aria-label="Profit Scenarios">
        <thead>
          <tr>
            <th scope="col">Scenario</th>
            <th scope="col">Profit</th>
          </tr>
        </thead>
        <tbody id="profitBody"></tbody>
      </table>

      <div class="eve-sic__note"><span id="extNote"></span></div>
    </div>
  </section>
</div>
