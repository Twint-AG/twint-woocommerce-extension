# Throwaway build environment for bin/archive.sh: PHP 8.1 CLI + composer +
# node 18 + zip/git. archive.sh installs vendors with --ignore-platform-reqs,
# so runtime PHP extensions are not required here.
FROM php:8.1-cli

RUN set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends git unzip zip curl libzip-dev; \
    docker-php-ext-install zip; \
    curl -fsSL https://deb.nodesource.com/setup_18.x | bash -; \
    apt-get install -y --no-install-recommends nodejs; \
    curl -sS https://getcomposer.org/installer | php -- \
      --install-dir=/usr/local/bin --filename=composer; \
    apt-get clean; rm -rf /var/lib/apt/lists/*
