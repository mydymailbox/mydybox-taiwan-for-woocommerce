/**
 * Product UI — sticky add-to-cart bar.
 */
(function ($) {
	$( window ).scroll( function () {
		if ( $( this ).scrollTop() > 600 ) {
			$( '#mydyma-taiwan-commerce-suite-sticky-cart' ).addClass( 'is-visible' );
		} else {
			$( '#mydyma-taiwan-commerce-suite-sticky-cart' ).removeClass( 'is-visible' );
		}
	} );

	// Sticky buy button → click the hidden WC add-to-cart button.
	$( document ).on( 'click', '.mydyma-taiwan-commerce-suite-sticky-btn', function () {
		var btn = document.querySelector( '.single_add_to_cart_button' );
		if ( btn ) btn.click();
	} );

	// Quantity buttons
	$( document ).on( 'click', '.ts-sticky-qty-btn', function () {
		var $input    = $( '.ts-sticky-qty-input' );
		var currentVal = parseInt( $input.val(), 10 ) || 1;
		var isPlus     = $( this ).hasClass( 'plus' );

		var newVal = isPlus ? currentVal + 1 : currentVal - 1;
		if ( newVal < 1 ) newVal = 1;

		$input.val( newVal );
		// Sync to main WooCommerce quantity input
		$( '.quantity input.qty' ).val( newVal ).trigger( 'change' );
	} );

	// Sync main input back to sticky bar if changed elsewhere
	$( document ).on( 'change', '.quantity input.qty', function () {
		$( '.ts-sticky-qty-input' ).val( $( this ).val() );
	} );
}( jQuery ));
