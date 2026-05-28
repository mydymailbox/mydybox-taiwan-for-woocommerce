<?php
namespace Taiwan_Store_Core\Modules\Checkout_Tw;

defined( 'ABSPATH' ) || exit;

/**
 * Product UI Enhancement Module.
 * Adds a sticky add-to-cart bar on single product pages.
 */
class Product_UI {

	public function boot(): void {
		if ( 'yes' !== get_option( 'ts_product_sticky_bar', 'yes' ) ) {
			return;
		}
		add_action( 'woocommerce_after_single_product', [ $this, 'display_sticky_add_to_cart' ] );
		add_action( 'wp_head', [ $this, 'output_product_ui_css' ] );
	}

	public function display_sticky_add_to_cart(): void {
		global $product;
		if ( ! $product || ! is_product() || ! $product->is_purchasable() || ! $product->is_in_stock() ) return;

		$image_url = get_the_post_thumbnail_url( $product->get_id(), 'thumbnail' );
		$price_html = $product->get_price_html();
		$stock_status = $product->get_stock_status();
		$is_low_stock = $product->get_stock_quantity() > 0 && $product->get_stock_quantity() <= 5;
		$stock_label = $is_low_stock ? __( '最後倒數', 'taiwan-store-core' ) : __( '庫存充足', 'taiwan-store-core' );

		?>
		<div id="taiwan-store-core-sticky-cart" class="taiwan-store-core-sticky-cart-wrap">
			<div class="taiwan-store-core-sticky-cart-container">
				<div class="taiwan-store-core-sticky-info">
					<img src="<?php echo esc_url( $image_url ); ?>" alt="product thumb">
					<div class="taiwan-store-core-sticky-text">
						<span class="taiwan-store-core-sticky-title"><?php echo esc_html( $product->get_name() ); ?></span>
						<div style="display:flex; align-items:center; gap:10px;">
							<span class="taiwan-store-core-sticky-price"><?php echo wp_kses_post( $price_html ); ?></span>
							<span class="ts-stock-badge <?php echo $is_low_stock ? 'low' : ''; ?>"><?php echo esc_html( $stock_label ); ?></span>
						</div>
					</div>
				</div>
				<div class="taiwan-store-core-sticky-action">
					<div class="taiwan-store-core-sticky-qty-wrap">
						<button type="button" class="ts-sticky-qty-btn minus">−</button>
						<input type="number" class="ts-sticky-qty-input" value="1" min="1" step="1" readonly>
						<button type="button" class="ts-sticky-qty-btn plus">+</button>
					</div>
					<button type="button" class="taiwan-store-core-sticky-btn" onclick="document.querySelector('.single_add_to_cart_button').click();">
						<span class="dashicons dashicons-cart" style="vertical-align:middle; margin-right:5px;"></span>
						<?php esc_html_e( '立即購買', 'taiwan-store-core' ); ?>
					</button>
				</div>
			</div>
		</div>

		<script>
			(function($) {
				$(window).scroll(function() {
					if ($(this).scrollTop() > 600) {
						$('#taiwan-store-core-sticky-cart').addClass('is-visible');
					} else {
						$('#taiwan-store-core-sticky-cart').removeClass('is-visible');
					}
				});

				// Quantity Buttons Logic
				$(document).on('click', '.ts-sticky-qty-btn', function() {
					var $input = $('.ts-sticky-qty-input');
					var currentVal = parseInt($input.val()) || 1;
					var isPlus = $(this).hasClass('plus');
					
					var newVal = isPlus ? currentVal + 1 : currentVal - 1;
					if (newVal < 1) newVal = 1;

					$input.val(newVal);
					// Sync to main WooCommerce quantity input
					$('.quantity input.qty').val(newVal).trigger('change');
				});

				// Sync main input back to sticky bar if changed elsewhere
				$(document).on('change', '.quantity input.qty', function() {
					$('.ts-sticky-qty-input').val($(this).val());
				});
			})(jQuery);
		</script>
		<?php
	}

	public function output_product_ui_css(): void {
		if ( ! is_product() ) return;
		?>
		<style>
			.taiwan-store-core-sticky-cart-wrap { position: fixed; bottom: 20px; left: 20px; right: 20px; background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px); border: 1px solid rgba(226, 232, 240, 0.8); z-index: 9999; padding: 12px 0; transform: translateY(150%); transition: all 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275); box-shadow: 0 10px 30px rgba(0,0,0,0.12); border-radius: 16px; }
			.taiwan-store-core-sticky-cart-wrap.is-visible { transform: translateY(0); }
			.taiwan-store-core-sticky-cart-container { max-width: 1200px; margin: 0 auto; display: flex; align-items: center; justify-content: space-between; padding: 0 20px; }
			.taiwan-store-core-sticky-info { display: flex; align-items: center; gap: 15px; }
			.taiwan-store-core-sticky-info img { width: 50px; height: 50px; border-radius: 10px; object-fit: cover; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
			.taiwan-store-core-sticky-text { display: flex; flex-direction: column; }
			.taiwan-store-core-sticky-title { font-size: 15px; font-weight: 700; color: #1e293b; max-width: 250px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-bottom: 2px; }
			.taiwan-store-core-sticky-price { font-size: 15px; color: #ef4444; font-weight: 700; }
			
			.ts-stock-badge { font-size: 11px; background: #f0fdf4; color: #166534; padding: 2px 8px; border-radius: 4px; font-weight: 700; }
			.ts-stock-badge.low { background: #fef2f2; color: #991b1b; }

			.taiwan-store-core-sticky-action { display: flex; align-items: center; gap: 15px; }
			.taiwan-store-core-sticky-qty-wrap { display: flex; align-items: center; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; overflow: hidden; height: 44px; }
			.ts-sticky-qty-btn { background: none; border: none; width: 38px; height: 100%; font-size: 18px; cursor: pointer; color: #64748b; transition: all 0.2s; }
			.ts-sticky-qty-btn:hover { background: #e2e8f0; color: #1e293b; }
			.ts-sticky-qty-input { width: 42px !important; border: none !important; background: transparent !important; text-align: center !important; font-weight: 800 !important; color: #1e293b !important; padding: 0 !important; margin: 0 !important; font-size: 15px !important; }
			
			.taiwan-store-core-sticky-btn { background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%); color: #fff; border: none; padding: 0 30px; height: 44px; border-radius: 10px; font-weight: 800; cursor: pointer; transition: all 0.3s; white-space: nowrap; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2); }
			.taiwan-store-core-sticky-btn:hover { transform: translateY(-2px); box-shadow: 0 6px 15px rgba(37, 99, 235, 0.3); background: linear-gradient(135deg, #1d4ed8 0%, #1e40af 100%); }
			
			@media (max-width: 767px) { 
				.taiwan-store-core-sticky-cart-wrap { bottom: 10px; left: 10px; right: 10px; border-radius: 12px; }
				.taiwan-store-core-sticky-info { display: none; } 
				.taiwan-store-core-sticky-action { width: 100%; justify-content: space-between; } 
				.taiwan-store-core-sticky-qty-wrap { flex: 1; max-width: 130px; }
				.taiwan-store-core-sticky-btn { flex: 2; font-size: 15px; } 
			}
		</style>
		<?php
	}
}