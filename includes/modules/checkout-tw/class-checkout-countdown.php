<?php
namespace Mydyma_TCS\Modules\Checkout_Tw;

defined( 'ABSPATH' ) || exit;

/**
 * Checkout Countdown Module.
 * Displays a reservation timer on the checkout page to increase urgency.
 */
class Checkout_Countdown {

	public function boot(): void {
		if ( 'yes' !== get_option( 'mydyma_tcs_checkout_countdown', 'no' ) ) {
			return;
		}
		add_action( 'woocommerce_before_checkout_form', [ $this, 'display_countdown_timer' ], 5 );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );
	}

	public function enqueue_assets(): void {
		if ( ! function_exists( 'is_checkout' ) || ! is_checkout() ) return;

		wp_enqueue_style(
			'mydyma-tcs-checkout-countdown',
			MYDYMA_TCS_URL . 'assets/css/checkout-countdown.css',
			[],
			MYDYMA_TCS_VERSION
		);
		wp_enqueue_script(
			'mydyma-tcs-checkout-countdown',
			MYDYMA_TCS_URL . 'assets/js/checkout-countdown.js',
			[ 'jquery' ],
			MYDYMA_TCS_VERSION,
			true
		);
		wp_localize_script( 'mydyma-tcs-checkout-countdown', 'mydymaTcsCountdown', [
			'minutes'    => (int) get_option( 'mydyma_tcs_checkout_countdown_minutes', 15 ),
			'expiredMsg' => __( '保留時間已到期。請盡快完成您的結帳。', 'mydyma-taiwan-commerce-suite' ),
		] );
	}

	public function display_countdown_timer(): void {
		$minutes = (int) get_option( 'mydyma_tcs_checkout_countdown_minutes', 15 );
		?>
		<div id="mydyma-taiwan-commerce-suite-checkout-timer" class="mydyma-taiwan-commerce-suite-timer-banner">
			<div class="mydyma-taiwan-commerce-suite-timer-inner">
				<span class="dashicons dashicons-clock" style="color:#b45309; font-size:20px; width:20px; height:20px; line-height:20px; margin-right:8px;"></span>
				<span class="mydyma-taiwan-commerce-suite-timer-text">
					<?php
					printf(
						// translators: %1$s is reserved minutes count, %2$s is countdown clock HTML span
						esc_html__( '訂單已為您保留 %1$s 分鐘。請在 %2$s 內完成結帳以確保商品保留。', 'mydyma-taiwan-commerce-suite' ),
						esc_html( $minutes ),
						'<span id="mydyma-taiwan-commerce-suite-countdown-clock">' . esc_html( sprintf( '%02d:00', $minutes ) ) . '</span>'
					); ?>
				</span>
			</div>
		</div>
		<?php
	}
}
