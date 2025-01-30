<?php

require_once "includes.php";

include "header.php";

$wiki = $_POST[ 'wiki' ];
$wikiData = get_wiki_data( $wiki );

$auth = Authentication::getInstance();
if ( !$auth->canDelete( $wikiData['creator'] ) ) {
	error( '<p>You are not allowed to delete this wiki.</p>' );
}

error( '<p>Editing is not implemented yet</p>' );
// TODO actually implement editing
