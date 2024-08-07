#!/bin/bash

# create master copies of repositories
mkdir -p repositories
chown www-data: repositories
cd repositories
while IFS=' ' read -r repo dir; do
  git clone --no-checkout https://gerrit.wikimedia.org/r/$repo.git $repo
done < ../repository-lists/all.txt
cd ..

composer install --no-scripts --no-dev
npm ci --production