jQuery( function ( $ ) {
	var captured = false;

	function capture( email ) {
		if ( captured || ! email ) return;
		$.post( tsAbandonedCart.ajaxUrl, {
			action: 'mydyma_tcs_capture_checkout_email',
			nonce:  tsAbandonedCart.nonce,
			email:  email,
		} );
		captured = true;
	}

	// Capture on blur of billing email field
	$( document ).on( 'blur', '#billing_email', function () {
		capture( $( this ).val() );
	} );

	// Also capture if user submits with a valid email (edge case: no blur)
	$( document ).on( 'checkout_place_order', function () {
		capture( $( '#billing_email' ).val() );
	} );
} );
