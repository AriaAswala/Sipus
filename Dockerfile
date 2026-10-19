FROM php:8.2-apache

# Menonaktifkan MPM event & worker, lalu mengaktifkan mpm_prefork secara paksa
RUN rm -f /etc/apache2/mods-enabled/mpm_event.load \
    && rm -f /etc/apache2/mods-enabled/mpm_event.conf \
    && rm -f /etc/apache2/mods-enabled/mpm_worker.load \
    && rm -f /etc/apache2/mods-enabled/mpm_worker.conf \
    && a2enmod mpm_prefork

# Menginstal ekstensi database MySQL
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Menyesuaikan port Apache dengan port dinamis Railway
RUN sed -i "s/80/\${PORT}/g" /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf

# Menyalin file projek ke web server
COPY . /var/www/html/
RUN chown -R www-data:www-data /var/www/html