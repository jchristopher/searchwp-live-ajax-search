// this is a wrapper for our grunt-powered @imports since they cause jshint to fail

;(function ( $, window, document, undefined ) {
	@import '../vendor/spin.js';
	@import 'searchwp-live-search.js';
})( jQuery, window, document );
