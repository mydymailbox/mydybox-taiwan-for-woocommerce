<?php
namespace Mydyma_TCS\Modules\Checkout_Tw;

defined( 'ABSPATH' ) || exit;

/**
 * Checkout Fields Module.
 * Customizes WooCommerce checkout fields for Taiwan market.
 */
class Fields {

	public function boot(): void {
		add_filter( 'woocommerce_checkout_fields', [ $this, 'customize_fields' ], 10 );
		add_filter( 'woocommerce_default_address_fields', [ $this, 'customize_default_fields' ], 10 );

		add_action( 'wp_ajax_mydyma_tcs_lookup_tax_id', [ $this, 'ajax_lookup_tax_id' ] );
		add_action( 'wp_ajax_nopriv_mydyma_tcs_lookup_tax_id', [ $this, 'ajax_lookup_tax_id' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
		add_action( 'admin_notices', [ $this, 'admin_notice_api_failure' ] );
	}

	public function admin_notice_api_failure(): void {
		$msg = get_transient( 'mydyma_tcs_gcis_api_failed' );
		if ( ! $msg ) return;
		$settings_url = admin_url( 'admin.php?page=mydyma-taiwan-commerce-suite&tab=checkout' );
		echo '<div class="notice notice-error"><p>';
		echo '<strong>[Mydyma TCS]</strong> ' . esc_html__( '統一編號查詢 API 發生錯誤：', 'mydyma-taiwan-commerce-suite' ) . esc_html( $msg ) . ' ';
		echo '<a href="' . esc_url( $settings_url ) . '">' . esc_html__( '前往設定頁更新 UUID', 'mydyma-taiwan-commerce-suite' ) . '</a>';
		echo '</p></div>';
	}

	public function enqueue_scripts(): void {
		if ( ! is_checkout() ) {
			return;
		}

		wp_enqueue_style(
			'mydyma-taiwan-commerce-suite-checkout',
			MYDYMA_TCS_URL . 'assets/css/checkout.css',
			[],
			filemtime( MYDYMA_TCS_DIR . 'assets/css/checkout.css' )
		);

		wp_enqueue_script(
			'mydyma-taiwan-commerce-suite-checkout-tw',
			MYDYMA_TCS_URL . 'assets/js/checkout-tw.js',
			[ 'jquery' ],
			filemtime( MYDYMA_TCS_DIR . 'assets/js/checkout-tw.js' ),
			true
		);

		wp_localize_script( 'mydyma-taiwan-commerce-suite-checkout-tw', 'mydymaTcsCheckout', [
			'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
			'nonce'         => wp_create_nonce( 'mydyma_tcs_lookup_tax_id' ),
			'lookupEnabled' => get_option( 'mydyma_tcs_checkout_lookup_tax_id', 'no' ),
			'lookingUp'     => __( 'Looking up...', 'mydyma-taiwan-commerce-suite' ),
			'found'         => __( '已自動填入公司名稱', 'mydyma-taiwan-commerce-suite' ),
			'notFound'      => __( '查無此公司，請手動輸入', 'mydyma-taiwan-commerce-suite' ),
		] );
	}

	public function customize_fields( array $fields ): array {
		// 1. Add Invoice Type Field (Always show if module enabled)
		$fields['billing']['billing_mydyma_tcs_invoice_type'] = [
			'type'        => 'select',
			'label'       => __( '發票類型', 'mydyma-taiwan-commerce-suite' ),
			'required'    => true,
			'class'       => [ 'form-row-wide' ],
			'options'     => [
				''              => __( '─ 請選擇發票類型 ─', 'mydyma-taiwan-commerce-suite' ),
				'individual'    => __( '個人發票（存入雲端）', 'mydyma-taiwan-commerce-suite' ),
				'carrier_phone' => __( '手機載具（格式：/開頭+7碼英數）', 'mydyma-taiwan-commerce-suite' ),
				'carrier_cert'  => __( '自然人憑證（格式：2碼英文+14碼數字）', 'mydyma-taiwan-commerce-suite' ),
				'donate'        => __( '捐贈發票（輸入3～7碼愛心碼）', 'mydyma-taiwan-commerce-suite' ),
				'company'       => __( '公司三聯式（需統編）', 'mydyma-taiwan-commerce-suite' ),
			],
			'priority'    => 120,
		];

		// 2. Add Tax ID Fields (Conditional)
		if ( 'yes' === get_option( 'mydyma_tcs_checkout_show_tax_id', 'yes' ) ) {
			$fields['billing']['billing_mydyma_tcs_company_tax_id'] = [
				'type'        => 'text',
				'label'       => __( 'Tax ID', 'mydyma-taiwan-commerce-suite' ),
				'placeholder' => __( '8 digits', 'mydyma-taiwan-commerce-suite' ),
				'required'    => false,
				'class'       => [ 'form-row-first', 'mydyma-taiwan-commerce-suite-company-field' ],
				'priority'    => 121,
			];

			$fields['billing']['billing_mydyma_tcs_company_title'] = [
				'type'        => 'text',
				'label'       => __( 'Company Name', 'mydyma-taiwan-commerce-suite' ),
				'placeholder' => __( 'Required for companies (can be auto-filled)', 'mydyma-taiwan-commerce-suite' ),
				'required'    => false,
				'class'       => [ 'form-row-last', 'mydyma-taiwan-commerce-suite-company-field' ],
				'priority'    => 122,
			];
		}
		
		// 3. Add Carrier / Donation Code Field
		$fields['billing']['billing_mydyma_tcs_carrier_number'] = [
			'type'        => 'text',
			'label'       => __( 'Carrier / Donation Code', 'mydyma-taiwan-commerce-suite' ),
			'placeholder' => __( '/ABC+123 or 3-7 digits', 'mydyma-taiwan-commerce-suite' ),
			'required'    => false,
			'class'       => [ 'form-row-wide', 'mydyma-taiwan-commerce-suite-carrier-field' ],
			'priority'    => 123,
		];

		return $fields;
	}

	public function customize_default_fields( array $fields ): array {
		// Customize address labels for Taiwan
		if ( isset( $fields['state'] ) ) {
			$fields['state']['label'] = __( 'City/County', 'mydyma-taiwan-commerce-suite' );
		}
		if ( isset( $fields['city'] ) ) {
			$fields['city']['label'] = __( 'District', 'mydyma-taiwan-commerce-suite' );
		}
		return $fields;
	}

	/**
	 * AJAX Handler for Tax ID Lookup (Optimized with Dual API & Transients).
	 */
	public function ajax_lookup_tax_id(): void {
		check_ajax_referer( 'mydyma_tcs_lookup_tax_id', 'nonce' );

		// Server-side opt-in check: lookup must be explicitly enabled by the site owner.
		// External GCIS API calls are off by default to respect customer privacy.
		if ( 'yes' !== get_option( 'mydyma_tcs_checkout_lookup_tax_id', 'no' ) ) {
			wp_send_json_error( __( 'Tax ID lookup is disabled.', 'mydyma-taiwan-commerce-suite' ) );
			return;
		}

		$tax_id = sanitize_text_field( wp_unslash( $_POST['tax_id'] ?? '' ) );
		if ( ! preg_match( '/^[0-9]{8}$/', $tax_id ) ) {
			wp_send_json_error( __( 'Invalid Tax ID format.', 'mydyma-taiwan-commerce-suite' ) );
			return;
		}

		// 1. Check Cache
		$cache_key = 'mydyma_tcs_tax_id_cache_' . $tax_id;
		$cached_name = get_transient( $cache_key );
		if ( $cached_name ) {
			wp_send_json_success( [ 'name' => $cached_name, 'cached' => true ] );
		}

		// 2. Try Company API first, then Business API
		$uuid    = get_option( 'mydyma_tcs_gcis_api_uuid', '236EE382-4942-41A9-BD03-CA0709025E7C' );
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
			set_transient( 'mydyma_tcs_gcis_api_failed', $error_log, HOUR_IN_SECONDS );
		} else {
			$http_code = wp_remote_retrieve_response_code( $response );
			$body      = wp_remote_retrieve_body( $response );
			$data      = json_decode( $body, true );

			if ( $http_code !== 200 || ( is_string( $body ) && strpos( $body, '此API不存在' ) !== false ) ) {
				// translators: %s is HTTP status code
				set_transient( 'mydyma_tcs_gcis_api_failed', sprintf( __( 'HTTP %s：UUID 可能已失效，請至後台結帳設定更新 GCIS API UUID。', 'mydyma-taiwan-commerce-suite' ), $http_code ), HOUR_IN_SECONDS );
			} elseif ( ! empty( $data ) && is_array( $data ) ) {
				$name = $data[0]['Company_Name'] ?? '';
			}
		}

		if ( $name ) {
			delete_transient( 'mydyma_tcs_gcis_api_failed' );
			set_transient( $cache_key, $name, DAY_IN_SECONDS );
			wp_send_json_success( [ 'name' => $name, 'cached' => false ] );
		}

		wp_send_json_error( [
			'message' => $error_log ?: __( '查無此公司資料。', 'mydyma-taiwan-commerce-suite' ),
			'tax_id'  => $tax_id,
		] );
	}
}