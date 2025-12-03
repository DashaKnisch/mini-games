FROM php:8.1-apache

RUN apt-get update \
    && DEBIAN_FRONTEND=noninteractive apt-get install -y --no-install-recommends \
        unzip libzip-dev zip zlib1g-dev \
    && docker-php-ext-install zip pdo_mysql \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

RUN mkdir -p /var/www/html/repository \
    && chown -R www-data:www-data /var/www/html/repository \
    && chmod -R 775 /var/www/html/repository

EXPOSE 80

CMD ["apache2-foreground"]
