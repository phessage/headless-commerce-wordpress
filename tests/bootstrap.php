<?php declare(strict_types=1);
define('ABSPATH', __DIR__); define('MINUTE_IN_SECONDS', 60); define('DAY_IN_SECONDS', 86400); define('COOKIEPATH', '/'); define('COOKIE_DOMAIN', '');
$GLOBALS['options'] = []; $GLOBALS['transients'] = []; $GLOBALS['remote'] = null; $GLOBALS['requests'] = [];
function get_option($k, $d = '') { return $GLOBALS['options'][$k] ?? $d; }
function get_transient($k) { return $GLOBALS['transients'][$k] ?? false; }
function set_transient($k, $v, $ttl) { $GLOBALS['transients'][$k] = $v; return true; }
function wp_safe_remote_request($url, $args) { $GLOBALS['requests'][] = [$url, $args]; return $GLOBALS['remote']; }
function is_wp_error($v) { return false; } function wp_remote_retrieve_response_code($r) { return $r['response']['code']; } function wp_remote_retrieve_body($r) { return $r['body']; }
function wp_json_encode($v) { return json_encode($v, JSON_THROW_ON_ERROR); }
function esc_html($v) { return htmlspecialchars((string) $v, ENT_QUOTES); } function esc_attr($v) { return htmlspecialchars((string) $v, ENT_QUOTES); } function esc_url($v) { return htmlspecialchars((string) $v, ENT_QUOTES); }
function esc_html__($v, $d) { return esc_html($v); } function __($v, $d) { return $v; }
function add_action(...$x) {} function add_shortcode(...$x) {} function register_setting(...$x) {} function register_block_type(...$x) {}
function shortcode_atts($d, $a) { return array_merge($d, $a); } function esc_url_raw($v) { return $v; } function sanitize_text_field($v) { return trim(strip_tags((string) $v)); }
function sanitize_email($v) { return filter_var($v, FILTER_SANITIZE_EMAIL); } function sanitize_key($v) { return preg_replace('/[^a-z0-9_\-]/', '', strtolower((string) $v)); } function absint($v) { return abs((int) $v); }
function admin_url($p) { return 'https://wordpress.test/wp-admin/' . $p; } function wp_nonce_field(...$x) { return '<input type="hidden" name="_wpnonce" value="nonce">'; }
function is_ssl() { return true; } function check_admin_referer(...$x) {} function wp_get_referer() { return 'https://wordpress.test/shop'; } function home_url($p) { return 'https://wordpress.test' . $p; }
function add_query_arg($k, $v, $url) { return $url . '?' . rawurlencode($k) . '=' . rawurlencode($v); } function wp_safe_redirect(...$x) { return true; }
require __DIR__ . '/../src/CatalogClient.php'; require __DIR__ . '/../src/Plugin.php';
