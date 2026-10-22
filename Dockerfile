FROM php:8.5-apache

RUN a2enmod rewrite headers
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY config/php.ini "$PHP_INI_DIR/conf.d/custom.ini"

COPY public /var/www/html
COPY src /var/www/src
COPY config /config
RUN /config/assets.sh /var/www/html
