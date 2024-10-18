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

    # Separate the command from the cron timings, so as to first check for
    # duplicates, thus being immune to multiple insertions
    cronCommand="/usr/local/bin/php /var/www/artisan schedule:run > /tmp/cron.log" # >> /dev/null 2>&1"
    cronJob="* * * * * $cronCommand"

    # To add the above to local crontab
    ( crontab -l | grep -v -F "$cronCommand" ; echo "$cronJob" ) | crontab -

    ## To remove the above from local crontab
    # ( crontab -l | grep -v -F "$cronCommand" ) | crontab -

    # To activate cron service
    service cron start

    /usr/bin/supervisord -c /etc/supervisor/supervisord.conf
fi