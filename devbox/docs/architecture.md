# Architecture

```
                              (shared Shopware devbox proxy: devbox_proxy)
Browser ─:443→ Traefik ──┬─ sw65/sw66/sw67.$DOMAIN_BASE  → Shopware instances
   (:80 → :443)          ├─ wc1-$DOMAIN_BASE → wc1  (WP5.9 / PHP8.1 / Woo6.0)
                         ├─ wc2-$DOMAIN_BASE → wc2  (WP6.6 / PHP8.3 / Woo current)
                         └─ wc3-$DOMAIN_BASE → wc3  (WP latest / PHP8.4 / Woo latest)
                                    │
                         wc-db (MySQL 8: databases wc1, wc2, wc3)   [woo-devbox project]
```
Shared network: `devbox_web`; proxy container: `devbox_proxy`.

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
