FROM php:cli as version

WORKDIR /app

RUN apt-get update && apt-get install -y git wget zip
COPY .git composer.json skeleton/upgrade /app/
RUN php upgrade $(git describe --tags)
RUN cat /app/composer.json

FROM php:cli

WORKDIR /app

RUN apt-get update \
    && apt-get install -y \
        git \
        libc-client-dev \
        libfreetype6-dev \
        libgd3 \
        libjpeg62-turbo-dev \
        libkrb5-dev \
        libmpdec-dev \
        libpng-dev \
        libpng16-16 \
        libssl-dev \
        libwebp6 \
        libzip-dev \
        openssl \
        procps \
        unzip \
        zlib1g-dev \
    && pecl install \
        ast \
        decimal \
        xdebug \
    && docker-php-ext-configure gd \
        --with-freetype \
        --with-jpeg=/usr/include/ \
        --enable-gd \
    && PHP_OPENSSL=yes docker-php-ext-configure imap \
        --with-kerberos --with-imap-ssl \
    && docker-php-ext-install \
        gd \
        imap \
        json \
        opcache \
        sockets \
        zip \
    && docker-php-ext-enable \
        decimal \
    && echo "opcache.enable = 1" > /usr/local/etc/php/conf.d/opcache-enable.ini \
    && echo "opcache.enable_cli = 1" > /usr/local/etc/php/conf.d/opcache-enable-cli.ini \
    && echo "post_max_size = 32M" > /usr/local/etc/php/conf.d/post-max-size.ini \
    && echo "upload_max_filesize = 32M" > /usr/local/etc/php/conf.d/upload-max-filesize.ini \
    && rm -rf /var/lib/apt/lists/* /usr/bin/qemu-*-static

RUN cd /tmp && git clone https://github.com/swoole/swoole-src.git \
    && cd swoole-src \
    && git checkout v4.5.2 \
    && phpize \
    && ./configure  --enable-openssl \
    && make \
    && make install \
    && touch /usr/local/etc/php/conf.d/swoole.ini \
    && echo 'extension=swoole.so' > /usr/local/etc/php/conf.d/swoole.ini \
    && cd /tmp \
    && rm -rf swoole-src \
    && rm -rf /var/lib/apt/lists/* /usr/bin/qemu-*-static

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
RUN ln /usr/bin/composer composer.phar

COPY skeleton/composer.json /app/
RUN composer update --no-dev --no-progress --optimize-autoloader
COPY skeleton /app
RUN ./starter

COPY skeleton /app
COPY php /app/vendor/basis-company/framework/php
COPY --from=version /app/composer.json /app/vendor/basis-company/framework

CMD ./starter