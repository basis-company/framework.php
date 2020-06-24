FROM php:cli

WORKDIR /app

RUN apt-get update \
    && apt-get install -y zlib1g-dev git libzip-dev libc-client-dev libkrb5-dev libfreetype6-dev libjpeg62-turbo-dev libpng-dev unzip libgd3 libpng16-16 libwebp6 \
    && docker-php-ext-configure gd --with-freetype --with-jpeg=/usr/include/ --enable-gd \
    && docker-php-ext-install gd \
    && PHP_OPENSSL=yes docker-php-ext-configure imap --with-kerberos --with-imap-ssl \
    && docker-php-ext-install imap \
    && docker-php-ext-install opcache \
    && docker-php-ext-install zip \
    && docker-php-ext-install sockets \
    && docker-php-ext-install json \
    && pecl install ast \
    && pecl install xdebug \
    && curl -sS https://getcomposer.org/installer | php \
    && echo "upload_max_filesize = 32M" > /usr/local/etc/php/conf.d/upload-max-filesize.ini \
    && echo "post_max_size = 32M" > /usr/local/etc/php/conf.d/post-max-size.ini

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

ADD skeleton /app
RUN composer install --no-progress --no-dev -o
RUN ./vendor/bin/rr get-binary

CMD ./vendor/basis-company/framework/bin/starter