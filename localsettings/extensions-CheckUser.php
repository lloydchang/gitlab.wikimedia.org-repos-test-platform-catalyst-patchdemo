<?php

$wgGroupPermissions['checkuser'] = [];
$wgGroupPermissions['checkuser']['checkuser'] = true;
$wgGroupPermissions['checkuser']['checkuser-log'] = true;
$wgGroupPermissions['checkuser']['checkuser-temporary-account'] = true;

// Hide the Temporary Accounts onboarding dialog by default for new users,
// to avoid annoyance when testing features other than this one.
$wgDefaultUserOptions['checkuser-temporary-accounts-onboarding-dialog-seen'] = 1;
