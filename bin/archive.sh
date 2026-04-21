#!/usr/bin/env bash

set -euo pipefail
set -x

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
cp composer81.lock composer.lock
composer config platform.php 8.1.0
composer install --no-dev --optimize-autoloader --prefer-dist --ignore-platform-reqs

VERSION="${CI_COMMIT_TAG:-0.0.1-dev}"
VERSION_DISPLAY="${CI_COMMIT_TAG:-$(git rev-parse --short=6 HEAD)}"

FILES=("${PWD}/src/Constant/TwintConstant.php" "${PWD}/package.json" "${PWD}/composer.json" "${PWD}/readme.txt" "${PWD}/version.json" )

for FILE in "${FILES[@]}"; do
  sed -i -e "s@0.0.1-dev@${VERSION}@g" "${FILE}"
done

# Replace version for plugin file, can see in plugin list
sed -i -e "s@0.0.1-dev@${VERSION_DISPLAY}@g" "${PWD}/twint-woocommerce-extension.php"

# Run PHP-Scoper
composer global require humbug/php-scoper

# Remove old build
rm -rf "${ARCHIVE_BUILD_DIR}"

# For PHP 8.1 (baseline): scope to ${ARCHIVE_BUILD_DIR}/vendor
composer global exec php-scoper -- add-prefix --working-dir "${PWD}" --output-dir "${ARCHIVE_BUILD_DIR}" --quiet
composer dump-autoload --working-dir "${ARCHIVE_BUILD_DIR}" --classmap-authoritative

# For PHP 8.2, 8.3, 8.4, 8.5: scope each into vendor{82,83,84,85}
for PHP_VERSION in 8.2 8.3 8.4 8.5; do
  PHP_SUFFIX="${PHP_VERSION//./}"
  composer config platform.php "${PHP_VERSION}.0"
  rm -rf vendor
  rm -f composer.lock
  cp "composer${PHP_SUFFIX}.lock" composer.lock
  composer install --no-dev --optimize-autoloader --prefer-dist --ignore-platform-reqs

  composer global exec php-scoper -- add-prefix --working-dir "${PWD}" --output-dir "${ARCHIVE_BUILD_DIR}${PHP_SUFFIX}" --quiet
  composer dump-autoload --working-dir "${ARCHIVE_BUILD_DIR}${PHP_SUFFIX}" --classmap-authoritative

  mv "${ARCHIVE_BUILD_DIR}${PHP_SUFFIX}/vendor" "${ARCHIVE_BUILD_DIR}/vendor${PHP_SUFFIX}"
done

# Create archive
rm -f "${ARCHIVE_PATH}"
(cd "${PWD}/build" && zip -qr "${ARCHIVE_PATH}" "${ARCHIVE_PLUGIN_NAME}")
