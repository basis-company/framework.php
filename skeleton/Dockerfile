FROM php:cli as version

WORKDIR /app

RUN apt-get update && apt-get install -y git wget zip
COPY .git composer.json skeleton/upgrade /app/
RUN php upgrade $(git describe --tags)
RUN cat /app/composer.json

FROM php:cli

WORKDIR /app

RUN apt-get update \
    && apt-get install procps zip -y \
    && pecl install ast swoole xdebug \
    && docker-php-ext-install opcache \
    && echo "extension=swoole.so" > /usr/local/etc/php/conf.d/swoole.ini \
    && echo "opcache.enable = 1" > /usr/local/etc/php/conf.d/opcache-enable.ini \
    && echo "opcache.enable_cli = 1" > /usr/local/etc/php/conf.d/opcache-enable-cli.ini \
    && echo "post_max_size = 32M" > /usr/local/etc/php/conf.d/post-max-size.ini \
    && echo "upload_max_filesize = 32M" > /usr/local/etc/php/conf.d/upload-max-filesize.ini \
    && rm -rf /var/lib/apt/lists/* /usr/bin/qemu-*-static

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY skeleton /app
RUN ln /usr/bin/composer composer.phar \
    && composer update --no-dev --no-progress --optimize-autoloader

COPY php /app/vendor/basis-company/framework/php
COPY --from=version /app/composer.json /app/vendor/basis-company/framework

CMD ./starter