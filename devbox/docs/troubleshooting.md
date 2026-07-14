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
| wcN routers 503 / no cert | shared Traefik's `le` resolver or `devbox-auth` middleware name changed on the Shopware side | verify the Shopware devbox still defines resolver `le` and middleware `devbox-auth`; check `docker logs devbox_proxy` |

Reset the shared DB creds: they are set only on **first** `wc_db` init. Changing
`.env` DB creds later requires `docker volume rm woo-devbox_wc_db` (destroys all
three databases) then `up.sh` + `provision.sh all`.
