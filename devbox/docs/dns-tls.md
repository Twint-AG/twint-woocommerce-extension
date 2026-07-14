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
