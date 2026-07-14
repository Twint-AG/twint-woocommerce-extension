# Devbox: Multi-version WooCommerce dev box — Design

**Date:** 2026-07-14
**Status:** Approved — ready for implementation planning

**Topic:** Run three WooCommerce / WordPress / PHP version combinations concurrently
on the EC2 dev box (`ssh twint-dev`) via Docker Compose, **behind the Traefik
proxy that the Shopware devbox already runs** (HTTPS + Let's Encrypt + basic
auth), with persistent data and a one-command plugin deploy that builds the
plugin at the branch the devbox clone is currently on and installs it into all
three instances.

This mirrors the Shopware devbox
(`../../../sw-plugin/docs/superpowers/specs/2026-07-14-devbox-multi-version-shopware-design.md`),
adapted to the realities of a WordPress plugin **and to sharing a host that
already runs the Shopware devbox**.

## Host is NOT fresh — coexistence with the Shopware devbox

The EC2 box already runs the Shopware devbox, which means:

- **Docker Engine + Compose are already installed** (Shopware's `bootstrap.sh`
  has run). Bootstrap is therefore a no-op here; the Woo box's `bin/bootstrap.sh`
  stays present and idempotent for a truly fresh host but is not part of the
  normal path.
- **A Traefik proxy already owns `:80`, `:443`, and `127.0.0.1:8080`.** A second
  Traefik cannot bind those ports. So the Woo stack **runs no proxy of its own**:
  it attaches its containers to the Shopware Traefik's Docker network and adds
  Traefik labels, and the running Traefik auto-discovers and routes them. This
  reuses the existing Let's Encrypt `le` resolver and the `devbox-auth`
  basic-auth middleware — one proxy serves both stacks, no port conflict, clean
  URLs on standard `:443`.
- **Prerequisite:** the Shopware devbox (its Traefik + network) must be up before
  the Woo stack starts. `up.sh` verifies the shared network exists and fails with
  a clear message otherwise.

### Confirmed deployment facts (from the live `twint-dev` box, 2026-07-14)

Inspected on the running box; these pin the defaults above:

| Fact | Value |
|------|-------|
| Shopware devbox path | `/home/ubuntu/twint-shopware-plugin` (devbox at `.../devbox`) |
| Shared Traefik container | `devbox_proxy` (`traefik:v3.7`), publishing `:80`, `:443`, `127.0.0.1:8080` |
| Shared Docker network | `devbox_web` → `PROXY_NETWORK` default confirmed |
| Basic-auth middleware | `devbox-auth` (reference as `devbox-auth@docker`), user `twint` |
| `DOMAIN_BASE` | `twint.dev.nfq-asia.com` (wildcard `*.` already resolves → box); Woo uses `wc1/wc2/wc3.twint.dev.nfq-asia.com` |
| Let's Encrypt resolver | `le`, `ACME_EMAIL=ngoctai.tran@gradion.com` |
| GitLab | host `git.nfq.asia`, user `taitran`, SDK `git.nfq.asia/twint-ag/sdk.git` |

**Credentials are reused, not re-issued.** The Woo `.env` copies `GITLAB_TOKEN`,
`GITLAB_USERNAME`, basic-auth creds, and `DOMAIN_BASE` from
`/home/ubuntu/twint-shopware-plugin/devbox/.env`. Secrets live only in that
gitignored `.env`; none are committed. The Woo repo is expected at
`/home/ubuntu/twint-woocommerce-extension` on the box.

## Problem

The `twint-woocommerce-extension` must be validated against multiple WordPress +
WooCommerce + PHP combinations. The existing `infra/` setup (`oldest`, `latest`,
`wc10`, `local`) bakes the plugin into per-version images and each instance
hard-codes host port `80`, so **only one version can run at a time**. We want all
three running simultaneously on a shared EC2 dev box, reachable in a browser over
HTTPS, with data that survives container recreates, and a one-command plugin
deploy.

## Key differences from the Shopware devbox

The Shopware box could `composer require twint-ag/twint-shopware-plugin:dev-<branch>`
inside each container because the plugin is a Composer package installed into the
app's `vendor/`. A WooCommerce plugin instead lives as files under
`wp-content/plugins/` and is installed/activated through WordPress. So:

