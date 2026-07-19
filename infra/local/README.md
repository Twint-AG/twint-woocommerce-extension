# Local dev environment (`infra/local/`)

Two WordPress + WooCommerce instances with the TWINT plugin **live-mounted**,
built and provisioned automatically inside Docker. Use it to develop and test the
plugin (including the Express Checkout hosted-payment flow) locally.

| Instance | URL | WordPress | PHP |
|----------|-----|-----------|-----|
| `wc_latest` | http://localhost:8081 | latest | 8.4 |
| `wc_oldest` | http://localhost:8082 | 5.9 (min supported) | 8.1 |

MySQL is exposed on `localhost:3366`.

## Prerequisites

- Docker + Docker Compose.
- **VPN access to `git.nfq.asia`** — the build pulls the TWINT SDK
  (`twint-ag/sdk:dev-dev/v9`) via Composer.
- TWINT **test** credentials (Store UUID + `.p12` certificate + password) to
  actually exercise a payment.

## Quickstart

```bash
cd infra/local
cp .env.example .env        # fill in GITLAB_USERNAME / GITLAB_TOKEN (SDK access)
docker compose up -d --build
```

First boot order: `mysql` (healthcheck) → `builder` (runs `composer install` +
`npm run build` once, into a shared `vendor` volume and the mounted `dist/`) →
`wc_latest` / `wc_oldest`, which auto-install WordPress, install + activate
WooCommerce, activate the TWINT plugin, and flush rewrites.

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
- **Edit plugin JS/SCSS** → rebuild assets: `docker compose run --rm builder`
  (rewrites `dist/`). Or run `npm run build` on the host if you have Node.
- **wp-cli**: `docker compose exec wc_latest wp --allow-root <cmd>`
- **Logs**: `docker compose logs -f wc_latest` — or in `wp-admin → WooCommerce →
  Status → Logs` (source `twint-woocommerce-extension`).
- **Stop** (keeps data): `docker compose down`
- **Reset everything** (drops DBs + built deps): `docker compose down -v`
- **Force a clean rebuild of vendor**: `docker compose down`, then
  `docker volume rm twint-local_vendor`, then `docker compose up -d --build`.

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

## Notes

- WordPress core and WooCommerce are **not** committed — they come from the
  official image + wp-cli. `infra/.gitignore` blocks re-committing core/deps.
- The `builder` runs on PHP 8.1 (the floor) so resolved Composer deps work on
  both instances.
