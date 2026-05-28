<?php
namespace Mydybox\Modules\Checkout_Tw;

defined( 'ABSPATH' ) || exit;

/**
 * NewebPay (藍新) CVS Shipping Module.
 *
 * 藍新物流超商取貨地圖選店。
 * API 文件：https://developers.newebpay.com/
 * 選店方式：POST form 導頁至藍新地圖，選完後 ServerReplyURL 回傳。
 *
 * Staging endpoint：https://cvsmap.newebpay.com/search/select
 * 正式 endpoint：https://cvsmap.newebpay.com/search/select
 * （藍新測試與正式使用相同地圖 URL，以 MerchantID 區分環境）
 */
class NewebPay_CVS_Shipping {

	const MAP_URL = 'https://cvsmap.newebpay.com/search/select';

	public function boot(): void {
		if ( 'yes' !== get_option( 'mydybox_newebpay_cvs_enabled', 'no' ) ) {
			return;
		}

		add_filter( 'woocommerce_shipping_methods', [ $this, 'register_shipping_method' ] );
		add_action( 'woocommerce_shipping_init', [ $this, 'load_shipping_method' ] );

		add_action( 'wp_ajax_mydybox_open_newebpay_cvs_map', [ $this, 'ajax_open_map' ] );
		add_action( 'wp_ajax_nopriv_mydybox_open_newebpay_cvs_map', [ $this, 'ajax_open_map' ] );

		add_action( 'wp_ajax_mydybox_newebpay_cvs_callback', [ $this, 'ajax_map_callback' ] );
		add_action( 'wp_ajax_nopriv_mydybox_newebpay_cvs_callback', [ $this, 'ajax_map_callback' ] );

		add_action( 'woocommerce_checkout_create_order', [ $this, 'save_store_to_order' ], 10, 2 );

		add_action( 'woocommerce_order_details_after_order_table', [ $this, 'display_store_in_order' ] );
		add_action( 'woocommerce_email_order_meta', [ $this, 'display_store_in_email' ], 10, 3 );
		add_action( 'woocommerce_admin_order_data_after_shipping_address', [ $this, 'display_store_in_admin' ] );
	}

	public function load_shipping_method(): void {
		if ( class_exists( 'WC_Shipping_Method' ) ) {
			require_once __DIR__ . '/class-newebpay-cvs-shipping-method.php';
		}
	}

	public function register_shipping_method( array $methods ): array {
		$methods['taiwan_store_newebpay_cvs'] = '\Mydybox\Modules\Checkout_Tw\NewebPay_CVS_Shipping_Method';
		return $methods;
	}

	/**
	 * AJAX：產生藍新地圖選店表單並回傳。
	 */
	public function ajax_open_map(): void {
		check_ajax_referer( 'mydybox_cvs_map', 'nonce' );

		$cvs_type    = sanitize_text_field( wp_unslash( $_POST['cvs_type'] ?? 'SEVEN' ) );
		$is_test     = 'yes' === get_option( 'mydybox_newebpay_cvs_test_mode', 'yes' );
		$merchant_id = $is_test ? 'TestMerchant' : get_option( 'mydybox_newebpay_cvs_merchant_id', '' );
		$hash_key    = $is_test ? 'TestKey123456789' : get_option( 'mydybox_newebpay_cvs_hash_key', '' );
		$hash_iv     = $is_test ? 'TestIV1234567890' : get_option( 'mydybox_newebpay_cvs_hash_iv', '' );

		$callback_url = admin_url( 'admin-ajax.php?action=mydybox_newebpay_cvs_callback&nonce=' . wp_create_nonce( 'mydybox_newebpay_cvs_callback' ) );

		$params = [
			'MerchantID'  => $merchant_id,
			'CVSType'     => $cvs_type,
			'ReturnURL'   => $callback_url,
			'TimeStamp'   => time(),
		];

		// 藍新地圖使用 SHA256 + 加密方式與 ECPay 不同，直接 POST form
		$form = '<form id="ts-newebpay-map-form" method="post" action="' . esc_url( self::MAP_URL ) . '">';
		foreach ( $params as $k => $v ) {
			$form .= '<input type="hidden" name="' . esc_attr( $k ) . '" value="' . esc_attr( $v ) . '">';
		}
		$form .= '</form>'; // submit() is triggered by cvs-map.js after document.write to avoid an inline script tag.

		wp_send_json_success( [ 'form' => $form ] );
	}

	/**
	 * AJAX：接收藍新地圖回傳的門市資料。
	 * 藍新回傳欄位：CVSStoreID、CVSStoreName、CVSAddress、CVSType
	 */
	public function ajax_map_callback(): void {
		check_ajax_referer( 'mydybox_newebpay_cvs_callback', 'nonce' );
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- nonce verified via check_ajax_referer above
		$store_id   = sanitize_text_field( wp_unslash( $_POST['CVSStoreID']   ?? $_GET['CVSStoreID']   ?? '' ) );
		$store_name = sanitize_text_field( wp_unslash( $_POST['CVSStoreName'] ?? $_GET['CVSStoreName'] ?? '' ) );
		$store_addr = sanitize_text_field( wp_unslash( $_POST['CVSAddress']   ?? $_GET['CVSAddress']   ?? '' ) );
		$cvs_type   = sanitize_text_field( wp_unslash( $_POST['CVSType']      ?? $_GET['CVSType']      ?? '' ) );

		if ( ! $store_id ) {
			wp_die( 'Missing store data', 400 );
		}

		WC()->session->set( 'mydybox_newebpay_cvs_store', [
			'id'   => $store_id,
			'name' => $store_name,
			'addr' => $store_addr,
			'type' => $cvs_type,
		] );

		header( 'Content-Type: text/html; charset=utf-8' );
		$store_json = wp_json_encode( [
			'id'       => $store_id,
			'name'     => $store_name,
			'addr'     => $store_addr,
			'type'     => $cvs_type,
			'provider' => 'newebpay',
		] );
		echo '<!DOCTYPE html><html><body>';
		wp_print_inline_script_tag(
			'var store = ' . $store_json . ';' .
			'if (window.opener) { window.opener.postMessage({ type: "mydybox_cvs_store", store: store }, "*"); window.close(); }'
		);
		echo '</body></html>';
		exit;
	}

