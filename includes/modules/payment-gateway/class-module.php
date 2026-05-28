<?php
namespace Mydybox\Modules\Payment_Gateway;

defined( 'ABSPATH' ) || exit;

class Module implements \Mydybox\Module {

	public function id(): string {
		return 'payment_gateway';
	}

	public function boot(): void {
		add_filter( 'woocommerce_payment_gateways', [ $this, 'register_gateway' ] );
	}

	public function register_gateway( array $gateways ): array {
		require_once __DIR__ . '/class-ecpay-gateway.php';
		$gateways[] = '\Mydybox\Modules\Payment_Gateway\ECPay_Gateway';
		return $gateways;
	}

	public function is_admin_only(): bool {
		return false;
	}
}
