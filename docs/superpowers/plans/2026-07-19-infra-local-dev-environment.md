# Local Dev Environment Rework — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: superpowers:subagent-driven-development or superpowers:executing-plans. Steps use checkbox (`- [ ]`) syntax.

**Goal:** Replace the bloated multi-variant `infra/` with a single clean `infra/local/` that boots WP-latest + WP-5.9 with the TWINT plugin live-mounted, built and provisioned in-container.

**Architecture:** One `mysql`, one one-shot `builder` (PHP 8.1 image: `composer install` + `npm run build` into a shared `vendor` volume and the mounted `dist/`), and two WordPress services (`wc_latest`, `wc_oldest`) that `depends_on` the builder and auto-provision via wp-cli. Committed WP-core/Woo (~16,250 files) is deleted.

**Tech Stack:** Docker Compose, official `wordpress` images, wp-cli, Composer, Node 20.

**Spec:** `docs/superpowers/specs/2026-07-19-infra-local-dev-environment-design.md`

## Global Constraints
- Delete only committed WP-core/Woo/vendor; keep the plugin repo intact.
- Plugin loads `vendor/autoload.php` (dev = plain `composer install`, no scoping).
- SDK `dev-dev/v9` needs `GITLAB_TOKEN` (gitignored `.env`).
- Builder runs on PHP 8.1 (the floor) so resolved deps work on both instances.
- No host toolchain required; no `/etc/hosts` edits (published ports).
- TWINT credentials entered manually via wp-admin.

---

### Task 1: Remove committed bloat + gitignore guards

**Files:** delete dirs; edit `.gitignore`, `infra/.gitignore`.

- [ ] **Step 1: Remove the sibling variants and committed Woo**
```bash
cd "$(git rev-parse --show-toplevel)"
git rm -r -q infra/latest infra/oldest infra/wc10 infra/wordpress infra/local/woocommerce
git rm -q --ignore-unmatch infra/.DS_Store
```
- [ ] **Step 2: Guard against re-committing WP core under infra**
Append to `infra/.gitignore`:
```gitignore
# Never commit WordPress core, WooCommerce, or built deps into infra
wp-admin/
wp-includes/
wp-content/
woocommerce/
vendor/
node_modules/
dist/
.env
*.p12
.DS_Store
```
- [ ] **Step 3: Verify + commit**
```bash
git status --short | wc -l          # expect a large deletion count
git add infra/.gitignore
git commit -m "chore(infra): remove committed WP core / WooCommerce bloat + gitignore guards"
```
Expected: ~16k deletions staged by the `git rm`.

---

### Task 2: Build image (`infra/local/docker/Dockerfile` + `custom.ini`)

**Files:** Create `infra/local/docker/Dockerfile`, `infra/local/docker/custom.ini`. Delete old `Dockerfile`, `Dockerfile59`, `proxy.conf`, `latest.conf`, `oldest.conf`.

- [ ] **Step 1: Remove old docker confs**
```bash
git rm -q infra/local/docker/Dockerfile infra/local/docker/Dockerfile59 \
  infra/local/docker/proxy.conf infra/local/docker/latest.conf infra/local/docker/oldest.conf
```
- [ ] **Step 2: Write `infra/local/docker/Dockerfile`**
```dockerfile
# syntax=docker/dockerfile:1
ARG BASE_IMAGE=wordpress:latest
FROM ${BASE_IMAGE}

# TWINT SDK PHP extensions + build tooling
RUN set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends \
        git unzip less default-mysql-client ca-certificates curl \
        libxslt-dev libxml2-dev libicu-dev; \
    docker-php-ext-install -j"$(nproc)" xsl soap intl; \
    curl -fsSL https://deb.nodesource.com/setup_20.x | bash -; \
    apt-get install -y --no-install-recommends nodejs; \
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer; \
    curl -fsSL https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar -o /usr/local/bin/wp; \
    chmod +x /usr/local/bin/wp; \
    apt-get clean; rm -rf /var/lib/apt/lists/*

COPY custom.ini "$PHP_INI_DIR/conf.d/zz-twint-dev.ini"
COPY entrypoint.sh /usr/local/bin/twint-entrypoint.sh
RUN chmod +x /usr/local/bin/twint-entrypoint.sh

ENTRYPOINT ["/usr/local/bin/twint-entrypoint.sh"]
CMD ["apache2-foreground"]
```
- [ ] **Step 3: Write `infra/local/docker/custom.ini`**
```ini
upload_max_filesize = 64M
post_max_size = 64M
memory_limit = 512M
max_execution_time = 120
```
- [ ] **Step 4: Commit** (`git add infra/local/docker/Dockerfile infra/local/docker/custom.ini`)

