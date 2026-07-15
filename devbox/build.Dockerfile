# Build environment for bin/archive.sh — replicates the GitLab CI `build-archive`
# job so archive.sh produces the SAME working ZIP it produces in CI.
#
# Why this matters: archive.sh runs php-scoper over the full dependency set. In a
# lean PHP env (e.g. php:8.x-cli missing intl/soap/gd/sodium/…), php-scoper drops
# psl's apply.php files from the scoped vendor, causing a Fatal
# "Failed opening required .../Psl/Iter/apply.php" when the plugin loads. CI does
# NOT hit this because it builds on shivammathur/node:jammy with PHP 8.5 + a full
# extension set (see .gitlab-ci.yml build-archive job). We mirror that exactly.
#
# spc is shivammathur's PHP switcher shipped in the image; `spc --php-version`
# installs+activates that PHP with the requested extensions, persisted into this
# image layer so the later `docker run ... bin/archive.sh` uses it.
FROM shivammathur/node:jammy

ENV COMPOSER_ALLOW_SUPERUSER=1

RUN set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends git unzip zip; \
    rm -rf /var/lib/apt/lists/*; \
    spc -U; \
    spc --php-version 8.5 --extensions "mbstring, curl, dom, fileinfo, gd, iconv, intl, json, xml, pdo, phar, zip, sodium, pdo_mysql, bcmath, soap, xsl, tokenizer"; \
    php -v
