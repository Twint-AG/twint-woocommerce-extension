# Operations

## Day-to-day

```bash
bin/up.sh [all|wcN]     # start (build if needed)
bin/down.sh [all|wcN]   # stop + remove containers; KEEPS data volumes
bin/logs.sh [all|wcN]   # follow logs
bin/shell.sh wcN        # bash inside an instance
```

WP-CLI in an instance:
```bash
docker compose -f compose.yaml exec -u www-data wc1 wp --path=/var/www/html plugin list
```

## Data safety

- `bin/down.sh` **never** uses `-v`; WordPress files (in `wcN_html`) and the
  shared DB (`wc_db`) survive.
- Destroy data only deliberately:
  ```bash
  docker compose -f compose.yaml down -v   # DESTROYS all Woo volumes
  ```
- The Woo stack never touches the shared Traefik or the `devbox_web` network.

## Reset one instance

```bash
bin/down.sh wc1
docker volume rm woo-devbox_wc1_html
bin/up.sh wc1 && bin/provision.sh wc1 && bin/deploy.sh
```

## Bump a version

Edit `WCn_WP_TAG` / `WCn_WOO_VERSION` in `.env`, then recreate that instance
(resets its html volume):
```bash
bin/down.sh wc3 && docker volume rm woo-devbox_wc3_html
bin/up.sh wc3 && bin/provision.sh wc3 && bin/deploy.sh
```
