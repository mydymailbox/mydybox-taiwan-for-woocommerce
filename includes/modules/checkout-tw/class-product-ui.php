<?php
namespace Mydyma_TCS\Modules\Checkout_Tw;

defined( 'ABSPATH' ) || exit;

/**
 * Product UI Enhancement Module.
 * Adds a sticky add-to-cart bar on single product pages.
 */
class Product_UI {

	public function boot(): void {
		if ( 'yes' !== get_option( 'mydyma_tcs_product_sticky_bar', 'yes' ) ) {
			return;
		}
		add_action( 'woocommerce_after_single_product', [ $this, 'display_sticky_add_to_cart' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );
	}

	public function enqueue_assets(): void {
		if ( ! function_exists( 'is_product' ) || ! is_product() ) return;

		wp_enqueue_style(
			'mydyma-tcs-product-ui',
			MYDYMA_TCS_URL . 'assets/css/product-ui.css',
			[],
			MYDYMA_TCS_VERSION
		);
		wp_enqueue_script(
			'mydyma-tcs-product-ui',
			MYDYMA_TCS_URL . 'assets/js/product-ui.js',
			[ 'jquery' ],
			MYDYMA_TCS_VERSION,
			true
		);
	}

	public function display_sticky_add_to_cart(): void {
		global $product;
		if ( ! $product || ! is_product() || ! $product->is_purchasable() || ! $product->is_in_stock() ) return;

		$image_url    = get_the_post_thumbnail_url( $product->get_id(), 'thumbnail' );
		$price_html   = $product->get_price_html();
		$is_low_stock = $product->get_stock_quantity() > 0 && $product->get_stock_quantity() <= 5;
		$stock_label  = $is_low_stock ? __( '最後倒數', 'mydyma-taiwan-commerce-suite' ) : __( '庫存充足', 'mydyma-taiwan-commerce-suite' );

		?>
		<div id="mydyma-taiwan-commerce-suite-sticky-cart" class="mydyma-taiwan-commerce-suite-sticky-cart-wrap">
			<div class="mydyma-taiwan-commerce-suite-sticky-cart-container">
				<div class="mydyma-taiwan-commerce-suite-sticky-info">
					<img src="<?php echo esc_url( $image_url ); ?>" alt="product thumb">
					<div class="mydyma-taiwan-commerce-suite-sticky-text">
						<span class="mydyma-taiwan-commerce-suite-sticky-title"><?php echo esc_html( $product->get_name() ); ?></span>
						<div style="display:flex; align-items:center; gap:10px;">
							<span class="mydyma-taiwan-commerce-suite-sticky-price"><?php echo wp_kses_post( $price_html ); ?></span>
							<span class="ts-stock-badge <?php echo $is_low_stock ? 'low' : ''; ?>"><?php echo esc_html( $stock_label ); ?></span>
						</div>
					</div>
				</div>
				<div class="mydyma-taiwan-commerce-suite-sticky-action">
					<div class="mydyma-taiwan-commerce-suite-sticky-qty-wrap">
						<button type="button" class="ts-sticky-qty-btn minus">−</button>
						<input type="number" class="ts-sticky-qty-input" value="1" min="1" step="1" readonly>
						<button type="button" class="ts-sticky-qty-btn plus">+</button>
					</div>
					<button type="button" class="mydyma-taiwan-commerce-suite-sticky-btn">
						<span class="dashicons dashicons-cart" style="vertical-align:middle; margin-right:5px;"></span>
						<?php esc_html_e( '立即購買', 'mydyma-taiwan-commerce-suite' ); ?>
					</button>
				</div>
			</div>
		</div>
		<?php
	}
}
