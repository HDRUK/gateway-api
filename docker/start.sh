#!/bin/bash

if [ -e /var/www/.env ]; then
    source /var/www/.env
fi

# Initialize the base command
base_command="php artisan serve --host=0.0.0.0 --port=8000"

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

if [ "$APP_ENV" = 'local' ]; then
    # Separate the command from the cron timings, so as to first check for
    # duplicates, thus being immune to multiple insertions
    cronCommand="/usr/local/bin/php /var/www/artisan schedule:run >> /dev/null 2>&1"
    cronJob="* * * * * $cronCommand"

    # To add the above to local crontab
    ( crontab -l | grep -v -F "$cronCommand" ; echo "$cronJob" ) | crontab -

    ## To remove the above from local crontab
    # ( crontab -l | grep -v -F "$cronCommand" ) | crontab -

    # To activate cron service
    service cron start
fi

php artisan horizon


