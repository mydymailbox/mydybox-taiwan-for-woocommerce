<?php
namespace Mydyma_TCS\Rule_Engine\Conditions; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound -- Mydyma_TCS is the plugin prefix

use Mydyma_TCS\Rule_Engine\Condition;
use Mydyma_TCS\Rule_Engine\Context;

defined( 'ABSPATH' ) || exit;

/**
 * Checks whether the cart contains products from specific categories.
 * Ancestor categories are included in the match.
 *
 * Config:
 *   ['op' => 'contains'|'not_contains', 'categories' => int[]]
 */
class Category implements Condition {

	public function id(): string {
		return 'category';
	}

	public function label(): string {
		return __( 'Product Category', 'mydyma-taiwan-commerce-suite' );
	}

	public function type(): string {
		return 'category_select';
	}

	public function operators(): array {
		return [
			[ 'id' => 'contains',     'label' => __( 'In cart contains', 'mydyma-taiwan-commerce-suite' ) ],
			[ 'id' => 'not_contains', 'label' => __( 'In cart does not contain', 'mydyma-taiwan-commerce-suite' ) ],
		];
	}

	public function matches( Context $ctx, array $config ): bool {
		$cats = array_map( 'intval', (array) ( $config['categories'] ?? [] ) );
		if ( ! $cats ) {
			return false;
		}

		$op       = (string) ( $config['op'] ?? 'contains' );
		$in_cart  = $ctx->category_ids();
		$contains = (bool) array_intersect( $cats, $in_cart );

		return 'not_contains' === $op ? ! $contains : $contains;
	}
}

