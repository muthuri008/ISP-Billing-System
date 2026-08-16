FROM php:8.3-cli-bookworm
WORKDIR /var/www/html
RUN apt-get update && apt-get install -y --no-install-recommends git unzip libzip-dev libicu-dev libonig-dev && docker-php-ext-install pdo_mysql mbstring intl zip && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY composer.json composer.lock* ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-progress --optimize-autoloader
COPY . .
RUN chown -R www-data:www-data storage bootstrap/cache
EXPOSE 8000
CMD ["php","artisan","serve","--host=0.0.0.0","--port=8000"]
