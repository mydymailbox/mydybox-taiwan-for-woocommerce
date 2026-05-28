<?php
namespace Taiwan_Store_Core\Modules\Cart_Rules;

defined( 'ABSPATH' ) || exit;

/**
 * Cart Rules Module.
 * Enforces purchase restrictions based on rule engine conditions.
 */
class Module implements \Taiwan_Store_Core\Module {
	public function id(): string { return 'cart_rules'; }
	public function boot(): void {
		add_action( 'woocommerce_check_cart_items', [ $this, 'validate_cart' ] );
		add_action( 'woocommerce_after_checkout_validation', [ $this, 'validate_checkout' ], 10, 2 );
	}
	public function is_admin_only(): bool { return false; }

	public function validate_cart(): void {
		if ( is_admin() || ! is_cart() ) return;
		$this->run_engine();
	}

	public function validate_checkout( $data, $errors ): void {
		$this->run_engine( $errors );
	}

	private function run_engine( $errors = null ): void {
		$engine = \Taiwan_Store_Core\Rule_Engine\Rule_Engine::instance();
		$rules  = get_option( 'taiwan_store_core_rules_cart_rules', [] );
		if ( empty( $rules ) ) return;

		$ctx = new \Taiwan_Store_Core\Rule_Engine\Context();
		$actions = $engine->evaluate_rules( $rules, $ctx->get_data() );

		foreach ( $actions as $action_list ) {
			foreach ( $action_list as $action ) {
				if ( $action['type'] === 'block_checkout' ) {
					$msg = $action['message'] ?: __( 'Checkout is currently blocked due to purchase restrictions.', 'taiwan-store-core' );
					if ( $errors ) {
						$errors->add( 'rule_block', $msg );
					} else {
						wc_add_notice( $msg, 'error' );
					}
				}
			}
		}
	}
}