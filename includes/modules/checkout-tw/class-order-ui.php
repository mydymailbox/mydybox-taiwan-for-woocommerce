<?php
namespace Taiwan_Store_Core\Modules\Checkout_Tw;

defined( 'ABSPATH' ) || exit;

/**
 * Order UI Enhancement Module.
 * Displays shipping timeline and tracking info using Dashicons to avoid encoding issues.
 */
class Order_UI {

	public function boot(): void {
		add_action( 'woocommerce_view_order', [ $this, 'display_order_timeline' ], 5 );
		add_action( 'wp_head', [ $this, 'output_timeline_css' ] );
	}

	public function display_order_timeline( int $order_id ): void {
		$order = wc_get_order( $order_id );
		if ( ! $order ) return;

		$carrier = $order->get_meta( '_ts_tracking_carrier' );
		$number  = $order->get_meta( '_ts_tracking_number' );
		$arrived = $order->get_meta( '_ts_tracking_notified_arrived' );
		$status  = $order->get_status();

		// Timeline steps using Dashicons instead of Emojis to prevent garbled text
		$steps = [
			'on-hold'    => [ 'label' => __( '訂單處理中', 'taiwan-store-core' ), 'icon' => 'dashicons-clock', 'active' => true ],
			'processing' => [ 'label' => __( '準備出貨', 'taiwan-store-core' ), 'icon' => 'dashicons-archive', 'active' => false ],
			'shipping'   => [ 'label' => __( '商品已出貨', 'taiwan-store-core' ), 'icon' => 'dashicons-car', 'active' => false ],
			'arrived'    => [ 'label' => __( '商品到店', 'taiwan-store-core' ), 'icon' => 'dashicons-store', 'active' => false ],
			'completed'  => [ 'label' => __( '訂單已完成', 'taiwan-store-core' ), 'icon' => 'dashicons-yes-alt', 'active' => false ],
		];

		if ( in_array( $status, [ 'processing', 'shipping', 'completed' ] ) ) $steps['processing']['active'] = true;
		if ( in_array( $status, [ 'shipping', 'completed' ] ) || $number ) $steps['shipping']['active'] = true;
		if ( $arrived || $status === 'completed' ) $steps['arrived']['active'] = true;
		if ( $status === 'completed' ) $steps['completed']['active'] = true;

		?>
		<div class="taiwan-store-core-order-timeline-wrap">
			<h3><span class="dashicons dashicons-location-alt"></span> <?php esc_html_e( '物流進度追蹤', 'taiwan-store-core' ); ?></h3>
			<div class="taiwan-store-core-timeline">
				<?php foreach ( $steps as $key => $step ) : ?>
					<div class="taiwan-store-core-step <?php echo $step['active'] ? 'is-active' : ''; ?>">
						<div class="taiwan-store-core-step-icon">
							<span class="dashicons <?php echo esc_attr( $step['icon'] ); ?>"></span>
						</div>
						<div class="taiwan-store-core-step-label"><?php echo esc_html( $step['label'] ); ?></div>
					</div>
				<?php endforeach; ?>
			</div>

			<?php if ( $number ) : ?>
				<div class="taiwan-store-core-tracking-info-card">
					<div class="taiwan-store-core-tracking-main">
						<strong><?php esc_html_e( '物流商：', 'taiwan-store-core' ); ?></strong> <?php echo esc_html( strtoupper( $carrier ) ); ?> | 
						<strong><?php esc_html_e( '追蹤單號：', 'taiwan-store-core' ); ?></strong> <code><?php echo esc_html( $number ); ?></code>
					</div>
					<p class="taiwan-store-core-tracking-tip"><?php esc_html_e( 'Tracking info may have a delay. Please refer to SMS notifications.', 'taiwan-store-core' ); ?></p>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	public function output_timeline_css(): void {
		if ( ! is_account_page() || ! is_wc_endpoint_url( 'view-order' ) ) return;
		?>
		<style>
			.taiwan-store-core-order-timeline-wrap { background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 30px; margin-bottom: 35px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
			.taiwan-store-core-order-timeline-wrap h3 { margin-top: 0; font-size: 1.25rem; color: #1e293b; margin-bottom: 30px; display: flex; align-items: center; gap: 10px; }
			.taiwan-store-core-order-timeline-wrap h3 .dashicons { color: #2563eb; font-size: 24px; width: 24px; height: 24px; }
			
			.taiwan-store-core-timeline { display: flex; justify-content: space-between; position: relative; margin-bottom: 20px; }
			.taiwan-store-core-timeline::before { content: ''; position: absolute; top: 22px; left: 8%; right: 8%; height: 3px; background: #f1f5f9; z-index: 1; }
			
			.taiwan-store-core-step { position: relative; z-index: 2; text-align: center; flex: 1; }
			.taiwan-store-core-step-icon { width: 45px; height: 45px; background: #fff; border: 2px solid #f1f5f9; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 12px; }
			.taiwan-store-core-step-icon .dashicons { font-size: 20px; color: #94a3b8; width: 20px; height: 20px; }
			
			.taiwan-store-core-step-label { font-size: 14px; color: #94a3b8; font-weight: 500; }
			
			.taiwan-store-core-step.is-active .taiwan-store-core-step-icon { background: #f0f7ff; border-color: #2563eb; }
			.taiwan-store-core-step.is-active .taiwan-store-core-step-icon .dashicons { color: #2563eb; }
			.taiwan-store-core-step.is-active .taiwan-store-core-step-label { color: #1e293b; font-weight: 600; }
			
			.taiwan-store-core-tracking-info-card { background: #f8fafc; border-radius: 12px; padding: 20px; margin-top: 25px; border: 1px solid #e2e8f0; }
			@media (max-width: 767px) {
				.taiwan-store-core-timeline::before { display: none; }
				.taiwan-store-core-timeline { flex-direction: column; gap: 20px; }
				.taiwan-store-core-step { display: flex; align-items: center; gap: 20px; text-align: left; }
				.taiwan-store-core-step-icon { margin: 0; }
			}
		</style>
		<?php
	}
}
