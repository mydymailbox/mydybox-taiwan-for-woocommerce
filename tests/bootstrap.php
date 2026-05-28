<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- test stubs intentionally use core WP function names
// phpcs:disable WordPress.Security.ValidatedSanitizedInput -- test bootstrap runs outside WP context
/**
 * PHPUnit bootstrap for taiwan-store-core unit tests.
 * Stubs out WordPress and WooCommerce globals so tests run without a full WP install.
 */

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/../' );
}
define( 'TAIWAN_STORE_CORE_DIR', __DIR__ . '/../' );
define( 'TAIWAN_STORE_CORE_URL', 'https://example.com/' );
define( 'TAIWAN_STORE_CORE_VERSION', '1.0.0-test' );

// ── WordPress function stubs ──────────────────────────────────────────────────

if ( ! function_exists( 'add_filter' ) ) {
	function add_filter() {}
	function add_action() {}
	function apply_filters( $tag, $value ) { return $value; }
	function do_action() {}
	function get_option( $key, $default = false ) { return $default; }
	function update_option() {}
	function absint( $val ) { return abs( (int) $val ); }
	function wp_strip_all_tags( $str ) { return preg_replace( '/<[^>]*>/', '', $str ); }
	function sanitize_text_field( $str ) { return trim( wp_strip_all_tags( $str ) ); }
	function esc_html( $str ) { return htmlspecialchars( (string) $str, ENT_QUOTES ); }
	function __( $text ) { return $text; }
	function wp_die( $msg ) { throw new \RuntimeException( esc_html( $msg ) ); }
}
// phpcs:enable

// ── Autoload plugin classes ───────────────────────────────────────────────────
spl_autoload_register( function ( $class ) {
	// Only handle Taiwan_Store_Core namespace
	if ( strpos( $class, 'Taiwan_Store_Core\\' ) !== 0 ) return;

	$relative = str_replace( 'Taiwan_Store_Core\\', '', $class );
	$parts    = explode( '\\', $relative );

	// Convert namespace segments to path
	$file_parts = array_map( function( $part ) {
		return strtolower( preg_replace( '/([A-Z])/', '-$1', lcfirst( $part ) ) );
	}, $parts );

	$file = TAIWAN_STORE_CORE_DIR . 'includes/' . implode( '/', $file_parts ) . '.php';

	// Try class-{name}.php pattern
	$last     = array_pop( $file_parts );
	$dir      = TAIWAN_STORE_CORE_DIR . 'includes/' . ( $file_parts ? implode( '/', $file_parts ) . '/' : '' );
	$alt_file = $dir . 'class-' . $last . '.php';

	if ( file_exists( $alt_file ) ) {
		require_once $alt_file;
	} elseif ( file_exists( $file ) ) {
		require_once $file;
	}
} );
