FROM php:8.2.3-fpm

ENV GOOGLE_APPLICATION_CREDENTIALS="/usr/local/etc/gcloud/application_default_credentials.json"

WORKDIR /var/www

COPY composer.* /var/www/

RUN apt-get update && apt-get install -y \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libmcrypt-dev \
    libxml2-dev \
    libzip-dev \
    libc-dev \
    zlib1g-dev \
    zip \
    default-mysql-client \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd pdo pdo_mysql soap zip iconv bcmath \
    && docker-php-ext-configure pdo_mysql --with-pdo-mysql=mysqlnd \
    && docker-php-ext-install sockets

# RUN pecl install xdebug && \
#     docker-php-ext-enable xdebug

RUN pecl install mongodb

RUN curl -sS https://getcomposer.org/installer | php -- \
    --install-dir=/usr/local/bin --filename=composer

# COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

# COPY ./init/xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini
COPY ./init/php.development.ini /usr/local/etc/php/php.ini
RUN echo "extension=mongodb.so" > /usr/local/etc/php/conf.d/mongo.ini

# RUN echo "pm.status_path = /status" >> /usr/local/etc/php/php.ini
# RUN echo "ping.path = /ping" >> /usr/local/etc/php/php.ini

COPY . /var/www

RUN composer install \
    && chmod -R 777 storage bootstrap/cache \
    && php artisan optimize:clear \
    && php artisan optimize

# RUN composer install \
#     && chmod -R 777 storage bootstrap/cache \
#     && php artisan optimize:clear \
#     && php artisan optimize
# php artisan key:generate

# RUN apt-get clean && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

RUN php artisan l5-swagger:generate

RUN php artisan route:cache && php artisan config:cache && php artisan event:cache

# RUN apt-get update && apt-get install -y vim

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
EXPOSE 8000

# for study:
# composer install -q -n --no-ansi --no-dev --no-scripts --no-progress --prefer-dist