#!/bin/bash

# Update NPM
npm install npm@latest
# Let www-data run NPM
mkdir -p /var/www/.npm /var/www/.config
chown www-data: /var/www/.npm /var/www/.config
# We used to run NPM as root
chown -R www-data: node_modules

# create master copies of repositories
mkdir -p repositories
chown www-data: repositories
cd repositories
while IFS=' ' read -r repo dir; do
	sudo -u www-data git clone --depth 1 --no-checkout https://gerrit.wikimedia.org/r/$repo.git $repo
done < ../repository-lists/all.txt
cd ..

# Composer wants a directory for itself (COMPOSER_HOME)
mkdir -p composer
chown www-data: composer

# Create folder for wikis
mkdir -p wikis
chown www-data: wikis

# Create a database user that is allowed to create databases for each wiki,
# and the central patchdemo database
mysql -u root --password='' < sql/user.sql
# Create the central patchdemo database
mysql -u patchdemo --password='patchdemo' < sql/patchdemo.sql

# dependencies for the website
composer install --no-interaction --no-dev
sudo -u www-data npm ci --production

# setup daily cron job to deduplicate files
echo "#!/bin/bash
$(readlink -f deduplicate.sh)" > /etc/cron.daily/patchdemo-deduplicate
chmod u+x /etc/cron.daily/patchdemo-deduplicate
# setup monthly cron job to optimize databases and free disk space
mkdir -p /etc/cron.monthly
echo "#!/bin/bash
mysqlcheck --optimize --all-databases -u root --password=''" > /etc/cron.monthly/patchdemo-optimize
chmod u+x /etc/cron.monthly/patchdemo-optimize

# PHP settings
echo "
; set session expiration to a month (default is 24 minutes???), cookie expiration too
session.gc_maxlifetime = 2592000
session.cookie_lifetime = 2592000

; double the default memory limit
memory_limit = 256M
" > /etc/php/8.2/apache2/conf.d/patchdemo.ini

# enable .htaccess files
echo "<Directory /var/www/html>
Options -Indexes
AllowOverride All
</Directory>" > /etc/apache2/sites-available/patchdemo.conf

# Support Parsoid URLs for pages with slashes in the title.
#
# https://www.mediawiki.org/wiki/Extension:VisualEditor#Troubleshooting
# This has to be set for each virtualhost (it doesn't work when set at server level while using
# virtualhosts), so we have to edit the 000-default file, where the default (and only) virtualhost
# is defined. This is extremely stupid behavior from Apache, and the Byzantine configuration
# utilities provided by Debian/Ubuntu turn out to be entirely incapable of handling it.
#
# So, here goes grep and sed.
grep -q "AllowEncodedSlashes NoDecode" /etc/apache2/sites-available/000-default.conf ||
	sed -i "/<\/VirtualHost>/i\
	AllowEncodedSlashes NoDecode" /etc/apache2/sites-available/000-default.conf

a2ensite patchdemo
# enable mod_rewrite
a2enmod rewrite
chgrp -R www-data /var/log/apache2
chmod g+rwxs /var/log/apache2

