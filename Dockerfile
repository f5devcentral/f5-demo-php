FROM php:7.0-apache
COPY php.ini /usr/local/etc/php/php.ini
COPY f5demo /var/www
RUN a2enmod headers;\
a2enmod rewrite
