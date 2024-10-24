#!/usr/bin/env bash

set -euo pipefail

VERSION="$1"
RELEASE_BOT_NAME="TWINT Release Bot"
RELEASE_BOT_EMAIL="plugin@twint.ch"

# We are on the correct branch
test "`git rev-parse --abbrev-ref HEAD`" == "master"

# There are no pending changes
git diff --exit-code
git diff --exit-code --cached

FILES=("${PWD}/src/Constant/TwintConstant.php" "${PWD}/package.json")

for FILE in "${FILES[@]}"; do
  sed -e "s@9.9.9-dev@${VERSION}@g" -i "${FILE}"
done

export GIT_COMMITTER_NAME="${RELEASE_BOT_NAME}"
export GIT_COMMITTER_EMAIL="${RELEASE_BOT_EMAIL}"
export GIT_AUTHOR_NAME="${RELEASE_BOT_NAME}"
export GIT_AUTHOR_EMAIL="${RELEASE_BOT_EMAIL}"

git commit -m "Prepare release of ${VERSION}" "${FILES[@]}"
git tag --no-sign -a "${VERSION}" -m "Tag ${VERSION}"

# Reset release preparation commit
git reset --hard HEAD^

# Push tag
git push origin "${VERSION}"
