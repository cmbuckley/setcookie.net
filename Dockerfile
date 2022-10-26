ARG PHP_VERSION=8.1
FROM php:${PHP_VERSION}-apache
COPY . /var/www/html
