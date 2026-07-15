#!/usr/bin/env bash

set -euo pipefail

RELEASE_HOST=github.com
RELEASE_REPOSITORY=git@${RELEASE_HOST}:Twint-AG/twint-woocommerce-extension.git

RELEASE_BOT_NAME="TWINT Release Bot"
RELEASE_BOT_EMAIL="plugin@twint.ch"

# Internal deployment tooling that must never reach the public GitHub mirror.
# `git push` ignores .gitattributes export-ignore (that only affects `git
# archive`), so these paths are stripped from the pushed tree with a scrub
# commit before pushing. (infra/ is intentionally NOT excluded — it is already
# published on the public mirror.)
EXCLUDE_PATHS=(devbox docs/superpowers)

echo "Syncing release ${CI_COMMIT_TAG}"
mkdir -p ~/.ssh
chmod 400 "${TWINT_GITHUB_DEPLOY_KEY}"
ssh-keyscan "${RELEASE_HOST}" >> ~/.ssh/known_hosts

# Strip excluded paths from the index (--ignore-unmatch keeps this a no-op for
# paths absent at this commit), then record a scrub commit so BOTH the 'latest'
# branch and the tag published to GitHub omit them.
git rm -r --cached --quiet --ignore-unmatch "${EXCLUDE_PATHS[@]}"
if ! git diff --cached --quiet; then
  git -c user.name="${RELEASE_BOT_NAME}" -c user.email="${RELEASE_BOT_EMAIL}" \
      commit --no-gpg-sign --quiet \
      -m "chore(sync): strip internal deployment tooling from public mirror"
fi

# Point the release tag at the scrubbed commit so the published tag matches the
# 'latest' branch. This only rewrites the tag in this CI checkout; the origin
# (GitLab) tag is untouched.
git -c user.name="${RELEASE_BOT_NAME}" -c user.email="${RELEASE_BOT_EMAIL}" \
    tag -f -a "${CI_COMMIT_TAG}" -m "release ${CI_COMMIT_TAG}" --no-sign

GIT_SSH_COMMAND="ssh -i ${TWINT_GITHUB_DEPLOY_KEY}" \
  git push --force "${RELEASE_REPOSITORY}" \
    HEAD:refs/heads/latest "${CI_COMMIT_TAG}:${CI_COMMIT_TAG}"

mv build/twint-woocommerce-extension-*.zip build/twint-woocommerce-extension.zip
gh release create \
  --repo "${RELEASE_REPOSITORY}" \
  "${CI_COMMIT_TAG}" \
  --title "${CI_COMMIT_TAG}" \
  --verify-tag \
  --notes "Release ${CI_COMMIT_TAG}" \
  "build/twint-woocommerce-extension.zip#twint-woocommerce-extension-${CI_COMMIT_TAG}.zip"
