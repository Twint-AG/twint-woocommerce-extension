# syntax=docker/dockerfile:1
ARG BASE_IMAGE=wordpress:php8.4
FROM ${BASE_IMAGE}

# TWINT SDK PHP extensions (soap/intl/xsl) + build tooling (node, composer, wp-cli)
RUN set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends \
        git unzip less default-mysql-client ca-certificates curl \
        libxslt-dev libxml2-dev libicu-dev; \
    docker-php-ext-install -j"$(nproc)" xsl soap intl; \
    curl -fsSL https://deb.nodesource.com/setup_20.x | bash -; \
    apt-get install -y --no-install-recommends nodejs; \
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer; \
    curl -fsSL https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar -o /usr/local/bin/wp; \
    chmod +x /usr/local/bin/wp; \
    apt-get clean; rm -rf /var/lib/apt/lists/*

COPY custom.ini "$PHP_INI_DIR/conf.d/zz-twint-dev.ini"
COPY entrypoint.sh /usr/local/bin/twint-entrypoint.sh
RUN chmod +x /usr/local/bin/twint-entrypoint.sh

ENTRYPOINT ["/usr/local/bin/twint-entrypoint.sh"]
CMD ["apache2-foreground"]
