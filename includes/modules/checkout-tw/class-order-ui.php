<?php
namespace Mydyma_TCS\Modules\Checkout_Tw;

defined( 'ABSPATH' ) || exit;

/**
 * Order UI Enhancement Module.
 * Displays shipping timeline and tracking info using Dashicons to avoid encoding issues.
 */
class Order_UI {

	public function boot(): void {
		add_action( 'woocommerce_view_order', [ $this, 'display_order_timeline' ], 5 );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );
	}

	public function enqueue_assets(): void {
		if ( ! function_exists( 'is_account_page' ) || ! is_account_page() || ! is_wc_endpoint_url( 'view-order' ) ) return;
		wp_enqueue_style(
			'mydyma-tcs-order-ui',
			MYDYMA_TCS_URL . 'assets/css/order-ui.css',
			[],
			MYDYMA_TCS_VERSION
		);
	}

	public function display_order_timeline( int $order_id ): void {
		$order = wc_get_order( $order_id );
		if ( ! $order ) return;

		$carrier = $order->get_meta( '_mydyma_tcs_tracking_carrier' );
		$number  = $order->get_meta( '_mydyma_tcs_tracking_number' );
		$arrived = $order->get_meta( '_mydyma_tcs_tracking_notified_arrived' );
		$status  = $order->get_status();

		// Timeline steps using Dashicons instead of Emojis to prevent garbled text
		$steps = [
			'on-hold'    => [ 'label' => __( '訂單處理中', 'mydyma-taiwan-commerce-suite' ), 'icon' => 'dashicons-clock', 'active' => true ],
			'processing' => [ 'label' => __( '準備出貨', 'mydyma-taiwan-commerce-suite' ), 'icon' => 'dashicons-archive', 'active' => false ],
			'shipping'   => [ 'label' => __( '商品已出貨', 'mydyma-taiwan-commerce-suite' ), 'icon' => 'dashicons-car', 'active' => false ],
			'arrived'    => [ 'label' => __( '商品到店', 'mydyma-taiwan-commerce-suite' ), 'icon' => 'dashicons-store', 'active' => false ],
			'completed'  => [ 'label' => __( '訂單已完成', 'mydyma-taiwan-commerce-suite' ), 'icon' => 'dashicons-yes-alt', 'active' => false ],
		];

		if ( in_array( $status, [ 'processing', 'shipping', 'completed' ] ) ) $steps['processing']['active'] = true;
		if ( in_array( $status, [ 'shipping', 'completed' ] ) || $number ) $steps['shipping']['active'] = true;
		if ( $arrived || $status === 'completed' ) $steps['arrived']['active'] = true;
		if ( $status === 'completed' ) $steps['completed']['active'] = true;

		?>
		<div class="mydyma-taiwan-commerce-suite-order-timeline-wrap">
			<h3><span class="dashicons dashicons-location-alt"></span> <?php esc_html_e( '物流進度追蹤', 'mydyma-taiwan-commerce-suite' ); ?></h3>
			<div class="mydyma-taiwan-commerce-suite-timeline">
				<?php foreach ( $steps as $key => $step ) : ?>
					<div class="mydyma-taiwan-commerce-suite-step <?php echo $step['active'] ? 'is-active' : ''; ?>">
						<div class="mydyma-taiwan-commerce-suite-step-icon">
							<span class="dashicons <?php echo esc_attr( $step['icon'] ); ?>"></span>
						</div>
						<div class="mydyma-taiwan-commerce-suite-step-label"><?php echo esc_html( $step['label'] ); ?></div>
					</div>
				<?php endforeach; ?>
			</div>

			<?php if ( $number ) : ?>
				<div class="mydyma-taiwan-commerce-suite-tracking-info-card">
					<div class="mydyma-taiwan-commerce-suite-tracking-main">
						<strong><?php esc_html_e( '物流商：', 'mydyma-taiwan-commerce-suite' ); ?></strong> <?php echo esc_html( strtoupper( $carrier ) ); ?> | 
						<strong><?php esc_html_e( '追蹤單號：', 'mydyma-taiwan-commerce-suite' ); ?></strong> <code><?php echo esc_html( $number ); ?></code>
					</div>
					<p class="mydyma-taiwan-commerce-suite-tracking-tip"><?php esc_html_e( 'Tracking info may have a delay. Please refer to SMS notifications.', 'mydyma-taiwan-commerce-suite' ); ?></p>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

}
