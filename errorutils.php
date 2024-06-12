<?php

function bad_request_error( string $msg ) {
	error( $msg, 400 );
}

function error( string $msg, $http_response_code = 503 ) {
	http_response_code( $http_response_code );

	if ( defined( 'PATCH_DEMO_JSON_API' ) ) {
		echo json_encode_clean( [
			'error' => $msg
		] );
		die();
	} else {
		die( $msg );
	}
}
