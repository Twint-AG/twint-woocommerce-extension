FROM wordpress:php8.3-fpm

ARG TWINT_BRANCH=master
ENV TWINT_BRANCH=$TWINT_BRANCH
ENV WOO_VERSION=woocommerce.9.0.2

# Install packages
RUN apt-get update && apt-get install -y nginx git unzip vim
RUN apt-get update && \
    apt-get install -y libxml2-dev sendmail openssl && \
    docker-php-ext-install soap

# Config nginx
COPY docker/nginx.conf /etc/nginx/conf.d/nginx.conf
RUN rm /etc/nginx/sites-enabled/default

WORKDIR /var/www/html

# Install composer
COPY --from=composer:2.7.1@sha256:da5213f1c0c4db435ad335be4e782ebf8677b45d37677a6db1e73e474c7ad947 /usr/bin/composer /usr/bin/composer

# Copy entrypoint script
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Copy php config
COPY docker/zz-php.ini /usr/local/etc/php/conf.d/zz-php.ini

# Copy Plugins
COPY wordpress/. /usr/src/wordpress
RUN curl -k -L --output ${WOO_VERSION}.zip https://downloads.wordpress.org/plugin/${WOO_VERSION}.zip && \
    unzip -o ${WOO_VERSION}.zip -d /usr/src/wordpress/wp-content/plugins && \
    rm ${WOO_VERSION}.zip
COPY woocommerce-gateway-twint /usr/src/wordpress/wp-content/plugins/woocommerce-gateway-twint

RUN apt-get update && \
    apt-get install -y libxslt-dev && \
    docker-php-ext-install xsl

# Copy source code of website
RUN cp -a /usr/src/wordpress/. /var/www/html
RUN chown -R www-data:www-data /var/www/html

# GitLab Repo Config
ARG GITLAB_USERNAME
ARG GITLAB_TOKEN
RUN composer config --global http-basic.git.nfq.asia ${GITLAB_USERNAME} ${GITLAB_TOKEN}

EXPOSE 80

CMD [ "bash", "/usr/local/bin/entrypoint.sh"]
