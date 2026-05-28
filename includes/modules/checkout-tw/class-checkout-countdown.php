<?php
namespace Taiwan_Store_Core\Modules\Checkout_Tw;

defined( 'ABSPATH' ) || exit;

/**
 * Checkout Countdown Module.
 * Displays a reservation timer on the checkout page to increase urgency.
 */
class Checkout_Countdown {

	public function boot(): void {
		if ( 'yes' !== get_option( 'ts_checkout_countdown', 'no' ) ) {
			return;
		}
		add_action( 'woocommerce_before_checkout_form', [ $this, 'display_countdown_timer' ], 5 );
		add_action( 'wp_head', [ $this, 'output_countdown_css' ] );
	}

	public function display_countdown_timer(): void {
		$minutes = (int) get_option( 'ts_checkout_countdown_minutes', 15 );
		?>
		<div id="taiwan-store-core-checkout-timer" class="taiwan-store-core-timer-banner">
			<div class="taiwan-store-core-timer-inner">
				<span class="dashicons dashicons-clock" style="color:#b45309; font-size:20px; width:20px; height:20px; line-height:20px; margin-right:8px;"></span>
				<span class="taiwan-store-core-timer-text">
					<?php
					printf(
						// translators: %1$s is reserved minutes count, %2$s is countdown clock HTML span
						esc_html__( '訂單已為您保留 %1$s 分鐘。請在 %2$s 內完成結帳以確保商品保留。', 'taiwan-store-core' ),
						esc_html( $minutes ),
						'<span id="taiwan-store-core-countdown-clock">' . esc_html( sprintf( '%02d:00', $minutes ) ) . '</span>'
					); ?>
				</span>
			</div>
		</div>

		<script>
			(function($) {
				var minutes = <?php echo (int) $minutes; ?>;
				var seconds = 0;
				var timer = setInterval(function() {
					if (seconds === 0) {
						if (minutes === 0) {
							clearInterval(timer);
							$('#taiwan-store-core-checkout-timer').addClass('is-expired').find('.taiwan-store-core-timer-text').text('<?php echo esc_js( __( '保留時間已到期。請盡快完成您的結帳。', 'taiwan-store-core' ) ); ?>');
							return;
						}
						minutes--;
						seconds = 59;
					} else {
						seconds--;
					}
					var timeStr = (minutes < 10 ? '0' + minutes : minutes) + ':' + (seconds < 10 ? '0' + seconds : seconds);
					$('#taiwan-store-core-countdown-clock').text(timeStr);
				}, 1000);
			})(jQuery);
		</script>
		<?php
	}

	public function output_countdown_css(): void {
		if ( ! is_checkout() ) return;
		?>
		<style>
			.taiwan-store-core-timer-banner { background: #fffbeb; border: 1px solid #fde68a; border-radius: 12px; padding: 15px 20px; margin-bottom: 25px; }
			.taiwan-store-core-timer-inner { display: flex; align-items: center; gap: 12px; }
			.taiwan-store-core-timer-icon { font-size: 20px; }
			.taiwan-store-core-timer-text { font-size: 14px; color: #92400e; font-weight: 500; }
			#taiwan-store-core-countdown-clock { font-family: monospace; font-weight: 700; color: #b45309; background: rgba(251,191,36,0.2); padding: 2px 6px; border-radius: 4px; }
			.taiwan-store-core-timer-banner.is-expired { background: #fef2f2; border-color: #fecaca; }
			.taiwan-store-core-timer-banner.is-expired .taiwan-store-core-timer-text { color: #991b1b; }
		</style>
		<?php
	}
}