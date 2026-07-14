# Deploy

`deploy.sh` builds the plugin **once** and installs it into all three instances.

```bash
bin/deploy.sh              # deploy the branch the clone is currently on
bin/deploy.sh <branch>     # checkout <branch> first, then deploy it
```

## What it does

1. **Self-update:** checks out the requested branch on the host clone (or
   `git pull --ff-only` the current one), then re-execs. Refuses a detached HEAD.
   Opt out with `DEVBOX_NO_SELF_UPDATE=1`.
2. **Build (once):** makes a clean local clone under `build/src`, builds the
   `woo-devbox-build` image, and runs `bin/archive.sh` inside it (as your host
   user). Output: `build/twint-woocommerce-extension.zip`, carrying
   `vendor` + `vendor82…85` — one ZIP for every instance's PHP. The GitLab token
   is passed via env (never on the command line) so `composer` can fetch
   `twint-ag/sdk`.
3. **Install:** copies the ZIP into each container and runs
   `wp plugin install ... --force --activate`, re-activates WooCommerce, flushes
   rewrite + cache.

Every run is logged to `logs/deploy-<timestamp>-<branch>.log` (gitignored).

## Notes

- The branch you deploy must contain `devbox/` (this tooling lives in the repo).
- Re-running is idempotent (`--force` reinstalls).
- The same branch deploys to all three instances (no per-instance refs).