---

### Task 3: Entrypoint (`infra/local/docker/entrypoint.sh`)

**Files:** Create `infra/local/docker/entrypoint.sh`.

Serves two roles by `$TWINT_ROLE`: `builder` (build then exit) or default (build-guard skip → provision in background → start apache via the stock wordpress entrypoint).

- [ ] **Step 1: Write the script**
```bash
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

# web role: builder already produced vendor/dist; provision in background, then serve.
provision &
exec docker-entrypoint.sh "$@"
```
- [ ] **Step 2: Verify shell syntax**
```bash
bash -n infra/local/docker/entrypoint.sh && echo OK
```
Expected: `OK`
- [ ] **Step 3: Commit** (`git add infra/local/docker/entrypoint.sh`)

---

### Task 4: Compose + env (`infra/local/compose.yaml`, `.env.example`)

**Files:** Overwrite `infra/local/compose.yaml`; create `infra/local/.env.example`.

- [ ] **Step 1: Write `infra/local/compose.yaml`**
```yaml
name: twint-local

x-wp-build: &wp-build
  context: ./docker
  dockerfile: Dockerfile

services:
  mysql:
    image: mysql:8
    container_name: twint-db
    restart: unless-stopped
    environment:
      MYSQL_ROOT_PASSWORD: ${DB_ROOT_PASSWORD}
      MYSQL_USER: ${DB_USER}
      MYSQL_PASSWORD: ${DB_PASSWORD}
    ports:
      - "3366:3306"
    volumes:
      - db:/var/lib/mysql
      - ./mysql-init:/docker-entrypoint-initdb.d:ro
    healthcheck:
      test: ["CMD", "mysqladmin", "ping", "-h", "localhost", "-p${DB_ROOT_PASSWORD}"]
      interval: 5s
      timeout: 5s
      retries: 20

  builder:
    build:
      <<: *wp-build
      args:
        BASE_IMAGE: wordpress:6.6-php8.1-apache
    image: twint-local/wp:php81
    container_name: twint-builder
    environment:
      TWINT_ROLE: builder
      COMPOSER_AUTH: '{"http-basic":{"git.nfq.asia":{"username":"${GITLAB_USERNAME}","password":"${GITLAB_TOKEN}"}}}'
    volumes:
      - ../../:/var/www/html/wp-content/plugins/twint-woocommerce-extension
      - vendor:/var/www/html/wp-content/plugins/twint-woocommerce-extension/vendor
      - node_modules:/var/www/html/wp-content/plugins/twint-woocommerce-extension/node_modules

  wc_latest:
    build:
      <<: *wp-build
      args:
        BASE_IMAGE: wordpress:php8.3-apache
    restart: unless-stopped
    container_name: twint-wc-latest
    depends_on:
      mysql: { condition: service_healthy }
      builder: { condition: service_completed_successfully }
    ports:
      - "8081:80"
    environment:
      WORDPRESS_DB_HOST: mysql
      WORDPRESS_DB_USER: ${DB_USER}
      WORDPRESS_DB_PASSWORD: ${DB_PASSWORD}
      WORDPRESS_DB_NAME: wc_latest
      WP_URL: http://localhost:8081
      WP_TITLE: TWINT WC latest
      WP_ADMIN_USER: ${WP_ADMIN_USER}
      WP_ADMIN_PASSWORD: ${WP_ADMIN_PASSWORD}
      WP_ADMIN_EMAIL: ${WP_ADMIN_EMAIL}
      WOO_VERSION: ${WC_LATEST_WOO_VERSION:-}
    volumes:
      - wp_latest:/var/www/html/wp-content
      - ../../:/var/www/html/wp-content/plugins/twint-woocommerce-extension
      - vendor:/var/www/html/wp-content/plugins/twint-woocommerce-extension/vendor

  wc_oldest:
    build:
      <<: *wp-build
      args:
        BASE_IMAGE: wordpress:5.9-php8.1-apache
    restart: unless-stopped
    container_name: twint-wc-oldest
    depends_on:
      mysql: { condition: service_healthy }
      builder: { condition: service_completed_successfully }
    ports:
      - "8082:80"
    environment:
      WORDPRESS_DB_HOST: mysql
      WORDPRESS_DB_USER: ${DB_USER}
      WORDPRESS_DB_PASSWORD: ${DB_PASSWORD}
      WORDPRESS_DB_NAME: wc_oldest
      WP_URL: http://localhost:8082
      WP_TITLE: TWINT WC oldest
      WP_ADMIN_USER: ${WP_ADMIN_USER}
      WP_ADMIN_PASSWORD: ${WP_ADMIN_PASSWORD}
      WP_ADMIN_EMAIL: ${WP_ADMIN_EMAIL}
      WOO_VERSION: ${WC_OLDEST_WOO_VERSION:-}
    volumes:
      - wp_oldest:/var/www/html/wp-content
      - ../../:/var/www/html/wp-content/plugins/twint-woocommerce-extension
      - vendor:/var/www/html/wp-content/plugins/twint-woocommerce-extension/vendor

volumes:
  db:
  vendor:
  node_modules:
  wp_latest:
  wp_oldest:
```
- [ ] **Step 2: Write `infra/local/mysql-init/01-databases.sql`** (create both DBs + grant)
```sql
CREATE DATABASE IF NOT EXISTS wc_latest CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS wc_oldest CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
GRANT ALL PRIVILEGES ON wc_latest.* TO '${MYSQL_USER}'@'%';
GRANT ALL PRIVILEGES ON wc_oldest.* TO '${MYSQL_USER}'@'%';
FLUSH PRIVILEGES;
```
Note: MySQL init scripts don't expand shell vars; use a fixed user. Replace `${MYSQL_USER}` with the literal `DB_USER` value chosen in `.env.example` (`twint`). Final file uses `TO 'twint'@'%'`.
- [ ] **Step 3: Write `infra/local/.env.example`**
```dotenv
# Database
DB_ROOT_PASSWORD=rootpass
DB_USER=twint
DB_PASSWORD=twintpass

# WordPress admin (created by wp-cli on first boot)
WP_ADMIN_USER=admin
WP_ADMIN_PASSWORD=admin
WP_ADMIN_EMAIL=admin@example.com

# WooCommerce versions ("" = latest)
WC_LATEST_WOO_VERSION=
WC_OLDEST_WOO_VERSION=6.0.0

# TWINT SDK (dev-dev/v9) — private repo credentials for composer
GITLAB_USERNAME=
GITLAB_TOKEN=
```
- [ ] **Step 4: Validate compose + commit**
```bash
cp infra/local/.env.example infra/local/.env   # local only; .env is gitignored
docker compose -f infra/local/compose.yaml config >/dev/null && echo "compose OK"
git add infra/local/compose.yaml infra/local/.env.example infra/local/mysql-init
git commit -m "feat(infra): single local compose (builder + wc_latest/wc_oldest + mysql)"
```
Expected: `compose OK`. (If Docker is unavailable in this env, note it and defer live validation to the user.)

