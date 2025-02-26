<?php
require_once "includes.php";

$stmt = $mysqli->prepare( '
	SELECT wiki, UNIX_TIMESTAMP( created ) created, backend, patches, deleted, catalystId, keepWiki
	FROM wikis
	WHERE !deleted AND !keepWiki AND patches != "[]"
' );
if ( !$stmt ) {
	error( $mysqli->error );
}
$stmt->execute();
$results = $stmt->get_result();
if ( !$results ) {
	error( $mysqli->error );
}

while ( $data = $results->fetch_assoc() ) {
	// filter merged and abandoned wikis
	$data['patches'] = json_decode( $data['patches'] );
	$hasActivePatch = false;
	foreach ( $data['patches'] as $patch ) {
		[ $r, $p ] = explode( ',', $patch );
		$patchData = get_patch_data( $r, $p );
		if ( $patchData['status'] !== 'MERGED' && $patchData['status'] !== 'ABANDONED' ) {
			$hasActivePatch = true;
		}
	}
	if ( !$hasActivePatch ) {
		delete_wiki( $data['wiki'], getenv( 'SERVER_URL' ) );
	}
}
