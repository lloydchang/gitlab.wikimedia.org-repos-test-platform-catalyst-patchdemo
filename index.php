<?php
require_once "includes.php";

include "header.php";

$presetLabels = [
	'all' => [
		'title' => 'All',
		'description' => 'Selects all repos',
	],
	'wikimedia' => [
		'title' => 'Wikimedia',
		'description' => 'Most skins and extensions installed on most Wikimedia wikis, based on MediaWiki.org',
	],
	'tarball' => [
		'title' => 'Tarball',
		'description' => 'Skins and extensions included in the official MediaWiki release',
	],
	'minimal' => [
		'title' => 'Minimal',
		'description' => 'Only MediaWiki and default skin with anti-spam configuration',
	],
	'custom' => [
		'title' => 'Custom',
	]
];

$auth = Authentication::getInstance();
$authorized = !$auth->useOauth() || $auth->isSignedIn();
$canCreate = !$config['readOnly'] && $authorized;
$mediawikiCore = 'mediawiki/core';
$branches = get_branches_sorted( $mediawikiCore );

$branchOptions = array_map( static function ( $branch ) {
	return [
		'label' => preg_replace( '/^origin\//', '', $branch ),
		'data' => $branch,
	];
}, $branches );

$repoBranches = [];
$repoOptions = [];
$repoData = get_repo_data();
ksort( $repoData );
foreach ( $repoData as $repo => $path ) {
	$repoBranches[$repo] = get_branches( $repo );
	$repo = htmlspecialchars( $repo );
	$repoOptions[] = [
		'data' => $repo,
		'label' => get_repo_label( $repo ),
		'disabled' => ( $repo === $mediawikiCore ),
	];
}
$repoBranches = htmlspecialchars( json_encode_clean( $repoBranches ), ENT_NOQUOTES );
echo "<script>window.repoBranches = $repoBranches;</script>\n";

$presets = get_repo_presets();
$reposValid = array_keys( $repoData );
foreach ( $presets as $name => $repos ) {
	$presets[$name] = array_values( array_intersect( $repos, $reposValid ) );
}
$presets = htmlspecialchars( json_encode_clean( $presets ), ENT_NOQUOTES );
echo "<script>window.presets = $presets;</script>\n";

include_once 'ComboBoxInputWidget.php';
include_once 'DetailsFieldLayout.php';
include_once 'PatchSelectWidget.php';

$presetOptions = array_map( static function ( $data, $preset ) {
	$option = [
		'data' => $data
	];
	if ( isset( $preset[ 'description' ] ) ) {
		$option[ 'label' ] = new OOUI\HtmlSnippet(
			'<abbr title="' . htmlspecialchars( $preset[ 'description' ] ) . '">' . htmlspecialchars( $preset[ 'title' ] ) . '</abbr>'
		);
	} else {
		$option[ 'label' ] = $preset[ 'title' ];
	}
	return $option;
}, array_keys( $presetLabels ), array_values( $presetLabels ) );

