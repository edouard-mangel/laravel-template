# ---------- PHP dependencies ----------
FROM php:8.3-fpm-alpine AS vendor

WORKDIR /app

RUN apk add --no-cache \
    git \
    zip \
    unzip \
    libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql pcntl

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

COPY . .
RUN composer dump-autoload --optimize

# ---------- Frontend build ----------
FROM node:22-alpine AS frontend

WORKDIR /app/client

COPY client/package.json client/pnpm-lock.yaml* ./
RUN npm install -g pnpm && pnpm install --frozen-lockfile

COPY client/ .
RUN pnpm run build

# ---------- Production image ----------
FROM php:8.3-fpm-alpine AS production

RUN apk add --no-cache \
    nginx \
    libpq \
    libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql pcntl opcache

WORKDIR /var/www/html

COPY --from=vendor /app .
COPY --from=frontend /app/client/dist ./public/app

RUN chown -R www-data:www-data storage bootstrap/cache

COPY docker/nginx.conf /etc/nginx/http.d/default.conf

EXPOSE 80

CMD ["sh", "-c", "php-fpm -D && nginx -g 'daemon off;'"]
