FROM dunglas/frankenphp:php8.4

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
    && docker-php-ext-install -j"$(nproc)" gd pdo pdo_mysql soap zip iconv bcmath \
    && docker-php-ext-configure pdo_mysql --with-pdo-mysql=mysqlnd \
    && docker-php-ext-configure pcntl --enable-pcntl \
    && docker-php-ext-install pcntl \
    && docker-php-ext-install sockets \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Redis (PHP 8.4 compatible)
RUN pecl install redis-6.3.0 \
    && docker-php-ext-enable redis \
    && rm -rf /tmp/pear

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- \
    --install-dir=/usr/local/bin --filename=composer

#Send update for php.ini
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
    && php artisan octane:install \
    && composer dumpautoload

# Generate Swagger
RUN php artisan l5-swagger:generate

# Cleanup unwanted files
RUN rm /var/www/public/.htaccess

# Add symbolic link for public file storage
RUN php artisan storage:link

COPY ./docker/start.sh /var/www/docker/start.sh
RUN chmod +x /var/www/docker/start.sh

# Expose port
EXPOSE 8000

# Starts both, laravel server and job queue
CMD ["/var/www/docker/start.sh"]