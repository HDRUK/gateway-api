FROM php:8.3.3-fpm

ENV COMPOSER_PROCESS_TIMEOUT=600

WORKDIR /var/www

COPY composer.* /var/www/

RUN apt-get update && apt-get install -y \
    cron \
    nodejs \
    npm \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libwebp-dev \
    libmcrypt-dev \
    libxml2-dev \
    libzip-dev \
    libc-dev \
    wget \
    zlib1g-dev \
    zip \
    default-mysql-client \ 
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) gd pdo pdo_mysql soap zip iconv bcmath \
    && docker-php-ext-configure pdo_mysql --with-pdo-mysql=mysqlnd \
    && docker-php-ext-configure pcntl --enable-pcntl \
    && docker-php-ext-install pcntl \
    && docker-php-ext-install sockets \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Redis and Imagick
RUN wget -O redis-5.3.7.tgz 'http://pecl.php.net/get/redis-5.3.7.tgz' \
    && pecl install redis-5.3.7.tgz \
    && rm -rf redis-5.3.7.tgz \
    && rm -rf /tmp/pear \
    && docker-php-ext-enable redis \
    && docker-php-ext-enable gd

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- \
    --install-dir=/usr/local/bin --filename=composer

RUN curl https://frankenphp.dev/install.sh | sh \
    && mv frankenphp /usr/local/bin/frankenphp \
    && chmod +x /usr/local/bin/frankenphp

    # Send update for php.ini
COPY ./init/php.development.ini /usr/local/etc/php/php.ini

# Copy the application
COPY . /var/www

# Composer & laravel
RUN composer install --optimize-autoloader \
    && npm install --save-dev chokidar \
    && chmod -R 777 storage bootstrap/cache \
    && php artisan optimize:clear \
    && php artisan optimize \
    && php artisan config:clear \
    && php artisan ide-helper:generate \
    && composer dumpautoload

# Generate Swagger
RUN php artisan l5-swagger:generate

# Generate private and public keys
# RUN php artisan passport:keys

# Add symbolic link for public file storage
RUN php artisan storage:link

RUN chmod +x vendor/bin/pint

COPY ./docker/start.sh /var/www/docker/start.sh
RUN chmod +x /var/www/docker/start.sh

# Expose port
EXPOSE 8000

# Starts both, laravel server and job queue
CMD ["/var/www/docker/start.sh"]