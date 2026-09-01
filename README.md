# 1Ecomm Headless Commerce for WordPress

Installable preview plugin providing a server-rendered product-grid block, a `[onecomm_products]` catalog shortcode, and a `[onecomm_storefront]` shortcode with anonymous cart create/add/update/remove, guest checkout details, and selection from server-returned shipping/payment choices.

The storefront stops at checkout preparation. It does not create an order, authorize or capture payment, merge a customer cart, or subscribe to webhooks.

Run `php tests/run.php` for contract/security tests. `docker compose up -d` plus `tests/docker-smoke.sh` performs an actual clean WordPress activation and rendered-shortcode probe with synthetic API data.

From an installed WordPress CLI container, `wp eval-file wp-content/plugins/onecomm-headless/tests/live.php` runs a fail-closed deployed-fixture journey when `HEADLESS_API_URL` and `HEADLESS_PUBLISHABLE_KEY` are present. It creates an isolated guest cart and stops after checkout preparation and returned shipping/payment selection.

`WORDPRESS_LIVE_URL=http://localhost:8180/?page_id=<shop-page-id> npm run test:e2e:live` drives the actual WordPress page through Playwright. The test requires a clean site whose page contains `[onecomm_storefront]` and whose plugin options point at the deployed fixture API.

Credentials are stored as WordPress options and used only server-side. Use a public `pk_` key and an HTTPS API URL; never enter confidential credentials. Outbound calls use WordPress safe-request validation with redirects disabled.

The anonymous `hc_` cart capability is held in a `HttpOnly`, `SameSite=Lax` cookie and is never rendered into HTML or URLs. Every browser mutation posts to a fixed `admin-post.php` action protected by a WordPress nonce. Mutations are attempted once only; after a network interruption, show an uncertain result instead of replaying the request.
