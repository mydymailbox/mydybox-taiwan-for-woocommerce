<?php
namespace Mydybox\Modules\Logs;

defined( 'ABSPATH' ) || exit;

class Module implements \Mydybox\Module {

	private static bool $debug = false;

	public function id(): string {
		return 'logs';
	}

	public function boot(): void {
		self::$debug = 'yes' === get_option( 'mydybox_debug', 'no' );
		if ( is_admin() ) {
			add_action( 'wp_ajax_mydybox_get_stats', [ $this, 'ajax_get_stats' ] );
		}
	}

	public function is_admin_only(): bool { return false; }

	public function ajax_get_stats(): void {
		check_ajax_referer( 'mydybox_admin', 'nonce' );
		if ( ! current_user_can( 'manage_woocommerce' ) ) wp_send_json_error( 'Forbidden' );

		$today_start = gmdate( 'Y-m-d 00:00:00' );
		$today_end   = gmdate( 'Y-m-d 23:59:59' );

		$args = [ 'limit' => -1, 'date_created' => $today_start . '...' . $today_end, 'return' => 'ids' ];
		$order_ids = wc_get_orders( $args );

		$stats = [ 'personal' => 0, 'carrier_phone' => 0, 'carrier_cert' => 0, 'donate' => 0, 'company' => 0, 'none' => 0 ];

		foreach ( $order_ids as $id ) {
			$order = wc_get_order( $id );
			$type  = $order->get_meta( '_taiwan_store_core/invoice-type' );
			if ( $type && isset( $stats[ $type ] ) ) { $stats[ $type ]++; } else { $stats['none']++; }
		}

		wp_send_json_success( [ 'date' => gmdate( 'Y-m-d' ), 'total' => count( $order_ids ), 'stats' => $stats ] );
	}

	public static function info( string $msg, array $ctx = [] ): void {
		if ( ! self::$debug ) return;
		wc_get_logger()->info( $msg, array_merge( [ 'source' => 'mydybox-taiwan-for-woocommerce' ], $ctx ) );
	}

	public static function error( string $msg, array $ctx = [] ): void {
		wc_get_logger()->error( $msg, array_merge( [ 'source' => 'mydybox-taiwan-for-woocommerce' ], $ctx ) );
	}
}