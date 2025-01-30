<?php

require_once "includes.php";
require_once "Catalyst.php";
require_once "EnvironmentRequest.php";

include "header.php";

global $config;

$wiki = $_POST[ 'wiki' ];
$wikiData = get_wiki_data( $wiki );

$auth = Authentication::getInstance();
if ( !$auth->canDelete( $wikiData['creator'] ) ) {
	error( '<p>You are not allowed to delete this wiki.</p>' );
}


$patches = trim( $_POST['patches'] );
if ( $patches ) {
	$patches = array_map( 'trim', preg_split( "/\n|\|/", $patches ) );
} else {
	$patches = [];
}
$initialPatchCount = count( $patches );

$patchesApplied = [];
$usedRepos = [];
$refs = [];

function check_connection() {
	if ( connection_status() !== CONNECTION_NORMAL ) {
		abandon( 'User disconnected early' );
	}
}

// Iterate by reference, so that we can modify the $patches array to add new entries
foreach ( $patches as $i => &$patch ) {
	preg_match('/^(I[0-9a-f]+|(?<r>[0-9]+)(,(?<p>[0-9]+))?)$/', $patch, $matches);
	if (!$matches) {
		$patch = htmlentities($patch);
		abandon("Invalid patch number <em>$patch</em>");
	}
	if (isset($matches['p'])) {
		$query = $matches['r'];
		$o = 'ALL_REVISIONS';
	} else {
		$query = $patch;
		$o = 'CURRENT_REVISION';
	}
	$data = gerrit_query("changes/?q=change:$query&o=LABELS&o=$o", true);
	check_connection();

	if (count($data) === 0) {
		$patch = htmlentities($patch);
		abandon("Could not find patch <em>$patch</em>");
	}
	if (count($data) !== 1) {
		$patch = htmlentities($patch);
		abandon("Ambiguous query <em>$patch</em>");
	}

	// get the info
	$repo = $data[0]['project'];
	$base = 'origin/' . $data[0]['branch'];
	$revision = null;
	if (isset($matches['p'])) {
		foreach ($data[0]['revisions'] as $k => $v) {
			if ($v['_number'] === (int)$matches['p']) {
				$revision = $k;
				break;
			}
		}
	} else {
		$revision = $data[0]['current_revision'];
	}
	if (!$revision) {
		$patch = htmlentities($patch);
		abandon("Could not find patch <em>$patch</em>");
	}
	$ref = $data[0]['revisions'][$revision]['ref'];
	$id = $data[0]['id'];

	$repos = get_repo_data();
	if (!isset($repos[$repo])) {
		$repoHtml = htmlentities($repo);
		if ($i < $initialPatchCount) {
			// Patch requested by the user, so show an error
			abandon("Repository <em>$repoHtml</em> not supported");
		} else {
			// Patch added from 'Depends-On', so we can probably ignore it
			warn("One of your patches depends on a patch from the <em>$repoHtml</em> repository, which is not supported.");
			continue;
		}
	}
	$path = $repos[$repo];
	$usedRepos[] = $repo;

	if (
		$config['requireVerified'] &&
		($data[0]['labels']['Verified']['approved']['_account_id'] ?? null) !== 75 &&
		// Admin override
		!($auth->canAdmin() && isset($_POST['adminVerified']))
	) {
		// The patch doesn't have V+2, check if the uploader is trusted
		$uploaderId = $data[0]['revisions'][$revision]['uploader']['_account_id'];
		$uploader = gerrit_query('accounts/' . $uploaderId, true);
		check_connection();
		if (!is_trusted_user($uploader['email'])) {
			if ($auth->canAdmin()) {
				echo '<form method="POST" action=""><input type="hidden" name="adminVerified" value="1">';
				foreach ($_POST as $k => $v) {
					if (is_array($v)) {
						foreach ($v as $part) {
							echo '<input type="hidden" name="' . htmlentities($k) . '[]" value="' . htmlentities($part) . '">';
						}
					} else {
						echo '<input type="hidden" name="' . htmlentities($k) . '" value="' . htmlentities($v) . '">';
					}
				}
				echo "<p>If you are confident all the patches are safe, as an admin you can bypass these checks:</p>";
				echo new OOUI\ButtonInputWidget([
					'type' => 'submit',
					'label' => 'Bypass verification',
					'icon' => 'unLock',
					'flags' => ['destructive', 'primary'],
				]);
				echo '</form>';
			}
			abandon("Patch must be approved (Verified+2) by jenkins-bot, or uploaded by a trusted user.");
		}
	}

	$patchesApplied[] = $data[0]['_number'] . ',' . $data[0]['revisions'][$revision]['_number'];
	$refs[$repo][] =
		[
			'ref' => $ref,
			'base' => str_replace('origin/', '', $base),
			'hash' => $revision,
		];

	$relatedChanges = [];
	$relatedChanges[] = [$data[0]['_number'], $data[0]['revisions'][$revision]['_number']];

	// Look at all commits in this patch's tree for cross-repo dependencies to add
	$data = gerrit_query("changes/$id/revisions/$revision/related", true);
	check_connection();
	// Ancestor commits only, not descendants
	$foundCurr = false;
	foreach ($data['changes'] as $change) {
		if ($foundCurr) {
			// Querying by change number is allegedly deprecated, but the /related API doesn't return the 'id'
			$relatedChanges[] = [$change['_change_number'], $change['_revision_number']];
		}
		$foundCurr = $foundCurr || $change['commit']['commit'] === $revision;
	}

	foreach ($relatedChanges as [$c, $r]) {
		$data = gerrit_query("changes/$c/revisions/$r/commit", true);
		check_connection();

		preg_match_all('/^Depends-On: (.+)$/m', $data['message'], $m);
		foreach ($m[1] as $changeid) {
			if (!in_array($changeid, $patches, true)) {
				// The entry we add here will be processed by the topmost foreach
				$patches[] = $changeid;
			}
		}
	}
}

