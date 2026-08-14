FROM php:8.3-fpm

# --- PHP 8.5 REVERTED, DO NOT RE-BUMP WITHOUT READING THIS ---
# Dependabot bumped this image 8.3-fpm -> 8.5-fpm in f2ebb3dd. Getting a
# working *Docker build* on 8.5 took four separate rounds (missing
# pkg-config for gd's new detection method, gd needing its own isolated
# docker-php-ext-install call, the same bundling issue hitting the other
# seven extensions, then opcache specifically failing because PHP 8.5 made
# OPcache a non-optional part of the PHP binary itself and there's no
# opcache.so left to build at all) -- and then, with the image finally
# building, `composer install` failed outright: phpoffice/phpspreadsheet
# 1.30.6 (locked, pulled in transitively by maatwebsite/excel ^3.1 for the
# DataImport module) hard-caps `php: >=7.4.0 <8.5.0` in its OWN
# composer.json. Newer phpspreadsheet majors (2.x-5.x) drop that cap, but
# upgrading past 1.30.x is a real breaking-change bump for maatwebsite/excel
# too, not something to do as a side effect of a base-image version bump.
# So: reverted to 8.3-fpm, the version composer.json's own `"php": "^8.3"`
# constraint already targets and the whole dependency tree already
# supports. pkg-config below and the isolated-extension-install structure
# are harmless under 8.3 and left in place (no reason to re-introduce the
# same bundling risk if this is ever revisited); the opcache install line
# (removed while chasing the 8.5 bug) is restored below since 8.3 still
# builds it as a normal shared extension. Don't bump this image past 8.4
# again without FIRST checking phpoffice/phpspreadsheet's currently locked
# version's php constraint via `composer why-not php 8.5` (or whatever
# target version) -- the Docker-level issues above are all fixable, but a
# hard version cap in a locked dependency is not, short of a deliberate,
# tested upgrade of that dependency itself.
#
# pkg-config: required since PHP 8.4 for gd's configure script, which
# switched from manually searching for libjpeg/libfreetype headers to
# detecting them via pkg-config .pc files. Not strictly needed on 8.3, but
# harmless to keep installed.
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
