#!/bin/bash
set -ex

for ext in VisualEditor Wikibase WikibaseLexeme; do
	# Check for contents in the folder (#527)
	if [ -f $PATCHDEMO/wikis/$NAME/w/extensions/$ext/.git ]; then
		cd $PATCHDEMO/wikis/$NAME/w/extensions/$ext
		git submodule update --init --recursive
	fi
done
