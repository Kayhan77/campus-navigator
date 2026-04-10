# Use PHP 8.4 FPM Alpine for smaller image size
FROM php:8.4.16-fpm-alpine

# Set working directory
WORKDIR /var/www/html

# Install system dependencies
RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    oniguruma-dev \
    libxml2-dev \
    zip \
    unzip \
    mysql-client \
    postgresql-dev \
    icu-dev \
    libzip-dev \
    linux-headers \
    netcat-openbsd \
    $PHPIZE_DEPS

# Install PHP extensions
RUN docker-php-ext-install \
    pdo_mysql \
    pdo_pgsql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    opcache \
    intl \
    zip

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Copy production PHP configuration
COPY php.production.ini /usr/local/etc/php/conf.d/php.production.ini

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy application files
COPY . .

# Install PHP dependencies (production optimized)
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

# Set proper permissions for Laravel
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# Copy and set permissions for entrypoint script
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Expose port 10000 (Render requirement)
EXPOSE 10000

# Use entrypoint script
ENTRYPOINT ["docker-entrypoint.sh"]

# Start Laravel development server (suitable for small to medium traffic)
CMD ["sh", "-c", "php artisan migrate --force || true; php artisan serve --host=0.0.0.0 --port=10000"]