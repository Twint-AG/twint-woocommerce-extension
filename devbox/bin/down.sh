#!/usr/bin/env bash
# Stop the stack (or one instance). Volumes (data) are preserved; never uses -v.
# Never touches the shared Traefik or its network. Usage: down.sh [all|wc1|wc2|wc3]
set -euo pipefail
# shellcheck disable=SC1091
. "$(dirname "${BASH_SOURCE[0]}")/_lib.sh"
load_env

target="${1:-all}"
if [ "$target" = "all" ]; then
  dc down
else
  resolve_targets "$target"
  dc stop "${RESOLVED_TARGETS[@]}"
  dc rm -f "${RESOLVED_TARGETS[@]}"
fi
