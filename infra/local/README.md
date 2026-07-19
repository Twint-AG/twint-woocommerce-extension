# Local dev environment (`infra/local/`)

Two WordPress + WooCommerce instances with the TWINT plugin **live-mounted**,
built and provisioned automatically inside Docker. Use it to develop and test the
plugin (including the Express Checkout hosted-payment flow) locally.

| Instance | URL | WordPress | PHP |
|----------|-----|-----------|-----|
| `wc_latest` | http://localhost:8081 | latest | 8.3 |
| `wc_oldest` | http://localhost:8082 | 5.9 (min supported) | 8.1 |

> `wc_latest` uses PHP **8.3**, the highest that satisfies both the master stable
> SDK (deps cap at 8.3) and the `feat/hosted-payment` dev SDK (deps need ≥ 8.3).

MySQL is exposed on `localhost:3366`.

## Prerequisites

- Docker + Docker Compose.
- **VPN + `GITLAB_TOKEN`** only if the checked-out branch pins the private dev SDK
  (`twint-ag/sdk:dev-dev/v9` from `git.nfq.asia`, e.g. `feat/hosted-payment`).
  Branches using the stable SDK from packagist (e.g. `master`) need neither.
- TWINT **test** credentials (Store UUID + `.p12` certificate + password) to
  actually exercise a payment.

## Quickstart

```bash
cd infra/local
cp .env.example .env        # fill in GITLAB_USERNAME / GITLAB_TOKEN (SDK access)
docker compose up -d --build
```

On first boot, each instance builds its **own** `vendor/` (with its own PHP) +
`node_modules/` + `dist/`, then auto-installs WordPress, installs + activates
WooCommerce, activates the TWINT plugin, and flushes rewrites. Apache comes up
immediately; the build + provisioning run in the background (watch the logs).

Then open:
- Storefront / admin: http://localhost:8081 and http://localhost:8082
- `wp-admin` login: `WP_ADMIN_USER` / `WP_ADMIN_PASSWORD` from `.env` (default `admin`/`admin`)

The store is provisioned ready for TWINT: **currency CHF**, country Switzerland
(`CH:ZH`), the setup wizard skipped, and the bundled WooCommerce **sample
products** imported (only when the catalog is empty).

## Enter TWINT credentials (manual)

`wp-admin → TWINT → Credentials`: enter the Store UUID, upload the `.p12`, enter
the password, Save. Wait for *"Your certificate is successfully validated"*. Then
enable TWINT Checkout and TWINT Express Checkout under their settings tabs.
(Credentials are per-instance; secrets are never committed.)

## Everyday use

- **Edit plugin PHP** → reflected immediately (source is bind-mounted).
- **Edit plugin JS/SCSS** → rebuild assets: `docker compose restart wc_latest`
  (the entrypoint re-runs the build). Or run `npm run build` on the host if you
  have Node.
- **wp-cli**: `docker compose exec wc_latest wp --allow-root <cmd>`
- **Logs**: `docker compose logs -f wc_latest` — or in `wp-admin → WooCommerce →
  Status → Logs` (source `twint-woocommerce-extension`).
- **Stop** (keeps data): `docker compose down`
- **Reset everything** (drops DBs + built deps): `docker compose down -v`
- **Force a clean rebuild of one instance's vendor**: `docker compose down`, then
  `docker volume rm twint-local_vendor_latest`, then `docker compose up -d`.

## Testing Express Checkout (the hosted-payment flow)

TWINT redirects the popup back to `http://localhost:PORT/...` in your browser, and
the server polls TWINT outbound — so localhost works without a tunnel.

Watch the logs (source `twint-woocommerce-extension`) while testing:

1. **Happy path** — click the express button, pick address/shipping + pay in the
   TWINT test app. Expect exactly **one** `startFastCheckoutOrder`, the order
   marked paid once, cart emptied, redirect to the order-received page.
2. **Processing → PAID race** — let the popup redirect back while the poller is
   still finishing. The finalize page should **spin, not show a failure**, then
   redirect. (Confirms the `IN_PROGRESS`-during-race fix.)
3. **Concurrency** — expect an `ordering already in progress` log line and still
   only one `startFastCheckoutOrder`.
4. **Cron backstop / stale-lock reclaim** — close the browser mid-flow; the
   per-minute cron should complete it. Trigger manually:
   `docker compose exec wc_latest wp --allow-root cron event run --due-now`.
   Watch for `reclaimed N stale ordering lock(s)`. Also note how long
   `startFastCheckoutOrder` + `monitorPairing` take vs the 300s lock TTL.
5. **"I have paid"** — complete payment but block the auto-redirect, click the
   button, confirm it finalizes without a duplicate charge.

## Working across branches

This env is branch-agnostic: it live-mounts the plugin working tree, so it builds
and runs whatever branch you have checked out. `composer install` and the webpack
build run against that branch's `composer.json` / `package.json` — including a
branch that pins a dev SDK (e.g. `dev-dev/v9` from `git.nfq.asia`, which needs
`GITLAB_TOKEN` in `.env` + VPN).

After **switching branches**, rebuild each instance's deps so `vendor/` and
`dist/` match the new branch (they live in per-instance Docker volumes and would
otherwise be stale):

```bash
git switch <branch>
docker compose restart wc_latest wc_oldest   # entrypoint re-runs composer + webpack per instance
```

The entrypoint always re-runs `composer install` and the webpack build; `npm ci`
re-runs only when `package-lock.json` changed. For a completely clean slate
(new DBs + fresh deps): `docker compose down -v && docker compose up -d --build`.

**PHP-version caveat:** each instance builds with its own PHP, so a branch whose
locked deps require a different PHP than an instance provides will fail to build
*there* (by design — it surfaces the real supported range). Example:
`feat/hosted-payment` pins a dev SDK whose deps need **PHP ≥ 8.3**, so it builds
on `wc_latest` (8.3) but **not** on `wc_oldest` (8.1) — test that feature on
`wc_latest`. When a build fails, that instance still runs WordPress/WooCommerce;
the TWINT plugin is simply left **deactivated** (no fatal), so the site stays up.

## Changing dependencies / PHP versions

**Test another PHP (or WP) version** — set the base image in `.env` and rebuild:

```bash
# .env
WC_LATEST_IMAGE=wordpress:php8.2      # default wordpress:php8.3
WC_OLDEST_IMAGE=wordpress:5.9-php8.1
```
```bash
docker compose up -d --build wc_latest
```

**After changing `composer.json`** — regenerate the per-PHP lockfiles the repo
ships (`composer81.lock` … `composer85.lock`) and commit them:

```bash
infra/local/bin/relock.sh            # all versions 8.1–8.5
infra/local/bin/relock.sh 8.3 8.4    # only specific versions
git add composer8?.lock && git commit
```

Each lock is resolved on a **real PHP runtime of that version** in Docker (so the
right per-PHP deps are picked, e.g. `psl-compat` 1.x on ≤8.2 vs 2.x on ≥8.3).
Private-SDK auth comes from `.env`; extension platform reqs are ignored during
resolution (they don't change which versions are selected).

## Notes

- WordPress core and WooCommerce are **not** committed — they come from the
  official image + wp-cli. `infra/.gitignore` blocks re-committing core/deps.
- Each instance builds its **own** `vendor/` with its **own** PHP (a shared
  vendor cannot be correct across two PHP versions).
- This folder is self-contained and independent of `devbox/` (the twint-dev
  deploy). It can be merged to `master`; other branches then pick it up on rebase.
