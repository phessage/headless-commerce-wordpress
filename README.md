# 1Ecomm Headless Commerce for WordPress

Installable plugin providing a server-rendered product-grid block and shortcode backed by `/v1/headless/products`.

Run `php tests/run.php` for contract/security tests. `docker compose up -d` plus `tests/docker-smoke.sh` performs an actual clean WordPress activation and rendered-shortcode probe with synthetic API data.

Credentials are stored as WordPress options and used only server-side. Use a public `pk_` key; never enter confidential credentials.
