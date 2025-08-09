<?php
/*
Plugin Name: EVE Skill Injector Calculator
Description: Adds a shortcode [eve_skill_calculator] to display EVE Online skill injector calculators with ISK value using ESI.
Version: 1.3
Author: C4813
*/

defined('ABSPATH') || exit;

// ===== Constants =====
define('EVE_SIC_REGION_ID', 10000002);          // The Forge
define('EVE_SIC_PRIMARY_SYSTEM_ID', 30000142);  // Jita
define('EVE_SIC_SECONDARY_SYSTEM_ID', 30000144);// Perimeter
define('EVE_SIC_TYPE_LARGE', 40520);            // Large Injector
define('EVE_SIC_TYPE_SMALL', 45635);            // Small Injector
define('EVE_SIC_TYPE_EXTRACTOR', 40519);        // Skill Extractor
define('EVE_SIC_CACHE_TTL', 6 * HOUR_IN_SECONDS);
define('EVE_SIC_CACHE_DIRNAME', 'eve-skill-injector-calculator/cache');

// ===== Cache helpers =====
function eve_sic_cache_path(): array {
    $up = wp_upload_dir(null, false);
    $base = !empty($up['error'])
        ? trailingslashit(sys_get_temp_dir()) . EVE_SIC_CACHE_DIRNAME
        : trailingslashit($up['basedir']) . EVE_SIC_CACHE_DIRNAME;
    return [$base, trailingslashit($base) . 'prices.json'];
}
function eve_sic_ensure_cache_dir_secure(string $dir): void {
    if (!is_dir($dir)) wp_mkdir_p($dir);
    $index = trailingslashit($dir) . 'index.html';
    if (!file_exists($index)) @file_put_contents($index, '');
    $ht = trailingslashit($dir) . '.htaccess';
    if (!file_exists($ht)) {
        @file_put_contents($ht, "Options -Indexes\n<FilesMatch \"\\.(php|php\\d*)$\">\n  Deny from all\n</FilesMatch>\n");
    }
}
function eve_sic_write_cache_atomic(string $file, array $data): void {
    $tmp = $file . '.' . wp_generate_password(8, false) . '.tmp';
    @file_put_contents($tmp, wp_json_encode($data), LOCK_EX);
    @rename($tmp, $file);
    @chmod($file, 0640);
}
function eve_sic_sanitize_prices($prices): array {
    $ids = [EVE_SIC_TYPE_LARGE, EVE_SIC_TYPE_SMALL, EVE_SIC_TYPE_EXTRACTOR];
    $out = [];
    foreach ($ids as $id) {
        $row = (is_array($prices) && isset($prices[$id]) && is_array($prices[$id])) ? $prices[$id] : [];
        $out[$id] = [
            'buy'  => isset($row['buy'])  ? (int)$row['buy']  : 0,
            'sell' => isset($row['sell']) ? (int)$row['sell'] : 0,
        ];
    }
    return $out;
}