---

### Task 5: README + operational scripts

**Files:** Overwrite `infra/local/` README; optional thin `bin/` wrappers.

- [ ] **Step 1: Write `infra/local/README.md`** covering: prerequisites (Docker, VPN for SDK, `.env` from `.env.example`), quickstart (`docker compose up -d --build`), URLs (`localhost:8081`/`8082`, admin creds), how to enter TWINT test credentials in wp-admin, how to force a rebuild (`docker compose run --rm builder` after deleting the vendor volume), and the **EC test scenarios** (happy path, processing→PAID race, concurrency, cron/stale-lock reclaim, "I have paid") with the log location (`wp-admin → WooCommerce → Status → Logs`, source `twint-woocommerce-extension`) and greps.
- [ ] **Step 2: Commit** (`git add infra/local/README.md`)

---

### Task 6: Verification

- [ ] **Step 1: Static** — `docker compose -f infra/local/compose.yaml config` parses; `bash -n` on entrypoint; `git ls-files infra | wc -l` is now small (tens, not thousands).
- [ ] **Step 2: Live (user, needs Docker + VPN)** — `docker compose up -d --build`; builder completes; both sites reach the WooCommerce setup; `wp plugin list` shows woocommerce + twint active; edit a plugin PHP/JS file on host and confirm it reflects (JS after a `builder` rerun). Enter TWINT test creds and run the EC scenarios.
