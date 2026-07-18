FROM php:8.2-cli

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        curl \
        git \
        unzip \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libonig-dev \
        libpng-dev \
        libzip-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath \
        exif \
        gd \
        mbstring \
        pcntl \
        pdo_mysql \
        zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
COPY docker/entrypoint.sh /usr/local/bin/watchora-entrypoint.sh

RUN chmod +x /usr/local/bin/watchora-entrypoint.sh

WORKDIR /var/www/html