if ( $config['readOnly'] ) {
	echo new OOUI\MessageWidget( [
		'type' => 'warning',
		'label' => new OOUI\HtmlSnippet(
			$config['readOnlyText']
		)
	] );
} else {
	$catalystRepos = get_catalyst_repos();
	$catalystBackendDisabled = ( count( $catalystRepos ) < 1 );
	$useCatalystBackend = new OOUI\HtmlSnippet(
		'Use (<a href="https://wikitech.wikimedia.org/wiki/Catalyst">Catalyst</a>) Kubernetes backend (beta)'
	);
	$cannotUseCatalystBackend = new OOUI\HtmlSnippet(
		'Could not reach <a href="https://wikitech.wikimedia.org/wiki/Catalyst">Catalyst</a> api - option disabled.'
	);
	$catalystBackendLabel = $catalystBackendDisabled ?
		$cannotUseCatalystBackend :
		$useCatalystBackend;

	$editing = false;
	$editWikiData = [];
	if ( isset( $_GET['editWiki'] ) ) {
		$editing = true;
		$editWiki = $_GET['editWiki'];
		$editWikiData = get_wiki_data( $editWiki );
		if ( !$auth->canDelete( $editWikiData['creator'] ) ) {
			error( '<p>You are not allowed to edit this wiki.</p>' );
		}
		$headerText = "Edit wiki $editWiki";
	} elseif ( isset( $_GET['copiedFrom'] ) ) {
		$headerText = "Create a new wiki based on {$_GET['copiedFrom']}";
	} else {
		$headerText = 'Create a new wiki';
	}

	echo '<h2 class="mainHeader">' . htmlspecialchars( $headerText ) . '</h2>';

	echo new OOUI\FormLayout( [
		'infusable' => true,
		'method' => 'POST',
		'action' => $editing ? 'edit.php' : 'new.php',
		'id' => 'new-form',
		'classes' => ( $canCreate ? [] : [ 'form-disabled' ] ),
		'items' => [
			new OOUI\FieldsetLayout( [
				'label' => null,
				'items' => array_filter( [
					new OOUI\FieldLayout(
						new OOUI\DropdownInputWidget( [
							'classes' => [ 'form-branch' ],
							'name' => 'branch',
							'options' => $branchOptions,
							'value' => isset( $editWikiData['branch'] ) ? 'origin/' . $editWikiData['branch'] : (
								!empty( $_GET['branch' ] ) ? 'origin/' . $_GET['branch' ] : null
							)
						] ),
						[
							'label' => 'Start with version:',
							'align' => 'left',
						]
					),
					new OOUI\FieldLayout(
						new PatchSelectWidget( [
							'classes' => [ 'form-patches' ],
							'name' => 'patches',
							'rows' => 2,
							'placeholder' => "e.g. 456123",
							'value' => isset( $editWikiData['patches'] ) ? implode( "\n", $editWikiData['patches'] ) : (
								!empty( $_GET['patches'] ) ? str_replace( ',', "\n", $_GET['patches'] ) : null
							)
						] ),
						[
							'classes' => [ 'form-patches-layout' ],
							'infusable' => true,
							'label' => 'Then, apply patches:',
							'help' => 'Gerrit changeset number or Change-Id, one per line',
							'helpInline' => true,
							'align' => 'left',
						]
					),
					$config['conduitApiKey'] ?
						new OOUI\FieldLayout(
							new OOUI\CheckboxInputWidget( [
								'classes' => [ 'form-announce' ],
								'name' => 'announce',
								'value' => 1,
								'selected' => true,
								'disabled' => $editing,
							] ),
							// TODO list announced tasks here
							[
								'classes' => [ 'form-announce-layout' ],
								'label' => 'Announce wiki on Phabricator:',
								'help' => 'Any tasks linked to from patches applied will get a comment announcing this wiki.',
								'helpInline' => true,
								'align' => 'left',
							]
						) :
						null,
					new OOUI\FieldLayout(
						new OOUI\CheckboxInputWidget( [
							'classes' => [ 'form-backend' ],
							'name' => 'backend',
							'value' => isset( $editWikiData['backend'] ) ? $editWikiData['backend'] === 'catalyst' : 1,
							'selected' => false,
							'disabled' => $catalystBackendDisabled || $editing,
						] ),
						[
							'label' => $catalystBackendLabel,
							'align' => 'left',
						]
					),
					new OOUI\FieldLayout(
						new OOUI\RadioSelectInputWidget( [
							'classes' => [ 'form-preset' ],
							'name' => 'preset',
							'options' => $presetOptions,
							'value' => $editWikiData['repos']['preset'] ?? 'wikimedia',
							'disabled' => $editing,
						] ),
						[
							'label' => 'Choose configuration preset:',
							'align' => 'left',
						]
					),
					new DetailsFieldLayout(
						new OOUI\CheckboxMultiselectInputWidget( [
							'classes' => [ 'form-repos' ],
							'name' => 'repos[]',
							'options' => $repoOptions,
							'value' => isset( $editWikiData['repos'] ) ? (
								$editWikiData['repos']['preset'] === 'custom' ? $editWikiData['repos']['repos'] : get_repo_presets()[ $editWikiData['repos']['preset'] ]
							) : get_repo_presets()[ 'wikimedia' ],
							'disabled' => $editing,
						] ),
						[
							'label' => 'Choose included repos:',
							'help' => new OOUI\HtmlSnippet( 'If your extension is not listed, please create a <a href="https://phabricator.wikimedia.org/maniphest/task/edit/form/1/?title=Add%20[Extension]%20to%20PatchDemo&description=Repository%20link:%20[gitlab/gerrit%20link]&projects=PatchDemo">new task</a>.' ),
							'helpInline' => true,
							'align' => 'inline',
							'classes' => [ 'form-repos-field' ],
						]
					),
					new OOUI\FieldLayout(
						new OOUI\CheckboxInputWidget( [
							'classes' => [ 'form-instantCommons' ],
							'name' => 'instantCommons',
							'value' => 1,
							'selected' => true,
							'disabled' => $editing,
						] ),
						[
							'label' => 'Load images from Commons',
							'help' => 'Any images not local to the wiki will be pulled from Wikimedia Commons.',
							'helpInline' => true,
							'align' => 'left',
						]
					),
					new OOUI\FieldLayout(
						new OOUI\DropdownInputWidget( [
							'classes' => [ 'form-instantCommonsMethod' ],
							'name' => 'instantCommonsMethod',
							'options' => [
								[ 'data' => 'quick', 'label' => 'QuickInstantCommons' ],
								[ 'data' => 'full', 'label' => 'InstantCommons' ],
							],
							'disabled' => $editing,
						] ),
						[
							'label' => 'Method for loading images from Commons',
							'help' => new OOUI\HtmlSnippet(
								'<a href="https://www.mediawiki.org/wiki/Extension:QuickInstantCommons">QuickInstantCommons</a> is much faster than using full <a href="https://www.mediawiki.org/wiki/InstantCommons">InstantCommons</a> but may lack some advanced image viewing features.'
							),
							'helpInline' => true,
							'align' => 'left',
						]
					),
					new OOUI\FieldLayout(
						new ComboBoxInputWidget( [
							'placeholder' => 'Main Page', // FIXME this should be a preselection not a placeholder
							'classes' => [ 'form-landingPage' ],
							'name' => 'landingPage',
							'options' => array_map( static function ( string $page ) {
								return [ 'data' => $page ];
							}, get_known_pages() ),
							'menu' => [
								'filterFromInput' => true
							],
							'value' => $editWikiData['landingPage'] ?? ( !empty( $_GET['landingPage' ] ) ? $_GET['landingPage' ] : null ),
							'disabled' => $editing,
						] ),
						[
							'label' => 'Landing page:',
							'help' => 'The page linked to from this page, and any announcement posts.',
							'helpInline' => true,
							'align' => 'left',
						]
					),
					new OOUI\FieldLayout(
						new OOUI\TextInputWidget( [
							'name' => 'language',
							'value' => 'en',
							'classes' => [ 'form-language' ],
							'disabled' => $editing,
						] ),
						[
							'label' => 'Language code',
							'help' => 'Will be used as the content language and default interface language.',
							'helpInline' => true,
							'align' => 'left',
						]
					),
					new OOUI\FieldLayout(
						new OOUI\CheckboxInputWidget( [
							'name' => 'proxy',
							'value' => 1,
							'selected' => false,
							'classes' => [ 'form-proxy' ],
							'disabled' => $editing,
						] ),
						[
							'label' => 'Proxy articles from wikipedia.org',
							'help' => 'Any articles not local to the wiki will be pulled from Wikipedia, using the language code above.',
							'helpInline' => true,
							'align' => 'left',
						]
					),
					new OOUI\FieldLayout(
						new OOUI\CheckboxInputWidget( [
							'name' => 'docs',
							'value' => 1,
							'selected' => false,
							'classes' => [ 'form-docs' ],
							'disabled' => $editing,
						] ),
						[
							'label' => "Build core documentation",
							'align' => 'left',
						]
					),
					new OOUI\FieldLayout(
						new OOUI\CheckboxInputWidget( [
							'name' => 'tempuser',
							'value' => 1,
							'selected' => true,
							'classes' => [ 'form-tempuser' ],
							'disabled' => $editing,
						] ),
						[
							'label' => "Enable temporary user account creation (IP\u{00A0}Masking)",
							'help' => 'Anonymous editors will have a temporary user account created for them on edit.',
							'helpInline' => true,
							'align' => 'left',
						]
					),
					new OOUI\FieldLayout(
					// Placeholder, will be replaced by a ToggleButtonWidget in JS
						new OOUI\ButtonWidget( [
							'icon' => 'bell',
							'disabled' => 'true'
						] ),
						[
							'align' => 'inline',
							'classes' => [ 'enableNotifications' ],
							'label' => 'Get a browser notification when your wikis are ready',
							'infusable' => true,
						]
					),
					new OOUI\FieldLayout(
						new OOUI\ButtonInputWidget( [
							'classes' => [ 'form-submit' ],
							'label' => $editing ? 'Update demo' : 'Create demo',
							'type' => 'submit',
							// 'disabled' => true,
							'flags' => [ 'progressive', 'primary' ]
						] ),
						[
							'classes' => [ 'createField' ],
							'warnings' => $config[ 'newWikiWarning' ] ? [ new OOUI\HtmlSnippet( $config[ 'newWikiWarning' ] ) ] : [],
							'label' => ' ',
							'align' => 'left',
						]
					),
					new OOUI\FieldLayout(
						new OOUI\HiddenInputWidget( [
							'name' => 'csrf_token',
							'value' => $auth->getCsrfToken(),
						] )
					),
					new OOUI\FieldLayout(
						new OOUI\HiddenInputWidget( [
							'name' => 'wiki',
							'value' => $editing ? $editWiki : ''
						] )
					),
				] )
			] ),
		]
	] );
}

