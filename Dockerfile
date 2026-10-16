
FROM php:8.2-apache

RUN docker-php-ext-install pdo_mysql mysqli \
    && a2enmod rewrite

COPY docker/apache.conf /etc/apache2/conf-available/re-source.conf

RUN a2enconf re-source

WORKDIR /var/www/html/re.source

COPY . /var/www/html/re.source/

RUN chown -R www-data:www-data /var/www/html/re.source
