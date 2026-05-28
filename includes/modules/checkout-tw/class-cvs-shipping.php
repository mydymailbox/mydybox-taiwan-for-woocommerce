<?php
namespace Mydyma_TCS\Modules\Checkout_Tw;

defined( 'ABSPATH' ) || exit;

/**
 * CVS Shipping Module.
 * Integrates ECPay logistics API for 7-11 / FamilyMart store pickup.
 */
class CVS_Shipping {

	// ECPay Staging endpoints
	const API_STAGE = 'https://logistics-stage.ecpay.com.tw/Express/map';
	const API_LIVE  = 'https://logistics.ecpay.com.tw/Express/map';

	public function boot(): void {
		if ( 'yes' !== get_option( 'mydyma_tcs_cvs_enabled', 'no' ) ) {
			return;
		}

		// Register WooCommerce shipping method
		add_filter( 'woocommerce_shipping_methods', [ $this, 'register_shipping_method' ] );
		add_action( 'woocommerce_shipping_init', [ $this, 'load_shipping_method' ] );

		// Checkout field: store info display
		add_action( 'woocommerce_after_checkout_billing_form', [ $this, 'render_cvs_store_info' ] );

		// Save store info to order
		add_action( 'woocommerce_checkout_create_order', [ $this, 'save_store_to_order' ], 10, 2 );

		// AJAX: open ECPay map popup
		add_action( 'wp_ajax_mydyma_tcs_open_cvs_map', [ $this, 'ajax_open_cvs_map' ] );
		add_action( 'wp_ajax_nopriv_mydyma_tcs_open_cvs_map', [ $this, 'ajax_open_cvs_map' ] );

		// AJAX: receive callback from ECPay map
		add_action( 'wp_ajax_mydyma_tcs_cvs_map_callback', [ $this, 'ajax_map_callback' ] );
		add_action( 'wp_ajax_nopriv_mydyma_tcs_cvs_map_callback', [ $this, 'ajax_map_callback' ] );

		// Enqueue scripts on checkout
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );

		// Show store info in order detail / email
		add_action( 'woocommerce_order_details_after_order_table', [ $this, 'display_store_in_order' ] );
		add_action( 'woocommerce_email_order_meta', [ $this, 'display_store_in_email' ], 10, 3 );