if ( !$authorized ) {
	echo $auth->signInPrompt();
}

?>
<br/>
<h3>Previously generated wikis</h3>
<?php
if ( $config['notificationBanner'] ) {
	echo new OOUI\MessageWidget( [
			'type' => 'notice',
			'label' => new OOUI\HtmlSnippet( $config['notificationBanner'] ) ]
	);
	echo '<br />';
}

if ( $auth->isSignedIn() ) {
	echo new OOUI\FieldLayout(
		new OOUI\CheckboxInputWidget( [
			'infusable' => true,
			'classes' => [ 'closedWikis' ]
		] ),
		[
			'align' => 'inline',
			'label' => 'Show only wikis where all patches are merged or abandoned',
		]
	);
}
?>
<?php

$rows = '';
$closedWikis = 0;
$wikiPatches = [];
$username = $auth->getUserName();

$stmt = $mysqli->prepare( '
	SELECT wiki, creator, UNIX_TIMESTAMP( created ) created, backend, patches, branch, repos, announcedTasks, landingPage, timeToCreate, deleted, ready
	FROM wikis
	WHERE !deleted
	ORDER BY IF( creator = ?, 1, 0 ) DESC, created DESC
' );
if ( !$stmt ) {
	error( $mysqli->error );
}
$stmt->bind_param( 's', $username );
$stmt->execute();
$results = $stmt->get_result();
if ( !$results ) {
	error( $mysqli->error );
}
$shownMyWikis = false;
$shownOtherWikis = false;
while ( $data = $results->fetch_assoc() ) {
	$wikiData = get_wiki_data_from_row( $data );
	$wiki = $data['wiki'];

	$wikiPatches[$wiki] = $wikiData['patches'];

	$closed = false;
	$patches = format_patch_list( $wikiData['patchList'], $wikiData['branch'], $closed );
	$linkedTasks = format_linked_tasks( $wikiData['linkedTaskList'] );

	$creator = $wikiData[ 'creator' ] ?? '';
	$canDelete = $auth->canDelete( $creator );

	if ( !$shownMyWikis && $creator === $username ) {
		$rows .= '<tr class="wikiSection"><th colspan="99">My wikis</th></tr>';
		$shownMyWikis = true;
	}
	if ( $shownMyWikis && !$shownOtherWikis && $creator !== $username ) {
		$rows .= '<tr class="wikiSection"><th colspan="99">Other wikis</th></tr>';
		$shownOtherWikis = true;
	}

	$classes = [];
	if ( $creator !== $username ) {
		$classes[] = 'other';
	}
	if ( !$closed ) {
		$classes[] = 'open';
	}

	$actions = [];
	if ( $canCreate ) {
		$patchList = array_map( static function ( $data ) {
			return htmlspecialchars( $data['r'] );
		}, $wikiData['patchList'] );

		if (
			count( $patchList ) ||
			$wikiData[ 'branch' ] !== 'master' ||
			$wikiData[ 'repos' ][ 'preset' ] === 'custom'
		) {
			$repos = $wikiData[ 'repos' ];
			$actions[] = new OOUI\ButtonWidget( [
				'icon' => 'copy',
				'classes' => [ 'copyWiki' ],
				'href' => '?' . http_build_query( [
					'copiedFrom' => $wiki,
					'patches' => implode( ',', $patchList ),
					'branch' => $wikiData[ 'branch' ],
					'preset' => $repos[ 'preset' ] !== 'unknown' ? $repos[ 'preset' ] : null,
					'repos' => isset( $repos[ 'repos' ] ) ? implode( ',', $repos[ 'repos' ] ) : null,
					'landingPage' => $wikiData[ 'landingPage' ],
				], '' ),
				'title' => 'Copy',
				'framed' => false
			] );
		}
	}
	if ( $canDelete ) {
		$actions[] = new OOUI\ButtonWidget( [
			'icon' => 'edit',
			'href' => '?' . http_build_query( [
				'editWiki' => $wiki
			] ),
			'title' => 'Edit',
			'framed' => false,
		] );
		$actions[] = new OOUI\ButtonWidget( [
			'icon' => 'trash',
			'href' => "delete.php?wiki=$wiki",
			'title' => 'Delete',
			'framed' => false,
		] );
	}
	if ( $actions ) {
		$actionsGroup = new OOUI\ButtonGroupWidget( [
			'items' => $actions,
			'classes' => [ 'actions' ]
		] );
	}
	if ( !$data['ready'] ) {
		$classes[] = 'notReady';
	}
	$repos = '';
	$preset = $wikiData[ 'repos' ][ 'preset' ];
	switch ( $preset ) {
		case 'unknown':
			$repos = '?';
			break;
		case 'custom':
			$allRepos = get_repo_data();
			// mediawiki/core is always included and not stored in database.
			unset( $allRepos[$mediawikiCore] );
			$presetReposByName = array_map( static function ( $presetRepos ) use ( $allRepos ) {
				return array_intersect( $presetRepos, array_keys( $allRepos ) );
			}, get_repo_presets() );

			$closestPresetName = 'custom';
			$closestPresetDiff = count( $allRepos );
			foreach ( $presetReposByName as $presetName => $presetRepos ) {
				$differentRepos =
					count( array_diff( $presetRepos, $wikiData['repos']['repos'] ) ) +
					count( array_diff( $wikiData['repos']['repos'], $presetRepos ) );
				if ( $differentRepos < $closestPresetDiff ) {
					$closestPresetName = $presetName;
					$closestPresetDiff = $differentRepos;
				}
			}

			$closestPresetRepos = $presetReposByName[ $closestPresetName ];
			$removedRepos = array_diff( $closestPresetRepos, $wikiData['repos']['repos'] );
			$addedRepos = array_diff( $wikiData['repos']['repos'], $closestPresetRepos );

			$details = [];
			if ( $closestPresetName !== 'custom' ) {
				$details[] = '<dt>Base preset</dt>';
				$details[] = '<dd>' . htmlspecialchars( $presetLabels[ $closestPresetName ][ 'title' ] ) . '</dd>';
			}
			if ( $removedRepos ) {
				$details[] = '<dt>Excluded</dt>';
				foreach ( $removedRepos as $repo ) {
					$details[] = '<dd>' . htmlspecialchars( get_repo_label( $repo ) ) . '</dd>';
				}
			}
			if ( $addedRepos ) {
				$details[] = '<dt>Included</dt>';
				foreach ( $addedRepos as $repo ) {
					$details[] = '<dd>' . htmlspecialchars( get_repo_label( $repo ) ) . '</dd>';
				}
			}

			$repos = '<details>' .
				'<summary>' . htmlspecialchars( $presetLabels[ $preset ][ 'title' ] ) . '</summary>' .
				'<dl>' . implode( '', $details ) . '</dl>' .
				'</details>';
			break;
		default:
			$repos = htmlspecialchars( $presetLabels[ $preset ][ 'title' ] );
			break;
	}

	$rows .= '<tr data-wiki="' . htmlspecialchars( $wiki ) . '" class="' . implode( ' ', $classes ) . '">' .
		'<td data-label="Wiki" class="wiki">' .
			'<span class="wikiAnchor" id="' . substr( $wiki, 0, 10 ) . '"></span>' .
			get_wiki_link( $wiki, $wikiData['landingPage'], $wikiData['ready'] ) .
		'</td>' .
		'<td data-label="Patches" class="patches">' . $patches . '</td>' .
		'<td data-label="Linked tasks" class="linkedTasks">' . $linkedTasks . '</td>' .
		'<td data-label="Repos" class="repos">' . $repos . '</td>' .
		'<td data-label="Time" class="date">' . date( 'Y-m-d H:i:s', $wikiData[ 'created' ] ) . '</td>' .
		'<td data-label="Backend" class="backend">' . $wikiData[ 'backend' ] . '</td>' .
		( $auth->useOAuth() ? '<td data-label="Creator">' . ( $creator ? user_link( $creator ) : '?' ) . '</td>' : '' ) .
		( $auth->canAdmin() ? '<td data-label="Time to create">' . ( $wikiData['timeToCreate'] ? format_duration( $wikiData['timeToCreate'] ) : '' ) . '</td>' : '' ) .
		( $canCreate ? '<td data-label="Actions">' . $actionsGroup . '</td>' : '' ) .
	'</tr>';

	if ( $username && $username === $creator && $closed ) {
		$closedWikis++;
	}
}
$stmt->close();

if ( $closedWikis ) {
	echo new OOUI\MessageWidget( [
		'classes' => [ 'showClosed' ],
		'type' => 'warning',
		'label' => new OOUI\HtmlSnippet(
			new OOUI\ButtonWidget( [
				'infusable' => true,
				'label' => 'Show',
				'classes' => [ 'showClosedButton' ],
			] ) .
			'You have created ' . $closedWikis . ' ' . ( $closedWikis > 1 ? 'wikis' : 'wiki' ) . ' where all the patches ' .
			'have been merged or abandoned and therefore can be deleted.'
		)
	] );
}

echo '<table class="wikis">' .
	'<tr class="headerRow">' .
		'<th>Wiki</th>' .
		'<th>Patches<br /><em>✓=Merged ✗=Abandoned 🛇=<abbr title="Open patches marked with \'DNM\' or \'DO NOT MERGE\'">Do not merge</abbr></em></th>' .
		'<th>Linked tasks<br /><em>✓=Resolved ✗=Declined/Invalid</em></th>' .
		'<th>Repos</th>' .
		'<th>Time</th>' .
		'<th>Backend</th>' .
		( $auth->useOAuth() ? '<th>Creator</th>' : '' ) .
		( $auth->canAdmin() ? '<th><abbr title="Time to create">TTC</abbr></th>' : '' ) .
		( $canCreate ? '<th>Actions</th>' : '' ) .
	'</tr>' .
	$rows .
'</table>';

$windowData = '<script>
window.pd = window.pd || {};
pd.wikiPatches = ' . json_encode_clean( $wikiPatches ) . ';
pd.config = ' . json_encode_clean( [
	'phabricatorUrl' => $config['phabricatorUrl'],
	'gerritUrl' => $config['gerritUrl'],
] ) . ';';
if ( isset( $catalystRepos ) ) {
	$windowData .= '
pd.catalystRepos = ' . json_encode_clean( $catalystRepos ) . ';';
}
$windowData .= '
</script>';

echo $windowData;

?>
<script src="js/DetailsFieldLayout.js"></script>
<script src="js/PatchSelectWidget.js"></script>
<script src="js/index.js"></script>
<?php
include "footer.html";
