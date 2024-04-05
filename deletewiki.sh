#!/bin/bash
set -ex

rm -rf $PATCHDEMO/wikis/$WIKI

# delete database
mysql -u $DB_USER --password=$DB_PASS -h $DB_HOST -e "DROP DATABASE IF EXISTS ${DB_DATABASE}_$WIKI";
