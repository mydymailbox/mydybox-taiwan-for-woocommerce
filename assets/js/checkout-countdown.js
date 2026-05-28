/**
 * Checkout Countdown — reservation timer banner.
 * Data is provided by wp_localize_script via window.mydyboxCountdown.
 */
(function ($) {
	if ( typeof window.mydyboxCountdown === 'undefined' ) return;

	var minutes = parseInt( window.mydyboxCountdown.minutes, 10 ) || 0;
	var seconds = 0;
	var $banner = $( '#mydybox-taiwan-for-woocommerce-checkout-timer' );
	var $clock  = $( '#mydybox-taiwan-for-woocommerce-countdown-clock' );
	if ( ! $banner.length || ! $clock.length ) return;

	var timer = setInterval( function () {
		if ( seconds === 0 ) {
			if ( minutes === 0 ) {
				clearInterval( timer );
				$banner.addClass( 'is-expired' )
					.find( '.mydybox-taiwan-for-woocommerce-timer-text' )
					.text( window.mydyboxCountdown.expiredMsg );
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
