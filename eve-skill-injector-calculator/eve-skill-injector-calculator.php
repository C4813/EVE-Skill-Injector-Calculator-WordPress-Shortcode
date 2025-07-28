<?php
/*
Plugin Name: EVE Skill Injector Calculator
Description: Adds a shortcode [eve_skill_calculator] to display EVE Online skill injector calculators with ISK value using ESI.
Version: 1.2
Author: C4813
*/

function fetch_injector_prices() {
    $cached = get_transient('esi_injector_prices_v3');
    if ($cached !== false) return $cached;

    $type_ids = [40520, 45635, 40519]; // Large, Small, Extractor
    $region_id = 10000002; // The Forge (Jita)
    $prices = [];

    foreach ($type_ids as $type_id) {
        $sell_url = "https://esi.evetech.net/latest/markets/$region_id/orders/?datasource=tranquility&order_type=sell&type_id=$type_id";
        $buy_url = "https://esi.evetech.net/latest/markets/$region_id/orders/?datasource=tranquility&order_type=buy&type_id=$type_id";

        $sell_response = wp_remote_get($sell_url);
        $buy_response = wp_remote_get($buy_url);

        $sell_data = !is_wp_error($sell_response) ? json_decode(wp_remote_retrieve_body($sell_response), true) : [];
        $buy_data  = !is_wp_error($buy_response)  ? json_decode(wp_remote_retrieve_body($buy_response), true)  : [];

        $sell_price = null;
        foreach ($sell_data as $order) {
            if ($order['location_id'] == 60003760) {
                if ($sell_price === null || $order['price'] < $sell_price) {
                    $sell_price = $order['price'];
                }
            }
        }

        $buy_price = null;
        foreach ($buy_data as $order) {
            if ($order['location_id'] == 60003760) {
                if ($buy_price === null || $order['price'] > $buy_price) {
                    $buy_price = $order['price'];
                }
            }
        }

        $prices[$type_id] = [
            'sell' => $sell_price ?: 0,
            'buy'  => $buy_price  ?: 0
        ];
    }

    set_transient('esi_injector_prices_v3', $prices, 60 * 60);
    return $prices;
}

function eve_skill_calculator_shortcode() {
    $prices = fetch_injector_prices();
    $largeSell = $prices[40520]['sell'];
    $largeBuy  = $prices[40520]['buy'];
    $smallSell = $prices[45635]['sell'];
    $smallBuy  = $prices[45635]['buy'];
    $extractorSell = $prices[40519]['sell'];
    $extractorBuy  = $prices[40519]['buy'];

    ob_start();
    include plugin_dir_path(__FILE__) . 'includes/skill-calculator-interface.php';
    return ob_get_clean();
}
add_shortcode('eve_skill_calculator', 'eve_skill_calculator_shortcode');
