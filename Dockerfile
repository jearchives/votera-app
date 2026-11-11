FROM php:8.2-apache

# Instal pustaka sistem dan ekstensi resmi PDO PostgreSQL
RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql

# Salin semua file web proyek VOTERA ke root web server Apache
COPY . /var/www/html/

# Buka port standar HTTP
EXPOSE 80
