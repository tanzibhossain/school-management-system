FROM php:8.5-fpm

# pkg-config: required since PHP 8.4 (dependabot bumped this image from
# 8.3-fpm to 8.5-fpm in f2ebb3dd without anyone noticing the build broke) —
# the gd extension's configure script switched from manually searching for
# libjpeg/libfreetype headers to detecting them via pkg-config .pc files.
# Without it, `docker-php-ext-configure gd --with-jpeg --with-freetype`
# fails outright (exit code 2) even though the -dev packages below are
# installed, because configure can no longer find them on its own.
RUN apt-get update && apt-get install -y \
    git curl libpng-dev libonig-dev libxml2-dev zip unzip \
    libzip-dev libicu-dev libjpeg-dev libfreetype6-dev pkg-config

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
