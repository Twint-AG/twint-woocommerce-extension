#!/usr/bin/env bash
# Shared helpers for Woo devbox scripts.
# Source it:  . "$(dirname "${BASH_SOURCE[0]}")/_lib.sh"
set -euo pipefail

DEVBOX_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
ENV_FILE="$DEVBOX_DIR/.env"

INSTANCES=(wc1 wc2 wc3)

# Load and export every var from devbox/.env.
load_env() {
  if [ ! -f "$ENV_FILE" ]; then
    echo "ERROR: $ENV_FILE not found. Copy .env.example to .env and fill it in." >&2
    exit 1
  fi
  set -a
  # shellcheck disable=SC1090
  . "$ENV_FILE"
  set +a
}

# True if $1 is a known instance name.
is_instance() {
  local x="${1:-}" i
  for i in "${INSTANCES[@]}"; do [ "$i" = "$x" ] && return 0; done
  return 1
}

# Validate a target ("all" or one instance) and populate RESOLVED_TARGETS in the
# CALLER's shell. Exits non-zero on invalid/missing input. Call directly, then
# read "${RESOLVED_TARGETS[@]}". Do NOT wrap in $(...) — a subshell swallows exit.
# shellcheck disable=SC2034
resolve_targets() {
  local target="${1:-}"
  RESOLVED_TARGETS=()
  if [ -z "$target" ]; then
    echo "ERROR: missing instance (one of: ${INSTANCES[*]} all)" >&2; exit 1
  fi
  if [ "$target" = "all" ]; then RESOLVED_TARGETS=("${INSTANCES[@]}"); return 0; fi
  if is_instance "$target"; then RESOLVED_TARGETS=("$target"); return 0; fi
  echo "ERROR: unknown instance '$target' (expected: ${INSTANCES[*]} all)" >&2; exit 1
}

# docker compose pinned to the Woo devbox project.
dc() {
  docker compose --project-directory "$DEVBOX_DIR" -f "$DEVBOX_DIR/compose.yaml" "$@"
}

# Run WP-CLI inside an instance as the web user.
wp_cli() {
  local inst="$1"; shift
  dc exec -T -u www-data "$inst" wp --path=/var/www/html "$@"
}

# Normalize a remote to scheme-less host/path. Accepts:
#   git@host:group/repo.git | host/group/repo.git | https://host/group/repo.git
norm_remote() {
  local r="$1"
  r="${r#https://}"; r="${r#http://}"; r="${r#git@}"; r="${r/://}"
  printf '%s' "$r"
}
