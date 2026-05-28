<?php
namespace Mydyma_TCS\Rule_Engine\Conditions; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound -- Mydyma_TCS is the plugin prefix

use Mydyma_TCS\Rule_Engine\Condition;
use Mydyma_TCS\Rule_Engine\Context;

defined( 'ABSPATH' ) || exit;

/**
 * Checks whether specific products are (or are not) in the cart.
 *
 * Config:
 *   ['op' => 'in'|'not_in', 'products' => int[]]
 *   - 'in'     : at least one of the listed product IDs is in cart
 *   - 'not_in' : none of the listed product IDs is in cart
 */
class Product implements Condition {

	public function id(): string {
		return 'product';
	}

	public function label(): string {
		return __( 'Specific Product', 'mydyma-taiwan-commerce-suite' );
	}

	public function type(): string {
		return 'product_select';
	}

	public function operators(): array {
		return [
			[ 'id' => 'in',     'label' => __( 'In cart contains', 'mydyma-taiwan-commerce-suite' ) ],
			[ 'id' => 'not_in', 'label' => __( 'In cart does not contain', 'mydyma-taiwan-commerce-suite' ) ],
		];
	}

	public function matches( Context $ctx, array $config ): bool {
		$required = array_map( 'intval', (array) ( $config['products'] ?? [] ) );
		if ( ! $required ) {
			return false;
		}

		$op      = (string) ( $config['op'] ?? 'in' );
		$in_cart = $ctx->product_ids();
		$has     = (bool) array_intersect( $required, $in_cart );

		return 'not_in' === $op ? ! $has : $has;
	}
}

