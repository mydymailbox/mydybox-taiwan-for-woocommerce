<?php
namespace Mydyma_TCS\Modules\Checkout_Tw;

use Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface;

defined( 'ABSPATH' ) || exit;

/**
 * 區塊結帳集成 (Blocks Integration)
 * 負責將台灣在地化欄位注入 WooCommerce Blocks 結帳流程中。
 */
class Blocks_Integration implements IntegrationInterface {

	public function get_name(): string {
		return 'mydyma-taiwan-commerce-suite';
	}

	public function initialize(): void {
		$asset_path = MYDYMA_TCS_DIR . 'build/index.asset.php';
		$asset_url  = MYDYMA_TCS_URL . 'build/index.js';

		$dependencies = [ 'wp-element', 'wp-i18n', 'wc-blocks-registry' ];
		$version      = MYDYMA_TCS_VERSION;

		if ( file_exists( $asset_path ) ) {
			$asset        = require $asset_path;
			$dependencies = $asset['dependencies'] ?? $dependencies;
			$version      = $asset['version'] ?? $version;
		}

		wp_register_script(
			'mydyma-taiwan-commerce-suite-blocks-frontend',
			$asset_url,
			$dependencies,
			$version,
			true
		);
	}

	public function get_script_handles(): array {
		return [ 'mydyma-taiwan-commerce-suite-blocks-frontend' ];
	}

	public function get_editor_script_handles(): array {
		return [ 'mydyma-taiwan-commerce-suite-blocks-frontend' ];
	}

	public function get_script_data(): array {
		return [
			'is_tax_id_enabled' => get_option( 'mydyma_tcs_checkout_show_tax_id', 'yes' ),
			'is_taxid_lookup'   => get_option( 'mydyma_tcs_checkout_lookup_tax_id', 'no' ),
			'ajaxUrl'           => admin_url( 'admin-ajax.php' ),
			'taxidNonce'        => wp_create_nonce( 'mydyma_tcs_lookup_tax_id' ),
			'is_postcode_auto'  => get_option( 'mydyma_tcs_checkout_postcode_autofill', 'yes' ),
			'name_consolidate'  => get_option( 'mydyma_tcs_checkout_name_consolidate', 'yes' ),
			'gcisNonce'         => wp_create_nonce( 'mydyma_tcs_lookup_tax_id' ),
		];
	}
}