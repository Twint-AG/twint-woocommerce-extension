#!/usr/bin/env bash
# Start the stack (or one instance). Usage: up.sh [all|wc1|wc2|wc3]
# Requires the Shopware devbox's Traefik network to already exist.
set -euo pipefail
# shellcheck disable=SC1091
. "$(dirname "${BASH_SOURCE[0]}")/_lib.sh"
load_env

: "${PROXY_NETWORK:?set PROXY_NETWORK in .env}"
if ! docker network inspect "$PROXY_NETWORK" >/dev/null 2>&1; then
  echo "ERROR: shared proxy network '$PROXY_NETWORK' not found." >&2
  echo "       Is the Shopware devbox up? Start it first:" >&2
  echo "       (cd /home/ubuntu/twint-shopware-plugin/devbox && bin/up.sh)" >&2
  exit 1
fi

target="${1:-all}"
if [ "$target" = "all" ]; then
  dc up -d --build
else
  resolve_targets "$target"
  dc up -d --build wc-db "${RESOLVED_TARGETS[@]}"
fi

dc ps
