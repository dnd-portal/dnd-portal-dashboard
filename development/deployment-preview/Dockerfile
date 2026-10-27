FROM php:8.4-apache
RUN a2enmod rewrite headers && docker-php-ext-install pdo_mysql
COPY apache-v4.conf /etc/apache2/sites-available/000-default.conf
