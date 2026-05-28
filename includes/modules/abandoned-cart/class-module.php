<?php
namespace Taiwan_Store_Core\Modules\Abandoned_Cart;

defined( 'ABSPATH' ) || exit;

class Module implements \Taiwan_Store_Core\Module {

	public function id(): string {
		return 'abandoned_cart';
	}

	public function boot(): void {
		if ( 'yes' !== get_option( 'ts_checkout_abandoned_cart', 'no' ) ) return;

		require_once __DIR__ . '/class-tracker.php';
		require_once __DIR__ . '/class-notifier.php';

		( new Tracker() )->boot();
		( new Notifier() )->boot();
	}

	public function is_admin_only(): bool {
		return false;
	}
}
