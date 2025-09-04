FROM php:8.2-fpm

# Installe les extensions nécessaires pour Symfony et MySQL
RUN apt-get update && apt-get install -y \
    git unzip libicu-dev libpq-dev libzip-dev zip \
    && docker-php-ext-install intl pdo pdo_mysql zip

# Installe Composer
COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Droits sur le dossier
RUN chown -R www-data:www-data /var/www/html