#!/usr/bin/env bash
#
# Regenerate the per-PHP-version Composer lockfiles (composer81.lock …
# composer85.lock) from composer.json, each resolved on a REAL PHP runtime of
# that version in Docker. Run this after changing composer.json / dependencies,
# then review and commit the updated locks.
#
# Usage:
#   infra/local/bin/relock.sh              # all versions: 8.1 8.2 8.3 8.4 8.5
#   infra/local/bin/relock.sh 8.3 8.4      # only the given versions
#
set -euo pipefail

HERE="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT="$(cd "$HERE/../../.." && pwd)"          # repo root (infra/local/bin -> repo)
ENV_FILE="$HERE/../.env"

# Composer auth for the private SDK repo (needed on dev-SDK branches; harmless
# for branches that use the packagist SDK).
GITLAB_USERNAME=""; GITLAB_TOKEN=""
# shellcheck disable=SC1090
[ -f "$ENV_FILE" ] && { set -a; . "$ENV_FILE"; set +a; }
AUTH="{\"http-basic\":{\"git.nfq.asia\":{\"username\":\"${GITLAB_USERNAME:-}\",\"password\":\"${GITLAB_TOKEN:-}\"}}}"

VERSIONS=("$@")
[ ${#VERSIONS[@]} -eq 0 ] && VERSIONS=(8.1 8.2 8.3 8.4 8.5)

cd "$ROOT"
[ -f composer.json ] || { echo "composer.json not found at $ROOT" >&2; exit 1; }

for V in "${VERSIONS[@]}"; do
  SUF="${V//./}"
  echo "== regenerating composer${SUF}.lock (PHP $V) =="
  docker run --rm \
    -v "$ROOT":/app -w /app \
    -e COMPOSER_AUTH="$AUTH" \
    "php:${V}-cli" bash -c '
      set -e
      export DEBIAN_FRONTEND=noninteractive
      apt-get update -qq >/dev/null
      apt-get install -y -qq git unzip >/dev/null
      curl -sS https://getcomposer.org/installer | php -- --quiet --install-dir=/usr/local/bin --filename=composer
      # Resolve deps for THIS PHP version and write the lock, without installing
      # vendor. Extension platform reqs are ignored (they do not affect which
      # package VERSIONS are selected — only installability — so the lock still
      # matches what a fully-extensioned runtime would resolve).
      composer update --no-install --no-interaction --no-scripts --ignore-platform-req=ext-*
    '
  cp composer.lock "composer${SUF}.lock"
  echo "   -> wrote composer${SUF}.lock"
done

rm -f composer.lock
echo
echo "Done. Review the diff and commit, e.g.:  git add composer8?.lock && git commit"
