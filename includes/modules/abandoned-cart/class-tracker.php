<?php
namespace Mydybox\Modules\Abandoned_Cart;

defined( 'ABSPATH' ) || exit;

/**
 * Tracks abandoned carts by capturing email at checkout and scheduling reminders.
 */
class Tracker {

	const TABLE = 'mydybox_abandoned_carts';

	public function boot(): void {
		// Capture email as user types in checkout
		add_action( 'wp_ajax_mydybox_capture_checkout_email',        [ $this, 'ajax_capture' ] );
		add_action( 'wp_ajax_nopriv_mydybox_capture_checkout_email', [ $this, 'ajax_capture' ] );

		// Enqueue the JS that fires the capture AJAX
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );

		// Remove record when order is placed successfully
		add_action( 'woocommerce_checkout_order_created', [ $this, 'on_order_created' ] );

		// Scheduled event to trigger reminders
		add_action( 'mydybox_process_abandoned_carts', [ $this, 'process_abandoned' ] );
		if ( ! wp_next_scheduled( 'mydybox_process_abandoned_carts' ) ) {
			wp_schedule_event( time(), 'hourly', 'mydybox_process_abandoned_carts' );
		}

		// Create DB table if needed
		add_action( 'init', [ $this, 'maybe_create_table' ] );
	}

	public function maybe_create_table(): void {
		if ( get_option( 'mydybox_abandoned_cart_table_v1' ) ) return;

		global $wpdb;
		$table   = $wpdb->prefix . self::TABLE;
		$charset = $wpdb->get_charset_collate();

		// phpcs:disable WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name is always $wpdb->prefix . constant, cannot use prepare() for identifiers
		$wpdb->query( "CREATE TABLE IF NOT EXISTS {$table} (
			id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			email       VARCHAR(200)    NOT NULL,
			user_id     BIGINT UNSIGNED DEFAULT NULL,
			cart_data   LONGTEXT        NOT NULL,
			cart_total  DECIMAL(10,2)   DEFAULT 0,
			captured_at DATETIME        NOT NULL,
			reminded_at DATETIME        DEFAULT NULL,
			recovered   TINYINT(1)      DEFAULT 0,
			PRIMARY KEY (id),
			KEY email (email),
			KEY recovered (recovered)
		) {$charset};" );
		// phpcs:enable

		update_option( 'mydybox_abandoned_cart_table_v1', '1' );
	}

	public function enqueue_scripts(): void {
		if ( ! is_checkout() ) return;

		wp_enqueue_script(
			'ts-abandoned-cart',
			MYDYBOX_URL . 'assets/js/abandoned-cart.js',
			[ 'jquery' ],
			MYDYBOX_VERSION,
			true
		);
		wp_localize_script( 'ts-abandoned-cart', 'tsAbandonedCart', [
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'mydybox_abandoned_cart' ),
		] );
	}

	public function ajax_capture(): void {
		check_ajax_referer( 'mydybox_abandoned_cart', 'nonce' );

		$email = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
		if ( ! is_email( $email ) ) wp_send_json_error();

		global $wpdb;
		$table     = $wpdb->prefix . self::TABLE;
		$cart      = WC()->cart;
		$cart_data = $cart ? serialize( $cart->get_cart() ) : '';
		$total     = $cart ? $cart->get_total( 'edit' ) : 0;
		$user_id   = get_current_user_id() ?: null;

		// Upsert: update if email exists and not yet recovered
		// phpcs:disable WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name is $wpdb->prefix . constant
		$existing = $wpdb->get_var( $wpdb->prepare(
			"SELECT id FROM {$table} WHERE email = %s AND recovered = 0 LIMIT 1",
			$email
		) );

		if ( $existing ) {
			$wpdb->update( $table, [
				'cart_data'   => $cart_data,
				'cart_total'  => $total,
				'captured_at' => current_time( 'mysql' ),
				'reminded_at' => null,
			], [ 'id' => $existing ] );
		} else {
			$wpdb->insert( $table, [
				'email'       => $email,
				'user_id'     => $user_id,
				'cart_data'   => $cart_data,
				'cart_total'  => $total,
				'captured_at' => current_time( 'mysql' ),
			] );
		}

		// phpcs:enable
		wp_send_json_success();
	}

	public function on_order_created( \WC_Order $order ): void {
		global $wpdb;
		$table = $wpdb->prefix . self::TABLE;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- single row update, caching not applicable
		$wpdb->update( $table, [ 'recovered' => 1 ], [ 'email' => $order->get_billing_email() ] );
	}

	public function process_abandoned(): void {
		global $wpdb;
		$table   = $wpdb->prefix . self::TABLE;
		$minutes = (int) get_option( 'mydybox_abandoned_cart_delay', 60 );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name is $wpdb->prefix . constant
		$rows = $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM {$table}
			 WHERE recovered = 0
			   AND reminded_at IS NULL
			   AND captured_at <= %s
			 LIMIT 50",
			wp_date( 'Y-m-d H:i:s', time() - $minutes * 60 )
		) );

		// phpcs:enable
		foreach ( $rows as $row ) {
			do_action( 'mydybox_abandoned_cart_reminder', $row );
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- update single row, no cache needed
			$wpdb->update( $table, [ 'reminded_at' => current_time( 'mysql' ) ], [ 'id' => $row->id ] );
		}
	}
}
