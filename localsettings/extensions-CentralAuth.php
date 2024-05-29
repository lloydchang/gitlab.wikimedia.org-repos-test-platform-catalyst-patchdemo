<?php

# We don't have a real wiki farm shared between all of the demo wikis,
# just a minimal installation separate for each wiki to allow some testing.
$wgCentralAuthDatabase = $wgDBname;
$wgCentralAuthLoginWiki = $wgDBname;
$wgLocalDatabases = [ $wgDBname ];

# Make CentralAuth aware of the only site of the farm – this one.
$wgConf->wikis = [ $wgDBname ];
$wgConf->suffixes = [ '' ];
$wgConf->settings = [
	'wgServer' => [ $wgDBname => $wgServer ],
	'wgCanonicalServer' => [ $wgDBname => $wgCanonicalServer ],
	'wgArticlePath' => [ $wgDBname => $wgArticlePath ],
];

$wgCentralAuthEnableGlobalRenameRequest = true;
