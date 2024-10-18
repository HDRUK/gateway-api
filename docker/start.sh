#!/bin/bash

if [ -e /var/www/.env ]; then
    source /var/www/.env
fi

# Check the application environment and append the appropriate options
if [ "$APP_ENV" = 'local' ]; then
    echo "running in dev mode - with watch"

    # Initialize the base command
    base_command="php artisan octane:start --host=0.0.0.0 --port=8000"

    base_command="$base_command --watch"

    # Add workers option if OCTANE_WORKERS is set
    if [ -n "$OCTANE_WORKERS" ]; then
        base_command="$base_command --workers=${OCTANE_WORKERS}"
    fi

    # Start the Octane server in the background
    $base_command &

    # Start the queue worker in the background
    php artisan queue:work &
else
    echo "running in prod mode"

    /usr/bin/supervisord -c /etc/supervisor/supervisord.conf
fi