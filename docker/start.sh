#!/bin/bash

php artisan octane:start --host=0.0.0.0 --port=8000 &
php artisan queue:work