// ===== ESI fetch =====
function eve_sic_esi_fetch_type_prices(int $type_id, array $system_ids): ?array {
    $base = 'https://esi.evetech.net/latest/markets/' . rawurlencode((string)EVE_SIC_REGION_ID) . '/orders/';
    $args = [
        'datasource' => 'tranquility',
        'order_type' => 'all',
        'type_id'    => $type_id,
        'page'       => 1,
    ];
    $highest_buy = null;
    $lowest_sell = null;

    $request_page = function (int $page) use ($base, $args) {
        $args['page'] = $page;
        $url = add_query_arg($args, $base);
        return wp_safe_remote_get($url, [
            'timeout'            => 8,
            'redirection'        => 2,
            'sslverify'          => true,
            'reject_unsafe_urls' => true,
            'headers'            => [
                'Accept'     => 'application/json',
                'User-Agent' => 'EVE-Skill-Injector-Calculator/1.1; ' . home_url('/'),
            ],
        ]);
    };

    $process = function ($response) use (&$highest_buy, &$lowest_sell, $system_ids) {
        $body = wp_remote_retrieve_body($response);
        if (strlen((string)$body) > 2000000) return; // 2MB guard
        $rows = json_decode((string)$body, true);
        if (!is_array($rows)) return;

        foreach ($rows as $row) {
            if (!isset($row['system_id']) || !in_array((int)$row['system_id'], $system_ids, true)) continue;
            if (!isset($row['price'], $row['is_buy_order'])) continue;
            $price = (float)$row['price'];
            if (!is_finite($price)) continue;

            if (!empty($row['is_buy_order'])) {
                if ($highest_buy === null || $price > $highest_buy) $highest_buy = $price;
            } else {
                if ($lowest_sell === null || $price < $lowest_sell) $lowest_sell = $price;
            }
        }
    };

    // Page 1 + one retry on transient 5xx or transport error
    $resp = $request_page(1);
    $code = is_wp_error($resp) ? 0 : (int) wp_remote_retrieve_response_code($resp);
    if (is_wp_error($resp) || ($code >= 500 && $code <= 599)) {
        $resp = $request_page(1); // single retry
        $code = is_wp_error($resp) ? 0 : (int) wp_remote_retrieve_response_code($resp);
    }
    if (is_wp_error($resp) || $code !== 200) return null;

    $process($resp);

    // Remaining pages (capped)
    $pages_header = wp_remote_retrieve_header($resp, 'x-pages');
    $max_pages = max(1, min((int)($pages_header ?: 1), 20));
    for ($p = 2; $p <= $max_pages; $p++) {
        $resp = $request_page($p);
        if (is_wp_error($resp)) break;
        $code = (int) wp_remote_retrieve_response_code($resp);
        if ($code !== 200) break;
        $process($resp);
    }

    return [
        'buy'  => (int)round(max(0, (float)($highest_buy ?? 0))),
        'sell' => (int)round(max(0, (float)($lowest_sell ?? 0))),
    ];
}

function eve_sic_provider_fetch_prices(): ?array {
    $systems = [EVE_SIC_PRIMARY_SYSTEM_ID, EVE_SIC_SECONDARY_SYSTEM_ID];
    $large = eve_sic_esi_fetch_type_prices(EVE_SIC_TYPE_LARGE, $systems);
    $small = eve_sic_esi_fetch_type_prices(EVE_SIC_TYPE_SMALL, $systems);
    $ext   = eve_sic_esi_fetch_type_prices(EVE_SIC_TYPE_EXTRACTOR, $systems);
    if (!is_array($large) || !is_array($small) || !is_array($ext)) return null;
    return [
        EVE_SIC_TYPE_LARGE     => $large,
        EVE_SIC_TYPE_SMALL     => $small,
        EVE_SIC_TYPE_EXTRACTOR => $ext,
    ];
}

function eve_sic_get_prices_from_cache_or_refresh(): array {
    [$dir, $file] = eve_sic_cache_path();
    $defaults = [
        EVE_SIC_TYPE_LARGE     => ['buy' => 0, 'sell' => 0],
        EVE_SIC_TYPE_SMALL     => ['buy' => 0, 'sell' => 0],
        EVE_SIC_TYPE_EXTRACTOR => ['buy' => 0, 'sell' => 0],
    ];

    if (file_exists($file)) {
        $mtime = @filemtime($file);
        if ($mtime !== false && (time() - $mtime) < EVE_SIC_CACHE_TTL) {
            $body = @file_get_contents($file);
            $data = json_decode((string)$body, true);
            if (is_array($data)) return eve_sic_sanitize_prices($data);
        }
    }

    $fresh = eve_sic_provider_fetch_prices();
    if (is_array($fresh)) {
        $clean = eve_sic_sanitize_prices($fresh);
        eve_sic_ensure_cache_dir_secure($dir);
        eve_sic_write_cache_atomic($file, $clean);
        return $clean;
    }

    if (file_exists($file)) {
        $body = @file_get_contents($file);
        $data = json_decode((string)$body, true);
        if (is_array($data)) return eve_sic_sanitize_prices($data);
    }

    return $defaults;
}

