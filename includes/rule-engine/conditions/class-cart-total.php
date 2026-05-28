<?php
namespace Mydyma_TCS\Rule_Engine\Conditions;

use Mydyma_TCS\Rule_Engine\Condition;
use Mydyma_TCS\Rule_Engine\Context;

defined( 'ABSPATH' ) || exit;

/**
 * Cart Total Condition.
 */
class Cart_Total implements Condition {

	public function id(): string { return 'cart_total'; }
	public function label(): string { return __( 'Cart Subtotal', 'mydyma-taiwan-commerce-suite' ); }
	public function type(): string { return 'number'; }
	public function operators(): array {
		return [
			[ 'id' => 'gte', 'label' => __( 'Greater than or equal (>=)', 'mydyma-taiwan-commerce-suite' ) ],
			[ 'id' => 'lte', 'label' => __( 'Less than or equal (<=)', 'mydyma-taiwan-commerce-suite' ) ],
			[ 'id' => 'eq',  'label' => __( 'Equal to (=)', 'mydyma-taiwan-commerce-suite' ) ],
		];
	}

	public function matches( Context $ctx, array $config ): bool {
		$op     = (string) ( $config['op'] ?? 'gte' );
		$amount = (float) ( $config['amount'] ?? 0 );
		$total  = $ctx->cart_total();

		switch ( $op ) {
			case 'gte': return $total >= $amount;
			case 'lte': return $total <= $amount;
			case 'eq':  return abs( $total - $amount ) < 0.001;
			default:    return $total >= $amount;
		}
	}
}