#!/usr/bin/env bash
set -euo pipefail

PLUGIN_DIR=/var/www/html/wp-content/plugins/twint-woocommerce-extension
WP="wp --allow-root --path=/var/www/html"

build() {
  if [ ! -f "$PLUGIN_DIR/vendor/autoload.php" ]; then
    echo "[twint] composer install…"
    composer install -d "$PLUGIN_DIR" --no-interaction --prefer-dist --no-progress
  else
    echo "[twint] vendor present — skipping composer install"
  fi
  if [ ! -f "$PLUGIN_DIR/dist/express.js" ]; then
    echo "[twint] npm build…"
    ( cd "$PLUGIN_DIR" && npm ci --no-audit --no-fund && npm run build )
  else
    echo "[twint] dist present — skipping npm build"
  fi
}

provision() {
  echo "[twint] waiting for wp-config + database…"
  until [ -f /var/www/html/wp-config.php ]; do sleep 2; done
  # Use PHP mysqli (WordPress's own driver) to test readiness — the mariadb CLI
  # rejects MySQL 8's self-signed TLS cert, so `wp db check` is unreliable here.
  until php -r '$c=@mysqli_connect(getenv("WORDPRESS_DB_HOST"),getenv("WORDPRESS_DB_USER"),getenv("WORDPRESS_DB_PASSWORD"),getenv("WORDPRESS_DB_NAME")); exit($c?0:1);' >/dev/null 2>&1; do sleep 2; done

  if ! $WP core is-installed >/dev/null 2>&1; then
    echo "[twint] installing WordPress…"
    $WP core install --url="$WP_URL" --title="$WP_TITLE" \
      --admin_user="$WP_ADMIN_USER" --admin_password="$WP_ADMIN_PASSWORD" \
      --admin_email="$WP_ADMIN_EMAIL" --skip-email
  fi

  if ! $WP plugin is-installed woocommerce >/dev/null 2>&1; then
    # shellcheck disable=SC2086 -- WOO_VERSION is an intentional optional flag
    $WP plugin install woocommerce ${WOO_VERSION:+--version="$WOO_VERSION"} --activate
  else
    $WP plugin activate woocommerce || true
  fi

  $WP plugin activate twint-woocommerce-extension || true

  # Store defaults for TWINT: Swiss Francs / Switzerland, skip the setup wizard.
  $WP option update woocommerce_currency CHF || true
  $WP option update woocommerce_default_country "CH:ZH" || true
  $WP option update woocommerce_store_address "Bahnhofstrasse 1" || true
  $WP option update woocommerce_store_city "Zurich" || true
  $WP option update woocommerce_store_postcode "8001" || true
  $WP option update woocommerce_currency_pos "left_space" || true
  $WP option update woocommerce_onboarding_profile '{"skipped":true,"completed":true}' --format=json || true

  # Sample WooCommerce products (only when the catalog is empty).
  if [ "$($WP post list --post_type=product --format=count 2>/dev/null)" = "0" ]; then
    SAMPLE=/var/www/html/wp-content/plugins/woocommerce/sample-data/sample_products.xml
    if [ -f "$SAMPLE" ]; then
      echo "[twint] importing sample products…"
      $WP plugin install wordpress-importer --activate || true
      $WP import "$SAMPLE" --authors=create || true
    fi
  fi

  $WP rewrite structure '/%postname%/' --hard || true
  $WP rewrite flush --hard || true
  echo "[twint] provision complete → $WP_URL"
}

if [ "${TWINT_ROLE:-web}" = "builder" ]; then
  build
  echo "[twint] builder done"
  exit 0
fi

# web role: the builder service already produced vendor/ + dist/.
# Provision in the background once WP core + DB are ready, then serve.
provision &
exec docker-entrypoint.sh "$@"
