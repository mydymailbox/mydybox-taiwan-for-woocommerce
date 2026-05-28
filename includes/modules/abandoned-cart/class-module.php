<?php
namespace Mydyma_TCS\Modules\Abandoned_Cart;

defined( 'ABSPATH' ) || exit;

class Module implements \Mydyma_TCS\Module {

	public function id(): string {
		return 'abandoned_cart';
	}

	public function boot(): void {
		if ( 'yes' !== get_option( 'mydyma_tcs_checkout_abandoned_cart', 'no' ) ) return;

		require_once __DIR__ . '/class-tracker.php';
		require_once __DIR__ . '/class-notifier.php';

		( new Tracker() )->boot();
		( new Notifier() )->boot();
	}

	public function is_admin_only(): bool {
		return false;
	}
}
