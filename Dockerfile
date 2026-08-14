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

# gd gets its own docker-php-ext-install call, separate from the rest of
# this list. Bundling it in with several plain extensions in one invocation
# (as this used to do) failed under PHP 8.5 with `cp: cannot stat
# 'modules/*'` at the "Installing shared extensions" step — gd's own
# configure/make completed ("Build complete"), but no .so ever landed in
# its modules/ dir, so the install step had nothing to copy. gd is the one
# extension here with extra configure flags (--with-jpeg/--with-freetype)
# and external library detection (libjpeg/libfreetype via pkg-config, see
# above) — building it in isolation avoids whatever state gets shared/
# clobbered across extensions in a single docker-php-ext-install call.
RUN docker-php-ext-configure gd --with-jpeg --with-freetype && \
    docker-php-ext-install gd

# Same "bundling multiple extensions into one docker-php-ext-install call"
# pattern that broke gd above ALSO broke this line under PHP 8.5 — this
# used to be a single `docker-php-ext-install pdo_mysql mbstring exif pcntl
# bcmath zip intl opcache` call. Whichever extension actually failed isn't
# knowable without a real build log (not available in the environment that
# wrote this) — rather than keep guessing one extension at a time, every
# extension here gets its own isolated RUN so the NEXT failure (if any)
# points at exactly one extension instead of eight, and a successful build
# no longer depends on all eight sharing build state cleanly in one call.
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
