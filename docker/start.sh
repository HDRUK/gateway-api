#!/bin/bash

if [ -e /var/www/.env ]; then
    source /var/www/.env
fi

# Initialize the base command
base_command="php artisan octane:start --host=0.0.0.0 --port=8000"

# Check the application environment and append the appropriate options
if [ "$APP_ENV" = 'local' ] || [ "$APP_ENV" = 'dev' ]; then
    echo "running in dev mode - with watch"
    base_command="$base_command --watch"
else
    echo "running in prod mode"
fi

# Add workers option if OCTANE_WORKERS is set
if [ -n "$OCTANE_WORKERS" ]; then
    base_command="$base_command --workers=${OCTANE_WORKERS}"
fi

# Start the Octane server in the background
$base_command &

php artisan queue:work