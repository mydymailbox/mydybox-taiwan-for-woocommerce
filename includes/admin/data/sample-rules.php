<?php
defined( 'ABSPATH' ) || exit;

/**
 * Sample Rules Data (Optimized for Card-based UI)
 */
return [
	'payment' => [
		[
			'id'          => 'pay_no_cod_outer_islands',
			'category'    => __( '區域限制', 'taiwan-store-core' ),
			'name'        => __( '離島禁用貨到付款', 'taiwan-store-core' ),
			'description' => __( '當配送地址為金門、連江、澎湖時，自動隱藏貨到付款選項。', 'taiwan-store-core' ),
			'icon'        => 'dashicons-location-alt',
			'enabled'     => true,
			'conditions'  => [
				[ 'type' => 'address', 'config' => [ 'field' => 'state', 'op' => 'in', 'values' => [ 'PEN', 'KMN', 'LNN' ] ] ],
			],
			'actions'     => [
				[ 'type' => 'hide_payment', 'config' => [ 'gateways' => [ 'cod' ] ] ],
			],
		],
		[
			'id'          => 'pay_b2b_only_transfer',
			'category'    => __( '企業採購 (B2B)', 'taiwan-store-core' ),
			'name'        => __( '公司戶訂單僅限銀行轉帳', 'taiwan-store-core' ),
			'description' => __( '當顧客填寫公司統編時，為了報帳流程，僅保留銀行轉帳選項。', 'taiwan-store-core' ),
			'icon'        => 'dashicons-welcome-learn-more',
			'enabled'     => true,
			'conditions'  => [
				[ 'type' => 'address', 'config' => [ 'field' => 'tax_id', 'op' => 'not_empty', 'values' => [] ] ],
			],
			'actions'     => [
				[ 'type' => 'hide_payment', 'config' => [ 'gateways' => [ 'cod', 'ecpay_pay_all' ] ] ],
			],
		],
		[
			'id'          => 'pay_limit_cod_amount',
			'category'    => __( '風險控管', 'taiwan-store-core' ),
			'name'        => __( '貨到付款金額限制', 'taiwan-store-core' ),
			'description' => __( '為了防範風險，當訂單總額超過 $10,000 時隱藏貨到付款。', 'taiwan-store-core' ),
			'icon'        => 'dashicons-money-alt',
			'enabled'     => true,
			'conditions'  => [
				[ 'type' => 'cart_total', 'config' => [ 'op' => 'gte', 'amount' => 10000 ] ],
			],
			'actions'     => [
				[ 'type' => 'hide_payment', 'config' => [ 'gateways' => [ 'cod' ] ] ],
			],
		],
	],
	'shipping' => [
		[
			'id'          => 'ship_free_over_2000',
			'category'    => __( '免運優惠', 'taiwan-store-core' ),
			'name'        => __( '全館滿額免運費', 'taiwan-store-core' ),
			'description' => __( '滿 $2,000 即享免運，自動隱藏原本的付費運送方式。', 'taiwan-store-core' ),
			'icon'        => 'dashicons-cart',
			'enabled'     => true,
			'conditions'  => [
				[ 'type' => 'cart_total', 'config' => [ 'op' => 'gte', 'amount' => 2000 ] ],
			],
			'actions'     => [
				[ 'type' => 'hide_shipping', 'config' => [ 'methods' => [ 'flat_rate' ] ] ],
			],
		],
		[
			'id'          => 'ship_cat_free',
			'category'    => __( '免運優惠', 'taiwan-store-core' ),
			'name'        => __( '特定類別 (如電子書) 免運費', 'taiwan-store-core' ),
			'description' => __( '當購物車僅含有虛擬商品或特定免運類別時，排除物流費用。', 'taiwan-store-core' ),
			'icon'        => 'dashicons-email-alt',
			'enabled'     => true,
			'conditions'  => [
				[ 'type' => 'category', 'config' => [ 'op' => 'contains_only', 'categories' => [] ] ],
			],
			'actions'     => [
				[ 'type' => 'hide_shipping', 'config' => [ 'methods' => [ 'flat_rate', '711', 'family' ] ] ],
			],
		],
		[
			'id'          => 'ship_no_cvs_for_heavy',
			'category'    => __( '物流限制', 'taiwan-store-core' ),
			'name'        => __( '重量商品限制超取', 'taiwan-store-core' ),
			'description' => __( '當特定笨重商品在購物車時，隱藏超商取貨選項。', 'taiwan-store-core' ),
			'icon'        => 'dashicons-info',
			'enabled'     => true,
			'conditions'  => [
				[ 'type' => 'product', 'config' => [ 'op' => 'in', 'products' => [] ] ],
			],
			'actions'     => [
				[ 'type' => 'hide_shipping', 'config' => [ 'methods' => [ '711', 'family' ] ] ],
			],
		],
	],
	'cart' => [
		[
			'id'          => 'cart_min_500',
			'category'    => __( '結帳門檻', 'taiwan-store-core' ),
			'name'        => __( '最低結帳金額 $500', 'taiwan-store-core' ),
			'description' => __( '若訂單總額未滿 $500，則阻止結帳並顯示自訂提示。', 'taiwan-store-core' ),
			'icon'        => 'dashicons-warning',
			'enabled'     => true,
			'conditions'  => [
				[ 'type' => 'cart_total', 'config' => [ 'op' => 'lt', 'amount' => 500 ] ],
			],
			'actions'     => [
				[ 'type' => 'block_checkout', 'config' => [ 'message' => __( '抱歉，本站最低結帳金額為 $500 元。', 'taiwan-store-core' ) ] ],
			],
		],
		[
			'id'          => 'cart_limit_3_items',
			'category'    => __( '數量限制', 'taiwan-store-core' ),
			'name'        => __( '熱銷商品限購 3 件', 'taiwan-store-core' ),
			'description' => __( '限制特定商品在購物車中的最大購買數量。', 'taiwan-store-core' ),
			'icon'        => 'dashicons-shield',
			'enabled'     => true,
			'conditions'  => [
				[ 'type' => 'cart_item_count', 'config' => [ 'op' => 'gt', 'count' => 3 ] ],
			],
			'actions'     => [
				[ 'type' => 'block_checkout', 'config' => [ 'message' => __( '抱歉，此熱銷商品每人限購 3 件。', 'taiwan-store-core' ) ] ],
			],
		],
		[
			'id'          => 'cart_maintenance_mode',
			'category'    => __( '特殊維護', 'taiwan-store-core' ),
			'name'        => __( '深夜維護模式 (暫停結帳)', 'taiwan-store-core' ),
			'description' => __( '在特定時段（如凌晨 02:00 - 05:00）禁止結帳，適合系統盤點或更新時使用。', 'taiwan-store-core' ),
			'icon'        => 'dashicons-clock',
			'enabled'     => false,
			'conditions'  => [
				[ 'type' => 'time_range', 'config' => [ 'start' => '02:00', 'end' => '05:00' ] ],
			],
			'actions'     => [
				[ 'type' => 'block_checkout', 'config' => [ 'message' => __( '目前為系統維護時間 (02:00-05:00)，請稍後再進行結帳，謝謝。', 'taiwan-store-core' ) ] ],
			],
		],
	],
	'marketing' => [
		[
			'id'          => 'mkt_off_1000',
			'category'    => __( '折扣與優惠', 'taiwan-store-core' ),
			'name'        => __( '全館滿千享 9 折', 'taiwan-store-core' ),
			'description' => __( '當購物車總額滿 $1,000 時，系統自動套用 10% 折扣優惠。', 'taiwan-store-core' ),
			'icon'        => 'dashicons-megaphone',
			'enabled'     => true,
			'conditions'  => [
				[ 'type' => 'cart_total', 'config' => [ 'op' => 'gte', 'amount' => 1000 ] ],
			],
			'actions'     => [
				[ 'type' => 'apply_discount', 'config' => [ 'name' => __( '滿千 9 折優惠', 'taiwan-store-core' ), 'type' => 'percent', 'amount' => 10 ] ],
			],
		],
		[
			'id'          => 'mkt_bulk_discount',
			'category'    => __( '折扣與優惠', 'taiwan-store-core' ),
			'name'        => __( '任選 3 件以上享 85 折 (量販優惠)', 'taiwan-store-core' ),
			'description' => __( '鼓勵消費者一次購買多件商品。只要購物車內商品總數達到門檻，即享整單折扣。', 'taiwan-store-core' ),
			'icon'        => 'dashicons-archive',
			'enabled'     => true,
			'conditions'  => [
				[ 'type' => 'cart_item_count', 'config' => [ 'op' => 'gte', 'count' => 3 ] ],
			],
			'actions'     => [
				[ 'type' => 'apply_discount', 'config' => [ 'name' => __( '量販 3 件優惠', 'taiwan-store-core' ), 'type' => 'percent', 'amount' => 15 ] ],
			],
		],
		[
			'id'          => 'mkt_first_purchase',
			'category'    => __( '折扣與優惠', 'taiwan-store-core' ),
			'name'        => __( '新會員首購現折 $100', 'taiwan-store-core' ),
			'description' => __( '偵測到該顧客從未在本站完成訂單時，自動給予首購歡迎禮。', 'taiwan-store-core' ),
			'icon'        => 'dashicons-welcome-add-page',
			'enabled'     => true,
			'conditions'  => [
				[ 'type' => 'first_purchase', 'config' => [] ],
			],
			'actions'     => [
				[ 'type' => 'apply_discount', 'config' => [ 'name' => __( '新會員首購禮', 'taiwan-store-core' ), 'type' => 'fixed', 'amount' => 100 ] ],
			],
		],
		[
			'id'          => 'mkt_gift_2000',
			'category'    => __( '贈品活動', 'taiwan-store-core' ),
			'name'        => __( '滿 $2,000 送精美贈品', 'taiwan-store-core' ),
			'description' => __( '滿足門檻後，系統會自動將指定的贈品加入購物車並將價格設為 0 元。', 'taiwan-store-core' ),
			'icon'        => 'dashicons-awards',
			'enabled'     => true,
			'conditions'  => [
				[ 'type' => 'cart_total', 'config' => [ 'op' => 'gte', 'amount' => 2000 ] ],
			],
			'actions'     => [
				[ 'type' => 'add_free_gift', 'config' => [ 'product_id' => 0 ] ],
			],
		],
		[
			'id'          => 'mkt_cat_gift',
			'category'    => __( '贈品活動', 'taiwan-store-core' ),
			'name'        => __( '指定類別商品滿件送贈品', 'taiwan-store-core' ),
			'description' => __( '針對特定分類（如：服飾、美妝）設定滿額或滿件贈禮，協助出清特定庫存。', 'taiwan-store-core' ),
			'icon'        => 'dashicons-tag',
			'enabled'     => true,
			'conditions'  => [
				[ 'type' => 'cart_item_count', 'config' => [ 'op' => 'gte', 'count' => 2 ] ],
			],
			'actions'     => [
				[ 'type' => 'add_free_gift', 'config' => [ 'product_id' => 0 ] ],
			],
		],
		[
			'id'          => 'mkt_progress_notice',
			'category'    => __( '視覺與提示', 'taiwan-store-core' ),
			'name'        => __( '提示：再買 $XX 享免運 (進度條)', 'taiwan-store-core' ),
			'description' => __( '在購物車與結帳頁顯示精美的進度條，動態計算距離免運門檻還差多少錢。', 'taiwan-store-core' ),
			'icon'        => 'dashicons-chart-area',
			'enabled'     => true,
			'conditions'  => [
				[ 'type' => 'cart_total', 'config' => [ 'op' => 'lt', 'amount' => 1500 ] ],
			],
			'actions'     => [
				[ 'type' => 'cart_progress', 'config' => [ 'threshold' => 1500, 'message' => __( '再買 $AMOUNT 元即享【免運費】優惠！', 'taiwan-store-core' ) ] ],
			],
		],
		[
			'id'          => 'mkt_flash_countdown',
			'category'    => __( '視覺與提示', 'taiwan-store-core' ),
			'name'        => __( '限時快閃！全館結帳倒數', 'taiwan-store-core' ),
			'description' => __( '在頁面頂端顯示倒數計時器，營造活動即將結束的緊迫感，加速成交。', 'taiwan-store-core' ),
			'icon'        => 'dashicons-clock',
			'enabled'     => true,
			'conditions'  => [
				[ 'type' => 'time_range', 'config' => [ 'start' => '09:00', 'end' => '23:59' ] ],
			],
			'actions'     => [
				[ 'type' => 'flash_sale_countdown', 'config' => [ 'end_time' => '2026-12-31 23:59:59', 'message' => __( '【限時快閃】活動倒數計時：', 'taiwan-store-core' ) ] ],
			],
		],
		[
			'id'          => 'mkt_addon_deal',
			'category'    => __( '加購與分眾', 'taiwan-store-core' ),
			'name'        => __( '特定商品加價購優惠', 'taiwan-store-core' ),
			'description' => __( '當滿足特定條件時，提供指定商品的超值加購價，增加客單價 (UP-SELL)。', 'taiwan-store-core' ),
			'icon'        => 'dashicons-plus-alt',
			'enabled'     => true,
			'conditions'  => [
				[ 'type' => 'cart_total', 'config' => [ 'op' => 'gte', 'amount' => 500 ] ],
			],
			'actions'     => [
				[ 'type' => 'addon_deal', 'config' => [ 'product_id' => 0, 'addon_price' => 99, 'name' => __( '超值加購！', 'taiwan-store-core' ) ] ],
			],
		],
		[
			'id'          => 'mkt_vip_deal',
			'category'    => __( '加購與分眾', 'taiwan-store-core' ),
			'name'        => __( 'VIP 會員結帳再折 $50', 'taiwan-store-core' ),
			'description' => __( '針對特定身分（如 VIP 或已登入會員）提供專屬禮遇，增加品牌忠誠度。', 'taiwan-store-core' ),
			'icon'        => 'dashicons-star-filled',
			'enabled'     => true,
			'conditions'  => [
				[ 'type' => 'user_role', 'config' => [ 'roles' => [ 'administrator', 'editor', 'author', 'contributor', 'subscriber', 'customer' ] ] ],
			],
			'actions'     => [
				[ 'type' => 'apply_discount', 'config' => [ 'name' => __( '會員專屬折扣', 'taiwan-store-core' ), 'type' => 'fixed', 'amount' => 50 ] ],
			],
		],
	],
];
