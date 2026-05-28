/**
 * Admin Manual — smooth scroll for TOC links.
 */
document.querySelectorAll( '.ts-manual-toc-link' ).forEach( function ( link ) {
	link.addEventListener( 'click', function ( e ) {
		e.preventDefault();
		var target = document.querySelector( this.getAttribute( 'href' ) );
		if ( target ) target.scrollIntoView( { behavior: 'smooth', block: 'start' } );
	} );
} );
