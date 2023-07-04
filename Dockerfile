FROM php:8.2.7-apache
RUN a2enmod rewrite
COPY public /var/www/html
COPY src /var/www/src
