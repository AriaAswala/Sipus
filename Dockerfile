# Dockerfile untuk PHP 8.2 dengan Apache dan ekstensi MySQLi

# Gunakan image resmi PHP 8.2 Apache
FROM php:8.2-apache

# Install ekstensi mysqli untuk PHP Native (Prosedural)
RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli

# Aktifkan mod_rewrite Apache (opsional, untuk kelancaran routing URL)
RUN a2enmod rewrite
