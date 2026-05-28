<?php
namespace Taiwan_Store_Core\Modules\Invoice;

defined( 'ABSPATH' ) || exit;

/**
 * ECPay B2C Electronic Invoice Integration.
 *
 * API Reference: https://developers.ecpay.com.tw/?p=7809
 * Supports: Individual / Mobile Carrier / Citizen Digital Certificate / Donation / Company (3-part)
 */
class ECPay_Invoice {

	const API_STAGE = 'https://einvoice-stage.ecpay.com.tw/B2CInvoice/Issue';
	const API_LIVE  = 'https://einvoice.ecpay.com.tw/B2CInvoice/Issue';

	const VOID_STAGE = 'https://einvoice-stage.ecpay.com.tw/B2CInvoice/Invalid';
	const VOID_LIVE  = 'https://einvoice.ecpay.com.tw/B2CInvoice/Invalid';

	public function boot(): void {
		// Auto-issue invoice when order status changes to processing/completed
		$trigger = get_option( 'ts_invoice_trigger', 'processing' );
		add_action( "woocommerce_order_status_{$trigger}", [ $this, 'auto_issue_on_status' ], 10, 2 );

		// Manual issue / void from order edit page
		add_action( 'woocommerce_order_actions', [ $this, 'add_order_actions' ] );
		add_action( 'woocommerce_order_action_ts_issue_invoice', [ $this, 'manual_issue' ] );
		add_action( 'woocommerce_order_action_ts_void_invoice',  [ $this, 'manual_void' ] );

		// Display invoice info in order admin
		add_action( 'woocommerce_admin_order_data_after_billing_address', [ $this, 'display_invoice_in_admin' ] );

		// Display in order thank-you page and email
		add_action( 'woocommerce_thankyou', [ $this, 'display_invoice_thankyou' ] );
		add_action( 'woocommerce_email_order_meta', [ $this, 'display_invoice_in_email' ], 10, 3 );
	}

	/* ────────────────────────────────────────────
	 * Trigger Hooks
	 * ──────────────────────────────────────────── */

	public function auto_issue_on_status( int $order_id, \WC_Order $order ): void {
		if ( 'yes' !== get_option( 'ts_invoice_auto_issue', 'yes' ) ) return;
		if ( $order->get_meta( 'ts_invoice_number' ) ) return; // already issued
		$this->issue_invoice( $order );
	}

	public function manual_issue( \WC_Order $order ): void {
		$result = $this->issue_invoice( $order );
		if ( is_wp_error( $result ) ) {
			// translators: %s is error message
			$order->add_order_note( '❌ ' . sprintf( __( '手動開立發票失敗：%s', 'taiwan-store-core' ), $result->get_error_message() ) );
		}
	}

	public function manual_void( \WC_Order $order ): void {
		$result = $this->void_invoice( $order );
		if ( is_wp_error( $result ) ) {
			// translators: %s is error message
			$order->add_order_note( '❌ ' . sprintf( __( '作廢發票失敗：%s', 'taiwan-store-core' ), $result->get_error_message() ) );
		}
	}

	public function add_order_actions( array $actions ): array {
		global $theorder;
		if ( ! $theorder ) return $actions;

		if ( ! $theorder->get_meta( 'ts_invoice_number' ) ) {
			$actions['ts_issue_invoice'] = '🧾 ' . __( '開立電子發票', 'taiwan-store-core' );
		} else {
			$actions['ts_void_invoice'] = '❌ ' . __( '作廢電子發票', 'taiwan-store-core' );
		}
		return $actions;
	}

	/* ────────────────────────────────────────────
	 * Core: Issue Invoice
	 * ──────────────────────────────────────────── */

