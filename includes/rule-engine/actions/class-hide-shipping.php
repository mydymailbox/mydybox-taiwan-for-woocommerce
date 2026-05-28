<?php
namespace Mydybox\Rule_Engine\Actions; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound -- Mydybox is the plugin prefix

use Mydybox\Rule_Engine\Action;
use Mydybox\Rule_Engine\Context;

defined( 'ABSPATH' ) || exit;

/**
 * Removes specified shipping rates from the available rates array.
 * Used on the woocommerce_package_rates filter.
 *
 * Config:
 *   ['methods' => string[]]  ??list of rate IDs to hide (e.g. ['flat_rate:1', 'free_shipping:1'])
 */
class Hide_Shipping implements Action {

	public function id(): string {
		return 'hide_shipping';
	}

	public function label(): string {
		return __( 'Hide Shipping Methods', 'mydybox-taiwan-for-woocommerce' );
	}

	public function args(): array {
		return [
			[ 'id' => 'methods', 'label' => __( 'Select shipping methods to hide', 'mydybox-taiwan-for-woocommerce' ), 'type' => 'multiselect', 'source' => 'shipping_methods' ]
		];
	}

	public function execute( Context $ctx, array $config, array &$payload ): void {
		$methods = (array) ( $config['methods'] ?? [] );
		foreach ( $methods as $rate_id ) {
			unset( $payload[ $rate_id ] );
		}
	}
}

