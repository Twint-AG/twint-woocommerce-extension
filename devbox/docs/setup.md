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