$env = new EnvironmentRequest('wiki-' . $wiki, 'mediawiki');
$catalystId = $wikiData['catalystId'];

if ( $refs['mediawiki/core'] ) {
	$env->withCoreRefs($refs['mediawiki/core']);
}
//$env->withMwConfigVars( $configVars );
$catalystApi = Catalyst::newClient( $config['catalystApiToken'] );
$catalystApi->updateEnvironment( $catalystId , $env );

$repos = get_repo_data();
$start = 5;
$end = 40;
$n = 0;
$repoProgress = $start;
$repoCount = count( $repos );

function set_progress( float $pc, string $label ) {
	echo '<p>' . htmlspecialchars( $label ) . '</p>';
	$labelJson = json_encode_clean( $label );
	echo "<script>pd.setProgress( $pc, $labelJson );</script>";
}

function abandon( string $errHtml, bool $delete = true ) {
	global $wiki;
	$errJson = json_encode_clean( $errHtml );
	echo "<script>pd.abandon( $errJson );</script>";
	if ( $delete ) {
		delete_wiki( $wiki );
	}
	error( $errHtml );
}

check_connection();
set_progress( $repoProgress, "Initializing containers..." );
$error = $catalystApi->streamLogs( $catalystId, "mediawiki/install-mediawiki",
	static function ( $logs ) use ( $start, $end, $repoCount, &$n, &$repoProgress ) {
		foreach ( $logs as $log ) {
			$logMsg = $log['log'];
			if ( str_contains( $logMsg, 'Cloning' ) ) {
				$repoProgress += ( $end - $start ) / $repoCount;
				$n++;
				set_progress( $repoProgress, "Cloning repositories ($n/$repoCount)..." );
			} elseif ( str_contains( $logMsg, 'composer install' ) ) {
				set_progress( 60, "Running composer..." );
			} elseif ( str_contains( $logMsg, 'apt-get install -y npm' ) ) {
				set_progress( 80, "Setting up npm..." );
			} elseif ( str_contains( $logMsg, 'Installing Wiki' ) ) {
				set_progress( 90, "Installing wiki..." );
			}
			echo format_streamed_log( $log['timestamp'], $logMsg );
			echo '<br />';
		}
	} );
if ( $error ) {
	abandon( $error, false );
} else {
	$status_check_time = time();
	do {
		sleep( 10 );
		$catalyst_environment = $catalystApi->getEnvironment( $catalystId );
		$wiki_status = $catalyst_environment["status"];
	} while ( $wiki_status != 'running' && time() - $status_check_time < 60 );

	if ( $wiki_status != "running" ) {
		abandon( "Log stream has terminated, but deployment is not complete. Status is: " . $wiki_status, false );
	}
}

