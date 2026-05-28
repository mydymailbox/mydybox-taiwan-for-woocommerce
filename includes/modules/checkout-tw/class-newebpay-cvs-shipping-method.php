<?php
namespace Taiwan_Store_Core\Modules\Checkout_Tw;

defined( 'ABSPATH' ) || exit;

/**
 * WooCommerce Shipping Method: NewebPay CVS Store Pickup.
 */
class NewebPay_CVS_Shipping_Method extends \WC_Shipping_Method {

	public function __construct( $instance_id = 0 ) {
		$this->id                 = 'taiwan_store_newebpay_cvs';
		$this->instance_id        = absint( $instance_id );
		$this->method_title       = __( '超商取貨（藍新）', 'taiwan-store-core' );
		$this->method_description = __( '7-ELEVEN、全家、萊爾富、OK 超商取貨（藍新物流）', 'taiwan-store-core' );
		$this->supports           = [ 'shipping-zones', 'instance-settings' ];
		$this->init();
	}

	public function init(): void {
		$this->init_form_fields();
		$this->init_settings();

		$this->title      = $this->get_option( 'title', __( '超商取貨（藍新）', 'taiwan-store-core' ) );
		$this->tax_status = 'none';

		add_action( 'woocommerce_update_options_shipping_' . $this->id, [ $this, 'process_admin_options' ] );
	}

	public function init_form_fields(): void {
		$this->instance_form_fields = [
			'title' => [
				'title'   => __( '方式名稱', 'taiwan-store-core' ),
				'type'    => 'text',
				'default' => __( '超商取貨（藍新）', 'taiwan-store-core' ),
			],
			'cost' => [
				'title'       => __( '運費', 'taiwan-store-core' ),
				'type'        => 'price',
				'default'     => '60',
				'description' => __( '超商取貨運費，0 表示免運', 'taiwan-store-core' ),
			],
			'free_min' => [
				'title'       => __( '免運門檻（NT$）', 'taiwan-store-core' ),
				'type'        => 'price',
				'default'     => '0',
				'description' => __( '訂單滿此金額免運，0 表示停用', 'taiwan-store-core' ),
			],
			'cvs_type' => [
				'title'   => __( '超商類型', 'taiwan-store-core' ),
				'type'    => 'select',
				'default' => 'SEVEN',
				'options' => [
					'SEVEN'  => '7-ELEVEN',
					'FAMILY' => '全家 FamilyMart',
					'HILIFE' => '萊爾富',
					'OK'     => 'OK 超商',
				],
			],
		];
	}

	public function calculate_shipping( $package = [] ): void {
		$cost     = (float) $this->get_option( 'cost', 60 );
		$free_min = (float) $this->get_option( 'free_min', 0 );
		$subtotal = $package['cart_subtotal'] ?? WC()->cart->get_subtotal();

		if ( $free_min > 0 && $subtotal >= $free_min ) {
			$cost = 0;
		}

		$this->add_rate( [
			'id'       => $this->get_rate_id(),
			'label'    => $this->title,
			'cost'     => $cost,
			'calc_tax' => 'per_order',
		] );
	}

	public function is_available( $package ): bool {
		$is_test = 'yes' === get_option( 'ts_newebpay_cvs_test_mode', 'yes' );
		if ( ! $is_test && ! get_option( 'ts_newebpay_cvs_merchant_id' ) ) {
			return false;
		}
		return parent::is_available( $package );
	}
}
