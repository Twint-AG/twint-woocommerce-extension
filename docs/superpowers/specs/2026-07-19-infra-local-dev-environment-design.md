# Local Dev Environment Rework (`infra/local/`)

**Date:** 2026-07-19
**Branch:** `chore/infra-local-rework` (off `master`)
**Status:** Design — pending review

## Context

`infra/` is the Docker-based local environment for developing and testing the
TWINT WooCommerce plugin. It has decayed:

- **16,282 files tracked in git**, of which ~16,250 are committed WordPress core,
  WooCommerce, and vendor directories (`wp-content` 11,210, `wp-includes` 3,875,
  `wp-admin` 1,135) spread across five overlapping variants: `local/`, `latest/`,
  `oldest/`, `wc10/`, `wordpress/`.
- Only ~26 files are the actual useful Docker bits (compose, Dockerfiles,
  entrypoints, nginx/apache confs).
- The working pattern worth keeping: the current `infra/local/compose.yaml`
  live-mounts the plugin source into the container and uses a named `vendor`
  volume.

The base images are already the official `wordpress:6.7.2-php8.4` and
`wordpress:5.9-php8.1` — the bloat comes from `COPY . /var/www/html` layering
committed core on top, plus mounting committed `wp-content`.

## Goal

One clean, documented local environment that boots to a ready-to-test
WordPress + WooCommerce + TWINT-plugin site with two PHP/WP versions, live plugin
mounting, and in-container builds — with the committed WP-core bloat removed.

## Requirements

- Delete the committed WordPress/WooCommerce/vendor files from git (~16,250 files).
- Single canonical env under `infra/local/`; remove the other four variants.
- Two instances: WP-latest and WP-5.9 (minimum supported).
- Live-mount the plugin source so edits are immediate.
- Build `vendor/` (composer, incl. SDK `dev-dev/v9`) and `dist/` (JS) **in-container**.
- Auto-provision on `up`: install WordPress, install+activate WooCommerce, activate
  the TWINT plugin, flush rewrite rules. Idempotent.
- TWINT credentials entered manually via `wp-admin` (secrets stay out of the repo).
- No `*.wordpress.local` hosts file editing — publish ports directly.

## Decisions (from brainstorming)

| Question | Decision |
|----------|----------|
| WP/Woo source | Official images + wp-cli; delete committed core |
| Versions | Two: latest + oldest (WP 5.9 / PHP 8.1) |
| Build strategy | Everything in-container (composer + npm) |
| Provisioning | Auto-provision; TWINT creds manual via wp-admin |
| Routing | Published ports (no nginx proxy / hosts entries) |

## Architecture

```
infra/local/
  compose.yaml
  .env.example
  README.md
  docker/
    Dockerfile        # ARG BASE_IMAGE; + xsl, soap, intl, node20, composer, wp-cli
    entrypoint.sh     # in-container build + wp-cli provisioning (idempotent)
    custom.ini        # php dev ini (upload size, memory, etc.)
```

### Services (`compose.yaml`)

- `wc_latest` — built from `docker/Dockerfile` with `BASE_IMAGE=wordpress:latest`
  (php 8.x apache). Published on **`localhost:8081`**.
- `wc_oldest` — built with `BASE_IMAGE=wordpress:5.9-php8.1`. Published on
  **`localhost:8082`**.
- `mysql` — one `mysql:8` server; databases `wc_latest` and `wc_oldest`;
  credentials from `.env`; data in a named volume.

The nginx proxy and the per-instance apache site confs
(`proxy.conf`/`latest.conf`/`oldest.conf`) are removed — each WordPress
(apache) container publishes its port directly.

### Image (`docker/Dockerfile`)

```
ARG BASE_IMAGE
FROM ${BASE_IMAGE}
```
Adds: `git`, `libxslt-dev`, `libxml2-dev`, `libicu-dev`; PHP extensions
`xsl`, `soap`, `intl`; Node 20 + npm; Composer; wp-cli. Copies `custom.ini`.
No `COPY . /var/www/html` (that was the bloat vector).

### Volumes & mounts (per instance)

- `../../ → /var/www/html/wp-content/plugins/twint-woocommerce-extension`
  (bind mount — live plugin source).
- Named volume over `.../twint-woocommerce-extension/vendor` and over
  `.../node_modules` — so in-container builds don't fight the host tree
  (and the host's IDE tooling doesn't need them either way).
- Named volume for each instance's `wp-content` (persists WooCommerce + uploads
  across restarts), with the plugin dir bind-mounted over it.
- Named volume for MySQL data.

### Entrypoint (`docker/entrypoint.sh`, idempotent)

On container start, in order:
1. **Build** (once, guarded by a marker file): `composer install` (needs
   `GITLAB_TOKEN` for the SDK VCS repo) and `npm ci && npm run build` inside the
   plugin dir.
2. **Wait** for MySQL to accept connections.
3. **Provision** (guarded by `wp core is-installed`):
   `wp core install` (URL = the instance's `localhost:PORT`, title/admin from
   `.env`) → `wp plugin install woocommerce --version=$WOO_VERSION --activate` →
   `wp plugin activate twint-woocommerce-extension` → `wp rewrite flush`
   (needed for the EC `twint-finalize-payment` route).
4. Hand off to the base image's apache foreground command.

Re-running `up` is a no-op past the guards; a fresh build is forced by removing
the marker (documented in the README).

### `.env.example`

`DB_ROOT_PASSWORD`, `DB_USER`, `DB_PASSWORD`, `WP_ADMIN_USER`,
`WP_ADMIN_PASSWORD`, `WP_ADMIN_EMAIL`, `WC_LATEST_WOO_VERSION` (empty = latest),
`WC_OLDEST_WOO_VERSION`, `GITLAB_USERNAME`, `GITLAB_TOKEN`. The real `.env` is
gitignored.

### TWINT credentials (manual)

After `up`, the developer enters Store UUID + `.p12` + password via
`wp-admin → TWINT → Credentials`. The README documents this plus the EC test
scenarios (happy path, processing→PAID race, concurrency, cron/stale-lock
reclaim, "I have paid" fallback) and the log greps to watch
(source tag `twint-woocommerce-extension`).

## Cleanup / git changes

- `git rm -r` the committed core: `infra/latest`, `infra/oldest`, `infra/wc10`,
  `infra/wordpress`, and `infra/local/woocommerce/`.
- Rewrite `infra/local/` to the structure above.
- Extend `.gitignore` to prevent re-committing WP core / WooCommerce / vendor /
  node_modules under `infra/`.

## Out of scope

- The `devbox/` (twint-dev multi-version deploy) — untouched.
- Automated TWINT credential injection (chosen manual).
- CI integration / automated E2E — this is a local dev env only.
- A third (mid) version — the twint-dev devbox covers the full matrix.

## Verification plan

- `docker compose config` parses; `up.sh` boots both instances.
- Both sites reachable: `http://localhost:8081` (latest), `http://localhost:8082`
  (oldest); `wp plugin list` shows woocommerce + twint active on each.
- Plugin edits on the host reflect in the container without rebuild.
- `git status` after cleanup shows ~16k deletions and a small tracked infra tree.
- README's EC test scenarios runnable once TWINT test credentials are entered.
