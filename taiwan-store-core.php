<?php
/**
 * Plugin Name: Taiwan Store Core
 * Description: All-in-one localization solution for WooCommerce in Taiwan. Includes Social Login (LINE/Google/FB), Smart Checkout Fields (Tax ID lookup, Mobile barcode, address cascading), CVS Map integration, Checkout Countdown, and more.
 * Version:           1.0.7
 * Author:            mydymailbox
 * License:           GPL-2.0-or-later
 * Text Domain:       taiwan-store-core
 * Domain Path:       /languages
 * Requires at least: 6.5
 * Requires PHP:      8.1
 * WC requires at least: 8.0
 * WC tested up to:      9.0
 */

defined('ABSPATH') || exit;

define('TAIWAN_STORE_CORE_VERSION', '1.0.7');
define('TAIWAN_STORE_CORE_FILE', __FILE__);
define('TAIWAN_STORE_CORE_DIR', plugin_dir_path(__FILE__));
define('TAIWAN_STORE_CORE_URL', plugin_dir_url(__FILE__));

/**
 * HPOS (High-Performance Order Storage) Compatibility Declaration
 */
add_action('before_woocommerce_init', static function () {
	if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
			'custom_order_tables',
			__FILE__,
			true
		);
	}
});

/**
 * Activation: auto-create Taiwan shipping zone + CVS method if not already present.
 */
register_activation_hook( __FILE__, static function () {
	set_transient( 'taiwan_store_core_activated', true, 30 );
	// Defer setup until WC is fully loaded on next request
	set_transient( 'taiwan_store_core_setup_shipping', true, 60 );
} );

add_action( 'woocommerce_init', static function () {
	// Run on activation transient OR if CVS zone was never set up (version upgrade)
	$needs_setup = get_transient( 'taiwan_store_core_setup_shipping' )
		|| ! get_option( 'taiwan_store_core_cvs_zone_created' );

	if ( ! $needs_setup ) return;
	if ( ! class_exists( 'WC_Shipping_Zones' ) ) return;

	delete_transient( 'taiwan_store_core_setup_shipping' );
	taiwan_store_core_setup_cvs_zone();
} );

/**
 * Create a "Taiwan" shipping zone with CVS method if one doesn't exist yet.
 */
function taiwan_store_core_setup_cvs_zone(): void {
	// Check if our CVS method already exists in any zone
	foreach ( WC_Shipping_Zones::get_zones() as $zone_data ) {
		$zone = new WC_Shipping_Zone( $zone_data['zone_id'] );
		foreach ( $zone->get_shipping_methods() as $method ) {
			if ( $method->id === 'taiwan_store_core_cvs' ) {
				return; // Already set up
			}
		}
	}

	// Find or create a Taiwan zone
	$taiwan_zone = null;
	foreach ( WC_Shipping_Zones::get_zones() as $zone_data ) {
		$zone = new WC_Shipping_Zone( $zone_data['zone_id'] );
		foreach ( $zone->get_zone_locations() as $loc ) {
			if ( $loc->code === 'TW' ) {
				$taiwan_zone = $zone;
				break 2;
			}
		}
	}

	if ( ! $taiwan_zone ) {
		$taiwan_zone = new WC_Shipping_Zone();
		$taiwan_zone->set_zone_name( __( '台灣', 'taiwan-store-core' ) );
		$taiwan_zone->add_location( 'TW', 'country' );
		$taiwan_zone->save();
	}

	// Add CVS shipping method to the zone
	$instance_id = $taiwan_zone->add_shipping_method( 'taiwan_store_core_cvs' );

	// Set default options for this instance
	if ( $instance_id ) {
		update_option( "woocommerce_taiwan_store_core_cvs_{$instance_id}_settings", [
			'title'    => __( '超商取貨', 'taiwan-store-core' ),
			'cost'     => '60',
			'free_min' => '0',
			'cvs_type' => 'UNIMART',
		] );
	}

	// Mark as done so this doesn't run again
	update_option( 'taiwan_store_core_cvs_zone_created', '1' );
}

/**
 * Boot the plugin after WooCommerce is loaded
 */
add_action('plugins_loaded', static function () {
	if (!class_exists('WooCommerce')) {
		add_action('admin_notices', static function () {
			?>
			<div class="notice notice-error">
				<p><?php esc_html_e('Taiwan Store Core requires WooCommerce to be installed and active.', 'taiwan-store-core'); ?>
				</p>
			</div>
			<?php
		});
		return;
	}

	require_once TAIWAN_STORE_CORE_DIR . 'includes/class-compatibility.php';
	require_once TAIWAN_STORE_CORE_DIR . 'includes/class-plugin.php';
	\Taiwan_Store_Core\Plugin::instance()->boot();
}, 5);
