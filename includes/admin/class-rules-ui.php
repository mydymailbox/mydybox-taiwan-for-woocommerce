<?php
namespace Mydybox\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * Rules UI Class.
 * Handles the React-based rules editor interface.
 */
class Rules_UI {

	public function boot(): void {
		add_action( 'admin_menu', [ $this, 'add_menu_pages' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
	}

	public function add_menu_pages(): void {
		// Rules Editor is now integrated into the main dashboard tabs.
		// Standalone menu is no longer needed.
	}

	public function render_page(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- admin page render, type is sanitized via sanitize_key
		$type = sanitize_key( $_GET['type'] ?? 'payment' );
		$this->render_rules_editor( $type );
	}

	public function enqueue_assets( $hook ): void {
		if ( false === strpos( $hook, 'mydybox-taiwan-for-woocommerce' ) ) return;

		// SweetAlert2 for the rules editor - bundled locally
		wp_enqueue_script( 'sweetalert2', MYDYBOX_URL . 'assets/vendor/sweetalert2.all.min.js', [], '11.26.25', true );

		wp_enqueue_style( 'mydybox-taiwan-for-woocommerce-rules-css', MYDYBOX_URL . 'assets/css/rules-admin.css', [], MYDYBOX_VERSION );
		wp_enqueue_script( 'mydybox-taiwan-for-woocommerce-rules-js', MYDYBOX_URL . 'assets/js/rules-admin.js', [ 'jquery', 'sweetalert2' ], MYDYBOX_VERSION, true );

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only tab/type params, no data mutation
		$type = sanitize_key( wp_unslash( $_GET['type'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$tab  = sanitize_key( wp_unslash( $_GET['tab'] ?? '' ) );  // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		
		// If no explicit type, but we are in the marketing tab, force marketing type
		if ( empty( $type ) && $tab === 'marketing' ) {
			$type = 'marketing';
		}
		
		// Fallback to payment for other cases
		if ( empty( $type ) ) {
			$type = 'payment';
		}

		$rules = get_option( "mydybox_rules_{$type}", [] );

		// Prepare metadata for the editor
		$gateways = [];
		if ( function_exists( 'WC' ) ) {
			foreach ( WC()->payment_gateways->get_available_payment_gateways() as $gw ) {
				$gateways[] = [ 'id' => $gw->id, 'label' => $gw->get_title() ];
			}
		}

		$shipping = [];
		if ( function_exists( 'WC' ) ) {
			$zones = \WC_Shipping_Zones::get_zones();
			// Add locations not covered by other zones
			$zones[] = [ 'zone_id' => 0, 'zone_name' => __( 'Locations not covered by your other zones', 'mydybox-taiwan-for-woocommerce' ) ];
			
			foreach ( $zones as $zone_data ) {
				$zone_obj = new \WC_Shipping_Zone( $zone_data['zone_id'] );
				$methods  = $zone_obj->get_shipping_methods();
				foreach ( $methods as $method ) {
					$shipping[] = [ 
						'id'    => $method->get_rate_id(), 
						'label' => '[' . $zone_obj->get_zone_name() . '] ' . $method->get_title() 
					];
				}
			}
		}

		$categories = [];
		$terms = get_terms( [ 'taxonomy' => 'product_cat', 'hide_empty' => false ] );
		if ( ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				$categories[] = [ 'id' => (string) $term->term_id, 'label' => $term->name ];
			}
		}

		$samples_data = include MYDYBOX_DIR . 'includes/admin/data/sample-rules.php';

		wp_localize_script( 'mydybox-taiwan-for-woocommerce-rules-js', 'MydyboxRulesData', [
			'hook'       => $type,
			'rules'      => $rules,
			'nonce'      => wp_create_nonce( 'mydybox_rules' ),
			'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
			'gateways'   => $gateways,
			'shipping'   => $shipping,
			'categories' => $categories,
			'samples'    => $samples_data,
			'states'     => \WC()->countries->get_states( 'TW' ),
		]);
	}

	public function render_rules_editor( string $type ): void {
		?>
		<div class="taiwan-store-rules-container">
			<div id="wc-tw-rules-app" data-type="<?php echo esc_attr( $type ); ?>">
				<div class="rules-loading">
					<span class="spinner is-active"></span>
					<?php esc_html_e( '載入規則編輯器中...', 'mydybox-taiwan-for-woocommerce' ); ?>
				</div>
			</div>
		</div>
		<?php
	}
}