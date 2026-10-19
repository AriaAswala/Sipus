FROM php:8.2-apache

# Memperbaiki error MPM ganda di Apache
RUN a2dismod mpm_event mpm_worker && a2enmod mpm_prefork

# Mengaktifkan ekstensi mysqli
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Menyalin seluruh file projek ke server web
COPY . /var/www/html/