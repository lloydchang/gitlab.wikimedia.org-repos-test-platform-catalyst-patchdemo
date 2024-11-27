#!/bin/bash

if [ -n "$REPO_POOL_MOUNT_PATH" ]; then
  cp /var/www/html/repository-lists/config/repos.cfg /var/www/html/repository-lists/all.txt
fi

/entrypoint.sh