// ===== REST endpoint =====
function eve_sic_rate_limited(): bool {
    $ip  = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $key = 'eve_sic_rl_' . md5($ip);
    $hits = (int) get_transient($key);
    if ($hits > 30) return true;
    set_transient($key, $hits + 1, 5 * MINUTE_IN_SECONDS);
    return false;
}

add_action('rest_api_init', function () {
    register_rest_route('eve-sic/v1', '/prices', [
        'methods'             => 'GET',
        'permission_callback' => '__return_true',
        'args'                => [], // we accept no params
        'callback'            => function (WP_REST_Request $request) {
            // Reject any unexpected query params (defense-in-depth)
            if (!empty($request->get_params())) {
                return new WP_REST_Response(['error' => 'bad_request'], 400, [
                    'Content-Type'            => 'application/json; charset=utf-8',
                    'X-Content-Type-Options'  => 'nosniff',
                    'Referrer-Policy'         => 'same-origin',
                ]);
            }

            if (eve_sic_rate_limited()) {
                return new WP_REST_Response(['error' => 'rate_limited'], 429, [
                    'Content-Type'            => 'application/json; charset=utf-8',
                    'X-Content-Type-Options'  => 'nosniff',
                    'Referrer-Policy'         => 'same-origin',
                ]);
            }

            return new WP_REST_Response(
                eve_sic_get_prices_from_cache_or_refresh(),
                200,
                [
                    'Content-Type'            => 'application/json; charset=utf-8',
                    'Cache-Control'           => 'public, max-age=300',
                    'X-Content-Type-Options'  => 'nosniff',
                    'Referrer-Policy'         => 'same-origin',
                ]
            );
        }
    ]);
});

// ===== Activation / Uninstall (named functions only) =====
function eve_sic_on_activation() {
    [$dir] = eve_sic_cache_path();
    eve_sic_ensure_cache_dir_secure($dir);
}
register_activation_hook(__FILE__, 'eve_sic_on_activation');

function eve_sic_on_uninstall() {
    [$dir, $file] = eve_sic_cache_path();
    @unlink($file);
    if (is_dir($dir)) {
        $files = glob($dir . '/*');
        if (!$files || count($files) === 0) @rmdir($dir);
    }
}
register_uninstall_hook(__FILE__, 'eve_sic_on_uninstall');

// ===== Assets + Shortcode =====
function eve_sic_enqueue_assets() {
    $base_url  = plugin_dir_url(__FILE__);
    $base_path = plugin_dir_path(__FILE__);

    $style = $base_url . 'style.css';
    $style_ver = file_exists($base_path . 'style.css') ? filemtime($base_path . 'style.css') : '1.1';
    wp_enqueue_style('eve-sic-style', $style, [], $style_ver);

    $script = $base_url . 'script.js';
    $script_ver = file_exists($base_path . 'script.js') ? filemtime($base_path . 'script.js') : '1.1';
    wp_enqueue_script('eve-sic-script', $script, [], $script_ver, true);

    wp_localize_script('eve-sic-script', 'EVE_SIC_DATA', [
        'rest' => [
            'url'   => esc_url_raw(rest_url('eve-sic/v1/prices')),
            'nonce' => wp_create_nonce('wp_rest'),
        ],
    ]);
}

function eve_skill_calculator_shortcode(): string {
    eve_sic_enqueue_assets();
    ob_start();
    include plugin_dir_path(__FILE__) . 'template.php';
    return (string) ob_get_clean();
}
add_shortcode('eve_skill_calculator', 'eve_skill_calculator_shortcode');
