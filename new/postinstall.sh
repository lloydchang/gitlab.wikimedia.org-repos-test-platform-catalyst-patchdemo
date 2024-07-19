#!/bin/bash
set -ex

PATH=$EXTRA_PATH:$PATH

# run update script (#166, #244)
php $PATCHDEMO/wikis/$NAME/w/maintenance/update.php --quick

# create additional accounts
# generic accounts alice/bob e.g. for messaging tests
php $PATCHDEMO/wikis/$NAME/w/maintenance/createAndPromote.php Alice patchdemo1
php $PATCHDEMO/wikis/$NAME/w/maintenance/createAndPromote.php Bob patchdemo1
# blocked account
php $PATCHDEMO/wikis/$NAME/w/maintenance/createAndPromote.php Mallory patchdemo1
# This command may fail as --disable-autoblock was only added in 1.36, so suppress errors
echo "Mallory" | php $PATCHDEMO/wikis/$NAME/w/maintenance/blockUsers.php --reason "Blocking account for testing" --disable-autoblock || echo "Can't block Mallory"

# set dummy email addresses, in case Inbox is being used (#254)
if [ -f $PATCHDEMO/wikis/$NAME/w/maintenance/resetUserEmail.php ]; then
	php $PATCHDEMO/wikis/$NAME/w/maintenance/resetUserEmail.php --no-reset-password "Patch Demo" Patch_Demo@localhost
	php $PATCHDEMO/wikis/$NAME/w/maintenance/resetUserEmail.php --no-reset-password "Alice" Alice@localhost
	php $PATCHDEMO/wikis/$NAME/w/maintenance/resetUserEmail.php --no-reset-password "Bob" Bob@localhost
	php $PATCHDEMO/wikis/$NAME/w/maintenance/resetUserEmail.php --no-reset-password "Mallory" Mallory@localhost
fi

# if AntiSpoof is installed, run the maintenance script to pick up accounts created
# during installation (e.g. "Patch Demo")
if [ -f $PATCHDEMO/wikis/$NAME/w/extensions/AntiSpoof/maintenance/batchAntiSpoof.php ]; then
	php $PATCHDEMO/wikis/$NAME/w/extensions/AntiSpoof/maintenance/batchAntiSpoof.php
fi

# run arbitrary SQL
for sql in $(find $PATCHDEMO/sql-perwiki -name "*.sql" -not -type d -printf '%P\n')
do
	mysql -u patchdemo -ppatchdemo patchdemo_$NAME < $PATCHDEMO/sql-perwiki/$sql
done

# JSDoc build (ignore errors on earlier MediaWiki versions)
if [ "${BUILD_DOCS}" = "1" ]; then
	cd $PATCHDEMO/wikis/$NAME/w
	npm ci || true
	npm run doc || true
	# remove node_modules afterwards, they take up infinite disk space and won't be needed any more
	rm -rf $PATCHDEMO/wikis/$NAME/w/node_modules || true
	cd $PATCHDEMO
fi

# OOUI build
if [ -d $PATCHDEMO/wikis/$NAME/w/build/ooui ]; then
	cd $PATCHDEMO/wikis/$NAME/w/build/ooui
	npm ci
	npm x grunt build
	cd $PATCHDEMO
	# JS & CSS
	cp -r $PATCHDEMO/wikis/$NAME/w/build/ooui/dist/* $PATCHDEMO/wikis/$NAME/w/resources/lib/ooui/

	# PHP
	cd $PATCHDEMO/wikis/$NAME/w
	composer config repo.oojs/oojs-ui path build/ooui
	# composer install has already run, so clear out the old version of OOUI
	rm -rf vendor/oojs/oojs-ui
	# ensure we skip the cache when re-installing
	# should use `--no-cache` instead of COMPOSER_CACHE_DIR but requires a newer version of composer that we have
	COMPOSER_CACHE_DIR=/dev/null composer require "oojs/oojs-ui @dev" --update-no-dev
fi

# Codex build
if [ -d $PATCHDEMO/wikis/$NAME/w/build/codex ]; then
	cd $PATCHDEMO/wikis/$NAME/w/build/codex
	npm ci
	# FIXME: don't build the docs for now, since the docs build process runs out of memory
	#CODEX_DOC_ROOT=$SERVERPATH/wikis/$NAME/w/build/codex/docs npm run build-all
	npm run -w @wikimedia/codex-design-tokens build
	npm run -w @wikimedia/codex-icons build
	npm run -w @wikimedia/codex build
	cd $PATCHDEMO
	cp -r $PATCHDEMO/wikis/$NAME/w/build/codex/packages/codex/dist/* $PATCHDEMO/wikis/$NAME/w/resources/lib/codex/
	cp -r $PATCHDEMO/wikis/$NAME/w/build/codex/packages/codex-icons/dist/* $PATCHDEMO/wikis/$NAME/w/resources/lib/codex-icons/
	cp -r $PATCHDEMO/wikis/$NAME/w/build/codex/packages/codex-design-tokens/dist/* $PATCHDEMO/wikis/$NAME/w/resources/lib/codex-design-tokens/
	# Make docs available at /w/build/codex/docs/
	# FIXME: don't do this for now, since we can't build the docs without crashing
	#mv $PATCHDEMO/wikis/$NAME/w/build/codex/packages/codex-docs/docs/.vitepress/dist $PATCHDEMO/wikis/$NAME/w/build/codex/docs
fi

# grant FlaggedRevs editor rights to the default account
if [ -d $PATCHDEMO/wikis/$NAME/w/extensions/FlaggedRevs ]; then
	php $PATCHDEMO/wikis/$NAME/w/maintenance/createAndPromote.php "Patch Demo" --force --custom-groups editor
fi

if [ -d $PATCHDEMO/wikis/$NAME/w/extensions/SecurePoll ]; then
	php $PATCHDEMO/wikis/$NAME/w/maintenance/createAndPromote.php "Patch Demo" --force --custom-groups electionadmin
fi

if [ -d $PATCHDEMO/wikis/$NAME/w/extensions/CheckUser ]; then
	php $PATCHDEMO/wikis/$NAME/w/maintenance/createAndPromote.php "Patch Demo" --force --custom-groups checkuser
fi

# import extension/skin/service-specific XML dumps
while IFS=' ' read -r repo dir; do
	filename=$(echo $repo | sed "s/\//-/g" | sed "s/^mediawiki-//")
	# matches extension-foo.xml or extension-foo-*.xml
	for page in $(find $PATCHDEMO/pages -regextype egrep -regex ".*/$filename(-.+)?.xml" -not -type d -printf '%P\n')
	do
		echo "Importing $PATCHDEMO/pages/$page"
		php $PATCHDEMO/wikis/$NAME/w/maintenance/importDump.php < $PATCHDEMO/pages/$page
	done
