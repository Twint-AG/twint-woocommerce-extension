#!/usr/bin/env bash

set -euo pipefail

RELEASE_HOST=github.com
RELEASE_REPOSITORY=git@${RELEASE_HOST}:Twint-AG/twint-woocommerce-extension.git

echo "Syncing release ${CI_COMMIT_TAG}"
mkdir -p ~/.ssh
chmod 400 "${TWINT_GITHUB_DEPLOY_KEY}"
ssh-keyscan ${RELEASE_HOST} >> ~/.ssh/known_hosts
GIT_SSH_COMMAND="ssh -i ${TWINT_GITHUB_DEPLOY_KEY}" git push --force "${RELEASE_REPOSITORY}" HEAD:refs/heads/latest "${CI_COMMIT_TAG}:${CI_COMMIT_TAG}"

gh release create \
  --repo "${RELEASE_REPOSITORY}" \
  "${CI_COMMIT_TAG}" \
  --title "${CI_COMMIT_TAG}" \
  --verify-tag \
  --notes "Release ${CI_COMMIT_TAG}" \
  "build/twint-woocommerce-extension-${CI_COMMIT_TAG}.zip"
