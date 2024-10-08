<?php
header( 'Content-Type: text/event-stream' );
header( 'Access-Control-Allow-Origin: *' );

define( 'PATCH_DEMO_JSON_API', 1 );

require_once "includes.php";

$data = null;

if ( !isset( $_GET['action'] ) ) {
	bad_request_error( 'Missing param "action"' );
}

switch ( $_GET['action'] ) {
	case 'patchmeta':
		if ( !isset( $_GET['patch'] ) ) {
			bad_request_error( 'Missing param "patch"' );
		}

		$patch = $_GET['patch'];

		$linkedTasks = [];
		$r = null;
		$p = null;
		preg_match( '/^(I[0-9a-f]+|(?<r>[0-9]+)(,(?<p>[0-9]+))?)$/', $patch, $matches );
		if ( !isset( $matches['p'] ) ) {
			// Specific revision
			// Current revision of specified change
			if ( isset( $matches['r'] ) ) {
				$data = gerrit_query( "changes/?q=change:{$matches['r']}&o=LABELS&o=CURRENT_REVISION" );
			} else {
				$data = gerrit_query( "changes/?q=change:$patch&o=LABELS&o=CURRENT_REVISION" );
			}
			if ( $data ) {
				$revision = $data[0]['current_revision'];
				$r = $data[0]['_number'];
				$p = $data[0]['revisions'][$revision]['_number'];
			}
		} else {
			$r = (int)$matches['r'];
			$p = (int)$matches['p'];
		}
		if ( $r ) {
			$data = gerrit_query( "changes/$r/revisions/$p/commit" );
			$data['r'] = $r;
			$data['p'] = $p;
			if ( isset( $data['message'] ) ) {
				get_linked_tasks( $data['message'], $linkedTasks );
				$data['linkedTasks'] = $linkedTasks;
				$data = [ $data ];
			} else {
				$data = null;
			}
		}
		break;

	case 'findwikis':
		if ( empty( $_GET['change'] ) ) {
			bad_request_error( 'Missing param "change"' );
		}
		$change = $_GET['change'];
		// Patches in JSON look like "CHANGENUM,PS"
		$stmt = $mysqli->prepare( '
			SELECT wiki, creator, UNIX_TIMESTAMP( created ) created, patches, landingPage
			FROM wikis
			WHERE !deleted AND ready AND patches LIKE CONCAT( \'%"\', ?, \',%\' )
			ORDER BY created ASC
		' );
		if ( !$stmt ) {
			error( $mysqli->error );
		}
		$stmt->bind_param( 's', $change );
		$stmt->execute();
		$results = $stmt->get_result();
		if ( !$results ) {
			error( $mysqli->error );
		}

		while ( $wiki = $results->fetch_assoc() ) {
			$data[] = [
				'wiki' => $wiki['wiki'],
				'creator' => $wiki['creator'],
				'created' => date( 'Y-m-d H:i:s', $wiki['created'] ),
				'patches' => json_decode( $wiki['patches'] ),
				'url' => get_wiki_url( $wiki['wiki'], $wiki['landingPage'] ),
			];
		}

		break;

	default:
		bad_request_error( 'Invalid action' );
		break;
}

echo json_encode_clean( $data ?: [] );
