#!/bin/bash

php artisan octane:start --server=swoole &
php artisan queue:work