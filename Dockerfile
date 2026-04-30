# Stage 1: Build frontend assets
FROM node:22-alpine AS frontend

WORKDIR /app

COPY package.json ./
RUN npm install

COPY resources/ resources/
COPY vite.config.js ./
COPY public/ public/
COPY database/seeders/ database/seeders/
COPY database/migrations/ database/migrations/
##
RUN npm run build

# Stage 2: PHP application
FROM php:8.3-fpm-alpine AS app

# Install system dependencies
RUN apk add --no-cache \
    nginx \
    supervisor \
    sqlite \
    libpng-dev \
    libjpeg-turbo-dev \
    libwebp-dev \
    freetype-dev \
    libzip-dev \
    icu-dev \
    nano \
    oniguruma-dev \
    libxml2-dev \
    curl-dev \
    && rm -rf /var/cache/apk/*

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        mbstring \
        xml \
        zip \
        gd \
        curl \
        intl \
        bcmath \
        exif \
        opcache \
	fileinfo \
    && apk add --no-cache sqlite-dev \
    && docker-php-ext-install pdo_sqlite

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy application files
COPY . .

# Copy built frontend assets
COPY --from=frontend /app/public/build public/build

# Install PHP dependencies (production only, skip scripts that require .env)
RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction \
    --no-progress \
    --prefer-dist \
    --no-scripts

# Set up storage and bootstrap/cache directories
RUN mkdir -p \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 775 storage bootstrap/cache

# Copy Docker configuration files
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/php.ini /usr/local/etc/php/conf.d/app.ini
COPY docker/entrypoint.sh /entrypoint.sh

RUN chmod +x /entrypoint.sh

EXPOSE 80

ENTRYPOINT ["/entrypoint.sh"]
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
