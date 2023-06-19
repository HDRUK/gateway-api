#!/usr/bin/env bash

GIT_DIR=$(git rev-parse --git-dir)

echo "Installing hooks..."

ln -s ../hooks/pre-commit.bash $GIT_DIR/hooks/pre-commit

echo "Done!"