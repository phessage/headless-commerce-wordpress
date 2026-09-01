#!/bin/sh
set -eu
docker compose run --rm cli wp core is-installed || docker compose run --rm cli wp core install --url=http://localhost:8180 --title=Demo --admin_user=admin --admin_password=test-only-password --admin_email=demo@example.test --skip-email
docker compose run --rm cli wp plugin activate onecomm-headless
docker compose run --rm cli wp option update onecomm_api_url http://synthetic.invalid
docker compose run --rm cli wp option update onecomm_publishable_key pk_test_demo
docker compose run --rm cli wp eval 'echo do_shortcode("[onecomm_products]");' | grep 'onecomm-empty'
docker compose run --rm cli wp eval 'echo do_shortcode("[onecomm_storefront]");' | grep 'onecomm-cart'
