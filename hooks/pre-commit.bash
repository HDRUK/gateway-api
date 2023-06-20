#!/usr/bin/env bash

echo "Starting pre-commit hook - Running test pipeline"
./hooks/run-tests.bash

# $? stores exit value of the last command
if [ $? -ne 0 ]; then
    echo "Tests must pass before commit!"
    exit 1
fi