	public function save_store_to_order( \WC_Order $order, array $data ): void {
		// 只在選用藍新配送方式時儲存
		$chosen = WC()->session->get( 'chosen_shipping_methods', [] );
		$is_newebpay = false;
		foreach ( $chosen as $method ) {
			if ( str_starts_with( $method, 'taiwan_store_newebpay_cvs' ) ) {
				$is_newebpay = true;
				break;
			}
		}
		if ( ! $is_newebpay ) return;

		// Called via woocommerce_checkout_create_order; WooCommerce verifies the checkout nonce upstream.
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- nonce verified by WooCommerce checkout
		$store_id   = sanitize_text_field( wp_unslash( $_POST['mydybox_cvs_store_id']   ?? '' ) );
		$store_name = sanitize_text_field( wp_unslash( $_POST['mydybox_cvs_store_name'] ?? '' ) );
		$store_addr = sanitize_text_field( wp_unslash( $_POST['mydybox_cvs_store_addr'] ?? '' ) );
		$store_type = sanitize_text_field( wp_unslash( $_POST['mydybox_cvs_store_type'] ?? '' ) );
		// phpcs:enable

		if ( ! $store_id ) return;

		$order->update_meta_data( 'mydybox_cvs_store_id',       $store_id );
		$order->update_meta_data( 'mydybox_cvs_store_name',     $store_name );
		$order->update_meta_data( 'mydybox_cvs_store_addr',     $store_addr );
		$order->update_meta_data( 'mydybox_cvs_store_type',     $store_type );
		$order->update_meta_data( 'mydybox_cvs_store_provider', 'newebpay' );

		$type_label = $this->get_cvs_label( $store_type );
		$order->set_shipping_address_1( "[{$type_label}] {$store_name}" );
		$order->set_shipping_address_2( $store_addr );
	}

	public function display_store_in_order( \WC_Order $order ): void {
		if ( $order->get_meta( 'mydybox_cvs_store_provider' ) !== 'newebpay' ) return;
		if ( ! $order->get_meta( 'mydybox_cvs_store_id' ) ) return;
		$this->render_store_block( $order );
	}

	public function display_store_in_email( \WC_Order $order, bool $sent_to_admin, string $plain_text ): void {
		if ( $order->get_meta( 'mydybox_cvs_store_provider' ) !== 'newebpay' ) return;
		if ( ! $order->get_meta( 'mydybox_cvs_store_id' ) ) return;
		if ( $plain_text ) {
			echo "\n" . esc_html__( '取貨門市', 'mydybox-taiwan-for-woocommerce' ) . ': ' . esc_html( $order->get_meta( 'mydybox_cvs_store_name' ) ) . ' - ' . esc_html( $order->get_meta( 'mydybox_cvs_store_addr' ) ) . "\n";
		} else {
			$this->render_store_block( $order );
		}
	}

	public function display_store_in_admin( \WC_Order $order ): void {
		if ( $order->get_meta( 'mydybox_cvs_store_provider' ) !== 'newebpay' ) return;
		if ( ! $order->get_meta( 'mydybox_cvs_store_id' ) ) return;
		echo '<div style="margin-top:10px;padding:10px;background:#f0f9ff;border:1px solid #bae6fd;border-radius:6px;">';
		echo '<strong>🏪 ' . esc_html__( '超商取貨門市（藍新）', 'mydybox-taiwan-for-woocommerce' ) . '</strong><br>';
		echo esc_html( $this->get_cvs_label( $order->get_meta( 'mydybox_cvs_store_type' ) ) ) . ' ';
		echo esc_html( $order->get_meta( 'mydybox_cvs_store_name' ) ) . '<br>';
		echo '<small>' . esc_html( $order->get_meta( 'mydybox_cvs_store_addr' ) ) . '</small>';
		echo '</div>';
	}

	private function render_store_block( \WC_Order $order ): void {
		$label = $this->get_cvs_label( $order->get_meta( 'mydybox_cvs_store_type' ) );
		$name  = $order->get_meta( 'mydybox_cvs_store_name' );
		$addr  = $order->get_meta( 'mydybox_cvs_store_addr' );
		echo '<section class="ts-cvs-order-info" style="margin:1rem 0;padding:1rem;background:#f0fdf4;border:1px solid #86efac;border-radius:8px;">';
		echo '<h3 style="margin:0 0 .5rem;font-size:1rem;">🏪 ' . esc_html__( '取貨門市資訊（藍新）', 'mydybox-taiwan-for-woocommerce' ) . '</h3>';
		echo '<p style="margin:0;">' . esc_html( $label ) . '｜' . esc_html( $name ) . '</p>';
		echo '<p style="margin:.25rem 0 0;color:#6b7280;font-size:.875rem;">' . esc_html( $addr ) . '</p>';
		echo '</section>';
	}

	private function get_cvs_label( string $type ): string {
		$map = [
			'SEVEN'   => '7-ELEVEN',
			'FAMILY'  => '全家 FamilyMart',
			'HILIFE'  => '萊爾富',
			'OK'      => 'OK 超商',
		];
		return $map[ $type ] ?? $type;
	}
}