		// Admin: show store info in order edit page
		add_action( 'woocommerce_admin_order_data_after_shipping_address', [ $this, 'display_store_in_admin' ] );
	}

	public function load_shipping_method(): void {
		if ( class_exists( 'WC_Shipping_Method' ) ) {
			require_once __DIR__ . '/class-cvs-shipping-method.php';
		}
	}

	public function register_shipping_method( array $methods ): array {
		$methods['mydyma_tcs_cvs'] = '\Mydyma_TCS\Modules\Checkout_Tw\CVS_Shipping_Method';
		return $methods;
	}

	public function enqueue_scripts(): void {
		if ( ! is_checkout() ) return;

		wp_enqueue_script(
			'mydyma-taiwan-commerce-suite-cvs-map',
			MYDYMA_TCS_URL . 'assets/js/cvs-map.js',
			[ 'jquery' ],
			filemtime( MYDYMA_TCS_DIR . 'assets/js/cvs-map.js' ),
			true
		);

		wp_localize_script( 'mydyma-taiwan-commerce-suite-cvs-map', 'mydymaTcsCvs', [
			'ajaxUrl'        => admin_url( 'admin-ajax.php' ),
			'nonce'          => wp_create_nonce( 'mydyma_tcs_cvs_map' ),
			'selectStore'    => __( '選擇門市', 'mydyma-taiwan-commerce-suite' ),
			'changeStore'    => __( '更換門市', 'mydyma-taiwan-commerce-suite' ),
			'noStoreSelected'=> __( '尚未選擇門市，請點擊「選擇門市」按鈕', 'mydyma-taiwan-commerce-suite' ),
			'selectedStore'  => __( '已選擇門市', 'mydyma-taiwan-commerce-suite' ),
			'callbackUrl'    => admin_url( 'admin-ajax.php?action=mydyma_tcs_cvs_map_callback' ),
		] );
	}

	/**
	 * Render the store selection UI below billing form when CVS shipping is chosen.
	 */
	public function render_cvs_store_info(): void {
		?>
		<div id="ts-cvs-store-wrap" style="display:none; margin-top: 1rem;">
			<div id="ts-cvs-store-info" class="ts-cvs-info-box">
				<span class="ts-cvs-icon">🏪</span>
				<span id="ts-cvs-store-text"><?php esc_html_e( '尚未選擇門市，請點擊「選擇門市」按鈕', 'mydyma-taiwan-commerce-suite' ); ?></span>
			</div>
			<button type="button" id="ts-cvs-select-btn" class="ts-cvs-btn">
				🗺️ <?php esc_html_e( '選擇門市', 'mydyma-taiwan-commerce-suite' ); ?>
			</button>
			<input type="hidden" id="mydyma_tcs_cvs_store_id"   name="mydyma_tcs_cvs_store_id"   value="">
			<input type="hidden" id="mydyma_tcs_cvs_store_name" name="mydyma_tcs_cvs_store_name" value="">
			<input type="hidden" id="mydyma_tcs_cvs_store_addr" name="mydyma_tcs_cvs_store_addr" value="">
			<input type="hidden" id="mydyma_tcs_cvs_store_type" name="mydyma_tcs_cvs_store_type" value="">
		</div>
		<?php
	}

	/**
	 * AJAX: Generate ECPay map form and return the HTML to open as popup.
	 */
	public function ajax_open_cvs_map(): void {
		check_ajax_referer( 'mydyma_tcs_cvs_map', 'nonce' );

		$cvs_type    = sanitize_text_field( wp_unslash( $_POST['cvs_type'] ?? 'UNIMART' ) );
		$is_test     = 'yes' === get_option( 'mydyma_tcs_cvs_test_mode', 'yes' );
		$merchant_id = $is_test ? '3002607' : get_option( 'mydyma_tcs_cvs_merchant_id', '' );
		$hash_key    = $is_test ? 'pwFHCqoQZGmho4w6' : get_option( 'mydyma_tcs_cvs_hash_key', '' );
		$hash_iv     = $is_test ? 'EkRm7iFT261dpevs' : get_option( 'mydyma_tcs_cvs_hash_iv', '' );
		$endpoint    = $is_test ? self::API_STAGE : self::API_LIVE;

		$callback_url = admin_url( 'admin-ajax.php?action=mydyma_tcs_cvs_map_callback&nonce=' . wp_create_nonce( 'mydyma_tcs_cvs_callback' ) );

		$params = [
			'MerchantID'       => $merchant_id,
			'LogisticsType'    => 'CVS',
			'LogisticsSubType' => $cvs_type,
			'IsCollection'     => 'N',
			'ServerReplyURL'   => $callback_url,
			'ExtraData'        => '',
		];

		// Generate CheckMacValue
		$params['CheckMacValue'] = $this->generate_check_mac( $params, $hash_key, $hash_iv );

		// Build HTML form that auto-submits to open ECPay map
		$form = '<form id="ts-ecpay-map-form" method="post" action="' . esc_url( $endpoint ) . '">';
		foreach ( $params as $k => $v ) {
			$form .= '<input type="hidden" name="' . esc_attr( $k ) . '" value="' . esc_attr( $v ) . '">';
		}
		$form .= '</form>'; // submit() is triggered by cvs-map.js after document.write to avoid an inline script tag.

		wp_send_json_success( [ 'form' => $form ] );
	}

	/**
	 * AJAX: Receive store selection from ECPay map callback.
	 * ECPay posts to this URL after user selects a store.
	 */
	public function ajax_map_callback(): void {
		check_ajax_referer( 'mydyma_tcs_cvs_callback', 'nonce' );
		$store_id   = sanitize_text_field( wp_unslash( $_POST['CVSStoreID'] ?? '' ) );
		$store_name = sanitize_text_field( wp_unslash( $_POST['CVSStoreName'] ?? '' ) );
		$store_addr = sanitize_text_field( wp_unslash( $_POST['CVSAddress'] ?? '' ) );
		$cvs_type   = sanitize_text_field( wp_unslash( $_POST['LogisticsSubType'] ?? '' ) );

		if ( ! $store_id ) {
			wp_die( 'Missing store data', 400 );
		}

		// Store in session for checkout to pick up
		WC()->session->set( 'mydyma_tcs_cvs_store', [
			'id'   => $store_id,
			'name' => $store_name,
			'addr' => $store_addr,
			'type' => $cvs_type,
		] );

		// Return a tiny page that sends data back to the parent window and closes.
		// We use wp_print_inline_script_tag() — WordPress's canonical helper for emitting
		// inline scripts — so no script-tag literal appears in this PHP source.
		header( 'Content-Type: text/html; charset=utf-8' );
		$store_json = wp_json_encode( [
			'id'   => $store_id,
			'name' => $store_name,
			'addr' => $store_addr,
			'type' => $cvs_type,
		] );
		echo '<!DOCTYPE html><html><body>';
		wp_print_inline_script_tag(
			'var store = ' . $store_json . ';' .
			'if (window.opener) { window.opener.postMessage({ type: "mydyma_tcs_cvs_store", store: store }, "*"); window.close(); }'
		);
		echo '</body></html>';
		exit;
	}

	/**
	 * Save CVS store info to order meta on checkout.
	 */
	public function save_store_to_order( \WC_Order $order, array $data ): void {
		// Called via woocommerce_checkout_create_order; WooCommerce verifies the checkout nonce upstream.
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- nonce verified by WooCommerce checkout
		$store_id   = sanitize_text_field( wp_unslash( $_POST['mydyma_tcs_cvs_store_id'] ?? '' ) );
		$store_name = sanitize_text_field( wp_unslash( $_POST['mydyma_tcs_cvs_store_name'] ?? '' ) );
		$store_addr = sanitize_text_field( wp_unslash( $_POST['mydyma_tcs_cvs_store_addr'] ?? '' ) );
		$store_type = sanitize_text_field( wp_unslash( $_POST['mydyma_tcs_cvs_store_type'] ?? '' ) );
		// phpcs:enable

		if ( ! $store_id ) return;

		$order->update_meta_data( 'mydyma_tcs_cvs_store_id',   $store_id );
		$order->update_meta_data( 'mydyma_tcs_cvs_store_name', $store_name );
		$order->update_meta_data( 'mydyma_tcs_cvs_store_addr', $store_addr );
		$order->update_meta_data( 'mydyma_tcs_cvs_store_type', $store_type );

		// Set shipping address to store address
		$type_label = $this->get_cvs_label( $store_type );
		$order->set_shipping_address_1( "[{$type_label}] {$store_name}" );
		$order->set_shipping_address_2( $store_addr );
	}

	public function display_store_in_order( \WC_Order $order ): void {
		$store_id = $order->get_meta( 'mydyma_tcs_cvs_store_id' );
		if ( ! $store_id ) return;
		$this->render_store_block( $order );
	}

	public function display_store_in_email( \WC_Order $order, bool $sent_to_admin, string $plain_text ): void {
		$store_id = $order->get_meta( 'mydyma_tcs_cvs_store_id' );
		if ( ! $store_id ) return;
		if ( $plain_text ) {
			echo "\n" . esc_html__( '取貨門市', 'mydyma-taiwan-commerce-suite' ) . ': ' . esc_html( $order->get_meta( 'mydyma_tcs_cvs_store_name' ) ) . ' - ' . esc_html( $order->get_meta( 'mydyma_tcs_cvs_store_addr' ) ) . "\n";
		} else {
			$this->render_store_block( $order );
		}
	}

	public function display_store_in_admin( \WC_Order $order ): void {
		$store_id = $order->get_meta( 'mydyma_tcs_cvs_store_id' );
		if ( ! $store_id ) return;
		echo '<div style="margin-top:10px;padding:10px;background:#f0f9ff;border:1px solid #bae6fd;border-radius:6px;">';
		echo '<strong>🏪 ' . esc_html__( '超商取貨門市', 'mydyma-taiwan-commerce-suite' ) . '</strong><br>';
		echo esc_html( $this->get_cvs_label( $order->get_meta( 'mydyma_tcs_cvs_store_type' ) ) ) . ' ';
		echo esc_html( $order->get_meta( 'mydyma_tcs_cvs_store_name' ) ) . '<br>';
		echo '<small>' . esc_html( $order->get_meta( 'mydyma_tcs_cvs_store_addr' ) ) . '</small>';
		echo '</div>';
	}

	private function render_store_block( \WC_Order $order ): void {
		$type  = $order->get_meta( 'mydyma_tcs_cvs_store_type' );
		$name  = $order->get_meta( 'mydyma_tcs_cvs_store_name' );
		$addr  = $order->get_meta( 'mydyma_tcs_cvs_store_addr' );
		$label = $this->get_cvs_label( $type );
		echo '<section class="ts-cvs-order-info" style="margin:1rem 0;padding:1rem;background:#f0fdf4;border:1px solid #86efac;border-radius:8px;">';
		echo '<h3 style="margin:0 0 .5rem;font-size:1rem;">🏪 ' . esc_html__( '取貨門市資訊', 'mydyma-taiwan-commerce-suite' ) . '</h3>';
		echo '<p style="margin:0;">' . esc_html( $label ) . '｜' . esc_html( $name ) . '</p>';
		echo '<p style="margin:.25rem 0 0;color:#6b7280;font-size:.875rem;">' . esc_html( $addr ) . '</p>';
		echo '</section>';
	}

	private function get_cvs_label( string $type ): string {
		$map = [
			'UNIMART'      => '7-ELEVEN',
			'UNIMARTC2C'   => '7-ELEVEN（交貨便）',
			'FAMI'         => '全家 FamilyMart',
			'FAMIC2C'      => '全家（好賣+）',
			'HILIFE'       => '萊爾富',
			'HILIFEC2C'    => '萊爾富（Hi-Life）',
			'OKMART'       => 'OK 超商',
		];
		return $map[ $type ] ?? $type;
	}

	/**
	 * Generate ECPay CheckMacValue (SHA256).
	 */
	public function generate_check_mac( array $params, string $hash_key, string $hash_iv ): string {
		ksort( $params );
		$str = "HashKey={$hash_key}";
		foreach ( $params as $k => $v ) {
			$str .= "&{$k}={$v}";
		}
		$str .= "&HashIV={$hash_iv}";
		$str  = strtolower( urlencode( $str ) );
		// ECPay encoding rules
		$str  = str_replace( '%2d', '-', $str );
		$str  = str_replace( '%5f', '_', $str );
		$str  = str_replace( '%2e', '.', $str );
		$str  = str_replace( '%21', '!', $str );
		$str  = str_replace( '%2a', '*', $str );
		$str  = str_replace( '%28', '(', $str );
		$str  = str_replace( '%29', ')', $str );
		return strtoupper( hash( 'sha256', $str ) );
	}
}
