FROM wordpress:6.5.5-php8.3-fpm

ARG TWINT_BRANCH=master
ENV TWINT_BRANCH=$TWINT_BRANCH

# Install packages
RUN apt-get update && apt-get install -y nginx git unzip vim
RUN apt-get update && \
    apt-get install -y libxml2-dev && \
    docker-php-ext-install soap

# Config nginx
COPY docker/nginx.conf /etc/nginx/conf.d/nginx.conf
RUN rm /etc/nginx/sites-enabled/default

WORKDIR /var/www/html

# Install composer
COPY --from=composer:2.7.1@sha256:da5213f1c0c4db435ad335be4e782ebf8677b45d37677a6db1e73e474c7ad947 /usr/bin/composer /usr/bin/composer

# Copy entrypoint script
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
COPY docker/update-plugins.sh update-plugins.sh
RUN chmod +x /usr/local/bin/entrypoint.sh update-plugins.sh

# Copy Plugins
COPY wordpress65/. /usr/src/wordpress
COPY woocommerce /usr/src/wordpress/wp-content/plugins/woocommerce
COPY woocommerce-gateway-twint /usr/src/wordpress/wp-content/plugins/woocommerce-gateway-twint

# Copy source code of website
RUN cp -a /usr/src/wordpress/. /var/www/html

# GitLab Repo Config
ARG GITLAB_USERNAME
ARG GITLAB_TOKEN
RUN composer config --global http-basic.git.nfq.asia ${GITLAB_USERNAME} ${GITLAB_TOKEN}

EXPOSE 80

CMD [ "bash", "/usr/local/bin/entrypoint.sh"]