done <<< "$REPOSITORIES"

# import generic XML dumps (core-*.xml)
for page in $(find $PATCHDEMO/pages -name "core-*.xml" -not -type d -printf '%P\n')
do
	echo "Importing $PATCHDEMO/pages/$page"
	php $PATCHDEMO/wikis/$NAME/w/maintenance/importDump.php < $PATCHDEMO/pages/$page
done

# Add the proxy if selected
if [ "${USE_PROXY}" = "1" ]; then
	cp $PATCHDEMO/localsettings/feature-proxy.php $PATCHDEMO/wikis/$NAME/w/settings.d
	# Import custom Common.js for fetching CSS from the wiki
	for page in $(find $PATCHDEMO/pages-proxy -name "*.xml" -not -type d -printf '%P\n')
	do
		php $PATCHDEMO/wikis/$NAME/w/maintenance/importDump.php < $PATCHDEMO/pages-proxy/$page
	done
fi

# Enable temporary user account creation (IP Masking)
if [ "${USE_TEMPUSER}" = "1" ]; then
	cp $PATCHDEMO/localsettings/feature-tempuser.php $PATCHDEMO/wikis/$NAME/w/settings.d
fi

# Enable instantCommons if selected
if [ "${USE_INSTANT_COMMONS}" = "1" ]; then
	cp $PATCHDEMO/localsettings/feature-instantCommons.php $PATCHDEMO/wikis/$NAME/w/settings.d
fi

# populate interwiki table from en.wiki
if [ -f $PATCHDEMO/wikis/$NAME/w/maintenance/populateInterwiki.php ]; then
	php $PATCHDEMO/wikis/$NAME/w/maintenance/populateInterwiki.php
fi

# Update Main_Page
# Done after content import in case MediaWiki:Mainpage is changed
MAINPAGETITLE=$( echo 'echo Title::newMainPage()->getPrefixedText();' | php $PATCHDEMO/wikis/$NAME/w/maintenance/eval.php 2> /dev/null )
echo "$MAINPAGE" | php $PATCHDEMO/wikis/$NAME/w/maintenance/edit.php "$MAINPAGETITLE" || echo "Can't edit main page"

# update caches after import
php $PATCHDEMO/wikis/$NAME/w/maintenance/rebuildrecentchanges.php
php $PATCHDEMO/wikis/$NAME/w/maintenance/initSiteStats.php --update

# copy logo
cp $PATCHDEMO/images/logo.svg $PATCHDEMO/wikis/$NAME/w/
cp $PATCHDEMO/images/icon.svg $PATCHDEMO/wikis/$NAME/w/
cp $PATCHDEMO/images/wordmark.svg $PATCHDEMO/wikis/$NAME/w/
cp $PATCHDEMO/images/favicon.ico $PATCHDEMO/wikis/$NAME/w/
cp $PATCHDEMO/images/poweredby.svg $PATCHDEMO/wikis/$NAME/w/

# Populate ORES database if selected
if [ -f $PATCHDEMO/wikis/$NAME/w/extensions/ORES/maintenance/PopulateDatabase.php ]; then
	php $PATCHDEMO/wikis/$NAME/w/extensions/ORES/maintenance/PopulateDatabase.php
fi
