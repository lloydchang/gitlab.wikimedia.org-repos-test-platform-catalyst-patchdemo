<?php

# We don't have a real wiki farm shared between all of the demo wikis,
# just a minimal installation separate for each wiki to allow some testing.
$wgCentralAuthDatabase = $wgDBname;
$wgCentralAuthLoginWiki = $wgDBname;
$wgLocalDatabases = [ $wgDBname ];

$wgCentralAuthEnableGlobalRenameRequest = true;
