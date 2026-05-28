<?php
namespace Mydyma_TCS\Rule_Engine\Actions;

use Mydyma_TCS\Rule_Engine\Action;
use Mydyma_TCS\Rule_Engine\Context;

defined( 'ABSPATH' ) || exit;

/**
 * Hide Payment Action.
 */
class Hide_Payment implements Action {

	public function id(): string { return 'hide_payment'; }
	public function label(): string { return __( 'Hide Payment Gateways', 'mydyma-taiwan-commerce-suite' ); }
	public function args(): array {
		return [
			[ 'id' => 'gateways', 'label' => __( 'Select gateways to hide', 'mydyma-taiwan-commerce-suite' ), 'type' => 'multiselect', 'source' => 'gateways' ]
		];
	}

	public function execute( Context $ctx, array $config, array &$payload ): void {
		$gateways = (array) ( $config['gateways'] ?? [] );
		foreach ( $gateways as $id ) {
			unset( $payload[ $id ] );
		}
	}
}