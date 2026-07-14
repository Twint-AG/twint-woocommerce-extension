# Throwaway build environment for bin/archive.sh: PHP CLI + composer + node 18 +
# zip/git. archive.sh installs vendors with --ignore-platform-reqs, so runtime
# PHP extensions are not required here.
#
# Must run on a recent PHP: CI builds the archive on the latest PHP "to have
# access to the latest PHP-scoper" (.gitlab-ci.yml). On PHP 8.1, composer caps
# humbug/php-scoper at 0.18.x, whose older parser silently drops psl's
# apply.php files from the scoped vendor -> Fatal "Failed opening required
# .../Psl/Iter/apply.php" at plugin load. A newer PHP pulls a php-scoper whose
# parser handles those files.
FROM php:8.4-cli

RUN set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends git unzip zip curl libzip-dev; \
    docker-php-ext-install zip; \
    curl -fsSL https://deb.nodesource.com/setup_18.x | bash -; \
    apt-get install -y --no-install-recommends nodejs; \
    curl -sS https://getcomposer.org/installer | php -- \
      --install-dir=/usr/local/bin --filename=composer; \
    apt-get clean; rm -rf /var/lib/apt/lists/*
