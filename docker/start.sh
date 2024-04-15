#!/bin/bash

php artisan queue:work &
php artisan serve --host=0.0.0.0 --port=8000 