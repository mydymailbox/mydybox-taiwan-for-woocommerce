<?php
namespace Mydybox\Modules\Shipping_Rules;

defined( 'ABSPATH' ) || exit;

class Module implements \Mydybox\Module {
	public function id(): string { return 'shipping_rules'; }
	public function boot(): void {
		add_filter( 'woocommerce_package_rates', [ $this, 'filter_rates' ], 100, 2 );
	}
	public function is_admin_only(): bool { return false; }

	public function filter_rates( array $rates, array $package ): array {
		$engine = \Mydybox\Rule_Engine\Rule_Engine::instance();
		$rules  = get_option( 'mydybox_rules_shipping_rules', [] );
		if ( empty( $rules ) ) return $rates;

		$ctx = new \Mydybox\Rule_Engine\Context();
		$ctx->set_package( $package );
		$actions = $engine->evaluate_rules( $rules, $ctx->get_data() );

		foreach ( $actions as $action_list ) {
			foreach ( $action_list as $action ) {
				if ( $action['type'] === 'hide_shipping' ) {
					foreach ( (array) $action['methods'] as $id ) unset( $rates[ $id ] );
				}
			}
		}
		return $rates;
	}
}