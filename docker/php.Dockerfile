FROM php:8.1-apache

# Устанавливаем зависимости для расширений и unzip
RUN apt-get update \
    && DEBIAN_FRONTEND=noninteractive apt-get install -y --no-install-recommends \
        unzip \
        libzip-dev \
        zip \
        zlib1g-dev \
    && docker-php-ext-install zip pdo_mysql \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

# Настройка рабочего каталога
WORKDIR /var/www/html

# Создаём и устанавливаем права для папки repository
RUN mkdir -p /var/www/repository && chown -R www-data:www-data /var/www/repository && chmod -R 755 /var/www/repository

# По умолчанию сайт будет доступен на порту 80
EXPOSE 80

CMD ["apache2-foreground"]
