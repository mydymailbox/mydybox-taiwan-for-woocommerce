<?php
namespace Taiwan_Store_Core\Modules\Checkout_Tw;

defined( 'ABSPATH' ) || exit;

/**
 * Checkout TW Module.
 * Coordinates locale, fields, validation, and meta handling for Taiwan checkout.
 */
class Module implements \Taiwan_Store_Core\Module {

	public function id(): string {
		return 'checkout_tw';
	}

	public function boot(): void {
		// Manually require sub-components since we are not using a full autoloader yet
		require_once __DIR__ . '/class-locale.php';
		require_once __DIR__ . '/class-fields.php';
		require_once __DIR__ . '/class-validation.php';
		require_once __DIR__ . '/class-order-meta.php';
		require_once __DIR__ . '/class-order-ui.php';
		require_once __DIR__ . '/class-invoice-export.php';
		require_once __DIR__ . '/class-checkout-countdown.php';
		require_once __DIR__ . '/class-checkout-announcement.php';
		require_once __DIR__ . '/class-abandoned-cart.php';
		require_once __DIR__ . '/class-cvs-shipping.php';
		require_once __DIR__ . '/class-newebpay-cvs-shipping.php';

		( new Locale() )->boot();
		( new Fields() )->boot();
		( new Validation() )->boot();
		( new Order_Meta() )->boot();
		( new Order_UI() )->boot();
		( new Invoice_Export() )->boot();
		( new Checkout_Countdown() )->boot();
		( new Checkout_Announcement() )->boot();
		( new Abandoned_Cart() )->boot();
		( new CVS_Shipping() )->boot();
		( new NewebPay_CVS_Shipping() )->boot();

		// Register Blocks integration
		add_action( 'woocommerce_blocks_loaded', [ $this, 'register_blocks_integration' ] );
	}

	public function register_blocks_integration(): void {
		if ( class_exists( '\Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry' ) ) {
			add_action( 'woocommerce_blocks_checkout_block_registration', function( $registry ) {
				require_once __DIR__ . '/class-blocks-integration.php';
				$registry->register( new Blocks_Integration() );
			} );
		}
	}

	public function is_admin_only(): bool {
		return false;
	}
}
