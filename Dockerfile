FROM php:8.5-fpm

RUN apt-get update && apt-get install -y \
    git curl libpng-dev libonig-dev libxml2-dev zip unzip \
    libzip-dev libicu-dev libjpeg-dev libfreetype6-dev

RUN docker-php-ext-configure gd --with-jpeg --with-freetype && \
    docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip intl opcache

COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/zz-opcache.ini

RUN pecl install redis && docker-php-ext-enable redis

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# --- Copy dependency files FIRST (before the rest of the app) ---
# This lets Docker cache the composer install layer separately.
# If only your PHP code changes (not composer.json), Docker skips re-running composer.
COPY composer.json composer.lock* ./
RUN composer install --no-interaction --no-scripts --no-autoloader

# --- Now copy the full application ---
COPY . .

# --- Generate optimised autoloader with full codebase present ---
RUN composer dump-autoload --optimize

RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

EXPOSE 9000
CMD ["php-fpm"]
