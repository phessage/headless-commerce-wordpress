# 1Ecomm Headless Commerce for WordPress

Installable preview plugin providing a server-rendered product-grid block, a `[onecomm_products]` catalog shortcode, and a `[onecomm_storefront]` shortcode with anonymous cart create/add/update/remove, guest checkout details, and selection from server-returned shipping/payment choices.

For normal setup, save only `onecomm_store_id` (the site's UUID). The plugin resolves and caches the public runtime document. Direct API URL/publishable-key options remain as a compatibility escape hatch for isolated development.

The storefront can place a pending order when the selected method explicitly supports non-hosted placement. It retains one server-side intent key across an uncertain retry and never authorizes or captures payment. Customer cart merge and webhooks remain outside the preview.

Run `php tests/run.php` for contract/security tests. `docker compose up -d` plus `tests/docker-smoke.sh` performs an actual clean WordPress activation and rendered-shortcode probe with synthetic API data.

From an installed WordPress CLI container, `wp eval-file wp-content/plugins/onecomm-headless/tests/live.php` runs a fail-closed deployed-fixture journey. Set `HEADLESS_STORE_ID` to override the maintained fixture store. It creates an isolated guest cart, selects server-returned shipping/payment choices, and creates a pending non-hosted order with a fresh idempotency key.

`WORDPRESS_LIVE_URL=http://localhost:8180/?page_id=<shop-page-id> npm run test:e2e:live` drives the actual WordPress page through Playwright. The test requires a clean site whose page contains `[onecomm_storefront]` and whose plugin options point at the deployed fixture API.

Credentials are stored as WordPress options and used only server-side. Use a public `pk_` key and an HTTPS API URL; never enter confidential credentials. Outbound calls use WordPress safe-request validation with redirects disabled.

The anonymous `hc_` cart capability is held in a `HttpOnly`, `SameSite=Lax` cookie and is never rendered into HTML or URLs. Every browser mutation posts to a fixed `admin-post.php` action protected by a WordPress nonce. Mutations are attempted once only; after a network interruption, show an uncertain result instead of replaying the request.
