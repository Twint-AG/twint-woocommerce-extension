FROM wordpress:5.9-php8.1

# Install required packages and PHP extensions
RUN apt-get update && apt-get install -y \
    git \
    libxslt-dev \
    libxml2-dev \
    && docker-php-ext-install xsl soap

# Add Node.js repository and install Node.js & npm
RUN apt-get install -y curl && \
    curl -sL https://deb.nodesource.com/setup_20.x | bash - && \
    apt-get install -y nodejs && \
    npm install -g npm@latest

# Clean up to reduce image size
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Composer globally
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Confirm Composer installation
RUN composer --version

# Set up WordPress (if required)
COPY . /var/www/html
