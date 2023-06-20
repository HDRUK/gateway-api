#!/usr/bin/env bash

GIT_DIR=$(git rev-parse --git-dir)

echo "Installing hooks..."

ln -s ../../hooks/pre-push.bash $GIT_DIR/hooks/pre-push
chmod +x $GIT_DIR/hooks/pre-push

echo "Done!"