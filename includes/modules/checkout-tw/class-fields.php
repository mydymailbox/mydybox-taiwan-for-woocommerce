<?php
namespace Mydybox\Modules\Checkout_Tw;

defined( 'ABSPATH' ) || exit;

/**
 * Checkout Fields Module.
 * Customizes WooCommerce checkout fields for Taiwan market.
 */
class Fields {

	public function boot(): void {
		add_filter( 'woocommerce_checkout_fields', [ $this, 'customize_fields' ], 10 );
		add_filter( 'woocommerce_default_address_fields', [ $this, 'customize_default_fields' ], 10 );

		add_action( 'wp_ajax_mydybox_lookup_tax_id', [ $this, 'ajax_lookup_tax_id' ] );
		add_action( 'wp_ajax_nopriv_mydybox_lookup_tax_id', [ $this, 'ajax_lookup_tax_id' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
		add_action( 'admin_notices', [ $this, 'admin_notice_api_failure' ] );
	}

	public function admin_notice_api_failure(): void {
		$msg = get_transient( 'mydybox_gcis_api_failed' );
		if ( ! $msg ) return;
		$settings_url = admin_url( 'admin.php?page=mydybox-taiwan-for-woocommerce&tab=checkout' );
		echo '<div class="notice notice-error"><p>';
		echo '<strong>[Mydybox]</strong> ' . esc_html__( '統一編號查詢 API 發生錯誤：', 'mydybox-taiwan-for-woocommerce' ) . esc_html( $msg ) . ' ';
		echo '<a href="' . esc_url( $settings_url ) . '">' . esc_html__( '前往設定頁更新 UUID', 'mydybox-taiwan-for-woocommerce' ) . '</a>';
		echo '</p></div>';
	}

	public function enqueue_scripts(): void {
		if ( ! is_checkout() ) {
			return;
		}

		wp_enqueue_style(
			'mydybox-taiwan-for-woocommerce-checkout',
			MYDYBOX_URL . 'assets/css/checkout.css',
			[],
			filemtime( MYDYBOX_DIR . 'assets/css/checkout.css' )
		);

		wp_enqueue_script(
			'mydybox-taiwan-for-woocommerce-checkout-tw',
			MYDYBOX_URL . 'assets/js/checkout-tw.js',
			[ 'jquery' ],
			filemtime( MYDYBOX_DIR . 'assets/js/checkout-tw.js' ),
			true
		);

		wp_localize_script( 'mydybox-taiwan-for-woocommerce-checkout-tw', 'mydyboxCheckout', [
			'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
			'nonce'         => wp_create_nonce( 'mydybox_lookup_tax_id' ),
			'lookupEnabled' => get_option( 'mydybox_checkout_lookup_tax_id', 'no' ),
			'lookingUp'     => __( 'Looking up...', 'mydybox-taiwan-for-woocommerce' ),
			'found'         => __( '已自動填入公司名稱', 'mydybox-taiwan-for-woocommerce' ),
			'notFound'      => __( '查無此公司，請手動輸入', 'mydybox-taiwan-for-woocommerce' ),
		] );
	}

	public function customize_fields( array $fields ): array {
		// 1. Add Invoice Type Field (Always show if module enabled)
		$fields['billing']['billing_mydybox_invoice_type'] = [
			'type'        => 'select',
			'label'       => __( '發票類型', 'mydybox-taiwan-for-woocommerce' ),
			'required'    => true,
			'class'       => [ 'form-row-wide' ],
			'options'     => [
				''              => __( '─ 請選擇發票類型 ─', 'mydybox-taiwan-for-woocommerce' ),
				'individual'    => __( '個人發票（存入雲端）', 'mydybox-taiwan-for-woocommerce' ),
				'carrier_phone' => __( '手機載具（格式：/開頭+7碼英數）', 'mydybox-taiwan-for-woocommerce' ),
				'carrier_cert'  => __( '自然人憑證（格式：2碼英文+14碼數字）', 'mydybox-taiwan-for-woocommerce' ),
				'donate'        => __( '捐贈發票（輸入3～7碼愛心碼）', 'mydybox-taiwan-for-woocommerce' ),
				'company'       => __( '公司三聯式（需統編）', 'mydybox-taiwan-for-woocommerce' ),
			],
			'priority'    => 120,
		];

		// 2. Add Tax ID Fields (Conditional)
		if ( 'yes' === get_option( 'mydybox_checkout_show_tax_id', 'yes' ) ) {
			$fields['billing']['billing_mydybox_company_tax_id'] = [
				'type'        => 'text',
				'label'       => __( 'Tax ID', 'mydybox-taiwan-for-woocommerce' ),
				'placeholder' => __( '8 digits', 'mydybox-taiwan-for-woocommerce' ),
				'required'    => false,
				'class'       => [ 'form-row-first', 'mydybox-taiwan-for-woocommerce-company-field' ],
				'priority'    => 121,
			];

			$fields['billing']['billing_mydybox_company_title'] = [
				'type'        => 'text',
				'label'       => __( 'Company Name', 'mydybox-taiwan-for-woocommerce' ),
				'placeholder' => __( 'Required for companies (can be auto-filled)', 'mydybox-taiwan-for-woocommerce' ),
				'required'    => false,
				'class'       => [ 'form-row-last', 'mydybox-taiwan-for-woocommerce-company-field' ],
				'priority'    => 122,
			];
		}
		
		// 3. Add Carrier / Donation Code Field
		$fields['billing']['billing_mydybox_carrier_number'] = [
			'type'        => 'text',
			'label'       => __( 'Carrier / Donation Code', 'mydybox-taiwan-for-woocommerce' ),
			'placeholder' => __( '/ABC+123 or 3-7 digits', 'mydybox-taiwan-for-woocommerce' ),
			'required'    => false,
			'class'       => [ 'form-row-wide', 'mydybox-taiwan-for-woocommerce-carrier-field' ],
			'priority'    => 123,
		];

		return $fields;
	}

	public function customize_default_fields( array $fields ): array {
		// Customize address labels for Taiwan
		if ( isset( $fields['state'] ) ) {
			$fields['state']['label'] = __( 'City/County', 'mydybox-taiwan-for-woocommerce' );
		}
		if ( isset( $fields['city'] ) ) {
			$fields['city']['label'] = __( 'District', 'mydybox-taiwan-for-woocommerce' );
		}
		return $fields;
	}

	/**
	 * AJAX Handler for Tax ID Lookup (Optimized with Dual API & Transients).
	 */
	public function ajax_lookup_tax_id(): void {
		check_ajax_referer( 'mydybox_lookup_tax_id', 'nonce' );

		// Server-side opt-in check: lookup must be explicitly enabled by the site owner.
		// External GCIS API calls are off by default to respect customer privacy.
		if ( 'yes' !== get_option( 'mydybox_checkout_lookup_tax_id', 'no' ) ) {
			wp_send_json_error( __( 'Tax ID lookup is disabled.', 'mydybox-taiwan-for-woocommerce' ) );
			return;
		}

		$tax_id = sanitize_text_field( wp_unslash( $_POST['tax_id'] ?? '' ) );
		if ( ! preg_match( '/^[0-9]{8}$/', $tax_id ) ) {
			wp_send_json_error( __( 'Invalid Tax ID format.', 'mydybox-taiwan-for-woocommerce' ) );
			return;
		}

		// 1. Check Cache
		$cache_key = 'mydybox_tax_id_cache_' . $tax_id;
		$cached_name = get_transient( $cache_key );
		if ( $cached_name ) {
			wp_send_json_success( [ 'name' => $cached_name, 'cached' => true ] );
		}

		// 2. Try Company API first, then Business API
		$uuid    = get_option( 'mydybox_gcis_api_uuid', '236EE382-4942-41A9-BD03-CA0709025E7C' );
		$api_url = "https://data.gcis.nat.gov.tw/od/data/api/{$uuid}?" . http_build_query( [
			'$format' => 'json',
			'$filter' => "Business_Accounting_NO eq '{$tax_id}'",
			'$skip'   => '0',
			'$top'    => '1',
		] );

		$name      = '';
		$error_log = '';

		$response = wp_remote_get( $api_url, [
			'timeout'    => 8,
			'sslverify'  => ! ( defined( 'WP_DEBUG' ) && WP_DEBUG ),
			'user-agent' => 'Mozilla/5.0',
		] );

		if ( is_wp_error( $response ) ) {
			$error_log = $response->get_error_message();
			set_transient( 'mydybox_gcis_api_failed', $error_log, HOUR_IN_SECONDS );
		} else {
			$http_code = wp_remote_retrieve_response_code( $response );
			$body      = wp_remote_retrieve_body( $response );
			$data      = json_decode( $body, true );

			if ( $http_code !== 200 || ( is_string( $body ) && strpos( $body, '此API不存在' ) !== false ) ) {
				// translators: %s is HTTP status code
				set_transient( 'mydybox_gcis_api_failed', sprintf( __( 'HTTP %s：UUID 可能已失效，請至後台結帳設定更新 GCIS API UUID。', 'mydybox-taiwan-for-woocommerce' ), $http_code ), HOUR_IN_SECONDS );
			} elseif ( ! empty( $data ) && is_array( $data ) ) {
				$name = $data[0]['Company_Name'] ?? '';
			}
		}

		if ( $name ) {
			delete_transient( 'mydybox_gcis_api_failed' );
			set_transient( $cache_key, $name, DAY_IN_SECONDS );
			wp_send_json_success( [ 'name' => $name, 'cached' => false ] );
		}

		wp_send_json_error( [
			'message' => $error_log ?: __( '查無此公司資料。', 'mydybox-taiwan-for-woocommerce' ),
			'tax_id'  => $tax_id,
		] );
	}
}