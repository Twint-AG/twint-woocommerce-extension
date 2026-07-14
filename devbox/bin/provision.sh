#!/usr/bin/env bash
# One-time-per-instance provisioning (idempotent). Installs WordPress core and
# WooCommerce via WP-CLI, sets the site URL to the subdomain, and lays down a
# testable baseline (permalinks, CH/CHF, a demo product).
# Usage: provision.sh [all|wc1|wc2|wc3]
set -euo pipefail
# shellcheck disable=SC1091
. "$(dirname "${BASH_SOURCE[0]}")/_lib.sh"
load_env

: "${DOMAIN_BASE:?}"; : "${WP_ADMIN_USER:?}"; : "${WP_ADMIN_PASSWORD:?}"
: "${WP_ADMIN_EMAIL:?}"; : "${DB_ROOT_PASSWORD:?}"

resolve_targets "${1:-all}"
for inst in "${RESOLVED_TARGETS[@]}"; do
  url="https://${inst}.${DOMAIN_BASE}"

  echo "==> [$inst] waiting for database"
  db_ready=
  for _ in $(seq 1 30); do
    # $WORDPRESS_DB_HOST must expand in the container's shell, not here.
    # shellcheck disable=SC2016
    if dc exec -T -e MYSQL_PWD="$DB_ROOT_PASSWORD" "$inst" sh -c \
      'mysqladmin ping -h"$WORDPRESS_DB_HOST" -uroot --silent' \
      >/dev/null 2>&1; then db_ready=1; break; fi
    sleep 2
  done
  [ -n "${db_ready:-}" ] || echo "WARNING: [$inst] database not reachable after 60s; continuing" >&2

  if wp_cli "$inst" core is-installed >/dev/null 2>&1; then
    echo "==> [$inst] core already installed; asserting URL -> $url"
    wp_cli "$inst" option update home "$url"
    wp_cli "$inst" option update siteurl "$url"
  else
    echo "==> [$inst] installing WordPress core at $url"
    wp_cli "$inst" core install \
      --url="$url" --title="TWINT Woo ${inst}" \
      --admin_user="$WP_ADMIN_USER" --admin_password="$WP_ADMIN_PASSWORD" \
      --admin_email="$WP_ADMIN_EMAIL" --skip-email
  fi

  # WooCommerce at the pinned version (empty = latest). --force re-fetches every
  # run so a version bump can't collide with an existing install dir; --activate
  # is always safe.
  woo_var="${inst^^}_WOO_VERSION"     # wc1 -> WC1_WOO_VERSION
  woo_ver="${!woo_var:-}"
  if [ -n "$woo_ver" ]; then
    echo "==> [$inst] installing WooCommerce $woo_ver"
    wp_cli "$inst" plugin install woocommerce --version="$woo_ver" --force --activate
  else
    echo "==> [$inst] installing WooCommerce (latest)"
    wp_cli "$inst" plugin install woocommerce --force --activate
  fi

  echo "==> [$inst] baseline config (permalinks, CH/CHF)"
  wp_cli "$inst" rewrite structure '/%postname%/' --hard
  wp_cli "$inst" option update woocommerce_default_country 'CH'
  wp_cli "$inst" option update woocommerce_currency 'CHF'

  if [ "$(wp_cli "$inst" post list --post_type=product --format=count 2>/dev/null || echo 0)" = "0" ]; then
    echo "==> [$inst] creating demo product"
    wp_cli "$inst" wc product create \
      --name='TWINT Test Product' --regular_price='9.90' \
      --user="$WP_ADMIN_USER" || echo "WARNING: demo product not created" >&2
  fi

  wp_cli "$inst" cache flush || true
  echo "==> [$inst] provisioned"
done
