/**
 * Checkout Countdown — reservation timer banner.
 * Data is provided by wp_localize_script via window.mydymaTcsCountdown.
 */
(function ($) {
	if ( typeof window.mydymaTcsCountdown === 'undefined' ) return;

	var minutes = parseInt( window.mydymaTcsCountdown.minutes, 10 ) || 0;
	var seconds = 0;
	var $banner = $( '#mydyma-taiwan-commerce-suite-checkout-timer' );
	var $clock  = $( '#mydyma-taiwan-commerce-suite-countdown-clock' );
	if ( ! $banner.length || ! $clock.length ) return;

	var timer = setInterval( function () {
		if ( seconds === 0 ) {
			if ( minutes === 0 ) {
				clearInterval( timer );
				$banner.addClass( 'is-expired' )
					.find( '.mydyma-taiwan-commerce-suite-timer-text' )
					.text( window.mydymaTcsCountdown.expiredMsg );
				return;
			}
			minutes--;
			seconds = 59;
		} else {
			seconds--;
		}
		var timeStr = ( minutes < 10 ? '0' + minutes : minutes ) + ':' + ( seconds < 10 ? '0' + seconds : seconds );
		$clock.text( timeStr );
	}, 1000 );
}( jQuery ));
