FROM wordpress:latest

# Install required packages and PHP extensions
RUN apt-get update && apt-get install -y \
    git \
    libxslt-dev \
    libxml2-dev \
    && docker-php-ext-install xsl soap

# Clean up to reduce image size
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Composer globally
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Confirm Composer installation
RUN composer --version

# Set up WordPress (if required)
COPY . /var/www/html
