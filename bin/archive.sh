#!/usr/bin/env bash

set -euo pipefail

ARCHIVE_PLUGIN_NAME="twint-woocommerce-extension"
ARCHIVE_BASE_NAME="twint-woocommerce-extension-${CI_COMMIT_REF_SLUG}"
ARCHIVE_BUILD_BASE_DIR="${PWD}/build"
ARCHIVE_BUILD_DIR="${ARCHIVE_BUILD_BASE_DIR}/${ARCHIVE_PLUGIN_NAME}"
ARCHIVE_PATH="${ARCHIVE_BUILD_BASE_DIR}/${ARCHIVE_BASE_NAME}.zip"

# Build frontend assets
rm -rf "${PWD}/node_modules"
npm install --quiet
rm -rf "${PWD}/dist"
npm run build

# Install composer dependencies for production
rm -rf "${PWD}/vendor"
composer install --no-dev --optimize-autoloader --prefer-dist

VERSION="${CI_COMMIT_TAG:-9.9.9-dev}"
VERSION_DISPLAY="${CI_COMMIT_TAG:-$(git rev-parse --short=6 HEAD)}"

FILES=("${PWD}/src/Constant/TwintConstant.php" "${PWD}/package.json" "${PWD}/composer.json" "${PWD}/readme.txt" )

for FILE in "${FILES[@]}"; do
  sed -i -e "s@9.9.9-dev@${VERSION}@g" "${FILE}"
done

# Replace version for plugin file, can see in plugin list
sed -i -e "s@9.9.9-dev@${VERSION_DISPLAY}@g" "${PWD}/twint-woocommerce-extension.php"

# Run PHP-Scoper
composer global require humbug/php-scoper
rm -rf "${ARCHIVE_BUILD_DIR}"
composer global exec php-scoper -- add-prefix --working-dir "${PWD}" --output-dir "${ARCHIVE_BUILD_DIR}" --quiet

# Dump autoloader for rewritten classes
composer dump-autoload --working-dir "${ARCHIVE_BUILD_DIR}" --classmap-authoritative

# Create archive
rm -f "${ARCHIVE_PATH}"
(cd "${PWD}/build" && zip -qr "${ARCHIVE_PATH}" "${ARCHIVE_PLUGIN_NAME}")
