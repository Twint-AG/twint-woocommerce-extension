# Woo devbox — multi-version WooCommerce on twint-dev

Runs three WordPress/WooCommerce/PHP instances concurrently behind the **Shopware
devbox's existing Traefik** (no second proxy, no port conflict):

| Instance | URL | WordPress | PHP | WooCommerce |
|----------|-----|-----------|-----|-------------|
| `wc1` | `https://wc1-$DOMAIN_BASE` | 5.9 | 8.1 | 6.0.0 |
| `wc2` | `https://wc2-$DOMAIN_BASE` | 6.6 | 8.3 | current |
| `wc3` | `https://wc3-$DOMAIN_BASE` | latest | 8.4 | latest |

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
