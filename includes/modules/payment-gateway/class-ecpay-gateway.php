<?php
namespace Taiwan_Store_Core\Modules\Payment_Gateway;

defined( 'ABSPATH' ) || exit;

/**
 * ECPay AIO Payment Gateway for WooCommerce.
 * Supports: Credit Card, ATM, CVS Code, WebATM, TWQR (LINE Pay)
 */
class ECPay_Gateway extends \WC_Payment_Gateway {

	const API_STAGE = 'https://payment-stage.ecpay.com.tw/Cashier/AioCheckOut/V5';
	const API_LIVE  = 'https://payment.ecpay.com.tw/Cashier/AioCheckOut/V5';

	const QUERY_STAGE = 'https://payment-stage.ecpay.com.tw/Cashier/QueryTradeInfo/V5';
	const QUERY_LIVE  = 'https://payment.ecpay.com.tw/Cashier/QueryTradeInfo/V5';

	public function __construct() {
		$this->id                 = 'taiwan_store_core_ecpay';
		$this->method_title       = __( 'ECPay 綠界金流', 'taiwan-store-core' );
		$this->method_description = __( '支援信用卡、ATM、超商代碼、WebATM、TWQR（LINE Pay）', 'taiwan-store-core' );
		$this->has_fields         = false;
		$this->supports           = [ 'products', 'refunds' ];

		$this->init_form_fields();
		$this->init_settings();

		$this->title       = $this->get_option( 'title', __( 'ECPay 綠界金流', 'taiwan-store-core' ) );
		$this->description = $this->get_option( 'description', '' );

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, [ $this, 'process_admin_options' ] );
		add_action( 'woocommerce_api_taiwan_store_core_ecpay', [ $this, 'handle_callback' ] );
		add_action( 'woocommerce_receipt_' . $this->id, [ $this, 'render_payment_form' ] );
	}

	public function init_form_fields(): void {
		$this->form_fields = [
			'enabled' => [
				'title'   => __( '啟用', 'taiwan-store-core' ),
				'type'    => 'checkbox',
				'default' => 'no',
			],
			'title' => [
				'title'   => __( '付款方式名稱', 'taiwan-store-core' ),
				'type'    => 'text',
				'default' => 'ECPay 綠界金流',
			],
			'description' => [
				'title'   => __( '說明文字', 'taiwan-store-core' ),
				'type'    => 'textarea',
				'default' => '支援信用卡、ATM 轉帳、超商代碼繳費',
			],
			'test_mode' => [
				'title'   => __( '測試模式', 'taiwan-store-core' ),
				'type'    => 'checkbox',
				'default' => 'yes',
				'description' => __( '開啟時使用 ECPay Staging（MerchantID: 3002607）', 'taiwan-store-core' ),
			],
			'merchant_id' => [
				'title'       => __( '正式 MerchantID', 'taiwan-store-core' ),
				'type'        => 'text',
				'description' => __( '測試模式時無需填寫', 'taiwan-store-core' ),
			],
			'hash_key' => [
				'title' => __( '正式 HashKey', 'taiwan-store-core' ),
				'type'  => 'password',
			],
			'hash_iv' => [
				'title' => __( '正式 HashIV', 'taiwan-store-core' ),
				'type'  => 'password',
			],
			'choose_payment' => [
				'title'   => __( '預設付款方式', 'taiwan-store-core' ),
				'type'    => 'select',
				'default' => 'ALL',
				'options' => [
					'ALL'     => '全部（讓用戶選擇）',
					'Credit'  => '信用卡',
					'ATM'     => 'ATM 轉帳',
					'CVS'     => '超商代碼',
					'WebATM'  => 'WebATM',
					'TWQR'    => 'TWQR（LINE Pay / 街口）',
				],
			],
			'installment' => [
				'title'       => __( '信用卡分期', 'taiwan-store-core' ),
				'type'        => 'text',
				'default'     => '',
				'description' => __( '填入可分期期數，逗號分隔，例如：3,6,12。空白表示不啟用分期。', 'taiwan-store-core' ),
			],
		];
	}

	public function process_payment( $order_id ): array {
		$order = wc_get_order( $order_id );
		$order->update_status( 'pending', __( '等待 ECPay 付款確認', 'taiwan-store-core' ) );

		return [
			'result'   => 'success',
			'redirect' => $order->get_checkout_payment_url( true ),
		];
	}

	/**
	 * Render the auto-submit form to redirect to ECPay payment page.
	 */
	public function render_payment_form( int $order_id ): void {
		$order       = wc_get_order( $order_id );
		$is_test     = 'yes' === $this->get_option( 'test_mode', 'yes' );
		$merchant_id = $is_test ? '3002607'          : $this->get_option( 'merchant_id' );
		$hash_key    = $is_test ? 'pwFHCqoQZGmho4w6' : $this->get_option( 'hash_key' );
		$hash_iv     = $is_test ? 'EkRm7iFT261dpevs' : $this->get_option( 'hash_iv' );
		$endpoint    = $is_test ? self::API_STAGE    : self::API_LIVE;

		// MerchantTradeNo: max 20 chars, alphanumeric only
		$trade_no = 'TW' . gmdate( 'ymdHis' ) . substr( (string) $order_id, -3 ) . wp_rand( 10, 99 );

		// Save trade_no to order for callback matching
		$order->update_meta_data( 'ts_ecpay_trade_no', $trade_no );
		$order->save();

		$item_name = implode( '#', array_map(
			fn( $item ) => mb_substr( $item->get_name(), 0, 30 ),
			array_values( $order->get_items() )
		) ) ?: '商品';

		$choose_payment = $this->get_option( 'choose_payment', 'ALL' );
		$installment    = trim( $this->get_option( 'installment', '' ) );

		$params = [
			'MerchantID'        => $merchant_id,
			'MerchantTradeNo'   => $trade_no,
			'MerchantTradeDate' => gmdate( 'Y/m/d H:i:s' ),
			'PaymentType'       => 'aio',
			'TotalAmount'       => (int) $order->get_total(),
			'TradeDesc'         => urlencode( get_bloginfo( 'name' ) . ' 訂單' ),
			'ItemName'          => $item_name,
			'ReturnURL'         => home_url( '/?wc-api=taiwan_store_core_ecpay' ),
			'OrderResultURL'    => $this->get_return_url( $order ),
			'ChoosePayment'     => $choose_payment,
			'EncryptType'       => '1',
			'ClientBackURL'     => wc_get_cart_url(),
			'CustomField1'      => (string) $order_id,
		];

		if ( $choose_payment === 'Credit' && $installment ) {
			$params['CreditInstallment'] = $installment;
			$params['InstallmentAmount'] = (int) $order->get_total();
		}

		$params['CheckMacValue'] = $this->generate_check_mac( $params, $hash_key, $hash_iv );

		// Render auto-submit form
		echo '<p>' . esc_html__( '即將跳轉至綠界付款頁面...', 'taiwan-store-core' ) . '</p>';
		echo '<form id="ts-ecpay-form" method="post" action="' . esc_url( $endpoint ) . '">';
		foreach ( $params as $k => $v ) {
			echo '<input type="hidden" name="' . esc_attr( $k ) . '" value="' . esc_attr( $v ) . '">';
		}
		echo '</form>';
		echo '<script>document.getElementById("ts-ecpay-form").submit();</script>';
	}

	/**
	 * Handle ECPay server-side callback (ReturnURL).
	 */
	public function handle_callback(): void {
		// ECPay callback: verify the CheckMacValue instead of a WP nonce (ECPay signs its own payload).
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- ECPay uses CheckMacValue for authenticity
		$post = $_POST;
		if ( empty( $post['MerchantID'] ) || empty( $post['MerchantTradeNo'] ) ) {
			echo '0|ErrorMessage';
			exit;
		}

		$is_test  = 'yes' === $this->get_option( 'test_mode', 'yes' );
		$hash_key = $is_test ? 'pwFHCqoQZGmho4w6' : $this->get_option( 'hash_key' );
		$hash_iv  = $is_test ? 'EkRm7iFT261dpevs' : $this->get_option( 'hash_iv' );

		// Verify CheckMacValue
		$received_mac = $post['CheckMacValue'];
		unset( $post['CheckMacValue'] );
		$expected_mac = $this->generate_check_mac( $post, $hash_key, $hash_iv );

		if ( strtoupper( $received_mac ) !== strtoupper( $expected_mac ) ) {
			echo '0|CheckMacValue Error';
			exit;
		}

		// Find order by CustomField1 (order_id)
		$order_id = absint( $post['CustomField1'] ?? 0 );
		$order    = $order_id ? wc_get_order( $order_id ) : null;

		if ( ! $order ) {
			echo '0|OrderNotFound';
			exit;
		}

		$rtn_code = $post['RtnCode'] ?? '0';

		if ( $rtn_code === '1' ) {
			$order->payment_complete( $post['TradeNo'] ?? '' );
			$order->add_order_note( sprintf(
				// translators: %1$s is ECPay transaction ID, %2$s is payment method string
				__( 'ECPay 付款成功。交易編號：%1$s，付款方式：%2$s', 'taiwan-store-core' ),
				$post['TradeNo'] ?? '',
				$post['PaymentType'] ?? ''
			) );
		} else {
			$order->update_status( 'failed', sprintf(
				// translators: %1$s is ECPay RtnCode number, %2$s is error message string
				__( 'ECPay 付款失敗。RtnCode：%1$s，訊息：%2$s', 'taiwan-store-core' ),
				$rtn_code,
				$post['RtnMsg'] ?? ''
			) );
		}

		echo '1|OK';
		exit;
	}

	/**
	 * Handle refunds.
	 */
	public function process_refund( $order_id, $amount = null, $reason = '' ): bool|\WP_Error {
		$order    = wc_get_order( $order_id );
		$trade_no = $order->get_meta( 'ts_ecpay_trade_no' );

		if ( ! $trade_no ) {
			return new \WP_Error( 'no_trade_no', __( '找不到 ECPay 交易編號，無法退款', 'taiwan-store-core' ) );
		}

		$is_test     = 'yes' === $this->get_option( 'test_mode', 'yes' );
		$merchant_id = $is_test ? '3002607'          : $this->get_option( 'merchant_id' );
		$hash_key    = $is_test ? 'pwFHCqoQZGmho4w6' : $this->get_option( 'hash_key' );
		$hash_iv     = $is_test ? 'EkRm7iFT261dpevs' : $this->get_option( 'hash_iv' );
		$endpoint    = $is_test
			? 'https://payment-stage.ecpay.com.tw/CreditDetail/DoAction'
			: 'https://payment.ecpay.com.tw/CreditDetail/DoAction';

		$params = [
			'MerchantID'      => $merchant_id,
			'MerchantTradeNo' => $trade_no,
			'TradeNo'         => $order->get_transaction_id(),
			'Action'          => 'R', // R = Refund
			'TotalAmount'     => (int) $amount,
		];
		$params['CheckMacValue'] = $this->generate_check_mac( $params, $hash_key, $hash_iv );

		$response = wp_remote_post( $endpoint, [
			'body'      => $params,
			'timeout'   => 15,
			'sslverify' => ! ( defined( 'WP_DEBUG' ) && WP_DEBUG ),
		] );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		parse_str( wp_remote_retrieve_body( $response ), $result );
		if ( ( $result['RtnCode'] ?? '' ) === '1' ) {
			// translators: %s is refund amount
			$order->add_order_note( sprintf( __( 'ECPay 退款成功：NT$ %s', 'taiwan-store-core' ), $amount ) );
			return true;
		}

		return new \WP_Error( 'refund_failed', $result['RtnMsg'] ?? __( '退款失敗', 'taiwan-store-core' ) );
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
		foreach ( [ '%2d' => '-', '%5f' => '_', '%2e' => '.', '%21' => '!', '%2a' => '*', '%28' => '(', '%29' => ')' ] as $from => $to ) {
			$str = str_replace( $from, $to, $str );
		}
		return strtoupper( hash( 'sha256', $str ) );
	}
}
