#!/usr/bin/env bash
#
# Run the GitLab CI jobs locally, in Docker, using the SAME image + spc as CI
# (shivammathur/node:jammy) — so you can verify `tests` (parallel PHP 8.1–8.5)
# and `build-archive` without the flaky shared runners.
#
# Usage:
#   infra/ci/local-ci.sh tests               # all matrix versions, in parallel
#   infra/ci/local-ci.sh tests 8.3           # only PHP 8.3
#   infra/ci/local-ci.sh archive             # build-archive job (PHP 8.5) -> zip
#   infra/ci/local-ci.sh all                 # tests then archive
#
# Notes:
#   - Each job copies the repo into a container-local workdir (excluding
#     .git/vendor/node_modules/dist/build), so parallel jobs never clobber each
#     other's composer.lock/vendor and your working tree is untouched.
#   - Running 5 versions in parallel is heavy (each does composer install +
#     phpstan). Pass specific versions if your machine struggles.
#   - Private-SDK auth: GITLAB_TOKEN from infra/local/.env (maps to CI's
#     GITLAB_ACCESS_TOKEN).
#
set -uo pipefail

HERE="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT="$(cd "$HERE/../.." && pwd)"
LOGS="$HERE/logs"
ART="$HERE/artifacts"
IMAGE="shivammathur/node:jammy"
ALL_VERSIONS=(8.1 8.2 8.3 8.4 8.5)

TESTS_EXT="bcmath, ctype, curl, dom, fileinfo, filter, gd, hash, iconv, intl, json, libxml, mbstring, openssl, pcre, pdo_mysql, simplexml, soap, sockets, sodium, xsl, tokenizer, xmlwriter, zlib"
ARCHIVE_EXT="mbstring, curl, dom, fileinfo, gd, iconv, intl, json, xml, pdo, phar, zip, sodium, pdo_mysql, bcmath, soap"

# GITLAB_TOKEN from infra/local/.env (for the private SDK).
GITLAB_TOKEN=""
# shellcheck disable=SC1091
[ -f "$ROOT/infra/local/.env" ] && { set -a; . "$ROOT/infra/local/.env"; set +a; }

mkdir -p "$LOGS" "$ART"

# Copy the repo into /work excluding heavy dirs — keeps each job isolated.
COPY_SRC='mkdir -p /work && tar cf - -C /src --exclude=./.git --exclude=./vendor --exclude=./node_modules --exclude=./dist --exclude=./build . | tar xf - -C /work && cd /work'

tests_script() {
  cat <<EOF
set -euo pipefail
$COPY_SRC
spc -U
spc --php-version "\$PHP_VERSION" --extensions "$TESTS_EXT"
PHP_SUFFIX="\${PHP_VERSION//./}"
cp "composer\${PHP_SUFFIX}.lock" composer.lock
[ -n "\${GITLAB_TOKEN:-}" ] && composer config --global gitlab-token.git.nfq.asia "\$GITLAB_TOKEN"
composer install --no-progress --no-interaction
vendor/bin/ecs --no-progress-bar
vendor/bin/rector process --dry-run
vendor/bin/phpstan analyse --memory-limit=2048M
npm install --no-audit --no-fund
npx prettier --check ./resources/
EOF
}

archive_script() {
  cat <<EOF
set -euo pipefail
$COPY_SRC
apt-get update -qq && apt-get install -y -qq zip >/dev/null
spc -U
spc --php-version 8.5 --extensions "$ARCHIVE_EXT"
[ -n "\${GITLAB_TOKEN:-}" ] && composer config --global gitlab-token.git.nfq.asia "\$GITLAB_TOKEN"
export CI_COMMIT_REF_SLUG="\${CI_COMMIT_REF_SLUG:-local}"
./bin/archive.sh
cp build/twint-woocommerce-extension-*.zip /artifacts/ 2>/dev/null || true
ls -la /artifacts/
EOF
}

run_one_test() {
  local v="$1"
  docker run --rm \
    -e PHP_VERSION="$v" -e GITLAB_TOKEN="$GITLAB_TOKEN" \
    -v "$ROOT":/src:ro \
    "$IMAGE" bash -c "$(tests_script)" >"$LOGS/tests-$v.log" 2>&1
}

cmd_tests() {
  local versions=("$@")
  [ ${#versions[@]} -eq 0 ] && versions=("${ALL_VERSIONS[@]}")
  echo "== tests: ${versions[*]} (logs in infra/ci/logs/) =="
  local pids=() vers=() rc=0
  for v in "${versions[@]}"; do
    run_one_test "$v" &
    pids+=("$!"); vers+=("$v")
  done
  for i in "${!pids[@]}"; do
    if wait "${pids[$i]}"; then
      echo "  PASS  tests ${vers[$i]}"
    else
      echo "  FAIL  tests ${vers[$i]}  -> infra/ci/logs/tests-${vers[$i]}.log"; rc=1
    fi
  done
  return $rc
}

cmd_archive() {
  echo "== build-archive (PHP 8.5) -> infra/ci/artifacts/ =="
  if docker run --rm \
      -e GITLAB_TOKEN="$GITLAB_TOKEN" \
      -v "$ROOT":/src:ro -v "$ART":/artifacts \
      "$IMAGE" bash -c "$(archive_script)" >"$LOGS/archive.log" 2>&1; then
    echo "  PASS  archive -> $(ls "$ART"/*.zip 2>/dev/null | tail -1)"
  else
    echo "  FAIL  archive -> infra/ci/logs/archive.log"; return 1
  fi
}

case "${1:-all}" in
  tests)   shift; cmd_tests "$@" ;;
  archive) cmd_archive ;;
  all)     cmd_tests && cmd_archive ;;
  *) echo "usage: local-ci.sh {tests [versions...]|archive|all}" >&2; exit 2 ;;
esac
