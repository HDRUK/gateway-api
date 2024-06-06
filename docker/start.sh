#!/bin/bash

php artisan serve --host=0.0.0.0 --port=8000 &
php artisan queue:work