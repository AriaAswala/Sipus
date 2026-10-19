FROM php:8.2-apache

# Hapus total semua file konfigurasi MPM bawaan yang sering bentrok
RUN rm -f /etc/apache2/mods-enabled/mpm_*.load \
    && rm -f /etc/apache2/mods-enabled/mpm_*.conf

# Paksa aktifkan hanya mpm_prefork saja
RUN a2enmod mpm_prefork

# Install ekstensi database MySQL
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Sesuaikan port Apache dengan port dinamis Railway
RUN sed -i "s/80/\$PORT/g" /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf

# Salin seluruh file projek
COPY . /var/www/html/
RUN chown -R www-data:www-data /var/www/html