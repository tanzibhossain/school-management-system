FROM php:8.5-fpm

# pkg-config: required since PHP 8.4 — the gd extension's configure script
# switched from manually searching for libjpeg/libfreetype headers to
# detecting them via pkg-config .pc files. Without it,
# `docker-php-ext-configure gd --with-jpeg --with-freetype` fails outright
# (exit code 2) even though the -dev packages below are installed.
RUN apt-get update && apt-get install -y \
    git curl libpng-dev libonig-dev libxml2-dev zip unzip \
    libzip-dev libicu-dev libjpeg-dev libfreetype6-dev pkg-config

# gd gets its own docker-php-ext-install call, separate from the rest.
# Bundling several extensions into one docker-php-ext-install invocation
# has previously failed here (`cp: cannot stat 'modules/*'` at the
# "Installing shared extensions" step, even though each extension's own
# configure/make reported "Build complete") — isolating each one avoids
# whatever build state gets shared/clobbered across extensions in a single
# call, and points any future failure at exactly one extension.
RUN docker-php-ext-configure gd --with-jpeg --with-freetype && \
    docker-php-ext-install gd
RUN docker-php-ext-install pdo_mysql
RUN docker-php-ext-install mbstring
RUN docker-php-ext-install exif
RUN docker-php-ext-install pcntl
RUN docker-php-ext-install bcmath
RUN docker-php-ext-install zip
RUN docker-php-ext-install intl
RUN docker-php-ext-install opcache

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
