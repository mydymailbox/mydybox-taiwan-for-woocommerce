<?php
namespace Taiwan_Store_Core\Modules\Invoice;

defined( 'ABSPATH' ) || exit;

class Module implements \Taiwan_Store_Core\Module {

	public function id(): string {
		return 'invoice';
	}

	public function boot(): void {
		require_once __DIR__ . '/class-ecpay-invoice.php';
		( new ECPay_Invoice() )->boot();
	}

	public function is_admin_only(): bool {
		return false;
	}
}
