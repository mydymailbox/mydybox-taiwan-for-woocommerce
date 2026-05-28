<?php
namespace Taiwan_Store_Core\Modules\Checkout_Tw;

defined( 'ABSPATH' ) || exit;

/**
 * Abandoned Cart recovery via LINE notification.
 */
class Abandoned_Cart {

	private const META_KEY = '_taiwan_store_core_last_cart_activity';

	public function boot(): void {
		if ( 'yes' !== get_option( 'ts_checkout_abandoned_cart', 'no' ) ) {
			return;
		}

		add_action( 'woocommerce_add_to_cart', [ $this, 'update_cart_activity' ] );
		add_action( 'woocommerce_cart_item_removed', [ $this, 'update_cart_activity' ] );

		if ( ! wp_next_scheduled( 'taiwan_store_core_check_abandoned_carts' ) ) {
			wp_schedule_event( time(), 'hourly', 'taiwan_store_core_check_abandoned_carts' );
		}
		add_action( 'taiwan_store_core_check_abandoned_carts', [ $this, 'process_abandoned_carts' ] );
	}

	public function update_cart_activity(): void {
		$user_id = get_current_user_id();
		if ( ! $user_id ) return;

		$line_user_id = get_user_meta( $user_id, '_taiwan_store_core_line_user_id', true );
		if ( ! $line_user_id ) return;

		update_user_meta( $user_id, self::META_KEY, time() );
	}

	public function process_abandoned_carts(): void {
		$one_hour_ago = time() - HOUR_IN_SECONDS;
		$one_day_ago  = time() - DAY_IN_SECONDS;

		$users = get_users( [
			'meta_query' => [
				'relation' => 'AND',
				[
					'key'     => self::META_KEY,
					'value'   => [ $one_day_ago, $one_hour_ago ],
					'compare' => 'BETWEEN',
					'type'    => 'NUMERIC',
				],
				[
					'key'     => '_taiwan_store_core_line_user_id',
					'compare' => 'EXISTS',
				],
				[
					'key'     => '_taiwan_store_core_abandoned_notified',
					'compare' => 'NOT EXISTS',
				],
			],
		] );

		if ( empty( $users ) ) return;

		foreach ( $users as $user ) {
			$this->send_recovery_message( $user );
			update_user_meta( $user->ID, '_taiwan_store_core_abandoned_notified', time() );
		}
	}

	private function send_recovery_message( \WP_User $user ): void {
		$line_user_id = get_user_meta( $user->ID, '_taiwan_store_core_line_user_id', true );
		if ( ! $line_user_id ) return;

		$checkout_url = wc_get_checkout_url();
		$site_name    = get_bloginfo( 'name' );

		$message = sprintf(
			"Hi %s,\n\n%s\n\n%s\n%s",
			$user->display_name,
			__( 'You still have items left in your cart!', 'taiwan-store-core' ),
			__( 'Complete your purchase now:', 'taiwan-store-core' ),
			$checkout_url
		);

		$this->trigger_line_notification( $line_user_id, $message );
	}

	private function trigger_line_notification( string $to, string $message ): void {
		$token = get_option( 'ts_social_line_token' );
		if ( ! $token ) return;

		wp_remote_post( 'https://api.line.me/v2/bot/message/push', [
			'headers' => [
				'Content-Type'  => 'application/json',
				'Authorization' => 'Bearer ' . $token,
			],
			'body' => wp_json_encode( [
				'to'       => $to,
				'messages' => [
					[ 'type' => 'text', 'text' => $message ],
				],
			] ),
		] );
	}
}