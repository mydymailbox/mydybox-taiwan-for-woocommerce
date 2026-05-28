<?php
namespace Mydyma_TCS\Modules\Payment_Rules;

defined( 'ABSPATH' ) || exit;

class Module implements \Mydyma_TCS\Module {
	public function id(): string { return 'payment_rules'; }
	public function boot(): void {
		add_filter( 'woocommerce_available_payment_gateways', [ $this, 'filter_gateways' ], 100 );
	}
	public function is_admin_only(): bool { return false; }

	public function filter_gateways( array $gateways ): array {
		if ( is_admin() && ! defined( 'DOING_AJAX' ) ) return $gateways;
		$engine = \Mydyma_TCS\Rule_Engine\Rule_Engine::instance();
		$rules  = get_option( 'mydyma_tcs_rules_payment_rules', [] );
		if ( empty( $rules ) ) return $gateways;

		$ctx = new \Mydyma_TCS\Rule_Engine\Context();
		$actions = $engine->evaluate_rules( $rules, $ctx->get_data() );

		foreach ( $actions as $action_list ) {
			foreach ( $action_list as $action ) {
				if ( $action['type'] === 'hide_payment' ) {
					foreach ( (array) $action['gateways'] as $id ) unset( $gateways[ $id ] );
				}
			}
		}
		return $gateways;
	}
}