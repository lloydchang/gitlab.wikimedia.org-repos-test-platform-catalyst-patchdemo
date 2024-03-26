( function () {
	window.pd = window.pd || {};

	const installProgressField = OO.ui.infuse(
		document.getElementsByClassName( 'installProgressField' )[ 0 ]
	);

	installProgressField.fieldWidget.pushPending();

	const openWiki = OO.ui.infuse(
		document.getElementsByClassName( 'openWiki' )[ 0 ]
	);

	function endProgress() {
		installProgressField.fieldWidget.popPending();
	}

	pd.warn = function ( html ) {
		installProgressField.setWarnings(
			installProgressField.warnings.concat(
				[ new OO.ui.HtmlSnippet( html ) ]
			)
		);
	};

	pd.abandon = function ( html ) {
		installProgressField.fieldWidget.setDisabled( true );
		installProgressField.setErrors( [ new OO.ui.HtmlSnippet( html ) ] );
		document.title = 'Patch demo - Failed';
		pd.notify( 'Your PatchDemo wiki failed to build', html );
		endProgress();
	};

	pd.setProgress = function ( pc, label ) {
		installProgressField.fieldWidget.setProgress( pc );
		installProgressField.setLabel( label );
		document.title = 'Patch demo - ' + Math.round( pc ) + '%';
		if ( pc === 100 ) {
			openWiki.setDisabled( false );
			document.title = 'Patch demo - Done!';
			pd.notify( 'Your PatchDemo wiki is ready!' );
			endProgress();
		}
	};

	pd.notify = ( message, body ) => {
		if ( 'Notification' in window && +localStorage.getItem( 'patchdemo-notifications' ) ) {
			// eslint-disable-next-line no-new
			new Notification(
				message,
				{
					icon: './images/favicon-32x32.png',
					body: body
				}
			);
		}
	};

}() );
