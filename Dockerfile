FROM php:8.4-fpm-bookworm

# Dependências
RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    unzip \
    libicu-dev \
    libonig-dev \
    libzip-dev \
    libxml2-dev \
    && docker-php-ext-install -j"$(nproc)" intl mbstring opcache pdo_mysql zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*
    
# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY docker/php/entrypoint.sh /usr/local/bin/docker-entrypoint-app

RUN chmod +x /usr/local/bin/docker-entrypoint-app

WORKDIR /var/www

ENTRYPOINT ["/usr/local/bin/docker-entrypoint-app"]
CMD ["php-fpm"]
