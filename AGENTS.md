# AI engineering guide

Read `README.md`, the plugin bootstrap, `src/*`, block metadata/editor code, Docker smoke and all tests before editing.

## Boundary and contract

This WordPress plugin renders the headless preview server-side. `onecomm_store_id` is the primary setting; bootstrap supplies public runtime values. Canonical API truth is `https://www.1ecomm.com/headless-commerce/openapi.yaml`.

Preserve WordPress nonce/CSRF validation, capability checks for settings, key-derived tenant scope, fixed upstream paths, cart HttpOnly/SameSite cookie, server-side lookup transient and neutral lookup failure. Never place order email, cart token or lookup proof in redirect URLs/logs. Guest order count is `count($order['items'])`; there is no `itemCount` field.

Signed outbound webhooks are deployed. Receiver code verifies the exact raw body, timestamp, HMAC and delivery-ID binding before parsing or side effects, then atomically claims the delivery ID in durable storage.

## WordPress/PHP practices

- Follow WordPress escaping by context (`esc_html`, `esc_attr`, `esc_url`) at output and sanitize/validate at input. SQL, if ever required, uses `$wpdb->prepare`.
- Every state-changing action needs a nonce and correct capability/ownership check. Do not expose arbitrary proxy URLs or unauthenticated administrative actions.
- Use the WordPress HTTP API with `wp_safe_remote_*`, TLS verification, redirects disabled/bounded, timeouts and response-size checks.
- Prefix/global names must not collide. Keep translation strings and text domain intact.
- Blocks and shortcodes must remain accessible without JavaScript; preserve labels, notices, focus and progressive enhancement.
- Support the documented PHP/WordPress matrix; never require Composer in the installed plugin runtime.

## License boundary

`LICENSE.md` allows authorized 1Ecomm customer projects and deployed or compiled shopper applications, but prohibits redistribution of this reusable SDK/plugin or its derivatives. Preserve the notice in clones, packages, generated projects and documentation. Do not describe this repository as open source or grant broader rights in examples.

## Verification

Run `php tests/run.php`, `./tests/docker-smoke.sh`, the sandbox WP-CLI journey and real Playwright UI journey when prerequisites exist. Assert rendered items/status and network result, not only shortcode presence. Never clone production data into a demo.
