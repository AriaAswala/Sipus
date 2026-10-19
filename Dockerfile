FROM php:8.2-apache

# Pastikan hanya satu MPM yang aktif
RUN rm -f /etc/apache2/mods-enabled/mpm_*.load /etc/apache2/mods-enabled/mpm_*.conf \
    && a2enmod mpm_prefork

# Install ekstensi database MySQL
RUN docker-php-ext-install mysqli pdo_mysql

# Port dinamis Railway (default 80 kalau PORT tidak diset)
ENV PORT=80
RUN sed -i 's/Listen 80/Listen ${PORT}/' /etc/apache2/ports.conf \
    && sed -i 's/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/' /etc/apache2/sites-available/000-default.conf

# Salin seluruh file projek
COPY . /var/www/html/
RUN chown -R www-data:www-data /var/www/html