	public function issue_invoice( \WC_Order $order ): true|\WP_Error {
		$is_test     = 'yes' === get_option( 'ts_invoice_test_mode', 'yes' );
		$merchant_id = $is_test ? '3002607'          : get_option( 'ts_invoice_merchant_id', '' );
		$hash_key    = $is_test ? 'pwFHCqoQZGmho4w6' : get_option( 'ts_invoice_hash_key', '' );
		$hash_iv     = $is_test ? 'EkRm7iFT261dpevs' : get_option( 'ts_invoice_hash_iv', '' );
		$endpoint    = $is_test ? self::API_STAGE     : self::API_LIVE;

		$invoice_type  = $order->get_meta( 'ts_invoice_type' ) ?: 'individual';
		$customer_name = trim( $order->get_billing_last_name() . $order->get_billing_first_name() ) ?: __( '消費者', 'taiwan-store-core' );
		$customer_email = $order->get_billing_email();

		// Build item list
		[ $item_name, $item_count, $item_word, $item_price, $item_amount, $item_tax_type ] = $this->build_item_params( $order );

		// Base params
		$params = [
			'MerchantID'      => $merchant_id,
			'RelateNumber'    => 'TW' . gmdate( 'ymdHis' ) . substr( $order->get_id(), -4 ),
			'CustomerID'      => (string) $order->get_customer_id(),
			'CustomerName'    => $this->sanitize_invoice_string( $customer_name, 20 ),
			'CustomerAddr'    => $this->sanitize_invoice_string( $order->get_billing_address_1(), 100 ),
			'CustomerPhone'   => preg_replace( '/[^0-9]/', '', $order->get_billing_phone() ),
			'CustomerEmail'   => $customer_email,
			'ClearanceMark'   => '',
			'Print'           => '0',
			'Donation'        => '0',
			'LoveCode'        => '',
			'CarrierType'     => '',
			'CarrierNum'      => '',
			'TaxType'         => '1', // 1=應稅
			'SalesAmount'     => (int) $order->get_total(),
			'InvoiceRemark'   => __( '網路購物', 'taiwan-store-core' ),
			'ItemName'        => $item_name,
			'ItemCount'       => $item_count,
			'ItemWord'        => $item_word,
			'ItemPrice'       => $item_price,
			'ItemTaxType'     => $item_tax_type,
			'ItemAmount'      => $item_amount,
			'InvType'         => '07', // 07=一般稅額
			'vat'             => '1',
		];

		// Adjust params by invoice type
		switch ( $invoice_type ) {
			case 'carrier_phone':
				$params['CarrierType'] = '3';
				$params['CarrierNum']  = $order->get_meta( 'ts_carrier_number' ) ?: '';
				break;

			case 'carrier_cert':
				$params['CarrierType'] = '2';
				$params['CarrierNum']  = $order->get_meta( 'ts_carrier_number' ) ?: '';
				break;

			case 'donate':
				$params['Donation']  = '1';
				$params['LoveCode']  = $order->get_meta( 'ts_carrier_number' ) ?: '168001';
				break;

			case 'company':
				$params['Print']          = '1';
				$params['CustomerName']   = $this->sanitize_invoice_string( $order->get_meta( 'ts_company_title' ) ?: $customer_name, 20 );
				$params['CustomerIdentifier'] = $order->get_meta( 'ts_company_tax_id' ) ?: '';
				break;

			case 'individual':
			default:
				// Cloud invoice, no carrier
				break;
		}

		$params['CheckMacValue'] = $this->generate_check_mac( $params, $hash_key, $hash_iv );

		$response = wp_remote_post( $endpoint, [
			'body'      => [ 'MerchantID' => $merchant_id, 'RqHeader' => $this->build_rq_header(), 'Data' => $this->encrypt_data( $params, $hash_key, $hash_iv ) ],
			'timeout'   => 15,
			'sslverify' => ! ( defined( 'WP_DEBUG' ) && WP_DEBUG ),
		] );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body   = json_decode( wp_remote_retrieve_body( $response ), true );
		$result = $this->decrypt_data( $body['Data'] ?? '', $hash_key, $hash_iv );

		if ( ( $result['RtnCode'] ?? '' ) !== '1' ) {
			return new \WP_Error( 'invoice_failed', $result['RtnMsg'] ?? __( '開立失敗', 'taiwan-store-core' ) );
		}

		// Save invoice info to order
		$order->update_meta_data( 'ts_invoice_number',   $result['InvoiceNo'] ?? '' );
		$order->update_meta_data( 'ts_invoice_date',     $result['InvoiceDate'] ?? '' );
		$order->update_meta_data( 'ts_invoice_random',   $result['RandomNumber'] ?? '' );
		$order->update_meta_data( 'ts_invoice_type',     $invoice_type );
		$order->save();

		$order->add_order_note( '🧾 ' . sprintf(
			// translators: %1$s is invoice number, %2$s is invoice date, %3$s is random code
			__( '電子發票開立成功。發票號碼：%1$s，日期：%2$s，隨機碼：%3$s', 'taiwan-store-core' ),
			$result['InvoiceNo'] ?? '',
			$result['InvoiceDate'] ?? '',
			$result['RandomNumber'] ?? ''
		) );

		return true;
	}

	/* ────────────────────────────────────────────
	 * Core: Void Invoice
	 * ──────────────────────────────────────────── */

