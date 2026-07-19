#!/usr/bin/env bash
set -euo pipefail

PLUGIN_DIR=/var/www/html/wp-content/plugins/twint-woocommerce-extension
WP="wp --allow-root --path=/var/www/html"

# Build the plugin's deps with THIS instance's PHP, into this instance's own
# vendor/ + node_modules/ + dist/ volumes. Always returns 0 — a build failure
# (e.g. a branch whose deps need a newer PHP than this instance) must not crash
# the container; the plugin's own vendor check keeps it inactive with a notice.
build() {
  # `composer update` (not install): the repo ships per-PHP-version lockfiles
  # (composer81.lock … composer85.lock) because deps like twint-ag/psl-compat
  # differ by PHP, and there is no single shared composer.lock that is correct
  # for every instance. Resolving fresh against composer.json for THIS instance's
  # PHP picks the right versions and avoids a shared-lockfile race between
  # instances. --no-dev: runtime only (scoper/rector/phpstan aren't needed here).
  echo "[twint] composer update (PHP $(php -r 'echo PHP_VERSION;'))…"
  if ! composer update -d "$PLUGIN_DIR" --no-interaction --prefer-dist --no-progress --no-dev; then
    echo "[twint] composer update FAILED on this PHP — plugin will stay inactive on this instance"
    return 0
  fi
  # Don't leave a composer.lock in the live-mounted source: the repo tracks
  # per-PHP composerXX.lock (not a single lock) and we always resolve fresh, so a
  # stray composer.lock would only dirty the working tree.
  rm -f "$PLUGIN_DIR/composer.lock"

  # npm ci is expensive → only when package-lock.json changed (e.g. branch switch);
  # the webpack build is cheap so always run it, keeping dist/ current.
  local hashfile="$PLUGIN_DIR/node_modules/.pkg-lock-hash" want
  want="$(sha1sum "$PLUGIN_DIR/package-lock.json" 2>/dev/null | cut -d' ' -f1)"
  if [ ! -d "$PLUGIN_DIR/node_modules/.bin" ] || [ "$(cat "$hashfile" 2>/dev/null)" != "$want" ]; then
    echo "[twint] npm ci…"
    ( cd "$PLUGIN_DIR" && npm ci --no-audit --no-fund ) && printf '%s' "$want" > "$hashfile"
  else
    echo "[twint] node_modules up to date — skipping npm ci"
  fi
  echo "[twint] npm run build…"
  ( cd "$PLUGIN_DIR" && npm run build ) || echo "[twint] npm run build failed"
  return 0
}

provision() {
  echo "[twint] waiting for wp-config + database…"
  until [ -f /var/www/html/wp-config.php ]; do sleep 2; done
  # Use PHP mysqli (WordPress's own driver) — the mariadb CLI rejects MySQL 8's
  # self-signed TLS cert, so `wp db check` is unreliable here.
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

  # Only activate TWINT when its deps built for this instance's PHP. vendor/ is a
  # mounted volume (always a dir), so the plugin's own is_dir() guard can't stop a
  # fatal on a missing autoload — gate activation on the actual autoload file.
  if [ -f "$PLUGIN_DIR/vendor/autoload.php" ]; then
    $WP plugin activate twint-woocommerce-extension || true
  else
    echo "[twint] vendor missing (build failed for this PHP) — leaving TWINT deactivated"
    $WP plugin deactivate twint-woocommerce-extension >/dev/null 2>&1 || true
  fi

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

  # Root-run wp-cli (sample import, plugin installs) can leave root-owned files in
  # uploads/, which blocks wp-admin (www-data) from writing there. Hand it back so
  # theme/plugin/media installs work from the UI. (Not the bind-mounted plugin.)
  chown -R www-data:www-data /var/www/html/wp-content/uploads 2>/dev/null || true

  echo "[twint] provision complete → $WP_URL"
}

# Build this instance's deps and provision in the background (build first so the
# plugin can activate), then hand off to the stock wordpress entrypoint (apache).
( build; provision ) &
exec docker-entrypoint.sh "$@"
