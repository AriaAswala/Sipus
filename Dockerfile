FROM php:8.2-apache

# Menyalin konfigurasi MPM prefork khusus
COPY mpm_prefork.conf /etc/apache2/mods-available/mpm_prefork.conf
RUN a2dismod -f mpm_event mpm_worker && a2enmod mpm_prefork

# Menginstal ekstensi database MySQL
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Mengubah port Apache agar membaca port dinamis Railway dengan benar
RUN sed -i "s/80/\$PORT/g" /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf

# Menyalin seluruh file projek
COPY . /var/www/html/
RUN chown -R www-data:www-data /var/www/html