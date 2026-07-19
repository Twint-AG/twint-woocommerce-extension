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
  until $WP db check >/dev/null 2>&1; do sleep 2; done

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
