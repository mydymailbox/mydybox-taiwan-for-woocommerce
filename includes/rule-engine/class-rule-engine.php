<?php
namespace Taiwan_Store_Core\Rule_Engine;

defined( 'ABSPATH' ) || exit;

/**
 * Rule Engine Core.
 * Evaluates rules based on conditions and triggers actions.
 */
class Rule_Engine {

	private static ?Rule_Engine $instance = null;
	private array $conditions = [];
	private array $actions = [];
	private array $condition_instances = [];
	private array $action_instances = [];

	private function __construct() {
		$this->load_core_components();
	}

	public static function instance(): self {
		if ( null === self::$instance ) {
			try {
				self::$instance = new self();
			} catch ( \Throwable $e ) {
				throw $e;
			}
		}
		return self::$instance;
	}

	private function load_core_components(): void {
		// Load Core Engine Classes
		require_once __DIR__ . '/class-context.php';
		require_once __DIR__ . '/interface-condition.php';
		require_once __DIR__ . '/interface-action.php';

		// Load common conditions
		require_once __DIR__ . '/conditions/class-address.php';
		require_once __DIR__ . '/conditions/class-cart-total.php';
		require_once __DIR__ . '/conditions/class-max-qty.php';
		require_once __DIR__ . '/conditions/class-category.php';
		require_once __DIR__ . '/conditions/class-product.php';

		$this->register_condition( new \Taiwan_Store_Core\Rule_Engine\Conditions\Address() );
		$this->register_condition( new \Taiwan_Store_Core\Rule_Engine\Conditions\Cart_Total() );
		$this->register_condition( new \Taiwan_Store_Core\Rule_Engine\Conditions\Max_Qty() );
		$this->register_condition( new \Taiwan_Store_Core\Rule_Engine\Conditions\Category() );
		$this->register_condition( new \Taiwan_Store_Core\Rule_Engine\Conditions\Product() );

		// Load common actions
		require_once __DIR__ . '/actions/class-hide-payment.php';
		require_once __DIR__ . '/actions/class-hide-shipping.php';
		require_once __DIR__ . '/actions/class-block-checkout.php';

		$this->register_action( new \Taiwan_Store_Core\Rule_Engine\Actions\Hide_Payment() );
		$this->register_action( new \Taiwan_Store_Core\Rule_Engine\Actions\Hide_Shipping() );
		$this->register_action( new \Taiwan_Store_Core\Rule_Engine\Actions\Block_Checkout() );

		// Allow other modules to register components
		do_action( 'taiwan_store_core_register_rule_components', $this );
	}

	public function register_condition( Condition $condition ): void {
		$this->condition_instances[ $condition->id() ] = $condition;
		$this->conditions[ $condition->id() ] = [
			'id'    => $condition->id(),
			'label' => $condition->label(),
			'type'  => $condition->type(),
			'ops'   => $condition->operators(),
		];
	}

	public function register_action( Action $action ): void {
		$this->action_instances[ $action->id() ] = $action;
		$this->actions[ $action->id() ] = [
			'id'    => $action->id(),
			'label' => $action->label(),
			'args'  => $action->args(),
		];
	}

	public function get_conditions(): array { return $this->conditions; }
	public function get_actions(): array { return $this->actions; }

	/**
	 * Checks if a hook has any enabled rules.
	 */
	public function has_rules( string $hook ): bool {
		$rules = $this->get_rules( $hook );
		foreach ( $rules as $rule ) {
			if ( ! empty( $rule['enabled'] ) ) {
				return true;
			}
		}
		return false;
	}

	private array $rules_cache = [];

	/**
	 * Get rules from WordPress options (with static caching).
	 */
	public function get_rules( string $hook ): array {
		if ( isset( $this->rules_cache[ $hook ] ) ) {
			return $this->rules_cache[ $hook ];
		}

		$rules = get_option( "taiwan_store_core_rules_{$hook}" );
		if ( false === $rules ) {
			$rules = get_option( "wc_tw_core_rules_{$hook}" );
		}

		$this->rules_cache[ $hook ] = is_array( $rules ) ? $rules : [];
		return $this->rules_cache[ $hook ];
	}

	/**
	 * Main evaluation entry point (New API).
	 */
	public function evaluate( string $hook, Context $ctx, array &$payload ): void {
		$rules = $this->get_rules( $hook );
		foreach ( $rules as $rule ) {
			if ( empty( $rule['enabled'] ) ) continue;

			if ( $this->check_conditions( $rule['conditions'] ?? [], $ctx ) ) {
				$this->apply_actions( $rule['actions'] ?? [], $ctx, $payload );
			}
		}
	}

	/**
	 * Legacy evaluation method for older modules.
	 */
	public function evaluate_rules( array $rules, array $context_data ): array {
		$ctx = new Context();
		$results = [];
		foreach ( $rules as $rule ) {
			if ( empty( $rule['enabled'] ) ) continue;
			if ( $this->check_conditions( $rule['conditions'] ?? [], $ctx ) ) {
				$results[] = $rule['actions'] ?? [];
			}
		}
		return $results;
	}

	private function check_conditions( array $conditions, Context $ctx ): bool {
		if ( empty( $conditions ) ) return true;

		foreach ( $conditions as $cond_data ) {
			$type = $cond_data['type'] ?? '';
			if ( ! isset( $this->condition_instances[ $type ] ) ) continue;

			if ( ! $this->condition_instances[ $type ]->matches( $ctx, $cond_data['config'] ?? [] ) ) {
				return false;
			}
		}
		return true;
	}

	private function apply_actions( array $actions, Context $ctx, array &$payload ): void {
		foreach ( $actions as $act_data ) {
			$type = $act_data['type'] ?? '';
			if ( ! isset( $this->action_instances[ $type ] ) ) continue;

			$this->action_instances[ $type ]->execute( $ctx, $act_data['config'] ?? [], $payload );
		}
	}
}