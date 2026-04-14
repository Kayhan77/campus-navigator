FROM php:8.4-cli-alpine

WORKDIR /var/www/html

RUN apk add --no-cache \
    git curl zip unzip libpng-dev oniguruma-dev libxml2-dev \
    mysql-client postgresql-dev icu-dev libzip-dev linux-headers \
    netcat-openbsd $PHPIZE_DEPS

RUN docker-php-ext-install pdo_mysql pdo_pgsql mbstring exif pcntl bcmath opcache intl zip

RUN pecl install redis && docker-php-ext-enable redis

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

COPY . .

RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

RUN chmod -R 775 storage bootstrap/cache

COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 10000

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=10000"]