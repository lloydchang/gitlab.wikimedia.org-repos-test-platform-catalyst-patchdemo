<?php

// Special:NewPagesFeed has some code that puts "created by new editor" if they are not autoconfirmed. But autoconfirmed needs to be turned on.
$wgAutoConfirmCount = 10;
$wgAutoConfirmAge = 4;
$wgPageTriageEnableCopyvio = true;

// Remove autopatrolled right from sysop, create autoreviewer
// and patroller groups for better mirroring enwiki.
$wgGroupPermissions['sysop']['autopatrol'] = false;
$wgGroupPermissions['autoreviewer']['autopatrol'] = true;
$wgGroupPermissions['patroller']['patrol'] = true;
