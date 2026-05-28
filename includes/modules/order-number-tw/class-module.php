<?php
namespace Taiwan_Store_Core\Modules\Order_Number_Tw;

defined( 'ABSPATH' ) || exit;

/**
 * Taiwan Style Order Number Module.
 * Supports YYYYMMDD-NNNN format for better order management.
 */
class Module implements \Taiwan_Store_Core\Module {

	private const META_KEY = '_taiwan_store_core_order_number';
	private const SEQ_OPT  = 'taiwan_store_core_order_seq_';

	public function id(): string {
		return 'order_number_tw';
	}

	public function boot(): void {
		add_action( 'woocommerce_new_order', [ $this, 'assign_number' ], 10, 2 );
		add_filter( 'woocommerce_order_number', [ $this, 'filter_display' ], 10, 2 );
	}

	public function is_admin_only(): bool {
		return false;
	}

	private function enabled(): bool {
		return 'yes' === get_option( 'ts_custom_order_number_enabled', 'yes' );
	}

	/**
	 * Assign custom number to new orders.
	 */
	public function assign_number( int $order_id, $order = null ): void {
		if ( ! $this->enabled() ) return;
		if ( ! $order instanceof \WC_Order ) $order = wc_get_order( $order_id );
		if ( ! $order || $order->get_meta( self::META_KEY ) ) return;

		$date    = wp_date( 'Ymd' );
		$prefix  = (string) get_option( 'ts_order_number_prefix', 'TW' );
		$padding = max( 1, (int) get_option( 'ts_order_number_digits', 4 ) );

		$seq_key = self::SEQ_OPT . $date;
		$seq     = (int) get_option( $seq_key, 0 ) + 1;
		update_option( $seq_key, $seq );

		$random = '';
		if ( get_option( 'ts_order_number_random_suffix', 'no' ) === 'yes' ) {
			$random = '-' . strtoupper( wp_generate_password( 2, false, false ) );
		}

		$new_number = $prefix . $date . '-' . str_pad( (string) $seq, $padding, '0', STR_PAD_LEFT ) . $random;
		$order->update_meta_data( self::META_KEY, $new_number );
		$order->save_meta_data();
	}

	/**
	 * Filter WooCommerce order number for display.
	 */
	public function filter_display( string $number, \WC_Order $order ): string {
		if ( ! $this->enabled() ) return $number;
		$custom = $order->get_meta( self::META_KEY );
		return $custom ?: $number;
	}
}