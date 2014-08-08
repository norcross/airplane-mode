
if ( window.jQuery ) {

	jQuery.ajaxPrefilter( function( options, originalOptions, jqXHR ) {

		if ( ! airmde.enabled ) {
			return;
		}

		jqXHR.abort();

		if ( originalOptions.fail && ( typeof originalOptions.fail === 'function' ) ) {
			originalOptions.fail.call( jqXHR, jqXHR, 'abort' );
		} else if ( originalOptions.error && ( typeof originalOptions.error === 'function' ) ) {
			originalOptions.error.call( jqXHR, jqXHR, 'abort' );
		}

		if ( window.console ) {
			console.warn( airmde.aborted.replace( '%1$s', options.url ) );
		}

	} );

}
