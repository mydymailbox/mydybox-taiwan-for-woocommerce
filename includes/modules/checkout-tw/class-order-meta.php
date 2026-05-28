<?php
namespace Mydyma_TCS\Modules\Checkout_Tw;

defined( 'ABSPATH' ) || exit;

/**
 * Order Meta Module.
 * Handles saving and displaying Taiwan-specific checkout fields.
 */
class Order_Meta {

	public function boot(): void {
		add_action( 'woocommerce_checkout_create_order', [ $this, 'save_invoice_meta' ], 10, 2 );
		add_action( 'woocommerce_admin_order_data_after_shipping_address', [ $this, 'display_invoice_meta_admin' ] );
	}

	public function save_invoice_meta( $order, $data ): void {
		// Called via woocommerce_checkout_create_order; WooCommerce verifies the checkout nonce upstream.
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- nonce verified by WooCommerce checkout
		$type    = sanitize_key( wp_unslash( $_POST['billing_mydyma_tcs_invoice_type'] ?? '' ) );
		$carrier = sanitize_text_field( wp_unslash( $_POST['billing_mydyma_tcs_carrier_number'] ?? '' ) );
		$tax_id  = sanitize_text_field( wp_unslash( $_POST['billing_mydyma_tcs_company_tax_id'] ?? '' ) );
		$title   = sanitize_text_field( wp_unslash( $_POST['billing_mydyma_tcs_company_title'] ?? '' ) );
		// phpcs:enable

		if ( $type ) {
			$order->update_meta_data( 'billing_mydyma_tcs_invoice_type', $type );
			$order->update_meta_data( 'billing_mydyma_tcs_carrier_number', $carrier );
			$order->update_meta_data( 'billing_mydyma_tcs_company_tax_id', $tax_id );
			$order->update_meta_data( 'billing_mydyma_tcs_company_title', $title );
		}
	}

	public function get_type_label( string $type ): string {
		$labels = [
			'individual'    => __( '個人電子發票（雲端）', 'mydyma-taiwan-commerce-suite' ),
			'carrier_phone' => __( '手機條碼', 'mydyma-taiwan-commerce-suite' ),
			'carrier_cert'  => __( '自然人憑證', 'mydyma-taiwan-commerce-suite' ),
			'donate'        => __( '捐贈碼', 'mydyma-taiwan-commerce-suite' ),
			'company'       => __( '公司三聯式（需統編）', 'mydyma-taiwan-commerce-suite' ),
		];
		return $labels[ $type ] ?? $type;
	}

	public function display_invoice_meta_admin( $order ): void {
		$type    = $order->get_meta( 'billing_mydyma_tcs_invoice_type' );
		$carrier = $order->get_meta( 'billing_mydyma_tcs_carrier_number' );
		$tax_id  = $order->get_meta( 'billing_mydyma_tcs_company_tax_id' );
		$title   = $order->get_meta( 'billing_mydyma_tcs_company_title' );

		if ( ! $type ) return;

		echo '<h3>' . esc_html__( '發票資訊', 'mydyma-taiwan-commerce-suite' ) . '</h3>';
		echo '<div class="address">';
		echo '<p><strong>' . esc_html__( '發票類型', 'mydyma-taiwan-commerce-suite' ) . ':</strong> ' . esc_html( $this->get_type_label( $type ) ) . '</p>';
		if ( $carrier ) {
			echo '<p><strong>' . esc_html__( '載具 / 捐贈碼', 'mydyma-taiwan-commerce-suite' ) . ':</strong> ' . esc_html( $carrier ) . '</p>';
		}
		if ( $tax_id ) {
			echo '<p><strong>' . esc_html__( '統一編號', 'mydyma-taiwan-commerce-suite' ) . ':</strong> ' . esc_html( $tax_id ) . '</p>';
		}
		if ( $title ) {
			echo '<p><strong>' . esc_html__( '公司名稱', 'mydyma-taiwan-commerce-suite' ) . ':</strong> ' . esc_html( $title ) . '</p>';
		}
		echo '</div>';
	}
}