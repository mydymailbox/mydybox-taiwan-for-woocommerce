<?php
namespace Mydyma_TCS;

defined( 'ABSPATH' ) || exit;

/**
 * Main Plugin Class.
 * Initializes all modules and handles core lifecycle events.
 */
class Plugin {

	private static ?Plugin $instance = null;
	private array $modules = [];
	private array $config_cache = [];

	public static function instance(): self {
		if ( null === self::$instance ) self::$instance = new self();
		return self::$instance;
	}

	/**
	 * Internal helper to get a cached option value.
	 */
	public function get_option( string $key, $default = false ) {
		if ( ! isset( $this->config_cache[ $key ] ) ) {
			$this->config_cache[ $key ] = get_option( $key, $default );
		}
		return $this->config_cache[ $key ];
	}

	public function boot(): void {
		// 1. Load Helpers & Interfaces
		require_once MYDYMA_TCS_DIR . 'includes/helpers/class-nonce.php';
		require_once MYDYMA_TCS_DIR . 'includes/interface-module.php';
		require_once MYDYMA_TCS_DIR . 'includes/rule-engine/class-rule-engine.php';

		// 2. Load Admin UI
		if ( is_admin() ) {
			require_once MYDYMA_TCS_DIR . 'includes/admin/class-settings-page.php';
			( new \Mydyma_TCS\Admin\Settings_Page() )->boot();
			
			require_once MYDYMA_TCS_DIR . 'includes/admin/class-rules-ui.php';
			( new \Mydyma_TCS\Admin\Rules_UI() )->boot();

			require_once MYDYMA_TCS_DIR . 'includes/admin/class-rules-ajax.php';
			( new \Mydyma_TCS\Admin\Rules_Ajax() )->boot();
		}

		// 3. Load Functional Modules
		$this->load_modules();

		// 4. Show activation notice
		add_action( 'admin_notices', [ $this, 'show_activation_notice' ] );
	}

	/**
	 * Manually load and boot enabled modules.
	 */
	private function load_modules(): void {
		$enabled = $this->get_option( 'mydyma_tcs_enabled_modules', [ 'checkout_tw' => 'yes', 'order_number_tw' => 'yes', 'social_login' => 'yes' ] );
		if ( ! is_array( $enabled ) ) $enabled = [];

		// Checkout TW Module
		if ( ( $enabled['checkout_tw'] ?? '' ) === 'yes' ) {
			require_once MYDYMA_TCS_DIR . 'includes/modules/checkout-tw/class-module.php';
			$this->modules['checkout_tw'] = new \Mydyma_TCS\Modules\Checkout_Tw\Module();
			$this->modules['checkout_tw']->boot();

			// Product UI is dependent on Checkout TW
			require_once MYDYMA_TCS_DIR . 'includes/modules/checkout-tw/class-product-ui.php';
			$this->modules['product_ui'] = new \Mydyma_TCS\Modules\Checkout_Tw\Product_UI();
			$this->modules['product_ui']->boot();
		}

		// Order Number Module
		if ( ( $enabled['order_number_tw'] ?? '' ) === 'yes' ) {
			require_once MYDYMA_TCS_DIR . 'includes/modules/order-number-tw/class-module.php';
			$this->modules['order_number'] = new \Mydyma_TCS\Modules\Order_Number_Tw\Module();
			$this->modules['order_number']->boot();
		}

		// Social Login Module
		if ( ( $enabled['social_login'] ?? '' ) === 'yes' ) {
			require_once MYDYMA_TCS_DIR . 'includes/modules/social-login/class-module.php';
			$this->modules['social_login'] = new \Mydyma_TCS\Modules\Social_Login\Module();
			$this->modules['social_login']->boot();
		}

		// ECPay Payment Gateway
		require_once MYDYMA_TCS_DIR . 'includes/modules/payment-gateway/class-module.php';
		$this->modules['payment_gateway'] = new \Mydyma_TCS\Modules\Payment_Gateway\Module();
		$this->modules['payment_gateway']->boot();

		// Rule Engine Modules
		$rule_modules = [
			'payment_rules'  => \Mydyma_TCS\Modules\Payment_Rules\Module::class,
			'shipping_rules' => \Mydyma_TCS\Modules\Shipping_Rules\Module::class,
			'cart_rules'     => \Mydyma_TCS\Modules\Cart_Rules\Module::class,
		];

		foreach ( $rule_modules as $key => $class ) {
			$path = str_replace( '_', '-', $key );
			require_once MYDYMA_TCS_DIR . "includes/modules/{$path}/class-module.php";
			$this->modules[ $key ] = new $class();
			$this->modules[ $key ]->boot();
		}

		// Abandoned Cart Module
		require_once MYDYMA_TCS_DIR . 'includes/modules/abandoned-cart/class-module.php';
		$this->modules['abandoned_cart'] = new \Mydyma_TCS\Modules\Abandoned_Cart\Module();
		$this->modules['abandoned_cart']->boot();

		// Logs Module
		require_once MYDYMA_TCS_DIR . 'includes/modules/logs/class-module.php';
		$this->modules['logs'] = new \Mydyma_TCS\Modules\Logs\Module();
		$this->modules['logs']->boot();
	}

	public function show_activation_notice(): void {
		if ( ! get_transient( 'mydyma_tcs_activated' ) ) return;
		delete_transient( 'mydyma_tcs_activated' );
		?>
		<div class="notice notice-success is-dismissible">
			<p><?php esc_html_e( '「台灣商店：核心助手」已成功啟動。請前往設定頁面配置您的結帳與在地化選項。', 'mydyma-taiwan-commerce-suite' ); ?></p>
		</div>
		<?php
	}
}