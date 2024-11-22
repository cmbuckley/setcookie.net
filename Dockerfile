FROM php:8.4-apache
RUN a2enmod rewrite headers
COPY public /var/www/html
COPY src /var/www/src
COPY config /config
RUN /config/assets.sh /var/www/html
