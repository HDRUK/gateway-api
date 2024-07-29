#!/bin/bash

php artisan octane:start &
php artisan queue:work