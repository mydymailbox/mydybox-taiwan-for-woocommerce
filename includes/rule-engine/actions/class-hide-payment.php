<?php
namespace Taiwan_Store_Core\Rule_Engine\Actions;

use Taiwan_Store_Core\Rule_Engine\Action;
use Taiwan_Store_Core\Rule_Engine\Context;

defined( 'ABSPATH' ) || exit;

/**
 * Hide Payment Action.
 */
class Hide_Payment implements Action {

	public function id(): string { return 'hide_payment'; }
	public function label(): string { return __( 'Hide Payment Gateways', 'taiwan-store-core' ); }
	public function args(): array {
		return [
			[ 'id' => 'gateways', 'label' => __( 'Select gateways to hide', 'taiwan-store-core' ), 'type' => 'multiselect', 'source' => 'gateways' ]
		];
	}

	public function execute( Context $ctx, array $config, array &$payload ): void {
		$gateways = (array) ( $config['gateways'] ?? [] );
		foreach ( $gateways as $id ) {
			unset( $payload[ $id ] );
		}
	}
}