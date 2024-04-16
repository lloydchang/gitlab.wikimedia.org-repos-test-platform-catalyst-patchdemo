# dependencies of MediaWiki
sudo apt-get install apache2 default-mysql-server php libapache2-mod-php php-mysql php-intl php-xml php-mbstring php-curl php-gd php-wikidiff2 imagemagick librsvg2-bin lame
# dependencies of our system
sudo apt-get install composer npm unzip rdfind curl

#Node 18
sudo curl -fsSL https://deb.nodesource.com/setup_18.x | bash - &&\
sudo apt-get update
sudo apt-get install -y nodejs

# Update NPM
sudo npm install npm@latest
# Let www-data run NPM
sudo mkdir -p /var/www/.npm /var/www/.config
sudo chown www-data: /var/www/.npm /var/www/.config
# We used to run NPM as root
sudo chown -R www-data: node_modules

# create master copies of repositories
sudo mkdir -p repositories
sudo chown www-data: repositories
cd repositories
while IFS=' ' read -r repo dir; do
	sudo -u www-data git clone --no-checkout https://gerrit.wikimedia.org/r/$repo.git $repo
done < ../repository-lists/all.txt
cd ..

# Composer wants a directory for itself (COMPOSER_HOME)
sudo mkdir -p composer
sudo chown www-data: composer

# Create folder for wikis
sudo mkdir -p wikis
sudo chown www-data: wikis

# Create a database user that is allowed to create databases for each wiki,
# and the central patchdemo database
sudo mysql -u root --password='' < sql/user.sql
# Create the central patchdemo database
sudo mysql -u patchdemo --password='patchdemo' < sql/patchdemo.sql

# dependencies for the website
composer install --no-dev
sudo -u www-data npm ci --production

# setup daily cron job to deduplicate files
echo "#!/bin/bash
$(readlink -f deduplicate.sh)" > /etc/cron.daily/patchdemo-deduplicate
chmod u+x /etc/cron.daily/patchdemo-deduplicate
# setup monthly cron job to optimize databases and free disk space
echo "#!/bin/bash
sudo mysqlcheck --optimize --all-databases -u root --password=''" > /etc/cron.monthly/patchdemo-optimize
chmod u+x /etc/cron.monthly/patchdemo-optimize

# PHP settings
echo "
; set session expiration to 2 weeks (default is 24 minutes???), cookie expiration too
session.gc_maxlifetime = 1209600
session.cookie_lifetime = 1209600

; double the default memory limit
memory_limit = 256M
" > /etc/php/8.2/apache2/conf.d/patchdemo.ini

# enable .htaccess files
echo "<Directory /var/www/html>
Options -Indexes
AllowOverride All

# Workaround for some weird MediaWiki bug involving MobileFrontend and UrlShortener sending it into
# infinite redirect loop that makes scraper bots hammer the site to death (#603)
RewriteOptions Inherit
RewriteEngine On
RewriteCond \"%{QUERY_STRING}\" \"UrlShortener\"
RewriteRule \".\"               \"-\"   [F,L]
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

sudo a2ensite patchdemo
# enable mod_rewrite
sudo a2enmod rewrite
sudo systemctl restart apache2
