<?php
namespace Taiwan_Store_Core\Modules\Checkout_Tw;

use Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface;

defined( 'ABSPATH' ) || exit;

/**
 * 區塊結帳集成 (Blocks Integration)
 * 負責將台灣在地化欄位注入 WooCommerce Blocks 結帳流程中。
 */
class Blocks_Integration implements IntegrationInterface {

	public function get_name(): string {
		return 'taiwan-store-core';
	}

	public function initialize(): void {
		$asset_path = TAIWAN_STORE_CORE_DIR . 'build/index.asset.php';
		$asset_url  = TAIWAN_STORE_CORE_URL . 'build/index.js';

		$dependencies = [ 'wp-element', 'wp-i18n', 'wc-blocks-registry' ];
		$version      = TAIWAN_STORE_CORE_VERSION;

		if ( file_exists( $asset_path ) ) {
			$asset        = require $asset_path;
			$dependencies = $asset['dependencies'] ?? $dependencies;
			$version      = $asset['version'] ?? $version;
		}

		wp_register_script(
			'taiwan-store-core-blocks-frontend',
			$asset_url,
			$dependencies,
			$version,
			true
		);
	}

	public function get_script_handles(): array {
		return [ 'taiwan-store-core-blocks-frontend' ];
	}

	public function get_editor_script_handles(): array {
		return [ 'taiwan-store-core-blocks-frontend' ];
	}

	public function get_script_data(): array {
		return [
			'is_tax_id_enabled' => get_option( 'ts_checkout_show_tax_id', 'yes' ),
			'is_taxid_lookup'   => get_option( 'ts_checkout_lookup_tax_id', 'yes' ),
			'ajaxUrl'           => admin_url( 'admin-ajax.php' ),
			'taxidNonce'        => wp_create_nonce( 'ts_lookup_tax_id' ),
			'is_postcode_auto'  => get_option( 'ts_checkout_postcode_autofill', 'yes' ),
			'name_consolidate'  => get_option( 'ts_checkout_name_consolidate', 'yes' ),
			'gcisNonce'         => wp_create_nonce( 'ts_lookup_tax_id' ),
		];
	}
}