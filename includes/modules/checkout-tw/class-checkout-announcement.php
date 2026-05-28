<?php
namespace Taiwan_Store_Core\Modules\Checkout_Tw;

defined( 'ABSPATH' ) || exit;

/**
 * Checkout Announcement Component.
 * Displays a custom notice at the top of the checkout form.
 */
class Checkout_Announcement {

	public function boot(): void {
		add_action( 'woocommerce_before_checkout_form', [ $this, 'render_announcement' ], 5 );
	}

	public function render_announcement(): void {
		if ( get_option( 'ts_checkout_announcement_enabled', 'no' ) !== 'yes' ) {
			return;
		}

		$text = get_option( 'ts_checkout_announcement_text', '' );
		if ( empty( $text ) ) {
			return;
		}

		?>
		<div class="taiwan-store-checkout-announcement" style="background: #fff9e6; border: 1px solid #ffe58f; border-radius: 8px; padding: 15px 20px; margin-bottom: 30px; display: flex; align-items: center; gap: 15px; color: #856404; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
			<div class="ts-announcement-icon" style="background: #fff; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
				<span class="dashicons dashicons-megaphone" style="color: #f59e0b; font-size: 20px; width: 20px; height: 20px;"></span>
			</div>
			<div class="ts-announcement-content" style="font-size: 14px; line-height: 1.6; font-weight: 500;">
				<?php echo wp_kses_post( wpautop( $text ) ); ?>
			</div>
		</div>
		<?php
	}
}
