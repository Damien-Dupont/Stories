FROM php:8.4-fpm-alpine

# Installer l'extension PostgreSQL
RUN apk add --no-cache postgresql-dev \
    && docker-php-ext-install pdo pdo_pgsql

# Définir le répertoire de travail
WORKDIR /var/www

# Copier le code
COPY . /var/www

# Permissions
RUN chown -R www-data:www-data /var/www