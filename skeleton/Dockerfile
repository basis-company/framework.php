FROM php:8.2-apache as version

WORKDIR /var/www/html

RUN apt-get update && apt-get install -y git wget zip
COPY . skeleton/upgrade /var/www/html/
RUN php upgrade $(git describe --tags) $(git rev-parse --short HEAD) $(git rev-parse --abbrev-ref HEAD)

FROM php:8.2-apache

WORKDIR /var/www/html

RUN apt-get update \
    && apt-get install -y zlib1g-dev git libzip-dev libc-client-dev libkrb5-dev libfreetype6-dev libjpeg62-turbo-dev libpng-dev unzip libgd3 libpng16-16 libwebp6 \
    && docker-php-ext-configure gd --with-freetype --with-jpeg=/usr/include/ --enable-gd \
    && docker-php-ext-install gd \
    && docker-php-ext-install zip

RUN apt-get update \
    && apt-get install procps zip -y \
    && pecl install \
        ast \
        xdebug \
    && docker-php-ext-install opcache \
    && docker-php-ext-enable \
        opcache \
    && a2enmod rewrite \
    && echo "date.timezone = 'Europe/Moscow'" > /usr/local/etc/php/conf.d/timezone.ini \
    && echo "memory_limit = -1" > /usr/local/etc/php/conf.d/memory-limit.ini \
    && echo "opcache.enable = 1" > /usr/local/etc/php/conf.d/opcache-enable.ini \
    && echo "opcache.enable_cli = 1" > /usr/local/etc/php/conf.d/opcache-enable-cli.ini \
    && echo "post_max_size = 32M" > /usr/local/etc/php/conf.d/post-max-size.ini \
    && echo "upload_max_filesize = 32M" > /usr/local/etc/php/conf.d/upload-max-filesize.ini \
    && echo "ServerName default" > /etc/apache2/conf-enabled/server-name.conf \
    && sed -i 's~DocumentRoot.*$~DocumentRoot /var/www/html/public~' /etc/apache2/sites-enabled/000-default.conf \
    && sed -i 's/AccessLog/#AccessLog/g' /etc/apache2/sites-enabled/000-default.conf \
    && sed -i 's/#*ErrorLog.*$/ErrorLog \/dev\/null/g' /etc/apache2/sites-enabled/000-default.conf \
    && sed -i 's/#*CustomLog.*$/CustomLog \/dev\/null combined/g' /etc/apache2/sites-enabled/000-default.conf

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY skeleton /var/www/html

RUN ln /usr/bin/composer composer.phar \
    && composer update --no-dev --no-progress --optimize-autoloader

RUN rm -rf /var/www/html/vendor/basis-company/framework/php
COPY php /var/www/html/vendor/basis-company/framework/php
COPY --from=version /var/www/html/composer.json /var/www/html/vendor/basis-company/framework

RUN composer dump-autoload --classmap-authoritative

CMD php console module.starter