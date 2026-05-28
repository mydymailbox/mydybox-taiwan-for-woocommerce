<?php
namespace Mydybox\Helpers;

defined( 'ABSPATH' ) || exit;

class Nonce {

	public static function verify( string $action, string $key = '_wpnonce' ): void {
		$nonce = sanitize_text_field( wp_unslash( $_REQUEST[ $key ] ?? '' ) );
		if ( ! wp_verify_nonce( $nonce, $action ) ) {
			wp_die( esc_html__( 'Security verification failed. Please refresh the page and try again.', 'mydybox-taiwan-for-woocommerce' ), 403 );
		}
	}

	public static function verify_ajax( string $action, string $key = 'nonce' ): void {
		if ( ! check_ajax_referer( $action, $key, false ) ) {
			wp_send_json_error( [ 'message' => __( 'Security verification failed', 'mydybox-taiwan-for-woocommerce' ) ], 403 );
		}
	}
}