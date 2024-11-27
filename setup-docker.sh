#!/bin/bash

# create master copies of repositories
mkdir -p repositories
chown www-data: repositories

composer install --no-scripts --no-dev
npm ci --production