<?php
$config = [
	// Warning shown below the new wiki form (allows HTML formatting)
	'newWikiWarning' => getenv( 'NEW_WIKI_WARNING' ) ?: '',
	// notification banner at the top of the page (allows HTML formatting)
	'notificationBanner' => getenv( 'NOTIFICATION_BANNER' ) ?: '',
	'phabricatorUrl' => getenv( 'PHABRICATOR_URL' ) ?: 'https://phabricator.wikimedia.org',
	'gerritUrl' => getenv( 'GERRIT_URL' ) ?: 'https://gerrit.wikimedia.org',
	'catalystApiUrl' => getenv( 'CATALYST_API_URL' ) ?: 'https://api.catalyst.wmcloud.org',
	'catalystDomainName' => getenv( 'CATALYST_DOMAIN_NAME' ) ?: 'catalyst.wmcloud.org',
	'catalystApiToken' => getenv( 'CATALYST_API_TOKEN' ) ?: '',
	// Link to a status page, e.g. on https://grafana.wmcloud.org/
	'statusUrl' => getenv( 'STATUS_URL' ),
	// Require that patches are V+2 before building the wiki
	'requireVerified' => getenv( 'REQUIRE_VERIFIED' ) ? getenv( 'REQUIRE_VERIFIED' ) == "true" : true,
	// Additional paths, e.g. for npm when using nvm
	'extraPaths' => [],
	// OAuth config. When enabled only authenticated users can create
	// wikis, and can delete their own wikis.
	'oauth' => [
		'url' => getenv( 'OAUTH_URL' ),
		'callback' => getenv( 'OAUTH_CALLBACK' ),
		'key' => getenv( 'OAUTH_CONSUMER_KEY' ),
		'secret' => getenv( 'OAUTH_CONSUMER_SECRET' ),
		// OAuth admins can delete any wiki
		'admins' => getenv( 'ADMIN_USERS' ) ? explode( ',', getenv( 'ADMIN_USERS' ) ) : [],
		// These users can override site configs. This is the same level of trust as V+2,
		// as those users can also execute arbitrary code.
		'configurers' => [],
		// Same as above, but regexes e.g. / \(WMF\)$/
		'configurersMatch' => [],
		// Instructions to request 'configurers' user status, e.g. "File a request <a href=...>here</a>."
		'configurersRequestHtml' => '',
	],
	// Conduit API key for bot cross-posting to Phabricator
	'conduitApiKey' => getenv( 'CONDUIT_API_KEY' ),
	// Read only mode disables wiki creation
	'readOnly' => getenv( 'READ_ONLY' ) ? getenv( 'READ_ONLY' ) == "true" : false,
	'readOnlyText' => getenv( 'READ_ONLY_TEXT' ) ?: 'This patchdemo instance is in read only mode. You may' .
		' visit previously created wikis',
];
