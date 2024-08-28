FROM php:8.3.3-fpm

ARG TED_ENABLED
ARG TRASER_ENABLED
ARG FMA_ENABLED

ENV COMPOSER_PROCESS_TIMEOUT=600

WORKDIR /var/www

COPY composer.* /var/www/

RUN apt-get update && apt-get install -y \
    nodejs \
    npm \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libmcrypt-dev \
    libxml2-dev \
    libzip-dev \
    libc-dev \
    wget \
    zlib1g-dev \
    zip \
    default-mysql-client \ 
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd pdo pdo_mysql soap zip iconv bcmath \
    && docker-php-ext-configure pdo_mysql --with-pdo-mysql=mysqlnd \
    && docker-php-ext-configure pcntl --enable-pcntl \
    && docker-php-ext-install pcntl \
    && docker-php-ext-install sockets

# Install Redis and Imagick
RUN wget -O redis-5.3.7.tgz 'http://pecl.php.net/get/redis-5.3.7.tgz' \
    && pecl install redis-5.3.7.tgz \
    && rm -rf redis-5.3.7.tgz \
    && rm -rf /tmp/pear \
    && docker-php-ext-enable redis

RUN apt-get update && apt-get install -y libmagickwand-dev --no-install-recommends && rm -rf /var/lib/apt/lists/*

# install imagick
# Version is not officially released https://pecl.php.net/get/imagick but following works for PHP 8
RUN mkdir -p /usr/src/php/ext/imagick; \
    curl -fsSL https://github.com/Imagick/imagick/archive/refs/tags/3.7.0.tar.gz | tar xvz -C "/usr/src/php/ext/imagick" --strip 1; \
    docker-php-ext-install imagick;

RUN pecl install swoole \
    && docker-php-ext-enable swoole

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- \
    --install-dir=/usr/local/bin --filename=composer

# Send update for php.ini
COPY ./init/php.development.ini /usr/local/etc/php/php.ini

# Copy the application
COPY . /var/www

#add a new line to the end of the .env file
# RUN echo "" >> /var/www/.env
# #add in these extra variables to the .env file
# RUN echo "TED_ENABLED=$TED_ENABLED" >> /var/www/.env
# RUN echo "TRASER_ENABLED=$TRASER_ENABLED" >> /var/www/.env
# RUN echo "FMA_ENABLED=$TRASER_ENABLED" >> /var/www/.env


# Composer & laravel
RUN composer install \
    && npm install --save-dev chokidar \
    && chmod -R 777 storage bootstrap/cache \
    && php artisan optimize:clear \
    && php artisan optimize \
    && php artisan config:clear \
    && php artisan ide-helper:generate \
    && php artisan octane:install --server=swoole \
    && composer dumpautoload

# Generate Swagger
RUN php artisan l5-swagger:generate

# Generate private and public keys
# RUN ssh-keygen -t rsa -b 4096 -m PEM -E SHA512 -f storage/oauth-private.key -N ""
# RUN openssl rsa -in storage/oauth-private.key -pubout -outform PEM -out storage/oauth-public.key
# RUN php artisan passport:keys

# Add symbolic link for public file storage
RUN php artisan storage:link

# Starts both, laravel server and job queue
CMD ["/var/www/docker/start.sh"]

# Expose port
EXPOSE 8000

# for study:
# composer install -q -n --no-ansi --no-dev --no-scripts --no-progress --prefer-dist