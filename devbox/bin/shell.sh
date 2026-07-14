#!/usr/bin/env bash
# Open a bash shell inside an instance. Usage: shell.sh <wc1|wc2|wc3>
set -euo pipefail
# shellcheck disable=SC1091
. "$(dirname "${BASH_SOURCE[0]}")/_lib.sh"
load_env

target="${1:-}"
if ! is_instance "$target"; then
  echo "Usage: shell.sh <${INSTANCES[*]}>" >&2
  exit 1
fi
dc exec "$target" bash
