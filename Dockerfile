FROM php:8.4-apache
RUN docker-php-ext-install pdo_mysql
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/*.conf
WORKDIR /var/www/html
COPY public/ public/
COPY src/ src/
