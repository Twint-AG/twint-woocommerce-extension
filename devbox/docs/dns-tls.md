# DNS & TLS

Both are inherited from the Shopware devbox — nothing new to configure.

## DNS

Instance hosts are **hyphen-joined**, matching the Shopware devbox convention
(`sw65-$DOMAIN_BASE`): `wc1-$DOMAIN_BASE`, `wc2-$DOMAIN_BASE`, `wc3-$DOMAIN_BASE`
(e.g. `wc1-twint.dev.nfq-asia.com`). Each is a single label under the parent zone,
so it is covered by the **same wildcard that already points the `sw*-` hosts at
the box** (e.g. `*.dev.nfq-asia.com` when `DOMAIN_BASE=twint.dev.nfq-asia.com`).
No new record is needed.

> Note the hyphen: `wc1-$DOMAIN_BASE` resolves to the devbox; the dot form
> `wc1.$DOMAIN_BASE` is a different name under `*.$DOMAIN_BASE` and points
> elsewhere (a separate host pool) — using it yields 404s.

## TLS

The shared `devbox_proxy` Traefik terminates TLS. Its Let's Encrypt `le` resolver
(TLS-ALPN-01) issues a certificate for each `wcN-$DOMAIN_BASE` on first HTTPS
request and stores it in the Shopware `letsencrypt` volume. The Woo stack only
sets `traefik.http.routers.wcN.tls.certresolver=le` — no ACME config of its own.

WordPress is told it is behind an HTTPS proxy via `WORDPRESS_CONFIG_EXTRA`
(honours `X-Forwarded-Proto: https`) so it emits `https://` URLs and does not
redirect-loop.
