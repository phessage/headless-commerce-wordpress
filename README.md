# 1Ecomm Headless Commerce for WordPress

This plugin lets a WordPress page sell products from a 1Ecomm store. It renders products, cart, guest checkout choices and a pending non-hosted order confirmation on the server. It never charges a card or wallet.

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

The cart token stays in an HttpOnly, SameSite=Lax cookie. Browser changes use a fixed nonce-protected WordPress action. The order intent is retained server-side across uncertainty. Never enter an administrator password or secret API key. Customer-cart merge, webhooks, card/wallet payment and payment capture remain outside this preview.
