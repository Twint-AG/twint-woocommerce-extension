#!/usr/bin/env bash
# Build the plugin ZIP once (in a throwaway build container, via bin/archive.sh)
# and install it into ALL instances. Always deploys the branch the host clone is
# on, so the built code and the deployed code can never diverge.
#
# Usage:
#   devbox/bin/deploy.sh            # deploy the currently checked-out branch
#   devbox/bin/deploy.sh <branch>   # checkout <branch> on the host, then deploy it
#
# NOTE: the ref you deploy must itself contain devbox/ (this tooling lives in the
# plugin repo). Opt out of the git self-update with DEVBOX_NO_SELF_UPDATE=1.
set -euo pipefail
SELF_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

# First pass: bring the host clone to the ref to deploy, then re-exec once.
if [ -z "${DEVBOX_READY:-}" ] && [ -z "${DEVBOX_NO_SELF_UPDATE:-}" ] \
   && git -C "$SELF_DIR" rev-parse --git-dir >/dev/null 2>&1; then
  export GIT_TERMINAL_PROMPT=0
  if [ -n "${1:-}" ]; then
    echo "==> checking out '$1' on the host clone"
    git -C "$SELF_DIR" fetch --all --prune
    if git -C "$SELF_DIR" rev-parse --verify --quiet "origin/$1" >/dev/null; then
      git -C "$SELF_DIR" checkout -B "$1" "origin/$1"
      git -C "$SELF_DIR" reset --hard "origin/$1"
    else
      git -C "$SELF_DIR" checkout "$1"
    fi
  else
    echo "==> updating current branch (git pull --ff-only)"
    git -C "$SELF_DIR" pull --ff-only \
      || echo "WARNING: git pull failed; continuing with current checkout" >&2
  fi
  exec env DEVBOX_READY=1 "$0"
fi

# shellcheck disable=SC1091
. "$SELF_DIR/_lib.sh"
load_env

REPO_ROOT="$(git -C "$SELF_DIR" rev-parse --show-toplevel)"
BRANCH="$(git -C "$SELF_DIR" rev-parse --abbrev-ref HEAD)"
if [ "$BRANCH" = "HEAD" ]; then
  echo "ERROR: clone is in detached HEAD (no branch to deploy)." >&2
  echo "       Check out a branch first:  git -C $REPO_ROOT checkout <branch>" >&2
  exit 1
fi
SLUG="$(printf '%s' "$BRANCH" | tr -c 'a-zA-Z0-9' '-')"

: "${GITLAB_USERNAME:?GITLAB_USERNAME not set in .env}"
: "${GITLAB_TOKEN:?GITLAB_TOKEN not set in .env}"
GIT_NP="$(norm_remote "${GIT_REMOTE:?GIT_REMOTE not set in .env}")"
GITLAB_HOST="${GIT_NP%%/*}"

# Per-deploy log: tee everything to a timestamped file + console.
LOG_DIR="$DEVBOX_DIR/logs"; mkdir -p "$LOG_DIR"
LOG_FILE="$LOG_DIR/deploy-$(date +%Y%m%d-%H%M%S)-${SLUG}.log"
exec > >(tee -a "$LOG_FILE") 2>&1
echo "==> deploy started $(date -Is) | branch=$BRANCH | log=$LOG_FILE"

# 1) Clean local clone of the current branch (keeps the live clone pristine;
#    archive.sh mutates files + needs a real .git for `git rev-parse`).
BUILD_DIR="$DEVBOX_DIR/build"
SRC="$BUILD_DIR/src"
rm -rf "$BUILD_DIR"; mkdir -p "$BUILD_DIR"
git clone --local --no-hardlinks "$REPO_ROOT" "$SRC"
git -C "$SRC" checkout "$BRANCH"

# 2) Build the build image, then run archive.sh inside it as the host user so
#    output files are host-owned. Token is passed via env, never on argv.
echo "==> building build image"
docker build -q -f "$DEVBOX_DIR/build.Dockerfile" -t woo-devbox-build "$DEVBOX_DIR"

# Cap the build container's resources. archive.sh (npm + composer x5 + php-scoper
# x5) is heavy; on this shared box (Shopware devbox + 3 WordPress, 15G RAM, NO
# swap) an unbounded build starves the host and takes SSH/Traefik down. Leave one
# CPU and a memory ceiling for everything else. Overridable via .env.
BUILD_MEM="${BUILD_MEM:-5g}"
BUILD_CPUS="${BUILD_CPUS:-$(nproc --ignore=1 2>/dev/null || echo 2)}"
echo "==> building plugin ZIP in build container (mem=$BUILD_MEM cpus=$BUILD_CPUS)"
# Run as root, exactly like GitLab CI's build-archive job. Under a non-root UID,
# archive.sh's php-scoper step silently drops psl function files (e.g.
# Iter/apply.php) from the scoped vendor -> Fatal "Failed opening required
# .../Psl/Iter/apply.php" at plugin load. After the build, chown the output back
# to the invoking user so the next run's `rm -rf build/` (run as that user) works.
docker run --rm \
  --memory="$BUILD_MEM" --memory-swap="$BUILD_MEM" --cpus="$BUILD_CPUS" \
  -e HOME=/root -e COMPOSER_ALLOW_SUPERUSER=1 \
  -e HOST_UID="$(id -u)" -e HOST_GID="$(id -g)" \
  -e CI_COMMIT_REF_SLUG="$SLUG" \
  -e GITLAB_HOST="$GITLAB_HOST" \
  -e GITLAB_USERNAME \
  -e GITLAB_TOKEN \
  -v "$SRC:/app" -w /app \
  woo-devbox-build bash -lc '
    set -e
    # /app is a bind-mount owned by the host user, but we run as root — tell git
    # to trust it so archive.sh (git rev-parse for the version) does not abort
    # with "detected dubious ownership".
    git config --global --add safe.directory /app
    composer config --global http-basic."$GITLAB_HOST" "$GITLAB_USERNAME" "$GITLAB_TOKEN"
    bin/archive.sh
    chown -R "$HOST_UID:$HOST_GID" /app
  '

# shellcheck disable=SC2012
ZIP="$(ls "$SRC"/build/twint-woocommerce-extension-*.zip 2>/dev/null | head -1)"
if [ -z "$ZIP" ]; then echo "ERROR: build produced no ZIP" >&2; exit 1; fi
cp "$ZIP" "$BUILD_DIR/twint-woocommerce-extension.zip"
echo "==> built $(basename "$ZIP")"

# 3) Install into every instance. A failure on one instance must not skip the
#    others — the point of this tool is validating ALL three PHP versions in
#    one run.
failed=()
for inst in "${INSTANCES[@]}"; do
  echo "==> [$inst] copying + installing plugin ZIP"
  if dc cp "$BUILD_DIR/twint-woocommerce-extension.zip" "$inst:/tmp/twint.zip" \
     && wp_cli "$inst" plugin install /tmp/twint.zip --force --activate; then
    wp_cli "$inst" plugin activate woocommerce || true
    wp_cli "$inst" rewrite flush || true
    wp_cli "$inst" cache flush || true
    echo "==> [$inst] done"
  else
    echo "ERROR: [$inst] plugin install failed" >&2
    failed+=("$inst")
  fi
done

if [ "${#failed[@]}" -gt 0 ]; then
  echo "==> deploy finished WITH FAILURES on: ${failed[*]} | log=$LOG_FILE" >&2
  exit 1
fi
echo "==> all instances deployed from $BRANCH"
echo "==> deploy finished $(date -Is) | log=$LOG_FILE"
