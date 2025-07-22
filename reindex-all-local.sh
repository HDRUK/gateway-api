#!/bin/bash

# Step: Run Laravel Artisan reindexing commands
echo "🚀 Starting Laravel indexing tasks..."

# Array of artisan commands to execute
commands=(
    "php artisan app:reindex-entities datasets --sleep=1 --chunkSize=10 --fresh"
    "php artisan app:reindex-entities tools --sleep=1 --chunkSize=10 --fresh"
    "php artisan app:reindex-entities publications --sleep=1 --chunkSize=25 --fresh"
    "php artisan app:reindex-entities durs --sleep=1 --chunkSize=10 --fresh"
    "php artisan app:reindex-entities collections --sleep=1 --chunkSize=5 --fresh"
    "php artisan app:reindex-entities dataCustodianNetworks --sleep=1 --chunkSize=1 --fresh"
    "php artisan app:reindex-entities dataProviders --sleep=1 --chunkSize=1 --fresh"
)

# Loop through each command
for command in "${commands[@]}"; do
    echo "Running: $command"
    $command
    
    # Check if the command executed successfully
    if [ $? -eq 0 ]; then
        echo "Successfully executed: $command"
    else
        echo "Error executing: $command"
    fi

    # Delay of 10 seconds
    sleep 10
done

echo "All commands executed."