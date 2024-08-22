#!/bin/bash

if [ -e /var/www/.env ]; then
    source /var/www/.env
fi

if [ $APP_ENV = 'local' ] || [ $APP_ENV = 'dev' ]; then
    echo "running in dev mode - with watch"
    php artisan octane:start --host=0.0.0.0 --port=8000 --watch --workers=15 &
else
    echo "running in prod mode"
    php artisan octane:start --host=0.0.0.0 --port=8000 --workers=15 &
fi

php artisan queue:work