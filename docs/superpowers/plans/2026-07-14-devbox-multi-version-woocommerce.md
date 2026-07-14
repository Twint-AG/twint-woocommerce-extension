# Multi-version WooCommerce devbox — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Run three WordPress/WooCommerce/PHP instances (`wc1`/`wc2`/`wc3` = oldest/current/latest) concurrently on the `twint-dev` EC2 box behind the Shopware devbox's existing Traefik, with a one-command deploy that builds the plugin ZIP once and installs it into all three.

**Architecture:** A new top-level `devbox/` folder with its own Docker Compose project (`woo-devbox`). It runs one shared MySQL + three WordPress+Apache containers (stock `wordpress` image + WP-CLI + `ext-xsl`), all attached to the **external** `devbox_web` network so the already-running `devbox_proxy` Traefik auto-discovers and routes them (reusing its `le` Let's Encrypt resolver and `devbox-auth` basic-auth middleware). `deploy.sh` builds the plugin ZIP in a throwaway build container via the existing `bin/archive.sh`, then `wp plugin install --force --activate` into each instance.

**Tech Stack:** Docker Compose, Traefik v3.7 (shared, not managed here), `wordpress:*-apache` images, WP-CLI, MySQL 8, Bash, the existing `bin/archive.sh` (php-scoper multi-PHP build).

## Global Constraints

- **Spec:** `docs/superpowers/specs/2026-07-14-devbox-multi-version-woocommerce-design.md` — authoritative; this plan implements it.
- **Host is NOT fresh.** Docker is already installed; a Traefik (`devbox_proxy`) already owns `:80`/`:443`/`127.0.0.1:8080`. The Woo stack **publishes no host ports** and **runs no proxy of its own**.
- **Shared network name:** `devbox_web` (confirmed live). Woo containers attach to it as `external`.
- **Shared middleware / resolver:** reference `devbox-auth@docker` and `certresolver=le` — do not redefine them.
- **Confirmed live values** (copy into `devbox/.env` on the box from `/home/ubuntu/twint-shopware-plugin/devbox/.env`): `DOMAIN_BASE=twint.dev.nfq-asia.com`, `GITLAB_USERNAME=taitran`, `GITLAB_TOKEN=<from Shopware .env>`, basic-auth `twint`/`TwintDev2026` (already in Traefik, not needed by Woo stack), GitLab host `git.nfq.asia`, SDK `git.nfq.asia/twint-ag/sdk.git`.
- **Instances:** `wc1` (WP 5.9 / PHP 8.1 / Woo 6.0.0), `wc2` (WP 6.6 / PHP 8.3 / Woo current), `wc3` (WP latest / PHP 8.4 / Woo latest). Image tags verified present on Docker Hub: `wordpress:5.9-php8.1-apache`, `wordpress:6.6-php8.3-apache`, `wordpress:php8.4-apache`, `mysql:8`.
- **No secrets committed.** All secrets live only in the gitignored `devbox/.env` on the box.
- **Repo origin:** `git@git.nfq.asia:twint-ag/twint-woocommerce-extension.git`. Work branch: `feature/devbox-multi-version-woocommerce`.
- **One ZIP for all instances:** the plugin loads `vendor`/`vendor82`…`vendor85` per PHP version at runtime, so a single `archive.sh` build installs into all three.
- The plugin ZIP unpacks to folder `twint-woocommerce-extension` (main file `twint-woocommerce-extension.php`).
- Legacy `infra/` is untouched.

### Verification model

This is ops/infra tooling; "tests" are **shellcheck + `docker compose config` locally** and **live smoke tests over `ssh twint-dev`**. Live tasks assume the work branch is pushed to GitLab and the repo is cloned on the box at `/home/ubuntu/twint-woocommerce-extension` (Task 7 sets this up). Every script starts with `#!/usr/bin/env bash` + `set -euo pipefail` and must pass `shellcheck`.

---

### Task 1: Scaffolding — env template, gitignore, DB init, gitattributes

**Files:**
- Create: `devbox/.gitignore`
- Create: `devbox/.env.example`
- Create: `devbox/mysql-init/create-databases.sql`
- Create: `.gitattributes` (repo root)

**Interfaces:**
- Produces: the `.env` variable contract every script and `compose.yaml` reads — `DOMAIN_BASE`, `PROXY_NETWORK`, `DB_ROOT_PASSWORD`, `DB_USER`, `DB_PASSWORD`, `WC{1,2,3}_WP_TAG`, `WC{1,2,3}_WOO_VERSION`, `WP_ADMIN_USER`, `WP_ADMIN_PASSWORD`, `WP_ADMIN_EMAIL`, `GIT_REMOTE`, `SDK_REMOTE`, `GITLAB_USERNAME`, `GITLAB_TOKEN`.

- [ ] **Step 1: Write `devbox/.gitignore`**

```gitignore
# Host-specific / generated — never commit
/.env
/logs/
/build/
```

- [ ] **Step 2: Write `devbox/.env.example`**

```bash
# ── Woo devbox configuration ─────────────────────────────────────────
# Copy to `.env` (gitignored) and fill in. Compose auto-reads .env; the
# bin/ scripts source it too (via _lib.sh).
#
# On twint-dev, copy the shared values from the Shopware devbox .env:
#   /home/ubuntu/twint-shopware-plugin/devbox/.env
# (DOMAIN_BASE, GITLAB_USERNAME, GITLAB_TOKEN, GIT host, SDK_REMOTE).

# Base domain. Instances are served at wcN.$DOMAIN_BASE (dot-joined):
#   wc1.$DOMAIN_BASE, wc2.$DOMAIN_BASE, wc3.$DOMAIN_BASE
# Reuses the same *.<domain> wildcard record the Shopware box uses.
DOMAIN_BASE=twint.dev.nfq-asia.com

# The EXISTING Shopware Traefik's Docker network. The Woo stack joins it so
# that proxy routes wcN.*. Confirmed name on twint-dev: devbox_web.
PROXY_NETWORK=devbox_web

# Shared MySQL credentials (one container, databases wc1/wc2/wc3).
DB_ROOT_PASSWORD=change-me-root
DB_USER=wordpress
DB_PASSWORD=change-me-app

# Per-instance WordPress image tags (must be the -apache variant).
WC1_WP_TAG=5.9-php8.1-apache
WC2_WP_TAG=6.6-php8.3-apache
WC3_WP_TAG=php8.4-apache

# Per-instance WooCommerce version. Empty = install the latest available.
WC1_WOO_VERSION=6.0.0
WC2_WOO_VERSION=9.3.3
WC3_WOO_VERSION=

# WordPress admin account created by provision.sh (same for all instances).
WP_ADMIN_USER=admin
WP_ADMIN_PASSWORD=change-me-admin
WP_ADMIN_EMAIL=ops@example.com

# GitLab plugin + SDK access (for the build container's composer http-basic).
# Accepts any form; deploy.sh normalizes to host/path.
GIT_REMOTE=git@git.nfq.asia:twint-ag/twint-woocommerce-extension.git
SDK_REMOTE=git.nfq.asia/twint-ag/sdk.git
GITLAB_USERNAME=your-gitlab-username
# GitLab read token (deploy token / PAT with read_repository). Keep secret.
GITLAB_TOKEN=changeme
```

- [ ] **Step 3: Write `devbox/mysql-init/create-databases.sql`**

The MySQL entrypoint creates `MYSQL_USER` (`wordpress`) before running init scripts, so the grants below resolve. Runs only on first (empty-volume) init.

```sql
CREATE DATABASE IF NOT EXISTS `wc1` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS `wc2` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS `wc3` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
GRANT ALL PRIVILEGES ON `wc1`.* TO 'wordpress'@'%';
GRANT ALL PRIVILEGES ON `wc2`.* TO 'wordpress'@'%';
GRANT ALL PRIVILEGES ON `wc3`.* TO 'wordpress'@'%';
FLUSH PRIVILEGES;
```

- [ ] **Step 4: Write repo-root `.gitattributes`** (mark internal tooling export-ignore, parity with Shopware)

```gitattributes
devbox/ export-ignore
infra/ export-ignore
docs/superpowers/ export-ignore
```

- [ ] **Step 5: Verify the SQL parses and env has no accidental secrets**

Run: `grep -nE 'ZMhX|TwintDev2026' devbox/.env.example .gitattributes devbox/.gitignore devbox/mysql-init/create-databases.sql; echo "exit=$?"`
Expected: no matches (grep exit 1 → the `echo` prints `exit=1`), confirming no real secret leaked into committed files.

- [ ] **Step 6: Commit**

```bash
git add devbox/.gitignore devbox/.env.example devbox/mysql-init/create-databases.sql .gitattributes
git commit --no-gpg-sign -m "feat(devbox): scaffolding — env template, gitignore, DB init, gitattributes"
```

---

### Task 2: Container images — WordPress+WP-CLI image and the build image

**Files:**
- Create: `devbox/Dockerfile` (per-instance WordPress image, parameterized by `WP_TAG`)
- Create: `devbox/build.Dockerfile` (throwaway build environment for `archive.sh`)

**Interfaces:**
- Produces: `Dockerfile` build arg `WP_TAG` (full tag suffix, e.g. `5.9-php8.1-apache`); the resulting image has `wp` on `PATH` and `ext-xsl` enabled. `build.Dockerfile` produces an image with `php` 8.1, `composer`, `node`/`npm` 18, `zip`, `git`.

- [ ] **Step 1: Write `devbox/Dockerfile`**

```dockerfile
# Per-instance WordPress image: stock wordpress + WP-CLI + ext-xsl (+ mysql client
# for provision.sh DB checks). Parameterized by the WordPress image tag.
ARG WP_TAG=6.6-php8.3-apache
FROM wordpress:${WP_TAG}

RUN set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends less default-mysql-client libxslt1-dev; \
    docker-php-ext-install xsl; \
    curl -fsSL -o /usr/local/bin/wp \
      https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar; \
    chmod +x /usr/local/bin/wp; \
    apt-get clean; rm -rf /var/lib/apt/lists/*
```

- [ ] **Step 2: Write `devbox/build.Dockerfile`**

```dockerfile
# Throwaway build environment for bin/archive.sh: PHP 8.1 CLI + composer +
# node 18 + zip/git. archive.sh installs vendors with --ignore-platform-reqs,
# so runtime PHP extensions are not required here.
FROM php:8.1-cli

RUN set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends git unzip zip curl libzip-dev; \
    docker-php-ext-install zip; \
    curl -fsSL https://deb.nodesource.com/setup_18.x | bash -; \
    apt-get install -y --no-install-recommends nodejs; \
    curl -sS https://getcomposer.org/installer | php -- \
      --install-dir=/usr/local/bin --filename=composer; \
    apt-get clean; rm -rf /var/lib/apt/lists/*
```

- [ ] **Step 3: Verify both images build on the box**

(Requires the repo on the box — if not yet cloned, defer this step to Task 7 and just eyeball syntax now.)
Run: `ssh twint-dev 'cd /home/ubuntu/twint-woocommerce-extension && docker build -q -f devbox/build.Dockerfile -t woo-devbox-build devbox/ && docker build -q --build-arg WP_TAG=6.6-php8.3-apache -f devbox/Dockerfile -t woo-devbox-wc-test devbox/'`
Expected: two image IDs printed, no error.

- [ ] **Step 4: Commit**

```bash
git add devbox/Dockerfile devbox/build.Dockerfile
git commit --no-gpg-sign -m "feat(devbox): WordPress+WP-CLI image and build image"
```

---

### Task 3: Shared library and Compose file

**Files:**
- Create: `devbox/bin/_lib.sh`
- Create: `devbox/compose.yaml`

**Interfaces:**
- Consumes: `.env` contract from Task 1.
- Produces: shell helpers `load_env`, `is_instance`, `resolve_targets` (populates `RESOLVED_TARGETS`), `dc` (compose wrapper), `wp_cli <inst> <args...>`, `norm_remote <remote>`; global `INSTANCES=(wc1 wc2 wc3)`, `DEVBOX_DIR`. Compose project `woo-devbox` with services `wc-db`, `wc1`, `wc2`, `wc3` on external network `${PROXY_NETWORK}`.

- [ ] **Step 1: Write `devbox/bin/_lib.sh`**

```bash
#!/usr/bin/env bash
# Shared helpers for Woo devbox scripts.
# Source it:  . "$(dirname "${BASH_SOURCE[0]}")/_lib.sh"
set -euo pipefail

DEVBOX_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
ENV_FILE="$DEVBOX_DIR/.env"

INSTANCES=(wc1 wc2 wc3)

# Load and export every var from devbox/.env.
load_env() {
  if [ ! -f "$ENV_FILE" ]; then
    echo "ERROR: $ENV_FILE not found. Copy .env.example to .env and fill it in." >&2
    exit 1
  fi
  set -a
  # shellcheck disable=SC1090
  . "$ENV_FILE"
  set +a
}

# True if $1 is a known instance name.
is_instance() {
  local x="${1:-}" i
  for i in "${INSTANCES[@]}"; do [ "$i" = "$x" ] && return 0; done
  return 1
}

# Validate a target ("all" or one instance) and populate RESOLVED_TARGETS in the
# CALLER's shell. Exits non-zero on invalid/missing input. Call directly, then
# read "${RESOLVED_TARGETS[@]}". Do NOT wrap in $(...) — a subshell swallows exit.
resolve_targets() {
  local target="${1:-}"
  RESOLVED_TARGETS=()
  if [ -z "$target" ]; then
    echo "ERROR: missing instance (one of: ${INSTANCES[*]} all)" >&2; exit 1
  fi
  if [ "$target" = "all" ]; then RESOLVED_TARGETS=("${INSTANCES[@]}"); return 0; fi
  if is_instance "$target"; then RESOLVED_TARGETS=("$target"); return 0; fi
  echo "ERROR: unknown instance '$target' (expected: ${INSTANCES[*]} all)" >&2; exit 1
}

# docker compose pinned to the Woo devbox project.
dc() {
  docker compose --project-directory "$DEVBOX_DIR" -f "$DEVBOX_DIR/compose.yaml" "$@"
}

# Run WP-CLI inside an instance as the web user.
wp_cli() {
  local inst="$1"; shift
  dc exec -T -u www-data "$inst" wp --path=/var/www/html "$@"
}

# Normalize a remote to scheme-less host/path. Accepts:
#   git@host:group/repo.git | host/group/repo.git | https://host/group/repo.git
norm_remote() {
  local r="$1"
  r="${r#https://}"; r="${r#http://}"; r="${r#git@}"; r="${r/://}"
  printf '%s' "$r"
}
```

- [ ] **Step 2: Write `devbox/compose.yaml`**

```yaml
name: woo-devbox

services:
  wc-db:
    image: mysql:8
    container_name: wc-db
    restart: unless-stopped
    environment:
      MYSQL_ROOT_PASSWORD: ${DB_ROOT_PASSWORD}
      MYSQL_USER: ${DB_USER}
      MYSQL_PASSWORD: ${DB_PASSWORD}
    volumes:
      - "wc_db:/var/lib/mysql"
      - "./mysql-init:/docker-entrypoint-initdb.d:ro"
    networks:
      - web

  wc1:
    build:
      context: .
      dockerfile: Dockerfile
      args:
        WP_TAG: ${WC1_WP_TAG}
    image: woo-devbox/wc1:${WC1_WP_TAG}
    container_name: wc1
    restart: unless-stopped
    depends_on: [wc-db]
    environment:
      WORDPRESS_DB_HOST: wc-db
      WORDPRESS_DB_NAME: wc1
      WORDPRESS_DB_USER: ${DB_USER}
      WORDPRESS_DB_PASSWORD: ${DB_PASSWORD}
      WORDPRESS_CONFIG_EXTRA: |
        if (isset($$_SERVER['HTTP_X_FORWARDED_PROTO']) && $$_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') { $$_SERVER['HTTPS'] = 'on'; }
    volumes:
      - "wc1_html:/var/www/html"
    labels:
      - "traefik.enable=true"
      - "traefik.docker.network=${PROXY_NETWORK}"
      - "traefik.http.routers.wc1.rule=Host(`wc1.${DOMAIN_BASE}`)"
      - "traefik.http.routers.wc1.entrypoints=websecure"
      - "traefik.http.routers.wc1.tls.certresolver=le"
      - "traefik.http.services.wc1.loadbalancer.server.port=80"
      - "traefik.http.routers.wc1.middlewares=devbox-auth@docker"
      # WooCommerce REST/Store API (/wp-json) authenticates itself — bypass basic
      # auth via a higher-priority router (same backend, no middleware).
      - "traefik.http.routers.wc1-rest.rule=Host(`wc1.${DOMAIN_BASE}`) && PathPrefix(`/wp-json`)"
      - "traefik.http.routers.wc1-rest.entrypoints=websecure"
      - "traefik.http.routers.wc1-rest.tls.certresolver=le"
      - "traefik.http.routers.wc1-rest.service=wc1"
      - "traefik.http.routers.wc1-rest.priority=100"
    networks:
      - web

  wc2:
    build:
      context: .
      dockerfile: Dockerfile
      args:
        WP_TAG: ${WC2_WP_TAG}
    image: woo-devbox/wc2:${WC2_WP_TAG}
    container_name: wc2
    restart: unless-stopped
    depends_on: [wc-db]
    environment:
      WORDPRESS_DB_HOST: wc-db
      WORDPRESS_DB_NAME: wc2
      WORDPRESS_DB_USER: ${DB_USER}
      WORDPRESS_DB_PASSWORD: ${DB_PASSWORD}
      WORDPRESS_CONFIG_EXTRA: |
        if (isset($$_SERVER['HTTP_X_FORWARDED_PROTO']) && $$_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') { $$_SERVER['HTTPS'] = 'on'; }
    volumes:
      - "wc2_html:/var/www/html"
    labels:
      - "traefik.enable=true"
      - "traefik.docker.network=${PROXY_NETWORK}"
      - "traefik.http.routers.wc2.rule=Host(`wc2.${DOMAIN_BASE}`)"
      - "traefik.http.routers.wc2.entrypoints=websecure"
      - "traefik.http.routers.wc2.tls.certresolver=le"
      - "traefik.http.services.wc2.loadbalancer.server.port=80"
      - "traefik.http.routers.wc2.middlewares=devbox-auth@docker"
      - "traefik.http.routers.wc2-rest.rule=Host(`wc2.${DOMAIN_BASE}`) && PathPrefix(`/wp-json`)"
      - "traefik.http.routers.wc2-rest.entrypoints=websecure"
      - "traefik.http.routers.wc2-rest.tls.certresolver=le"
      - "traefik.http.routers.wc2-rest.service=wc2"
      - "traefik.http.routers.wc2-rest.priority=100"
    networks:
      - web

  wc3:
    build:
      context: .
      dockerfile: Dockerfile
      args:
        WP_TAG: ${WC3_WP_TAG}
    image: woo-devbox/wc3:${WC3_WP_TAG}
    container_name: wc3
    restart: unless-stopped
    depends_on: [wc-db]
    environment:
      WORDPRESS_DB_HOST: wc-db
      WORDPRESS_DB_NAME: wc3
      WORDPRESS_DB_USER: ${DB_USER}
      WORDPRESS_DB_PASSWORD: ${DB_PASSWORD}
      WORDPRESS_CONFIG_EXTRA: |
        if (isset($$_SERVER['HTTP_X_FORWARDED_PROTO']) && $$_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') { $$_SERVER['HTTPS'] = 'on'; }
    volumes:
      - "wc3_html:/var/www/html"
    labels:
      - "traefik.enable=true"
      - "traefik.docker.network=${PROXY_NETWORK}"
      - "traefik.http.routers.wc3.rule=Host(`wc3.${DOMAIN_BASE}`)"
      - "traefik.http.routers.wc3.entrypoints=websecure"
      - "traefik.http.routers.wc3.tls.certresolver=le"
      - "traefik.http.services.wc3.loadbalancer.server.port=80"
      - "traefik.http.routers.wc3.middlewares=devbox-auth@docker"
      - "traefik.http.routers.wc3-rest.rule=Host(`wc3.${DOMAIN_BASE}`) && PathPrefix(`/wp-json`)"
      - "traefik.http.routers.wc3-rest.entrypoints=websecure"
      - "traefik.http.routers.wc3-rest.tls.certresolver=le"
      - "traefik.http.routers.wc3-rest.service=wc3"
      - "traefik.http.routers.wc3-rest.priority=100"
    networks:
      - web

volumes:
  wc_db:
  wc1_html:
  wc2_html:
  wc3_html:

networks:
  web:
    external: true
    name: ${PROXY_NETWORK:-devbox_web}
```

- [ ] **Step 3: Shellcheck `_lib.sh`**

Run: `shellcheck devbox/bin/_lib.sh`
Expected: no errors (SC1090 is suppressed inline).

- [ ] **Step 4: Validate compose renders with a throwaway env**

Run:
```bash
( cd devbox && cp .env.example .env.tmp && \
  docker compose --env-file .env.tmp -f compose.yaml config -q && \
  echo "compose OK" && rm -f .env.tmp )
```
Expected: `compose OK` and no interpolation errors. (If `docker` is unavailable locally, run this on the box in Task 7.)

- [ ] **Step 5: Commit**

```bash
git add devbox/bin/_lib.sh devbox/compose.yaml
git commit --no-gpg-sign -m "feat(devbox): shared lib and compose (3 WP instances + shared MySQL on external proxy net)"
```

---

### Task 4: Lifecycle scripts — bootstrap, up, down, logs, shell

**Files:**
- Create: `devbox/bin/bootstrap.sh`
- Create: `devbox/bin/up.sh`
- Create: `devbox/bin/down.sh`
- Create: `devbox/bin/logs.sh`
- Create: `devbox/bin/shell.sh`

**Interfaces:**
- Consumes: `_lib.sh` helpers (`load_env`, `dc`, `resolve_targets`, `is_instance`, `INSTANCES`, `PROXY_NETWORK`).
- Produces: `up.sh [all|wcN]`, `down.sh [all|wcN]`, `logs.sh [all|wcN]`, `shell.sh <wcN>`, `bootstrap.sh` (fresh-host only).

- [ ] **Step 1: Write `devbox/bin/bootstrap.sh`** (carried from Shopware; idempotent; no-op on twint-dev)

```bash
#!/usr/bin/env bash
# One-time host setup (Ubuntu/Debian). Idempotent. On twint-dev this is a no-op
# because the Shopware devbox already installed Docker.
set -euo pipefail

if ! command -v apt-get >/dev/null 2>&1; then
  echo "ERROR: bootstrap.sh targets Ubuntu/Debian (apt-based). Aborting." >&2
  exit 1
fi

if command -v docker >/dev/null 2>&1 && docker compose version >/dev/null 2>&1; then
  echo "Docker + compose already present: $(docker --version)"
  echo "Nothing to do (this box already runs the Shopware devbox)."
  exit 0
fi

echo "==> Installing prerequisites (ca-certificates, curl, git)"
sudo apt-get update -y
sudo apt-get install -y ca-certificates curl git

echo "==> Adding Docker's official apt repository"
sudo install -m 0755 -d /etc/apt/keyrings
if [ ! -f /etc/apt/keyrings/docker.asc ]; then
  sudo curl -fsSL https://download.docker.com/linux/ubuntu/gpg -o /etc/apt/keyrings/docker.asc
  sudo chmod a+r /etc/apt/keyrings/docker.asc
fi
# shellcheck disable=SC1091
. /etc/os-release
echo "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.asc] https://download.docker.com/linux/ubuntu ${VERSION_CODENAME} stable" \
  | sudo tee /etc/apt/sources.list.d/docker.list > /dev/null

echo "==> Installing Docker Engine + Compose plugin"
sudo apt-get update -y
sudo apt-get install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin

sudo systemctl enable --now docker
sudo usermod -aG docker "$USER"
echo "NOTE: log out/in (or 'newgrp docker') before running up.sh."
```

- [ ] **Step 2: Write `devbox/bin/up.sh`** (verify shared network, then bring up + build)

```bash
#!/usr/bin/env bash
# Start the stack (or one instance). Usage: up.sh [all|wc1|wc2|wc3]
# Requires the Shopware devbox's Traefik network to already exist.
set -euo pipefail
. "$(dirname "${BASH_SOURCE[0]}")/_lib.sh"
load_env

: "${PROXY_NETWORK:?set PROXY_NETWORK in .env}"
if ! docker network inspect "$PROXY_NETWORK" >/dev/null 2>&1; then
  echo "ERROR: shared proxy network '$PROXY_NETWORK' not found." >&2
  echo "       Is the Shopware devbox up? Start it first:" >&2
  echo "       (cd /home/ubuntu/twint-shopware-plugin/devbox && bin/up.sh)" >&2
  exit 1
fi

target="${1:-all}"
if [ "$target" = "all" ]; then
  dc up -d --build
else
  resolve_targets "$target"
  dc up -d --build wc-db "${RESOLVED_TARGETS[@]}"
fi

dc ps
```

- [ ] **Step 3: Write `devbox/bin/down.sh`** (never `-v`; preserve data)

```bash
#!/usr/bin/env bash
# Stop the stack (or one instance). Volumes (data) are preserved; never uses -v.
# Never touches the shared Traefik or its network. Usage: down.sh [all|wc1|wc2|wc3]
set -euo pipefail
. "$(dirname "${BASH_SOURCE[0]}")/_lib.sh"
load_env

target="${1:-all}"
if [ "$target" = "all" ]; then
  dc down
else
  resolve_targets "$target"
  dc stop "${RESOLVED_TARGETS[@]}"
  dc rm -f "${RESOLVED_TARGETS[@]}"
fi
```

- [ ] **Step 4: Write `devbox/bin/logs.sh`**

```bash
#!/usr/bin/env bash
# Follow logs for the whole stack or one instance. Usage: logs.sh [all|wc1|wc2|wc3]
set -euo pipefail
. "$(dirname "${BASH_SOURCE[0]}")/_lib.sh"
load_env

target="${1:-all}"
if [ "$target" = "all" ]; then
  dc logs -f
else
  resolve_targets "$target"
  dc logs -f "${RESOLVED_TARGETS[@]}"
fi
```

- [ ] **Step 5: Write `devbox/bin/shell.sh`**

```bash
#!/usr/bin/env bash
# Open a bash shell inside an instance. Usage: shell.sh <wc1|wc2|wc3>
set -euo pipefail
. "$(dirname "${BASH_SOURCE[0]}")/_lib.sh"
load_env

target="${1:-}"
if ! is_instance "$target"; then
  echo "Usage: shell.sh <${INSTANCES[*]}>" >&2
  exit 1
fi
dc exec "$target" bash
```

- [ ] **Step 6: Make scripts executable and shellcheck them**

Run:
```bash
chmod +x devbox/bin/*.sh
shellcheck devbox/bin/bootstrap.sh devbox/bin/up.sh devbox/bin/down.sh devbox/bin/logs.sh devbox/bin/shell.sh
```
Expected: no errors.

- [ ] **Step 7: Commit**

```bash
git add devbox/bin/bootstrap.sh devbox/bin/up.sh devbox/bin/down.sh devbox/bin/logs.sh devbox/bin/shell.sh
git commit --no-gpg-sign -m "feat(devbox): lifecycle scripts (bootstrap/up/down/logs/shell)"
```

---

### Task 5: `provision.sh` — WP-CLI install of WordPress + WooCommerce

**Files:**
- Create: `devbox/bin/provision.sh`

**Interfaces:**
- Consumes: `_lib.sh` (`wp_cli`, `resolve_targets`, `load_env`), `.env` (`DOMAIN_BASE`, `WP_ADMIN_*`, `DB_ROOT_PASSWORD`, `WC{1,2,3}_WOO_VERSION`).
- Produces: `provision.sh [all|wcN]` — idempotent; leaves each instance with WP installed at `https://wcN.$DOMAIN_BASE`, WooCommerce active at the pinned version, pretty permalinks, CH/CHF, and one demo product.

- [ ] **Step 1: Write `devbox/bin/provision.sh`**

```bash
#!/usr/bin/env bash
# One-time-per-instance provisioning (idempotent). Installs WordPress core and
# WooCommerce via WP-CLI, sets the site URL to the subdomain, and lays down a
# testable baseline (permalinks, CH/CHF, a demo product).
# Usage: provision.sh [all|wc1|wc2|wc3]
set -euo pipefail
. "$(dirname "${BASH_SOURCE[0]}")/_lib.sh"
load_env

: "${DOMAIN_BASE:?}"; : "${WP_ADMIN_USER:?}"; : "${WP_ADMIN_PASSWORD:?}"
: "${WP_ADMIN_EMAIL:?}"; : "${DB_ROOT_PASSWORD:?}"

resolve_targets "${1:-all}"
for inst in "${RESOLVED_TARGETS[@]}"; do
  url="https://${inst}.${DOMAIN_BASE}"

  echo "==> [$inst] waiting for database"
  for _ in $(seq 1 30); do
    if dc exec -T "$inst" sh -c \
      "mysqladmin ping -h\"\$WORDPRESS_DB_HOST\" -uroot -p'${DB_ROOT_PASSWORD}' --silent" \
      >/dev/null 2>&1; then break; fi
    sleep 2
  done

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

  # WooCommerce at the pinned version (empty = latest). Idempotent: install is a
  # no-op if already present at that version; --activate is always safe.
  woo_var="${inst^^}_WOO_VERSION"     # wc1 -> WC1_WOO_VERSION
  woo_ver="${!woo_var:-}"
  if [ -n "$woo_ver" ]; then
    echo "==> [$inst] installing WooCommerce $woo_ver"
    wp_cli "$inst" plugin install woocommerce --version="$woo_ver" --activate
  else
    echo "==> [$inst] installing WooCommerce (latest)"
    wp_cli "$inst" plugin install woocommerce --activate
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
```

- [ ] **Step 2: Make executable and shellcheck**

Run: `chmod +x devbox/bin/provision.sh && shellcheck devbox/bin/provision.sh`
Expected: no errors.

- [ ] **Step 3: Commit**

```bash
git add devbox/bin/provision.sh
git commit --no-gpg-sign -m "feat(devbox): provision.sh — WP-CLI install of WordPress + WooCommerce"
```

(Live verification of provisioning happens in Task 8, after the stack is up on the box.)

---

### Task 6: `deploy.sh` — build the ZIP once, install into all three

**Files:**
- Create: `devbox/bin/deploy.sh`

**Interfaces:**
- Consumes: `_lib.sh` (`load_env`, `dc`, `wp_cli`, `norm_remote`, `INSTANCES`, `DEVBOX_DIR`), `.env` (`GIT_REMOTE`, `GITLAB_USERNAME`, `GITLAB_TOKEN`), the existing `bin/archive.sh`, `devbox/build.Dockerfile`.
- Produces: `deploy.sh [branch]` — self-updates to the branch, builds `build/twint-woocommerce-extension.zip` in the build container, installs+activates it on all instances.

- [ ] **Step 1: Write `devbox/bin/deploy.sh`**

```bash
#!/usr/bin/env bash
# Build the plugin ZIP once (in a throwaway build container, via bin/archive.sh)
# and install it into ALL instances. Always deploys the branch the host clone is
# on, so the built code and the deployed code can never diverge.
#
# Usage:
#   devbox/bin/deploy.sh            # deploy the currently checked-out branch
#   devbox/bin/deploy.sh <branch>   # checkout <branch> on the host, then deploy it
#
# NOTE: the ref you deploy must itself contain devbox/ (this tooling lives in the
# plugin repo). Opt out of the git self-update with DEVBOX_NO_SELF_UPDATE=1.
set -euo pipefail
SELF_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

# First pass: bring the host clone to the ref to deploy, then re-exec once.
if [ -z "${DEVBOX_READY:-}" ] && [ -z "${DEVBOX_NO_SELF_UPDATE:-}" ] \
   && git -C "$SELF_DIR" rev-parse --git-dir >/dev/null 2>&1; then
  export GIT_TERMINAL_PROMPT=0
  if [ -n "${1:-}" ]; then
    echo "==> checking out '$1' on the host clone"
    git -C "$SELF_DIR" fetch --all --prune
    if git -C "$SELF_DIR" rev-parse --verify --quiet "origin/$1" >/dev/null; then
      git -C "$SELF_DIR" checkout -B "$1" "origin/$1"
      git -C "$SELF_DIR" reset --hard "origin/$1"
    else
      git -C "$SELF_DIR" checkout "$1"
    fi
  else
    echo "==> updating current branch (git pull --ff-only)"
    git -C "$SELF_DIR" pull --ff-only \
      || echo "WARNING: git pull failed; continuing with current checkout" >&2
  fi
  exec env DEVBOX_READY=1 "$0"
fi

. "$SELF_DIR/_lib.sh"
load_env

REPO_ROOT="$(git -C "$SELF_DIR" rev-parse --show-toplevel)"
BRANCH="$(git -C "$SELF_DIR" rev-parse --abbrev-ref HEAD)"
if [ "$BRANCH" = "HEAD" ]; then
  echo "ERROR: clone is in detached HEAD (no branch to deploy)." >&2
  echo "       Check out a branch first:  git -C $REPO_ROOT checkout <branch>" >&2
  exit 1
fi
SLUG="$(printf '%s' "$BRANCH" | tr -c 'a-zA-Z0-9' '-')"

: "${GITLAB_USERNAME:?GITLAB_USERNAME not set in .env}"
: "${GITLAB_TOKEN:?GITLAB_TOKEN not set in .env}"
GIT_NP="$(norm_remote "${GIT_REMOTE:?GIT_REMOTE not set in .env}")"
GITLAB_HOST="${GIT_NP%%/*}"

# Per-deploy log: tee everything to a timestamped file + console.
LOG_DIR="$DEVBOX_DIR/logs"; mkdir -p "$LOG_DIR"
LOG_FILE="$LOG_DIR/deploy-$(date +%Y%m%d-%H%M%S)-${SLUG}.log"
exec > >(tee -a "$LOG_FILE") 2>&1
echo "==> deploy started $(date -Is) | branch=$BRANCH | log=$LOG_FILE"

# 1) Clean local clone of the current branch (keeps the live clone pristine;
#    archive.sh mutates files + needs a real .git for `git rev-parse`).
BUILD_DIR="$DEVBOX_DIR/build"
SRC="$BUILD_DIR/src"
rm -rf "$BUILD_DIR"; mkdir -p "$BUILD_DIR"
git clone --local --no-hardlinks "$REPO_ROOT" "$SRC"
git -C "$SRC" checkout "$BRANCH"

# 2) Build the build image, then run archive.sh inside it as the host user so
#    output files are host-owned. Token is passed via env, never on argv.
echo "==> building build image"
docker build -q -f "$DEVBOX_DIR/build.Dockerfile" -t woo-devbox-build "$DEVBOX_DIR"

echo "==> building plugin ZIP in build container"
docker run --rm \
  -u "$(id -u):$(id -g)" \
  -e HOME=/tmp/bh \
  -e CI_COMMIT_REF_SLUG="$SLUG" \
  -e GITLAB_HOST="$GITLAB_HOST" \
  -e GITLAB_USERNAME="$GITLAB_USERNAME" \
  -e GITLAB_TOKEN="$GITLAB_TOKEN" \
  -v "$SRC:/app" -w /app \
  woo-devbox-build bash -lc '
    set -e
    mkdir -p /tmp/bh
    composer config --global http-basic."$GITLAB_HOST" "$GITLAB_USERNAME" "$GITLAB_TOKEN"
    bin/archive.sh
  '

ZIP="$(ls "$SRC"/build/twint-woocommerce-extension-*.zip 2>/dev/null | head -1)"
if [ -z "$ZIP" ]; then echo "ERROR: build produced no ZIP" >&2; exit 1; fi
cp "$ZIP" "$BUILD_DIR/twint-woocommerce-extension.zip"
echo "==> built $(basename "$ZIP")"

# 3) Install into every instance.
for inst in "${INSTANCES[@]}"; do
  echo "==> [$inst] copying + installing plugin ZIP"
  dc cp "$BUILD_DIR/twint-woocommerce-extension.zip" "$inst:/tmp/twint.zip"
  wp_cli "$inst" plugin install /tmp/twint.zip --force --activate
  wp_cli "$inst" plugin activate woocommerce || true
  wp_cli "$inst" rewrite flush || true
  wp_cli "$inst" cache flush || true
  echo "==> [$inst] done"
done

echo "==> all instances deployed from $BRANCH"
echo "==> deploy finished $(date -Is) | log=$LOG_FILE"
```

- [ ] **Step 2: Make executable and shellcheck**

Run: `chmod +x devbox/bin/deploy.sh && shellcheck devbox/bin/deploy.sh`
Expected: no errors.

- [ ] **Step 3: Commit**

```bash
git add devbox/bin/deploy.sh
git commit --no-gpg-sign -m "feat(devbox): deploy.sh — build ZIP in build container, install into all instances"
```

---

### Task 7: Documentation, README, and push+clone on the box

**Files:**
- Create: `devbox/README.md`, `devbox/docs/setup.md`, `devbox/docs/deploy.md`, `devbox/docs/operations.md`, `devbox/docs/dns-tls.md`, `devbox/docs/troubleshooting.md`, `devbox/docs/architecture.md`

**Interfaces:**
- Consumes: everything above (documents it).
- Produces: the operator-facing docs; and gets the branch pushed + repo cloned on the box so Task 8 can run live.

- [ ] **Step 1: Write `devbox/README.md`**

````markdown
# Woo devbox — multi-version WooCommerce on twint-dev

Runs three WordPress/WooCommerce/PHP instances concurrently behind the **Shopware
devbox's existing Traefik** (no second proxy, no port conflict):

| Instance | URL | WordPress | PHP | WooCommerce |
|----------|-----|-----------|-----|-------------|
| `wc1` | `https://wc1.$DOMAIN_BASE` | 5.9 | 8.1 | 6.0.0 |
| `wc2` | `https://wc2.$DOMAIN_BASE` | 6.6 | 8.3 | current |
| `wc3` | `https://wc3.$DOMAIN_BASE` | latest | 8.4 | latest |

The TWINT plugin is built once (as the real release ZIP) and installed into all
three, so one build validates every supported PHP.

## Quickstart (on twint-dev)

```bash
# Prereq: the Shopware devbox is up (its Traefik + devbox_web network exist).
cd /home/ubuntu/twint-woocommerce-extension/devbox
cp .env.example .env         # fill in — copy shared values from the Shopware .env
bin/up.sh                    # build + start MySQL + wc1/wc2/wc3
bin/provision.sh all         # install WordPress + WooCommerce, baseline config
bin/deploy.sh master         # build the plugin ZIP, install+activate on all three
```

## Docs

| File | Purpose |
|------|---------|
| [docs/setup.md](docs/setup.md) | First-run walkthrough + `.env` fields |
| [docs/deploy.md](docs/deploy.md) | `deploy.sh` in depth |
| [docs/operations.md](docs/operations.md) | up/down/logs/shell, data safety, version bumps |
| [docs/dns-tls.md](docs/dns-tls.md) | DNS + how TLS is handled by the shared Traefik |
| [docs/troubleshooting.md](docs/troubleshooting.md) | Symptom → cause → fix |
| [docs/architecture.md](docs/architecture.md) | Diagram + rationale |

**Data safety:** `bin/down.sh` preserves volumes. Only `docker compose ... down -v`
destroys data. The Woo stack never touches the shared Traefik.
````

- [ ] **Step 2: Write `devbox/docs/setup.md`**

````markdown
# Setup

## Prerequisites

- The **Shopware devbox is already running** on this box, which means Docker is
  installed and the `devbox_web` Traefik network exists. Verify:
  ```bash
  docker network inspect devbox_web >/dev/null && echo OK
  docker ps --filter name=devbox_proxy
  ```
  If the network is missing, start the Shopware devbox first:
  ```bash
  (cd /home/ubuntu/twint-shopware-plugin/devbox && bin/up.sh)
  ```

## Configure `.env`

```bash
cd /home/ubuntu/twint-woocommerce-extension/devbox
cp .env.example .env
```

Fill in, copying the shared values from `/home/ubuntu/twint-shopware-plugin/devbox/.env`:

| Var | Notes |
|-----|-------|
| `DOMAIN_BASE` | `twint.dev.nfq-asia.com` (same as Shopware) |
| `PROXY_NETWORK` | `devbox_web` |
| `DB_ROOT_PASSWORD`, `DB_USER`, `DB_PASSWORD` | new MySQL creds for this stack |
| `WC{1,2,3}_WP_TAG` | WordPress image tags (defaults are fine) |
| `WC{1,2,3}_WOO_VERSION` | WooCommerce version; empty = latest |
| `WP_ADMIN_USER/PASSWORD/EMAIL` | WordPress admin created by `provision.sh` |
| `GITLAB_USERNAME`, `GITLAB_TOKEN` | copy from Shopware `.env` |
| `GIT_REMOTE`, `SDK_REMOTE` | plugin + SDK repos |

## First run

```bash
bin/up.sh            # build images, start MySQL + wc1/wc2/wc3
bin/provision.sh all # WordPress core + WooCommerce + baseline
bin/deploy.sh master # build + install the TWINT plugin on all three
```

Then browse `https://wc1.$DOMAIN_BASE` (basic auth: the Shopware `BASIC_AUTH_USER`
/ `BASIC_AUTH_PASSWORD`). `wp-admin` login uses `WP_ADMIN_USER`/`WP_ADMIN_PASSWORD`.
````

- [ ] **Step 3: Write `devbox/docs/deploy.md`**

````markdown
# Deploy

`deploy.sh` builds the plugin **once** and installs it into all three instances.

```bash
bin/deploy.sh              # deploy the branch the clone is currently on
bin/deploy.sh <branch>     # checkout <branch> first, then deploy it
```

## What it does

1. **Self-update:** checks out the requested branch on the host clone (or
   `git pull --ff-only` the current one), then re-execs. Refuses a detached HEAD.
   Opt out with `DEVBOX_NO_SELF_UPDATE=1`.
2. **Build (once):** makes a clean local clone under `build/src`, builds the
   `woo-devbox-build` image, and runs `bin/archive.sh` inside it (as your host
   user). Output: `build/twint-woocommerce-extension.zip`, carrying
   `vendor` + `vendor82…85` — one ZIP for every instance's PHP. The GitLab token
   is passed via env (never on the command line) so `composer` can fetch
   `twint-ag/sdk`.
3. **Install:** copies the ZIP into each container and runs
   `wp plugin install ... --force --activate`, re-activates WooCommerce, flushes
   rewrite + cache.

Every run is logged to `logs/deploy-<timestamp>-<branch>.log` (gitignored).

## Notes

- The branch you deploy must contain `devbox/` (this tooling lives in the repo).
- Re-running is idempotent (`--force` reinstalls).
- The same branch deploys to all three instances (no per-instance refs).
````

- [ ] **Step 4: Write `devbox/docs/operations.md`**

````markdown
# Operations

## Day-to-day

```bash
bin/up.sh [all|wcN]     # start (build if needed)
bin/down.sh [all|wcN]   # stop + remove containers; KEEPS data volumes
bin/logs.sh [all|wcN]   # follow logs
bin/shell.sh wcN        # bash inside an instance
```

WP-CLI in an instance:
```bash
docker compose -f compose.yaml exec -u www-data wc1 wp --path=/var/www/html plugin list
```

## Data safety

- `bin/down.sh` **never** uses `-v`; WordPress files (in `wcN_html`) and the
  shared DB (`wc_db`) survive.
- Destroy data only deliberately:
  ```bash
  docker compose -f compose.yaml down -v   # DESTROYS all Woo volumes
  ```
- The Woo stack never touches the shared Traefik or the `devbox_web` network.

## Reset one instance

```bash
bin/down.sh wc1
docker volume rm woo-devbox_wc1_html
bin/up.sh wc1 && bin/provision.sh wc1 && bin/deploy.sh
```

## Bump a version

Edit `WCn_WP_TAG` / `WCn_WOO_VERSION` in `.env`, then recreate that instance
(resets its html volume):
```bash
bin/down.sh wc3 && docker volume rm woo-devbox_wc3_html
bin/up.sh wc3 && bin/provision.sh wc3 && bin/deploy.sh
```
````

- [ ] **Step 5: Write `devbox/docs/dns-tls.md`**

````markdown
# DNS & TLS

Both are inherited from the Shopware devbox — nothing new to configure.

## DNS

`wc1/wc2/wc3.$DOMAIN_BASE` resolve via the **same `*.$DOMAIN_BASE` wildcard
record** that already points at the box for the Shopware instances. No new record
is needed.

## TLS

The shared `devbox_proxy` Traefik terminates TLS. Its Let's Encrypt `le` resolver
(TLS-ALPN-01) issues a certificate for each `wcN.$DOMAIN_BASE` on first HTTPS
request and stores it in the Shopware `letsencrypt` volume. The Woo stack only
sets `traefik.http.routers.wcN.tls.certresolver=le` — no ACME config of its own.

WordPress is told it is behind an HTTPS proxy via `WORDPRESS_CONFIG_EXTRA`
(honours `X-Forwarded-Proto: https`) so it emits `https://` URLs and does not
redirect-loop.
````

- [ ] **Step 6: Write `devbox/docs/troubleshooting.md`**

````markdown
# Troubleshooting

| Symptom | Cause | Fix |
|---------|-------|-----|
| `up.sh` errors "shared proxy network 'devbox_web' not found" | Shopware devbox not running | `(cd /home/ubuntu/twint-shopware-plugin/devbox && bin/up.sh)` |
| 404 from Traefik for `wcN.$DOMAIN_BASE` | container not on `devbox_web`, or label typo | `docker inspect wc1 --format '{{json .NetworkSettings.Networks}}'`; check `traefik.docker.network` label = `devbox_web` |
| 502 / bad gateway | Apache not up yet, or DB not reachable | `bin/logs.sh wc1`; confirm `wc-db` healthy |
| Redirect loop / mixed content | WP siteurl http vs https | re-run `bin/provision.sh wcN` (asserts https URL); check `WORDPRESS_CONFIG_EXTRA` present |
| `deploy.sh` composer 401/403 | wrong `GITLAB_USERNAME`/`GITLAB_TOKEN` | copy the working values from the Shopware `.env` |
| `deploy.sh` "build produced no ZIP" | `archive.sh` failed in the container | read `logs/deploy-*.log`; often a composer/npm error |
| WP-CLI "Error establishing a database connection" | DB creds mismatch or first-init still running | wait; verify `.env` DB creds match what `wc-db` was created with (creds are baked on first init only) |
| Basic-auth prompt on `/wp-json` | REST bypass router missing | confirm `wcN-rest` labels in `compose.yaml` |

Reset the shared DB creds: they are set only on **first** `wc_db` init. Changing
`.env` DB creds later requires `docker volume rm woo-devbox_wc_db` (destroys all
three databases) then `up.sh` + `provision.sh all`.
````

- [ ] **Step 7: Write `devbox/docs/architecture.md`**

````markdown
# Architecture

```
                              (shared Shopware devbox proxy: devbox_proxy)
Browser ─:443→ Traefik ──┬─ sw65/sw66/sw67.$DOMAIN_BASE  → Shopware instances
   (:80 → :443)          ├─ wc1.$DOMAIN_BASE → wc1  (WP5.9 / PHP8.1 / Woo6.0)
                         ├─ wc2.$DOMAIN_BASE → wc2  (WP6.6 / PHP8.3 / Woo current)
                         └─ wc3.$DOMAIN_BASE → wc3  (WP latest / PHP8.4 / Woo latest)
                                    │
                         wc-db (MySQL 8: databases wc1, wc2, wc3)   [woo-devbox project]
```

## Why these choices

- **Share the Shopware Traefik.** The box already runs Traefik on :80/:443. A
  second proxy can't bind those. The Woo containers join the `devbox_web` network
  and carry Traefik labels; the running proxy auto-discovers them and reuses its
  `le` resolver + `devbox-auth` middleware. The Woo stack publishes no host ports.
- **Stock `wordpress` image + WP-CLI provisioning.** No baked WooCommerce; version
  is a `.env` value applied by `provision.sh`. `ext-xsl` is added (plugin
  requirement).
- **One shared MySQL, three databases.** Lighter than three DB containers; matches
  `infra/local`.
- **Build-once ZIP.** `archive.sh` emits `vendor` + `vendor82…85`, so a single
  build installs on every instance's PHP. Built in a throwaway container, so the
  host needs only Docker.
- **Full-filesystem persistence** (`wcN_html`) so WordPress + WooCommerce + the
  deployed plugin survive recreates.
````

- [ ] **Step 8: Commit docs**

```bash
git add devbox/README.md devbox/docs
git commit --no-gpg-sign -m "docs(devbox): README + operational docs"
```

- [ ] **Step 9: Push the branch and clone on the box**

Run (locally):
```bash
git push -u origin feature/devbox-multi-version-woocommerce
```
Then on the box, clone via the GitLab token (uses the same creds as Shopware). Read them from the Shopware `.env` so nothing is typed inline:
```bash
ssh twint-dev bash -lc '
  set -e
  cd /home/ubuntu
  if [ ! -d twint-woocommerce-extension ]; then
    U=$(grep -E "^GITLAB_USERNAME=" twint-shopware-plugin/devbox/.env | cut -d= -f2)
    T=$(grep -E "^GITLAB_TOKEN="    twint-shopware-plugin/devbox/.env | cut -d= -f2)
    git clone "https://$U:$T@git.nfq.asia/twint-ag/twint-woocommerce-extension.git"
  fi
  cd twint-woocommerce-extension
  git fetch --all --prune
  git checkout feature/devbox-multi-version-woocommerce
  git pull --ff-only
  echo "cloned at: $(git rev-parse --abbrev-ref HEAD)"
'
```
Expected: `cloned at: feature/devbox-multi-version-woocommerce`.

---

### Task 8: Live end-to-end verification on twint-dev

**Files:** none (verification only).

**Interfaces:** Consumes the whole stack. This is the real acceptance test.

- [ ] **Step 1: Create `.env` on the box from the Shopware shared values**

Run:
```bash
ssh twint-dev bash -lc '
  set -e
  cd /home/ubuntu/twint-woocommerce-extension/devbox
  SW=/home/ubuntu/twint-shopware-plugin/devbox/.env
  U=$(grep -E "^GITLAB_USERNAME=" "$SW" | cut -d= -f2)
  T=$(grep -E "^GITLAB_TOKEN="    "$SW" | cut -d= -f2)
  D=$(grep -E "^DOMAIN_BASE="     "$SW" | cut -d= -f2)
  cp -n .env.example .env
  sed -i "s|^DOMAIN_BASE=.*|DOMAIN_BASE=${D}|" .env
  sed -i "s|^GITLAB_USERNAME=.*|GITLAB_USERNAME=${U}|" .env
  sed -i "s|^GITLAB_TOKEN=.*|GITLAB_TOKEN=${T}|" .env
  sed -i "s|^DB_ROOT_PASSWORD=.*|DB_ROOT_PASSWORD=WooDevRoot2026|" .env
  sed -i "s|^DB_PASSWORD=.*|DB_PASSWORD=WooDevApp2026|" .env
  sed -i "s|^WP_ADMIN_PASSWORD=.*|WP_ADMIN_PASSWORD=WooAdmin2026|" .env
  sed -i "s|^WP_ADMIN_EMAIL=.*|WP_ADMIN_EMAIL=ngoctai.tran@gradion.com|" .env
  echo "--- effective (secrets masked) ---"
  grep -E "^(DOMAIN_BASE|PROXY_NETWORK|WC1_WP_TAG|WC2_WP_TAG|WC3_WP_TAG)=" .env
'
```
Expected: prints DOMAIN_BASE=twint.dev.nfq-asia.com, PROXY_NETWORK=devbox_web, the three WP tags.

- [ ] **Step 2: Bring the stack up**

Run: `ssh twint-dev 'cd /home/ubuntu/twint-woocommerce-extension/devbox && bin/up.sh 2>&1 | tail -20'`
Expected: images build; `dc ps` shows `wc-db`, `wc1`, `wc2`, `wc3` running. No port-bind errors.

- [ ] **Step 3: Provision all instances**

Run: `ssh twint-dev 'cd /home/ubuntu/twint-woocommerce-extension/devbox && bin/provision.sh all 2>&1 | tail -40'`
Expected: each instance logs "installing WordPress core", "installing WooCommerce", "provisioned". No fatal errors.

- [ ] **Step 4: Deploy the plugin to all three**

Run: `ssh twint-dev 'cd /home/ubuntu/twint-woocommerce-extension/devbox && bin/deploy.sh 2>&1 | tail -40'`
Expected: "built twint-woocommerce-extension-...zip", then per-instance "Plugin 'twint-woocommerce-extension' activated." and "all instances deployed".

- [ ] **Step 5: Assert the plugin is active on every instance**

Run:
```bash
ssh twint-dev 'cd /home/ubuntu/twint-woocommerce-extension/devbox && for i in wc1 wc2 wc3; do
  echo "== $i =="
  docker compose -f compose.yaml exec -T -u www-data $i wp --path=/var/www/html plugin get twint-woocommerce-extension --field=status
  docker compose -f compose.yaml exec -T -u www-data $i wp --path=/var/www/html plugin get woocommerce --field=status
done'
```
Expected: `active` printed twice for each of wc1/wc2/wc3.

- [ ] **Step 6: Assert HTTPS routing through the shared Traefik**

Run:
```bash
ssh twint-dev 'D=$(grep ^DOMAIN_BASE= /home/ubuntu/twint-woocommerce-extension/devbox/.env | cut -d= -f2)
for i in wc1 wc2 wc3; do
  printf "%s -> " "$i.$D"
  curl -s -o /dev/null -w "%{http_code}\n" -u twint:TwintDev2026 "https://$i.$D/wp-login.php"
done
# Shopware still works:
curl -s -o /dev/null -w "sw65 -> %{http_code}\n" -u twint:TwintDev2026 "https://sw65.$D" || true'
```
Expected: `wc1/wc2/wc3 -> 200` (valid LE cert, basic auth accepted), and the Shopware instance still responds — confirming coexistence.

- [ ] **Step 7: Assert persistence across a recreate**

Run:
```bash
ssh twint-dev 'cd /home/ubuntu/twint-woocommerce-extension/devbox
bin/down.sh wc1 >/dev/null 2>&1
bin/up.sh wc1 >/dev/null 2>&1
sleep 5
docker compose -f compose.yaml exec -T -u www-data wc1 wp --path=/var/www/html plugin get twint-woocommerce-extension --field=status'
Expected: `active` — data + plugin survived the recreate.
```

- [ ] **Step 8: Final commit / branch wrap-up**

If any script needed fixes during live verification, they were committed in their own task. Confirm a clean tree and summarize:
```bash
git status --short
git log --oneline origin/master..HEAD
```
Then follow superpowers:finishing-a-development-branch to open the MR/PR.

---

## Self-Review

**1. Spec coverage:**
- Shared-proxy architecture → Task 3 (compose external net + labels), Task 4 (`up.sh` network guard). ✓
- Host-not-fresh / bootstrap no-op → Task 4 Step 1. ✓
- 3 instances wc1/wc2/wc3 = oldest/current/latest → Task 1 (.env tags), Task 3 (services). ✓
- Stock image + WP-CLI + ext-xsl → Task 2 Dockerfile. ✓
- Shared MySQL, 3 DBs → Task 1 SQL + Task 3 `wc-db`. ✓
- provision.sh via WP-CLI (WP + Woo + baseline) → Task 5. ✓
- Build-once ZIP in build container, install via WP-CLI → Task 2 build.Dockerfile + Task 6. ✓
- Reuse `le` + `devbox-auth`, REST bypass → Task 3 labels. ✓
- Full-filesystem + shared-DB persistence, no `-v` → Task 3 volumes + Task 4 down.sh. ✓
- Credentials reused from Shopware `.env` → Task 7 Step 9 + Task 8 Step 1. ✓
- Docs set (README + 6 docs) → Task 7. ✓
- Mirror export-ignore → Task 1 `.gitattributes`. ✓ (Note: Woo `bin/sync.sh` force-pushes the full tree, so `devbox/` still reaches the GitHub mirror; acceptable — no secrets are committed. `.gitattributes` covers any future git-archive-based flow.)
- Success criteria (concurrent HTTPS load, plugin active, persistence, Shopware still works) → Task 8 Steps 5–7. ✓

**2. Placeholder scan:** No TBD/TODO; all file contents are complete. The only deliberately deferred detail is the exact set of `/wp-json` paths to bypass basic auth (spec-sanctioned), implemented as a single `/wp-json` PathPrefix router that can be tightened later.

**3. Type/name consistency:** `INSTANCES=(wc1 wc2 wc3)`, `wp_cli`, `dc`, `norm_remote`, `resolve_targets`/`RESOLVED_TARGETS`, `DEVBOX_DIR` used consistently across `_lib.sh`, `up.sh`, `provision.sh`, `deploy.sh`. Compose service names (`wc-db`, `wc1/2/3`), volume names (`wc_db`, `wcN_html`), project name (`woo-devbox`), and network (`devbox_web`) match between `compose.yaml`, docs, and verification commands. Env var names match between `.env.example`, `compose.yaml`, and scripts.
