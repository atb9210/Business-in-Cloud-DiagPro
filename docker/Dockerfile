# =============================================================
# Multi-stage Dockerfile per BusinessCloud (Laravel 12 + Filament 3)
# Immagine leggera, production-ready
# =============================================================

# Stage 1: Composer dependencies (pinned to PHP 8.3 to satisfy nette/schema 8.1-8.4 constraint)
FROM php:8.3-cli-alpine AS composer-deps
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --ignore-platform-reqs

# Stage 2: Node build (frontend assets)
FROM node:20-alpine AS node-build
WORKDIR /app
COPY package.json package-lock.json* ./
RUN npm ci --prefer-offline
COPY vite.config.js tailwind.config.js* postcss.config.js* ./
COPY resources/ resources/
RUN npm run build

# Stage 3: Production image
FROM php:8.3-fpm-alpine AS production

# Install runtime libs + build deps, compile PHP extensions, then remove build deps
RUN apk add --no-cache \
        nginx \
        supervisor \
        curl \
        mysql-client \
        libpng \
        libxml2 \
        icu-libs \
        libzip \
        oniguruma \
        libjpeg-turbo \
        freetype \
    && apk add --no-cache --virtual .build-deps \
        linux-headers \
        oniguruma-dev \
        libpng-dev \
        libxml2-dev \
        icu-dev \
        libzip-dev \
        freetype-dev \
        libjpeg-turbo-dev \
        autoconf \
        g++ \
        make \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        pdo_mysql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        intl \
        zip \
        opcache \
    && pecl install redis \
    && docker-php-ext-enable redis opcache \
    && apk del .build-deps \
    && rm -rf /var/cache/apk/* /tmp/* /usr/local/lib/php/extensions/*/*.a

# OPcache config for production
RUN echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini \
    && echo "opcache.memory_consumption=128" >> /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini \
    && echo "opcache.interned_strings_buffer=8" >> /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini \
    && echo "opcache.max_accelerated_files=10000" >> /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini \
    && echo "opcache.validate_timestamps=0" >> /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini \
    && echo "opcache.save_comments=1" >> /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini

# PHP production settings
RUN echo "memory_limit=256M" >> /usr/local/etc/php/conf.d/zz-production.ini \
    && echo "upload_max_filesize=100M" >> /usr/local/etc/php/conf.d/zz-production.ini \
    && echo "post_max_size=100M" >> /usr/local/etc/php/conf.d/zz-production.ini \
    && echo "max_execution_time=60" >> /usr/local/etc/php/conf.d/zz-production.ini \
    && echo "date.timezone=Europe/Rome" >> /usr/local/etc/php/conf.d/zz-production.ini \
    && echo "expose_php=Off" >> /usr/local/etc/php/conf.d/zz-production.ini \
    && echo "display_errors=Off" >> /usr/local/etc/php/conf.d/zz-production.ini \
    && echo "log_errors=On" >> /usr/local/etc/php/conf.d/zz-production.ini

# Create www-data directories
RUN mkdir -p /var/log/supervisor /var/log/nginx /var/run/nginx \
    && chown -R www-data:www-data /var/log/nginx

# Set working directory
WORKDIR /var/www

# Copy composer deps from stage 1
COPY --from=composer-deps /app/vendor vendor/

# Copy application code
COPY . .
COPY .env.docker .env

# Copy built frontend assets from stage 2
COPY --from=node-build /app/public/build public/build/

# Generate autoloader with app code present (copy binary only, PHP runtime is 8.3-fpm-alpine)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
RUN composer dump-autoload --optimize --no-dev \
    && rm /usr/bin/composer

# Copy configuration files
COPY docker/nginx/nginx.conf /etc/nginx/nginx.conf
COPY docker/nginx/default.conf /etc/nginx/http.d/default.conf
COPY docker/php/www.conf /usr/local/etc/php-fpm.d/www.conf
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

# Set proper permissions
RUN chown -R www-data:www-data /var/www \
    && chmod -R 755 /var/www/storage \
    && chmod -R 755 /var/www/bootstrap/cache \
    && rm -rf /var/www/node_modules /var/www/.git /var/www/tests

# Expose port
EXPOSE 80

# Health check
HEALTHCHECK --interval=30s --timeout=5s --start-period=30s --retries=3 \
    CMD curl -f http://localhost/up || exit 1

ENTRYPOINT ["/entrypoint.sh"]
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
