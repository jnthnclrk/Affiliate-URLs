jQuery( document ).ready(function() {
	var getString = window.location.search.replace( "?", "" );
	if ( getString.indexOf( get ) != -1 ) {
		if (cookie.enabled()) {
			cookie.remove('ppc');
			cookie.set('ppc', 'true', { expires: 90, path: '/' });
			var allCookies = cookie.all();
			console.log( allCookies );
		}
	} else {
		if (cookie.enabled()) {
			cookie.remove('ppc');
			cookie.set('ppc', 'false', { expires: 90, path: '/' });
			var allCookies = cookie.all();
			console.log( allCookies );
		}
	}

	var data = { action: 'cookie_logic' }; 
	jQuery.post( check.ajaxurl, data, function(response) {
		console.log( 'Got this from the server: ' + response );
	});
});
