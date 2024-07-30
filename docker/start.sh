#!/bin/bash

php artisan octane:start --server=swoole --host=0.0.0.0 --port=8000 &
php artisan queue:work --daemon --tries=3 --sleep=5 --delay=300