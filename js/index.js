/* eslint-disable no-jquery/no-global-selector */

( function () {
	window.pd = window.pd || {};

	const form = document.getElementById( 'new-form' );

	if ( form ) {
		const patchesInput = OO.ui.infuse( $( '.form-patches' ) );
		const presetInput = OO.ui.infuse( $( '.form-preset' ) );
		const reposInput = OO.ui.infuse( $( '.form-repos' ) );
		const reposField = OO.ui.infuse( $( '.form-repos-field' ) );
		const branchSelect = OO.ui.infuse( $( '.form-branch' ) );
		const $formBackend = $( '.form-backend' );
		const landingPageInput = OO.ui.infuse( $( '.form-landingPage' ) );
		const instantCommonsCheckbox = OO.ui.infuse( $( '.form-instantCommons' ) );
		const instantCommonsMethodDropdown = OO.ui.infuse( $( '.form-instantCommonsMethod' ) );
		const languageInput = OO.ui.infuse( $( '.form-language' ) );
		const proxyInput = OO.ui.infuse( $( '.form-proxy' ) );
		const docsInput = OO.ui.infuse( $( '.form-docs' ) );
		const tempuserInput = OO.ui.infuse( $( '.form-tempuser' ) );
		const announceLayout = $( '.form-announce' ).length ? OO.ui.infuse( $( '.form-announce-layout' ) ) : null;
		const backendInput = $formBackend.length ? OO.ui.infuse( $formBackend ) : null;
		let catalystSelected = backendInput && backendInput.isSelected();
		const mediawikiCore = 'mediawiki/core';

		const toggleWidgetsForCatalyst = ( setDisabled ) => {
			landingPageInput.setDisabled( setDisabled );
			instantCommonsCheckbox.setDisabled( setDisabled );
			instantCommonsMethodDropdown.setDisabled( setDisabled );
			languageInput.setDisabled( setDisabled );
			if ( announceLayout ) {
				announceLayout.setDisabled( setDisabled );
			}
			proxyInput.setDisabled( setDisabled );
			docsInput.setDisabled( setDisabled );
			tempuserInput.setDisabled( setDisabled );

			if ( setDisabled ) {
				instantCommonsCheckbox.setSelected( false );
				landingPageInput.setValue( landingPageInput.placeholder );
				languageInput.setValue( 'en' );
				proxyInput.setSelected( false );
				docsInput.setSelected( false );
				tempuserInput.setSelected( false );
				if ( announceLayout ) {
					announceLayout.setSelected( false );
				}
			}
		};
		const setupPatchesInputs = () => {
			const submit = OO.ui.infuse( $( '.form-submit' ) );
			const patchesLayout = OO.ui.infuse( $( '.form-patches-layout' ) );

			form.addEventListener( 'submit', ( e ) => {
				// Blur is not fired on patchesInput, so call manually
				patchesInput.doInputEnter();

				if ( !patchesInput.getValue().length ) {
					OO.ui.confirm(
						'Are you sure you want to create a demo with no patches applied?'
					).then( ( confirmed ) => {
						if ( confirmed ) {
							form.submit();
						}
					} );
					e.preventDefault();
					return;
				}

				submit.setDisabled( true );
				return false;
			} );

			patchesInput.on( 'matchWikis', ( wikis ) => {
				patchesLayout.setWarnings(
					( wikis || [] ).map( ( wiki ) => {
						wiki = wiki.slice( 0, 10 );
						return $( '<span>' ).append(
							document.createTextNode( 'A wiki with these patches already exists: ' ),
							$( '<a>' ).addClass( 'wiki' ).attr( 'href', '#' + wiki ).text( wiki )
						);
					} )
				);
			} );
		};
		const setupAnnounceInput = () => {
			const taskLabel = new OO.ui.LabelWidget( { classes: [ 'form-announce-taskList' ] } );
			announceLayout.$field.append( taskLabel.$element );
			const updateLinkedTasks = ( linkedTasks ) => {
				let $label = $( [] );
				if ( !linkedTasks.length ) {
					$label = $( '<em>' ).text( 'No linked tasks found.' );
				} else {
					linkedTasks.forEach( ( task ) => {
						const id = 'T' + task;
						if ( $label.length ) {
							$label = $label.add( document.createTextNode( ', ' ) );
						}
						$label = $label.add(
							$( '<a>' )
								.attr( {
									href: window.pd.config.phabricatorUrl + '/' + id,
									target: '_blank'
								} )
								.text( id )
						);
					} );
				}
				taskLabel.setLabel( $label );
			};

			patchesInput.on( 'linkedTasks', updateLinkedTasks );
			updateLinkedTasks( [] );
		};
		const setupClosedWikis = () => {
			const $wikisTable = $( '.wikis' );
			const closedWikis = OO.ui.infuse( $( '.closedWikis' ) );

			// eslint-disable-next-line no-inner-declarations
			function updateTableClasses() {
				$wikisTable.toggleClass( 'hideOpen', !!closedWikis.isSelected() );
			}

			closedWikis.on( 'change', updateTableClasses );

			if ( $( '.showClosedButton' ).length ) {
				const showClosedButton = OO.ui.infuse( $( '.showClosedButton' ) );
				showClosedButton.on( 'click', () => {
					closedWikis.setSelected( true );
					updateTableClasses();
				} );
			}
		};

		const isRepoEnabledInCatalyst = ( repo, disableCore = false ) => {
			return repo === mediawikiCore && disableCore === true ? false :
				window.pd.catalystRepos.indexOf( repo ) !== -1;
		};

		const setupRepoForCatalyst = ( option ) => {
			const repo = option.data;
			option.setDisabled( !isRepoEnabledInCatalyst( repo, true ) );
			option.setSelected( option.isSelected() && isRepoEnabledInCatalyst( repo ) );
		};

		const setupReposInputs = () => {
			const reposFieldLabel = reposField.getLabel();
			presetInput.on( 'change', OO.ui.debounce( () => {
				const val = presetInput.getValue();
				if ( val === 'custom' ) {
					reposField.$body[ 0 ].open = true;
				}
				if ( val !== 'custom' ) {
					reposInput.setValue( window.presets[ val ] );
				}
			} ) );
			reposInput.on( 'change', OO.ui.debounce( () => {
				const val = reposInput.getValue();
				let matchingPresetName = 'custom';
				for ( const presetName in window.presets ) {
					if ( window.presets[ presetName ].sort().join( '|' ) === val.sort().join( '|' ) ) {
						matchingPresetName = presetName;
						break;
					}
				}
				if ( presetInput.getValue() !== matchingPresetName ) {
					presetInput.setValue( matchingPresetName );
				}

				let selected = 0, enabled = 0;
				reposInput.checkboxMultiselectWidget.items.forEach( ( option ) => {
					if ( catalystSelected ) {
						setupRepoForCatalyst( option );
					}
					if ( !option.isDisabled() ) {
						enabled++;
						if ( option.isSelected() ) {
							selected++;
						}
					}
				} );

				reposField.setLabel( reposFieldLabel + ' (' + selected + '/' + enabled + ')' );
			} ) );

			reposInput.emit( 'change' );
		};

		const setupBackendInput = () => {
			backendInput.on( 'change', ( value ) => {
				if ( value ) {
					document.getElementById( 'catalystHeader' ).hidden = false;
					reposField.$body[ 0 ].open = true;
					presetInput.radioSelectWidget.items.forEach( ( preset ) => {
						if ( preset.data !== 'custom' ) {
							preset.setDisabled( true );
						}
					} );
					reposInput.checkboxMultiselectWidget.items.forEach( ( option ) => {
						setupRepoForCatalyst( option );
					} );
					catalystSelected = true;
					toggleWidgetsForCatalyst( true );
				} else {
					document.getElementById( 'catalystHeader' ).hidden = true;
					presetInput.radioSelectWidget.items.forEach( ( preset ) => {
						preset.setDisabled( false );
					} );
					reposInput.checkboxMultiselectWidget.items.forEach( ( option ) => {
						option.setDisabled( option.data === mediawikiCore );
					} );
					catalystSelected = false;
					toggleWidgetsForCatalyst( false );
				}
			} );
		};

		const setupInstantCommonsInputs = () => {
			instantCommonsCheckbox.on( 'change', ( value ) => {
				instantCommonsMethodDropdown.setDisabled( !value );
			} );
		};

		const setupLanguageInput = () => {
			languageInput.setValidation( /^[a-z-]{2,}$/ );
		};

		const setupNotificationsInput = () => {
			const notifField = OO.ui.infuse( document.getElementsByClassName( 'enableNotifications' )[ 0 ] );
			// Enable placholder widget so field label isn't greyed out
			notifField.fieldWidget.setDisabled( false );
			const notifFieldLabel = notifField.getLabel();

			const notifToggle = new OO.ui.ToggleButtonWidget( {
				icon: 'bellOutline'
			} );

			const onRequestPermission = ( permission ) => {
				notifToggle.setValue( permission === 'granted' );
				if ( permission === 'granted' ) {
					notifField.setLabel( 'You will get a browser notification when your wiki is ready' );
				}
				if ( permission === 'denied' ) {
					notifField.setErrors( [ 'Permission denied' ] );
				}
			};

			const onNotifChange = ( value ) => {
				if ( !value ) {
					localStorage.setItem( 'patchdemo-notifications', '0' );
					notifField.setLabel( notifFieldLabel );
				} else {
					localStorage.setItem( 'patchdemo-notifications', '1' );
					Notification.requestPermission().then( onRequestPermission );
				}
			};

			notifToggle.on( 'change', onNotifChange );
			if ( +localStorage.getItem( 'patchdemo-notifications' ) && Notification.permission ) {
				onRequestPermission( Notification.permission );
			}

			notifField.$field.empty().append( notifToggle.$element );
		};

		setupPatchesInputs();
		if ( announceLayout ) {
			setupAnnounceInput();
		}
		if ( $( '.closedWikis' ).length ) {
			setupClosedWikis();
		}
		setupReposInputs();
		if ( backendInput ) {
			setupBackendInput();
		}
		setupInstantCommonsInputs();
		setupLanguageInput();
		if ( 'Notification' in window ) {
			setupNotificationsInput();
		}

		branchSelect.on( 'change', () => {
			const branch = branchSelect.value;
			for ( const repo in window.repoBranches ) {
				const validBranch = window.repoBranches[ repo ].indexOf( branch ) !== -1;
				reposInput.checkboxMultiselectWidget
					.findItemFromData( repo )
					.setDisabled( !validBranch || repo === mediawikiCore );
			}
			reposInput.emit( 'change' );
		} );

		$( '.copyWiki' ).on( 'click', function ( e ) {
			const params = new URL( this.href ).searchParams;
			patchesInput.setValue( params.get( 'patches' ) ? params.get( 'patches' ).split( ',' ) : [] );
			branchSelect.setValue( 'origin/' + params.get( 'branch' ) );
			const preset = params.get( 'preset' );
			if ( preset ) {
				presetInput.setValue( preset );
				const repos = params.get( 'repos' );
				if ( repos ) {
					reposInput.setValue( repos.split( ',' ) );
				}
			}
			branchSelect.scrollElementIntoView( { padding: { top: $( 'header' ).height() + 10 } } );
			landingPageInput.setValue( params.get( 'landingPage' ) );
			e.preventDefault();
		} );
	}

	let $lastMatch = $( [] );
	$( window ).on( 'hashchange', () => {
		if ( location.hash.match( /^#[0-9a-f]{10}$/ ) ) {
			$lastMatch.removeClass( 'highlight' );
			$lastMatch = $( location.hash ).closest( 'tr' );
			$lastMatch.addClass( 'highlight' );
		}
	} );
	$( window ).trigger( 'hashchange' );

}() );
