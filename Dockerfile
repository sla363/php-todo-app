# Use the official PHP image with Apache
FROM php:8.3-apache

# Install system dependencies for MongoDB and other extensions
RUN apt-get update && apt-get install -y \
        libpq-dev \
        libpng-dev \
        libonig-dev \
        libxml2-dev \
        git \
        curl \
        libcurl4-openssl-dev \
        pkg-config \
        libssl-dev \
        libzip-dev \
        zip \
        && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_mysql pdo_pgsql pgsql gd mbstring exif pcntl bcmath zip

# Set the working directory in the container
WORKDIR /var/www/html
COPY . .

# Update the Apache configuration to allow .htaccess overrides
RUN sed -i 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf

# Copy and run composer install
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN composer install

# Change the document root to point to the 'www' subdirectory
ENV APACHE_DOCUMENT_ROOT /var/www/html

# Enable mod_rewrite
RUN a2enmod rewrite

# Update the Apache site configuration
RUN sed -i 's|/var/www/html|${APACHE_DOCUMENT_ROOT}|' /etc/apache2/sites-available/000-default.conf

# Expose port 80 to access the Apache server
EXPOSE 80