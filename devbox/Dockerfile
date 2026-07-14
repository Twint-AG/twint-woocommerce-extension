# Per-instance WordPress image: stock wordpress + WP-CLI + ext-xsl (+ mysql client
# for provision.sh DB checks). Parameterized by the WordPress image tag.
ARG WP_TAG=6.6-php8.3-apache
FROM wordpress:${WP_TAG}

RUN set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends less default-mysql-client libxslt1-dev; \
    docker-php-ext-install xsl; \
    curl -fsSL -o /usr/local/bin/wp \
      https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar; \
    chmod +x /usr/local/bin/wp; \
    apt-get clean; rm -rf /var/lib/apt/lists/*
