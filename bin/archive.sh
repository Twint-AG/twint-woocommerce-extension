#!/usr/bin/env bash

set -euo pipefail

ARCHIVE_BASE_NAME="twint-woocommerce-extension-${CI_COMMIT_REF_SLUG}"
ARCHIVE_BUILD_BASE_DIR="${PWD}/build"
ARCHIVE_BUILD_DIR="${ARCHIVE_BUILD_BASE_DIR}/${ARCHIVE_BASE_NAME}"
ARCHIVE_PATH="${ARCHIVE_BUILD_BASE_DIR}/${ARCHIVE_BASE_NAME}.zip"

# Build frontend assets
rm -rf "${PWD}/node_modules"
npm install --quiet
rm -rf "${PWD}/dist"
npm run build

# Install composer dependencies for production
rm -rf "${PWD}/vendor"
composer install --no-dev --optimize-autoloader --prefer-dist

# Run PHP-Scoper
composer global require humbug/php-scoper
rm -rf "${ARCHIVE_BUILD_DIR}"
composer global exec php-scoper -- add-prefix --working-dir "${PWD}" --output-dir "${ARCHIVE_BUILD_DIR}" --quiet

# Dump autoloader for rewritten classes
composer dump-autoload --working-dir "${ARCHIVE_BUILD_DIR}" --classmap-authoritative

# Create archive
rm -f "${ARCHIVE_PATH}"
(cd "${PWD}/build" && zip -qr "${ARCHIVE_PATH}" "${ARCHIVE_BASE_NAME}")
