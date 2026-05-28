<?php
namespace Mydybox\Modules\Checkout_Tw;

defined( 'ABSPATH' ) || exit;

/**
 * Checkout Countdown Module.
 * Displays a reservation timer on the checkout page to increase urgency.
 */
class Checkout_Countdown {

	public function boot(): void {
		if ( 'yes' !== get_option( 'mydybox_checkout_countdown', 'no' ) ) {
			return;
		}
		add_action( 'woocommerce_before_checkout_form', [ $this, 'display_countdown_timer' ], 5 );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );
	}

	public function enqueue_assets(): void {
		if ( ! function_exists( 'is_checkout' ) || ! is_checkout() ) return;

		wp_enqueue_style(
			'mydyma-tcs-checkout-countdown',
			MYDYBOX_URL . 'assets/css/checkout-countdown.css',
			[],
			MYDYBOX_VERSION
		);
		wp_enqueue_script(
			'mydyma-tcs-checkout-countdown',
			MYDYBOX_URL . 'assets/js/checkout-countdown.js',
			[ 'jquery' ],
			MYDYBOX_VERSION,
			true
		);
		wp_localize_script( 'mydyma-tcs-checkout-countdown', 'mydyboxCountdown', [
			'minutes'    => (int) get_option( 'mydybox_checkout_countdown_minutes', 15 ),
			'expiredMsg' => __( '保留時間已到期。請盡快完成您的結帳。', 'mydybox-taiwan-for-woocommerce' ),
		] );
	}

	public function display_countdown_timer(): void {
		$minutes = (int) get_option( 'mydybox_checkout_countdown_minutes', 15 );
		?>
		<div id="mydybox-taiwan-for-woocommerce-checkout-timer" class="mydybox-taiwan-for-woocommerce-timer-banner">
			<div class="mydybox-taiwan-for-woocommerce-timer-inner">
				<span class="dashicons dashicons-clock" style="color:#b45309; font-size:20px; width:20px; height:20px; line-height:20px; margin-right:8px;"></span>
				<span class="mydybox-taiwan-for-woocommerce-timer-text">
					<?php
					printf(
						// translators: %1$s is reserved minutes count, %2$s is countdown clock HTML span
						esc_html__( '訂單已為您保留 %1$s 分鐘。請在 %2$s 內完成結帳以確保商品保留。', 'mydybox-taiwan-for-woocommerce' ),
						esc_html( $minutes ),
						'<span id="mydybox-taiwan-for-woocommerce-countdown-clock">' . esc_html( sprintf( '%02d:00', $minutes ) ) . '</span>'
					); ?>
				</span>
			</div>
		</div>
		<?php
	}
}
