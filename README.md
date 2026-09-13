# 1Ecomm Headless Commerce for WordPress

<!-- 1ecomm-discovery -->
> Part of **[1Ecomm headless commerce](https://www.1ecomm.com/headless-commerce)** — catalog, cart, checkout and order APIs for custom storefronts and apps.
> Fastest start: `npm create @1ecomm/storefront@latest` · [CLI guide](https://www.1ecomm.com/headless-commerce/cli.html) · [OpenAPI contract](https://www.1ecomm.com/headless-commerce/openapi.yaml) · [All starters and SDKs](https://www.1ecomm.com/headless-commerce#starters)

Free for authorized 1Ecomm customers and their developers to build and operate 1Ecomm-connected stores. You may deploy the finished store, but may not redistribute, resell, sublicense, mirror, or republish this plugin or a reusable derivative. See [LICENSE.md](LICENSE.md).

This plugin lets a WordPress page sell products from a 1Ecomm store. It renders products, cart, guest checkout choices, a pending non-hosted order confirmation, and account-free return-order lookup on the server. It never charges a card or wallet.

## Set it up

1. Install and activate the `onecomm-headless` plugin.
2. Save your 1Ecomm store ID in the `onecomm_store_id` setting. A store ID is an identifier, not a password.
3. Add `[onecomm_storefront]` to a page.
4. Open the page. No API URL or publishable-key copy is required.

For a clean local WordPress proof:

```bash
docker compose up -d
./tests/docker-smoke.sh
```

Run `php tests/run.php` for contract/security tests. In the WordPress CLI container, `wp eval-file wp-content/plugins/onecomm-headless/tests/live.php` creates an isolated fixture cart and pending bank-transfer test order. A real page can be driven with `WORDPRESS_LIVE_URL=http://localhost:8180/?page_id=<id> npm run test:e2e:live`. These tests do not move money, and demos must never clone production customer data.

CI now provisions a fresh WordPress runtime and an expiring 1Ecomm fixture for every run, executes the deployed catalog-to-order journey inside WordPress, and always revokes the temporary key and removes the containers. A missing allocator secret fails the gate instead of skipping it.

The cart token and short-lived order-lookup session stay in HttpOnly, SameSite=Lax cookies. Browser changes use a fixed nonce-protected WordPress action, so the checkout email is posted to WordPress and is not placed in the page URL. The order intent is retained server-side across uncertainty. Never enter an administrator password or secret API key. Customer-cart merge and direct payment capture remain outside this preview.

## Signed event receivers

Server integrations pass the exact REST request body and headers to `Phessage\OneComm\WebhookVerifier::verify`. Keep the `whsec_` value outside themes and browser code. Supply a replay-claim callback backed by a database unique constraint on delivery ID, then dispatch WordPress actions only after verification and the atomic claim succeed. The helper rejects stale timestamps, changed bytes, header/body ID mismatches and duplicate deliveries.
