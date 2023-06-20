#!/usr/bin/env bash

NC='\033[0m'
RED='\033[1;31m'
GREEN='\033[0;32m'

echo -e "-------------------------------------------------------------"
echo -e "${GREEN} Starting pre-push hook - Running test pipeline ${NC}"
echo -e "-------------------------------------------------------------"

./hooks/run-tests.bash

# $? stores exit value of the last command
if [ $? -ne 0 ]; then
    echo -e "-------------------------------------------------------------"
    echo -e "${RED} Tests must pass before commit - Aborting! ${NC}"
    echo -e "-------------------------------------------------------------"
    exit 1
fi
