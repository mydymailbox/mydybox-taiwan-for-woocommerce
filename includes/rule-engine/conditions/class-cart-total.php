<?php
namespace Taiwan_Store_Core\Rule_Engine\Conditions;

use Taiwan_Store_Core\Rule_Engine\Condition;
use Taiwan_Store_Core\Rule_Engine\Context;

defined( 'ABSPATH' ) || exit;

/**
 * Cart Total Condition.
 */
class Cart_Total implements Condition {

	public function id(): string { return 'cart_total'; }
	public function label(): string { return __( 'Cart Subtotal', 'taiwan-store-core' ); }
	public function type(): string { return 'number'; }
	public function operators(): array {
		return [
			[ 'id' => 'gte', 'label' => __( 'Greater than or equal (>=)', 'taiwan-store-core' ) ],
			[ 'id' => 'lte', 'label' => __( 'Less than or equal (<=)', 'taiwan-store-core' ) ],
			[ 'id' => 'eq',  'label' => __( 'Equal to (=)', 'taiwan-store-core' ) ],
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