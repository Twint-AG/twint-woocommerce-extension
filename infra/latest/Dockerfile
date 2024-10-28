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
COPY --chown=www-data wordpress/. /var/www/html
USER www-data
RUN curl -k -L --output ${WOO_VERSION}.zip https://downloads.wordpress.org/plugin/${WOO_VERSION}.zip && \
    unzip -o ${WOO_VERSION}.zip -d /var/www/html/wp-content/plugins && \
    rm ${WOO_VERSION}.zip
USER root
COPY --chown=www-data woocommerce-gateway-twint /var/www/html/wp-content/plugins/woocommerce-gateway-twint

ENV NVM_DIR /usr/local/nvm
ENV NODE_VERSION v18.12.0
RUN mkdir -p /usr/local/nvm && apt-get update && echo "y" | apt-get install curl
RUN curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.39.7/install.sh | bash
RUN /bin/bash -c "source $NVM_DIR/nvm.sh && nvm install $NODE_VERSION && nvm use --delete-prefix $NODE_VERSION"
ENV NODE_PATH $NVM_DIR/versions/node/$NODE_VERSION/bin
ENV PATH $NODE_PATH:$PATH

RUN apt-get update && \
    apt-get install -y libxslt-dev && \
    docker-php-ext-install xsl

# GitLab Repo Config
ARG GITLAB_USERNAME
ARG GITLAB_TOKEN
RUN composer config --global http-basic.git.nfq.asia ${GITLAB_USERNAME} ${GITLAB_TOKEN}

RUN git config --global --add safe.directory /var/www/html/wp-content/plugins/woocommerce-gateway-twint/vendor/twint-ch/twint-sdk \
    && git config --global --add safe.directory /var/www/html/wp-content/plugins/woocommerce-gateway-twint/vendor/twint-ag/sdk 

RUN sudo chown -R www-data:www-data /var/www/.npm

USER www-data
WORKDIR /var/www/html/wp-content/plugins/woocommerce-gateway-twint
RUN rm -rf node_modules \
    && npm install --quiet \
    && rm -rf dist \
    && npm run build 
RUN rm -f composer.lock \
    && composer install --no-interaction 
USER root
WORKDIR /var/www/html

EXPOSE 80

CMD [ "bash", "/usr/local/bin/entrypoint.sh"]
