FROM php:8.2-apache

# Install ekstensi database MySQL
RUN docker-php-ext-install mysqli pdo_mysql

# Pastikan hanya mpm_prefork yang aktif saat build
RUN rm -f /etc/apache2/mods-enabled/mpm_*.load /etc/apache2/mods-enabled/mpm_*.conf \
    && a2enmod mpm_prefork

# Salin seluruh file projek
COPY . /var/www/html/
RUN chown -R www-data:www-data /var/www/html

# Pastikan hanya 1 MPM aktif & port sesuai Railway saat container menyala
CMD ["sh", "-c", "rm -f /etc/apache2/mods-enabled/mpm_*.load /etc/apache2/mods-enabled/mpm_*.conf && a2enmod mpm_prefork && sed -i -E \"s/^Listen [0-9]+/Listen ${PORT:-80}/\" /etc/apache2/ports.conf && sed -i -E \"s/<VirtualHost \\*:[0-9]+>/<VirtualHost *:${PORT:-80}>/\" /etc/apache2/sites-available/000-default.conf && exec apache2-foreground"]
