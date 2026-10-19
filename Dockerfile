FROM php:8.2-apache

# Mengaktifkan modul prefork Apache untuk menghindari error MPM ganda
RUN a2dismod mpm_event mpm_worker && a2enmod mpm_prefork

# Menginstal ekstensi PHP mysqli untuk koneksi database
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Mengubah port default Apache agar sesuai dengan port dinamis Railway
RUN sed -i 's/80/${PORT}/g' /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf

# Menyalin seluruh file projek ke direktori web Apache
COPY . /var/www/html/

# Mengizinkan hak akses folder
RUN chown -R www-data:www-data /var/www/html