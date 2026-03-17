FROM php:8.2-fpm

ARG USER_ID=1000
ARG GROUP_ID=1000

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    curl \
    && docker-php-ext-install pdo pdo_mysql mbstring exif pcntl bcmath gd zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

COPY composer.json composer.lock* ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-scripts || true

COPY package.json package-lock.json* ./

COPY . .

RUN cp .env.example .env || true \
    && php artisan key:generate --force \
    || true

RUN groupadd -g ${GROUP_ID} www || true \
    && useradd -u ${USER_ID} -ms /bin/bash -g www www || true \
    && chown -R www:www /var/www

COPY docker/php-fpm/zz-log.conf /usr/local/etc/php-fpm.d/zz-log.conf

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]