	public function void_invoice( \WC_Order $order ): true|\WP_Error {
		$invoice_no = $order->get_meta( 'ts_invoice_number' );
		if ( ! $invoice_no ) {
			return new \WP_Error( 'no_invoice', __( '此訂單尚未開立發票', 'taiwan-store-core' ) );
		}

		$is_test     = 'yes' === get_option( 'ts_invoice_test_mode', 'yes' );
		$merchant_id = $is_test ? '3002607'          : get_option( 'ts_invoice_merchant_id', '' );
		$hash_key    = $is_test ? 'pwFHCqoQZGmho4w6' : get_option( 'ts_invoice_hash_key', '' );
		$hash_iv     = $is_test ? 'EkRm7iFT261dpevs' : get_option( 'ts_invoice_hash_iv', '' );
		$endpoint    = $is_test ? self::VOID_STAGE    : self::VOID_LIVE;

		$params = [
			'MerchantID'    => $merchant_id,
			'InvoiceNumber' => $invoice_no,
			'InvoiceDate'   => $order->get_meta( 'ts_invoice_date' ),
			'Reason'        => __( '訂單取消', 'taiwan-store-core' ),
		];
		$params['CheckMacValue'] = $this->generate_check_mac( $params, $hash_key, $hash_iv );

		$response = wp_remote_post( $endpoint, [
			'body'      => [ 'MerchantID' => $merchant_id, 'RqHeader' => $this->build_rq_header(), 'Data' => $this->encrypt_data( $params, $hash_key, $hash_iv ) ],
			'timeout'   => 15,
			'sslverify' => ! ( defined( 'WP_DEBUG' ) && WP_DEBUG ),
		] );

		if ( is_wp_error( $response ) ) return $response;

		$body   = json_decode( wp_remote_retrieve_body( $response ), true );
		$result = $this->decrypt_data( $body['Data'] ?? '', $hash_key, $hash_iv );

		if ( ( $result['RtnCode'] ?? '' ) !== '1' ) {
			return new \WP_Error( 'void_failed', $result['RtnMsg'] ?? __( '作廢失敗', 'taiwan-store-core' ) );
		}

		$order->update_meta_data( 'ts_invoice_voided', '1' );
		$order->save();
		// translators: %s is invoice number
		$order->add_order_note( '🗑️ ' . sprintf( __( '電子發票已作廢。發票號碼：%s', 'taiwan-store-core' ), $invoice_no ) );

		return true;
	}

	/* ────────────────────────────────────────────
	 * Display
	 * ──────────────────────────────────────────── */

	public function display_invoice_in_admin( \WC_Order $order ): void {
		$no = $order->get_meta( 'ts_invoice_number' );
		if ( ! $no ) return;

		$voided = $order->get_meta( 'ts_invoice_voided' );
		$date   = $order->get_meta( 'ts_invoice_date' );
		$random = $order->get_meta( 'ts_invoice_random' );

		echo '<div style="margin-top:12px;padding:10px 14px;background:' . ( $voided ? '#fef2f2' : '#f0fdf4' ) . ';border:1px solid ' . ( $voided ? '#fca5a5' : '#86efac' ) . ';border-radius:6px;">';
		echo '<strong>🧾 ' . ( $voided ? esc_html__( '（已作廢）', 'taiwan-store-core' ) : '' ) . esc_html__( '電子發票', 'taiwan-store-core' ) . '</strong><br>';
		echo esc_html__( '號碼：', 'taiwan-store-core' ) . '<strong>' . esc_html( $no ) . '</strong><br>';
		echo esc_html__( '日期：', 'taiwan-store-core' ) . esc_html( $date ) . '　' . esc_html__( '隨機碼：', 'taiwan-store-core' ) . esc_html( $random );
		echo '</div>';
	}

	public function display_invoice_thankyou( int $order_id ): void {
		$order = wc_get_order( $order_id );
		$no    = $order ? $order->get_meta( 'ts_invoice_number' ) : '';
		if ( ! $no ) return;
		echo '<section style="margin:1.5rem 0;padding:1rem;background:#f0fdf4;border:1px solid #86efac;border-radius:8px;">';
		echo '<h3 style="margin:0 0 .5rem;">🧾 ' . esc_html__( '電子發票資訊', 'taiwan-store-core' ) . '</h3>';
		echo '<p style="margin:0;">' . esc_html__( '發票號碼：', 'taiwan-store-core' ) . '<strong>' . esc_html( $no ) . '</strong></p>';
		echo '<p style="margin:.25rem 0 0;font-size:.875rem;color:#6b7280;">' . esc_html__( '發票已開立，如有需要請洽客服。', 'taiwan-store-core' ) . '</p>';
		echo '</section>';
	}

