#!/usr/bin/env bash
# Follow logs for the whole stack or one instance. Usage: logs.sh [all|wc1|wc2|wc3]
set -euo pipefail
# shellcheck disable=SC1091
. "$(dirname "${BASH_SOURCE[0]}")/_lib.sh"
load_env

target="${1:-all}"
if [ "$target" = "all" ]; then
  dc logs -f
else
  resolve_targets "$target"
  dc logs -f "${RESOLVED_TARGETS[@]}"
fi