- **Delivery = build ZIP, then install.** `deploy.sh` builds the release ZIP
  (via the existing `bin/archive.sh`) and installs it into each instance with
  `wp plugin install <zip> --force --activate`. The ZIP carries per-PHP-version
  vendors (`vendor`, `vendor82`…`vendor85`), so **one build installs cleanly into
  all three instances** regardless of their PHP version — preserving the Shopware
  box's "one deployed ref for all instances" property.
- **Build happens in a throwaway build container**, not on the host, so the EC2
  box stays Docker-only (matches the Shopware `bootstrap.sh` philosophy).
- **WordPress + WooCommerce are provisioned via WP-CLI** at first run, not baked
  into images. Images are stock `wordpress` + `wp-cli` + required PHP extensions.

## Decisions (locked)

| Topic | Decision |
|-------|----------|
| Proxy | **No proxy in the Woo stack.** Reuses the Shopware devbox's running **Traefik v3.7**; Woo containers join its Docker network and carry Traefik labels for auto-discovery |
| Proxy network | Woo containers attach to the external network `${PROXY_NETWORK:-devbox_web}` (the Shopware Traefik's network) |
| Access/routing | Subdomains `wc1.*`, `wc2.*`, `wc3.*` routed by the shared Traefik on `:443` (`:80`→`:443` redirect already configured there) |
| TLS | Reuses the shared Traefik's Let's Encrypt `le` resolver (TLS-ALPN-01); no separate cert config or volume in the Woo stack |
| Access control | Reuses the shared Traefik's `devbox-auth` basic-auth middleware (same credentials as Shopware); no htpasswd generated by the Woo box |
| DNS | `*.<domain>` A record → EC2 IP (Route53 or equivalent); `DOMAIN_BASE` in `.env` |
| Instances | 3 generic names `wc1` / `wc2` / `wc3` = **oldest / current / latest** |
| Base image | One shared parameterized Dockerfile: `FROM wordpress:${WP_TAG}-apache`, adds `wp-cli` + `ext-xsl` (+ soap). Apache serves `:80` internally |
| Versions | Per-instance `.env` vars for WP image tag + WooCommerce version. Defaults below; `wc3` is meant to track latest WP + Woo |
| Database | **One shared `mysql:8` container**, three databases `wc1`/`wc2`/`wc3`, one persisted volume |
| Plugin delivery | **Build the release ZIP once in a build container, then `wp plugin install --force --activate`** into each instance |
| Build environment | Throwaway build container (php 8.1 + node + composer + php-scoper) runs `bin/archive.sh`; host stays Docker-only |
| Deployed ref | The branch the devbox clone is currently on → applied to all three instances |
| Persistence | Per-instance full-filesystem volume (`wcN_html → /var/www/html`) + shared `mysql_data` volume (certs persist in the Shopware Traefik's existing `letsencrypt` volume) |
| GitLab auth | HTTPS + token (`GITLAB_USERNAME` / `GITLAB_TOKEN` in `.env`) for `git.nfq.asia`, incl. the private `twint-ag/sdk` |
| Location | New top-level `devbox/` folder; legacy `infra/` untouched |
| Mirror | `devbox/` excluded from the public GitHub mirror (`.gitattributes export-ignore` + `bin/sync.sh` scrub list) |

### Default version matrix (`.env`-configurable)

| Instance | Role | WordPress image | PHP | WooCommerce |
|----------|------|-----------------|-----|-------------|
| `wc1` | oldest (min support boundary) | `wordpress:5.9-php8.1-apache` | 8.1 | 6.0.0 |
| `wc2` | current stable | `wordpress:6.6-php8.3-apache` | 8.3 | 9.x (latest 9) |
| `wc3` | latest | `wordpress:latest` (php 8.4) | 8.4 | latest |

These are defaults in `.env.example`; `wc3` is intended to be bumped to whatever
WordPress and WooCommerce ship latest. Bumping a version means changing the tag
in `.env` and recreating that instance (its html volume is reset — documented in
`operations.md`).

## Architecture

The Woo Docker Compose project brings up **three WordPress instances + one shared
MySQL** and attaches the WordPress containers to the **existing Shopware
Traefik's network**. The already-running Traefik (from the Shopware stack)
discovers them via the Docker provider and routes by `Host` header to each
instance's internal Apache port 80:

```
                              (shared Shopware devbox proxy)
   ┌─────────────── sw65.$DOMAIN_BASE / sw66 / sw67  → Shopware instances
Browser ─:443→ Traefik ───┼─ Host(wc1.$DOMAIN_BASE) → wc1  (oldest:  WP5.9 / PHP8.1 / Woo6.0)
   (:80 → :443)           ├─ Host(wc2.$DOMAIN_BASE) → wc2  (current: WP6.x / PHP8.3 / Woo9.x)
                          └─ Host(wc3.$DOMAIN_BASE) → wc3  (latest:  WP latest / PHP8.4 / Woo latest)
                                     │
                          shared mysql  (databases: wc1, wc2, wc3)   [Woo stack]
```

- **The Woo stack publishes no host ports at all.** The WordPress and MySQL
  containers are reached only through the shared Traefik / Docker network. Traefik
  keeps owning `:80`/`:443`/`8080` for both stacks — no second binding, no
  conflict.
- The Woo `web` network is declared `external` and points at
  `${PROXY_NETWORK:-devbox_web}` (the Shopware Traefik's network), so Traefik can
  reach the `wcN` containers to proxy them.
- Hostnames are parameterized by `DOMAIN_BASE` in `.env` (the same base domain as
  the Shopware box). Each `wcN.$DOMAIN_BASE` resolves via the same `*.<domain>`
  DNS record → EC2 IP.

## Host bootstrap

On the shared `twint-dev` box, **bootstrap is already done** by the Shopware
devbox (Docker Engine + Compose present, user in the `docker` group). The Woo
box's `bin/bootstrap.sh` is carried over from Shopware and kept idempotent for a
truly fresh host, but on this box it is a no-op and not part of the normal path.

The host needs **only Docker** — no PHP, Node, or Composer, because the plugin
ZIP is built inside a container. The one live prerequisite is that the **Shopware
devbox is up** (its Traefik + `${PROXY_NETWORK}` network must exist before the Woo
stack starts); `up.sh` checks this.

## Directory layout

```
devbox/
  compose.yaml          # wc1 + wc2 + wc3 + mysql; joins the external Shopware Traefik network (no proxy service)
  Dockerfile            # shared, parameterized by WP_TAG: wordpress:${WP_TAG}-apache + wp-cli + ext-xsl (+ soap)
  .env                  # DOMAIN_BASE, PROXY_NETWORK, per-instance WP tags + Woo versions, DB creds,
                        #   GITLAB_USERNAME/GITLAB_TOKEN, WP admin creds (gitignored, host-specific)
  .env.example          # committed template
  .gitignore            # ignores .env, logs/, build/
  mysql-init/
    create-databases.sql # creates wc1/wc2/wc3 databases on first mysql init
  logs/                 # per-deploy logs (gitignored)
  build/                # ZIP build output (gitignored)
  bin/
    bootstrap.sh        # carried from Shopware; idempotent Docker install for a fresh host (no-op on twint-dev)
    up.sh               # verify shared proxy network exists + docker compose up -d  [whole stack | one instance]
    down.sh             # docker compose down  [whole stack | one instance]
    provision.sh        # one-time per instance: wp core install + install/activate WooCommerce + baseline config
    deploy.sh           # self-update to branch -> build ZIP in build container -> wp plugin install --force into all
    logs.sh             # tail logs for an instance
    shell.sh            # exec a shell into an instance
    _lib.sh             # shared helpers (instance list, env loader, dc wrapper, wp-cli wrapper, guards)
  README.md             # entry point: what devbox is, quickstart, links to docs/
  docs/
    setup.md            # first-run walkthrough on the shared box (prereqs, .env, up/provision/deploy)
    deploy.md           # deploy.sh usage: branch selection, the build + install steps, examples
    operations.md       # day-to-day: up/down/logs/shell, persistence & data safety, version bumps
    dns-tls.md          # DNS wildcard + how TLS is handled by the shared Shopware Traefik
    troubleshooting.md  # common failures (site URL, proxy 404/502, missing shared network, token, volumes, WP-CLI)
    architecture.md     # the diagram + why (shared proxy, images, volumes, build-once ZIP) for maintainers
```

The legacy `infra/` tree is not modified. `.env`, `logs/`, and `build/` are
gitignored (host-specific / generated).

## Containers & images

### Shared proxy (from the Shopware devbox — not part of this stack)

The Woo stack defines **no proxy service**. It relies on the Traefik the Shopware
devbox already runs, which provides:

- entrypoints `web` (`:80`) and `websecure` (`:443`), with `web`→`websecure`
  redirect;
- the Let's Encrypt `le` resolver (TLS-ALPN-01), certs in the Shopware
  `letsencrypt` volume;
- the `devbox-auth` basic-auth middleware (defined on the Shopware services);
- the Docker provider watching the `${PROXY_NETWORK:-devbox_web}` network and the
  dashboard on `127.0.0.1:8080`.

The Woo `wcN` services join that network and add their own router labels; Traefik
picks them up automatically. Basic-auth and TLS come from referencing the
existing `devbox-auth` middleware and `le` resolver by name.

### wc1 / wc2 / wc3 (WordPress + Apache)

Each `wcN` service:

- **Build**: the shared `devbox/Dockerfile` with build arg `WP_TAG=${WCN_WP_TAG}`
  → `FROM wordpress:${WP_TAG}-apache`, plus `wp-cli`, `ext-xsl`, and soap. Apache
  serves internally on port 80.
- **No published host ports.** Attached only to the external
  `${PROXY_NETWORK:-devbox_web}` network so the shared Traefik can reach it.
- **Traefik labels** (mirroring the Shopware pattern, referencing the shared
  proxy's resolver + middleware):
  - `traefik.enable=true`
  - `traefik.docker.network=${PROXY_NETWORK}` (tell Traefik which network to use,
    since the container is on a network it doesn't otherwise own).
  - router `Host(\`wcN.${DOMAIN_BASE}\`)` on `websecure`,
    `tls.certresolver=le`, middleware `devbox-auth@docker`, service port `80`.
  - a higher-priority `wcN-rest` router for `PathPrefix(\`/wp-json\`)` (the
    WooCommerce Store/REST API authenticates itself) that bypasses basic auth —
    the analog of Shopware's `/api`+`/store-api` bypass. (Finalized at
    implementation time based on which paths need to skip basic auth for
    payment/webhook callbacks.)
- **Environment**: `WORDPRESS_DB_HOST=mysql`, `WORDPRESS_DB_NAME=wcN`,
  `WORDPRESS_DB_USER`/`WORDPRESS_DB_PASSWORD` from `.env`,
  `WORDPRESS_CONFIG_EXTRA` to trust the proxy's `X-Forwarded-Proto` so WordPress
  emits `https://` URLs behind Traefik.
- **Volume**: `wcN_html:/var/www/html` (full filesystem persistence).
- `depends_on: mysql`.

### mysql (shared)

- `mysql:8`, root + app creds from `.env`, no published host ports. On the
  external `${PROXY_NETWORK}` network so the `wcN` containers can reach it by
  service name (it does not need Traefik labels).
- `mysql-init/create-databases.sql` mounted into
  `/docker-entrypoint-initdb.d/` creates the `wc1`/`wc2`/`wc3` databases on first
  init.
- Volume `mysql_data:/var/lib/mysql`.

## Persistence

- `wcN_html → /var/www/html` — full filesystem per instance. WordPress install
  state, the WooCommerce plugin, uploaded media, and the deployed TWINT plugin all
  survive `down`/`up`. Caveat: bumping a `WP_TAG` requires recreating the instance
  and resetting its html volume (documented in `operations.md`).
- `mysql_data → /var/lib/mysql` — shared, holds all three databases.
- TLS certs are **not** a Woo-stack volume — they persist in the Shopware
  Traefik's existing `letsencrypt` volume.

`docker compose down` (without `-v`) preserves the Woo volumes. Data is lost only
on explicit `down -v` / volume prune — documented in the README/operations. The
Woo `down` never touches the shared Traefik or its network.

## `provision.sh <wc1|wc2|wc3|all>` — one-time per instance (WP-CLI, idempotent)

Run once per instance after its first `up`:

1. Wait for the DB to be reachable.
2. `wp core install --url=https://wcN.${DOMAIN_BASE} --title=... --admin_user=...
   --admin_password=... --admin_email=...` (creds from `.env`). Skipped if WP is
   already installed.
3. `wp plugin install woocommerce --version=${WCN_WOO_VERSION} --activate`.
4. Baseline config so checkout is testable: pretty permalinks, store base country
   `CH`, currency `CHF`, and a demo product.
5. Re-running just re-asserts settings (idempotent).

All `wp` commands run inside the instance container as the web user via a `wp-cli`
wrapper in `_lib.sh`.

## `deploy.sh [branch]` — build ZIP once, install into all three

Signature: `deploy.sh [<branch>]`. Deploys the branch the host clone is on;
passing a branch checks it out first. Mirrors the Shopware `deploy.sh` control
flow.

Steps:

1. **Self-update** (first pass, opt-out via `DEVBOX_NO_SELF_UPDATE=1`):
   - with a branch arg → `git fetch` + checkout that branch (reset to origin for a
     branch; detached for a tag/commit);
   - without → `git pull --ff-only` the current branch;
   - then re-exec once with `DEVBOX_READY=1`.
   - Refuse to deploy from a detached HEAD (as the Shopware script does).
   - NOTE: the deployed ref must itself contain `devbox/` (this tooling lives in
     the plugin repo), same caveat as the Shopware box.
2. **Build the ZIP once** in a throwaway **build container**
   (`php:8.1-cli` + node + composer + php-scoper, or an equivalent prebuilt build
   image), with the repo bind-mounted and `git.nfq.asia` composer `http-basic`
   auth configured from `.env`:
   - run `bin/archive.sh` → produces `build/twint-woocommerce-extension.zip`
     carrying `vendor` + `vendor82`…`vendor85`.
   - `deploy.sh` sets `CI_COMMIT_REF_SLUG` (from the branch name) before running
     `archive.sh`, since the script references it unguarded under `set -u`;
     `CI_COMMIT_TAG` is left unset so the version stays `0.0.1-dev`. `deploy.sh`
     normalizes the produced `twint-woocommerce-extension-<slug>.zip` to a stable
     path before installing.
3. **Install into each instance** via WP-CLI:
   - `wp plugin install /path/to/twint-woocommerce-extension.zip --force --activate`
   - ensure WooCommerce is active (`wp plugin activate woocommerce`)
   - `wp cache flush` + rewrite flush.
4. **Per-deploy log**: tee the whole run to a timestamped
   `devbox/logs/deploy-<ts>-<branch>.log` (gitignored), like the Shopware box.

`deploy.sh` applies the same branch to all three instances (no per-instance
refs).

## GitLab access

- `.env` holds `GITLAB_USERNAME` and `GITLAB_TOKEN` (read access to `git.nfq.asia`,
  incl. the private `twint-ag/sdk`).
- The build container runs
  `composer config --global http-basic.git.nfq.asia $GITLAB_USERNAME $GITLAB_TOKEN`
  before `bin/archive.sh`'s composer steps. The token is passed as an argument,
  never echoed by the script.
- `.env` is gitignored; `.env.example` documents both variables with placeholders.

## Usage flow

```
# Prerequisite: the Shopware devbox is already up on this box (Docker installed,
# Traefik running, ${PROXY_NETWORK} network present).

# on the EC2 box, one time:
git clone <this-repo> && cd <repo>/devbox
cp .env.example .env                  # set DOMAIN_BASE, PROXY_NETWORK, WP admin creds,
                                      #   DB creds, version tags, GITLAB_USERNAME/GITLAB_TOKEN
bin/up.sh                             # verify shared proxy network + start mysql + all three instances
bin/provision.sh all                  # wp core install + install/activate WooCommerce + baseline config
bin/deploy.sh master                  # build the ZIP once, install+activate the plugin on all three

# DNS: wc*.<domain> already resolve via the same *.<domain> record the Shopware box uses.

# iterate — deploy any branch to all three:
bin/deploy.sh feature/express-checkout
bin/deploy.sh                         # redeploy the current branch
```

## DNS + TLS

- The `wc*.<domain>` subdomains resolve via the **same `*.<domain>` wildcard
  record the Shopware box already uses** — no new DNS needed. `DOMAIN_BASE=<domain>`
  in `.env`.
- TLS is handled entirely by the **shared Shopware Traefik**: its `le` resolver
  (TLS-ALPN-01) issues a per-host cert for each `wcN.<domain>` on first request
  and persists it in the existing `letsencrypt` volume. The Woo stack configures
  nothing TLS-related beyond the `tls.certresolver=le` label.
- Documented in `docs/dns-tls.md`.

## Documentation (deliverable, in Markdown)

Committed alongside the code, written as part of implementation (docs and code
land together). Same structure and conventions as the Shopware box.

| File | Audience / purpose |
|------|--------------------|
| `devbox/README.md` | One-paragraph what/why, quickstart commands, table linking to each `docs/*.md`. |
| `devbox/docs/setup.md` | First-run path on the shared box: prereqs (Shopware devbox up), `.env` fields, first `up`/`provision`/`deploy`. |
| `devbox/docs/deploy.md` | `deploy.sh [branch]` in depth: branch selection, build container, ZIP install, examples. |
| `devbox/docs/operations.md` | Day-to-day: `up/down/logs/shell`, one instance vs all, data-safety, version bumps. |
| `devbox/docs/dns-tls.md` | How `wc*` reuse the shared wildcard DNS + how TLS is issued by the shared Traefik. |
| `devbox/docs/troubleshooting.md` | Symptom → cause → fix (wrong site URL, proxy 404/502, missing/renamed `${PROXY_NETWORK}`, token/permission errors, empty/stale volumes, WP-CLI failures). |
| `devbox/docs/architecture.md` | The routing diagram + rationale (why share the Shopware Traefik, why stock wordpress + WP-CLI provisioning, why shared MySQL, why build-once ZIP). |

Doc conventions: relative links between docs, fenced copy-paste-runnable command
blocks, no secrets in examples (tokens shown as `$GITLAB_TOKEN`).

## Non-goals / YAGNI

- No live-mount of the developer's working tree — deploys always come from a
  pushed branch built into a ZIP.
- No CI integration — legacy `infra/ci/` remains the CI story.
- No per-instance independent refs — one branch deploys to all three.
- No automatic WordPress/WooCommerce version bumping — version pins are manual
  `.env` edits + an instance recreate.
- No per-instance MySQL — one shared MySQL with three databases.
- No proxy in the Woo stack — it reuses the Shopware devbox's Traefik. Running the
  Woo box on a host without the Shopware devbox is out of scope (would need the
  second-Traefik variant).

## Success criteria

- `devbox/bin/up.sh` brings up shared MySQL + all three instances on the existing
  Shopware Traefik network with **no new host-port bindings** (the shared Traefik
  keeps owning `:80`/`:443`), and fails clearly if that network is absent.
- `https://wc1.<domain>`, `https://wc2.<domain>`, `https://wc3.<domain>` each load
  their respective WordPress + WooCommerce storefront and admin from one browser,
  concurrently, over HTTPS with a valid Let's Encrypt cert, behind basic auth —
  **alongside the still-working `sw65/66/67` Shopware instances on the same
  proxy**.
- `provision.sh all` installs and activates the correct WooCommerce version on
  each instance and leaves a testable store (permalinks, CHF, a product).
- `deploy.sh <branch>` builds the plugin ZIP once in a build container and
  installs + activates it on all three instances; the TWINT gateway appears and is
  active on each.
- Re-running `deploy.sh` on already-deployed instances is idempotent
  (`--force` reinstall).
- `down.sh` then `up.sh` preserves each instance's WordPress data, WooCommerce
  config, and uploads, plus the shared DB.
- Switching `DOMAIN_BASE` changes the URLs with no other edits (DNS wildcard is
  already in place).
- A new maintainer can go from "Shopware devbox already running" to three running,
  plugin-deployed Woo instances using only `devbox/README.md` + `devbox/docs/*.md`.