	public function display_invoice_in_email( \WC_Order $order, bool $sent_to_admin, bool $plain_text ): void {
		$no = $order->get_meta( 'ts_invoice_number' );
		if ( ! $no ) return;
		if ( $plain_text ) {
			// translators: %s is invoice number
			echo "\n" . esc_html( sprintf( __( '電子發票號碼：%s', 'taiwan-store-core' ), $no ) ) . "\n";
		} else {
			echo '<p style="padding:8px 12px;background:#f0fdf4;border-left:3px solid #22c55e;">🧾 ' . esc_html__( '電子發票號碼：', 'taiwan-store-core' ) . '<strong>' . esc_html( $no ) . '</strong></p>';
		}
	}

	/* ────────────────────────────────────────────
	 * Helpers
	 * ──────────────────────────────────────────── */

	private function build_item_params( \WC_Order $order ): array {
		$names = $counts = $words = $prices = $amounts = $tax_types = [];

		foreach ( $order->get_items() as $item ) {
			$names[]     = $this->sanitize_invoice_string( $item->get_name(), 100 );
			$counts[]    = $item->get_quantity();
			$words[]     = __( '個', 'taiwan-store-core' );
			$qty         = max( 1, (int) $item->get_quantity() );
			$prices[]    = (int) ( $item->get_subtotal() / $qty );
			$amounts[]   = (int) $item->get_subtotal();
			$tax_types[] = '1';
		}

		// Add shipping if present
		$shipping = (int) $order->get_shipping_total();
		if ( $shipping > 0 ) {
			$names[]     = __( '運費', 'taiwan-store-core' );
			$counts[]    = 1;
			$words[]     = __( '式', 'taiwan-store-core' );
			$prices[]    = $shipping;
			$amounts[]   = $shipping;
			$tax_types[] = '1';
		}

		$sep = '|';
		return [
			implode( $sep, $names ),
			implode( $sep, $counts ),
			implode( $sep, $words ),
			implode( $sep, $prices ),
			implode( $sep, $amounts ),
			implode( $sep, $tax_types ),
		];
	}

	private function build_rq_header(): string {
		return wp_json_encode( [
			'Timestamp' => time(),
			'Revision'  => '3.0.0',
		] );
	}

	/**
	 * AES-128-CBC encrypt for ECPay invoice API data parameter.
	 */
	private function encrypt_data( array $params, string $hash_key, string $hash_iv ): string {
		$json    = json_encode( $params, JSON_UNESCAPED_UNICODE );
		$padded  = $this->pkcs7_pad( $json, 32 );
		$encrypt = openssl_encrypt( $padded, 'AES-128-CBC', $hash_key, OPENSSL_RAW_DATA, $hash_iv );
		return base64_encode( $encrypt );
	}

	private function decrypt_data( string $data, string $hash_key, string $hash_iv ): array {
		if ( ! $data ) return [];
		$decoded  = base64_decode( $data );
		$decrypted = openssl_decrypt( $decoded, 'AES-128-CBC', $hash_key, OPENSSL_RAW_DATA, $hash_iv );
		$unpadded = $this->pkcs7_unpad( $decrypted );
		return json_decode( $unpadded, true ) ?: [];
	}

	private function pkcs7_pad( string $data, int $block_size ): string {
		$pad = $block_size - ( strlen( $data ) % $block_size );
		return $data . str_repeat( chr( $pad ), $pad );
	}

	private function pkcs7_unpad( string $data ): string {
		$pad = ord( $data[ strlen( $data ) - 1 ] );
		return substr( $data, 0, -$pad );
	}

	private function sanitize_invoice_string( string $str, int $max ): string {
		$str = preg_replace( '/[^\x{4e00}-\x{9fff}\w\s\-\.,]/u', '', $str );
		return mb_substr( $str, 0, $max );
	}

	/**
	 * Generate CheckMacValue (URL encode + SHA256) — same as payment gateway.
	 */
	private function generate_check_mac( array $params, string $hash_key, string $hash_iv ): string {
		ksort( $params );
		$str = "HashKey={$hash_key}";
		foreach ( $params as $k => $v ) {
			$str .= "&{$k}={$v}";
		}
		$str .= "&HashIV={$hash_iv}";
		$str  = strtolower( urlencode( $str ) );
		foreach ( [ '%2d' => '-', '%5f' => '_', '%2e' => '.', '%21' => '!', '%2a' => '*', '%28' => '(', '%29' => ')' ] as $from => $to ) {
			$str = str_replace( $from, $to, $str );
		}
		return strtoupper( hash( 'sha256', $str ) );
	}
}
