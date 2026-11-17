FROM php:8.3-apache

# PHP extension used by the project (mysqli = MySQL connection)
RUN docker-php-ext-install mysqli

# Production PHP settings (hides error messages from visitors)
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# Render sends visitors to port 10000
RUN sed -i 's/Listen 80/Listen 10000/' /etc/apache2/ports.conf \
 && sed -i 's/<VirtualHost \*:80>/<VirtualHost *:10000>/' /etc/apache2/sites-available/000-default.conf

# Copy the website
COPY . /var/www/html/

# Folder for observation photos
RUN mkdir -p /var/www/html/uploads \
 && chown -R www-data:www-data /var/www/html/uploads

EXPOSE 10000
