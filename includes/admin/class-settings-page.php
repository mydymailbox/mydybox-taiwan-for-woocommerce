<?php
namespace Mydybox\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * Settings Page — tabbed admin dashboard.
 */
class Settings_Page {

	public function boot(): void {
		add_action( 'admin_menu',            [ $this, 'add_menu_item' ] );
		add_action( 'admin_init',            [ $this, 'register_settings' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
	}

	public function enqueue_assets( $hook ): void {
		if ( 'toplevel_page_mydybox-taiwan-for-woocommerce' !== $hook ) return;
		wp_enqueue_style( 'mydybox-taiwan-for-woocommerce-dashboard', MYDYBOX_URL . 'assets/css/admin-dashboard.css', [], MYDYBOX_VERSION );

		$active_tab = sanitize_key( wp_unslash( $_GET['tab'] ?? 'general' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only tab param
		if ( 'logs' === $active_tab ) {
			wp_enqueue_script( 'mydybox-taiwan-for-woocommerce-logs', MYDYBOX_URL . 'assets/js/logs-admin.js', [ 'jquery' ], MYDYBOX_VERSION, true );
			wp_localize_script( 'mydybox-taiwan-for-woocommerce-logs', 'MydyboxLogStats', [
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'mydybox_admin' ),
			] );
		}
		if ( 'manual' === $active_tab ) {
			wp_enqueue_style( 'mydyma-tcs-admin-manual', MYDYBOX_URL . 'assets/css/admin-manual.css', [], MYDYBOX_VERSION );
			wp_enqueue_script( 'mydyma-tcs-admin-manual', MYDYBOX_URL . 'assets/js/admin-manual.js', [], MYDYBOX_VERSION, true );
		}
	}

	public function add_menu_item(): void {
		add_menu_page(
			__( 'Mydybox', 'mydybox-taiwan-for-woocommerce' ),
			__( 'Mydybox', 'mydybox-taiwan-for-woocommerce' ),
			'manage_options',
			'mydybox-taiwan-for-woocommerce',
			[ $this, 'render_page' ],
			'dashicons-store',
			56
		);
	}

	public function register_settings(): void {
		// General
		register_setting( 'mydybox_settings_general', 'mydybox_custom_order_number_enabled', [ 'sanitize_callback' => 'sanitize_text_field' ] );
		register_setting( 'mydybox_settings_general', 'mydybox_order_number_prefix', [ 'sanitize_callback' => 'sanitize_text_field' ] );
		register_setting( 'mydybox_settings_general', 'mydybox_order_number_digits', [ 'sanitize_callback' => 'absint' ] );
		register_setting( 'mydybox_settings_general', 'mydybox_order_number_random_suffix', [ 'sanitize_callback' => 'sanitize_text_field' ] );
		register_setting( 'mydybox_settings_general', 'mydybox_checkout_announcement_enabled', [ 'sanitize_callback' => 'sanitize_text_field' ] );
		register_setting( 'mydybox_settings_general', 'mydybox_checkout_announcement_text', [ 'sanitize_callback' => 'sanitize_text_field' ] );
		register_setting( 'mydybox_settings_general', 'mydybox_license_key', [ 'sanitize_callback' => 'sanitize_text_field' ] );

		// Checkout
		register_setting( 'mydybox_settings_checkout', 'mydybox_checkout_show_tax_id', [ 'sanitize_callback' => 'sanitize_text_field' ] );
		register_setting( 'mydybox_settings_checkout', 'mydybox_checkout_name_consolidate', [ 'sanitize_callback' => 'sanitize_text_field' ] );
		register_setting( 'mydybox_settings_checkout', 'mydybox_checkout_validate_tax_id', [ 'sanitize_callback' => 'sanitize_text_field' ] );
		register_setting( 'mydybox_settings_checkout', 'mydybox_checkout_lookup_tax_id', [ 'sanitize_callback' => 'sanitize_text_field' ] );
		register_setting( 'mydybox_settings_checkout', 'mydybox_cvs_enabled', [ 'sanitize_callback' => 'sanitize_text_field' ] );
		register_setting( 'mydybox_settings_checkout', 'mydybox_cvs_test_mode', [ 'sanitize_callback' => 'sanitize_text_field' ] );
		register_setting( 'mydybox_settings_checkout', 'mydybox_cvs_merchant_id', [ 'sanitize_callback' => 'sanitize_text_field' ] );
		register_setting( 'mydybox_settings_checkout', 'mydybox_cvs_hash_key', [ 'sanitize_callback' => 'sanitize_text_field' ] );
		register_setting( 'mydybox_settings_checkout', 'mydybox_cvs_hash_iv', [ 'sanitize_callback' => 'sanitize_text_field' ] );
		register_setting( 'mydybox_settings_checkout', 'mydybox_newebpay_cvs_enabled', [ 'sanitize_callback' => 'sanitize_text_field' ] );
		register_setting( 'mydybox_settings_checkout', 'mydybox_newebpay_cvs_test_mode', [ 'sanitize_callback' => 'sanitize_text_field' ] );
		register_setting( 'mydybox_settings_checkout', 'mydybox_newebpay_cvs_merchant_id', [ 'sanitize_callback' => 'sanitize_text_field' ] );
		register_setting( 'mydybox_settings_checkout', 'mydybox_newebpay_cvs_hash_key', [ 'sanitize_callback' => 'sanitize_text_field' ] );
		register_setting( 'mydybox_settings_checkout', 'mydybox_newebpay_cvs_hash_iv', [ 'sanitize_callback' => 'sanitize_text_field' ] );
		register_setting( 'mydybox_settings_checkout', 'mydybox_gcis_api_uuid', [
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => '236EE382-4942-41A9-BD03-CA0709025E7C',
		] );
		register_setting( 'mydybox_settings_checkout', 'mydybox_checkout_phone_validate', [ 'sanitize_callback' => 'sanitize_text_field' ] );
		register_setting( 'mydybox_settings_checkout', 'mydybox_checkout_postcode_autofill', [ 'sanitize_callback' => 'sanitize_text_field' ] );
		register_setting( 'mydybox_settings_checkout', 'mydybox_checkout_abandoned_cart', [ 'sanitize_callback' => 'sanitize_text_field' ] );
		register_setting( 'mydybox_settings_checkout', 'mydybox_abandoned_cart_delay', [ 'sanitize_callback' => 'absint' ] );
		register_setting( 'mydybox_settings_checkout', 'mydybox_abandoned_cart_email', [ 'sanitize_callback' => 'sanitize_text_field' ] );
		register_setting( 'mydybox_settings_checkout', 'mydybox_abandoned_cart_email_subject', [ 'sanitize_callback' => 'sanitize_text_field' ] );
		register_setting( 'mydybox_settings_checkout', 'mydybox_abandoned_cart_email_body', [ 'sanitize_callback' => 'sanitize_textarea_field' ] );
		register_setting( 'mydybox_settings_checkout', 'mydybox_abandoned_cart_line', [ 'sanitize_callback' => 'sanitize_text_field' ] );
		register_setting( 'mydybox_settings_checkout', 'mydybox_abandoned_cart_line_message', [ 'sanitize_callback' => 'sanitize_textarea_field' ] );
		register_setting( 'mydybox_settings_checkout', 'mydybox_checkout_countdown', [ 'sanitize_callback' => 'sanitize_text_field' ] );
		register_setting( 'mydybox_settings_checkout', 'mydybox_checkout_countdown_minutes', [ 'sanitize_callback' => 'absint' ] );
		register_setting( 'mydybox_settings_checkout', 'mydybox_product_sticky_bar', [ 'sanitize_callback' => 'sanitize_text_field' ] );

		// Social
		register_setting( 'mydybox_settings_social', 'mydybox_social_line_enabled', [ 'sanitize_callback' => 'sanitize_text_field' ] );
		register_setting( 'mydybox_settings_social', 'mydybox_social_line_client_id', [ 'sanitize_callback' => 'sanitize_text_field' ] );
		register_setting( 'mydybox_settings_social', 'mydybox_social_line_client_secret', [ 'sanitize_callback' => 'sanitize_text_field' ] );
		register_setting( 'mydybox_settings_social', 'mydybox_social_line_token', [ 'sanitize_callback' => 'sanitize_text_field' ] );
		register_setting( 'mydybox_settings_social', 'mydybox_social_google_enabled', [ 'sanitize_callback' => 'sanitize_text_field' ] );
		register_setting( 'mydybox_settings_social', 'mydybox_social_google_client_id', [ 'sanitize_callback' => 'sanitize_text_field' ] );
		register_setting( 'mydybox_settings_social', 'mydybox_social_google_client_secret', [ 'sanitize_callback' => 'sanitize_text_field' ] );
		register_setting( 'mydybox_settings_social', 'mydybox_social_fb_enabled', [ 'sanitize_callback' => 'sanitize_text_field' ] );
		register_setting( 'mydybox_settings_social', 'mydybox_social_fb_client_id', [ 'sanitize_callback' => 'sanitize_text_field' ] );
		register_setting( 'mydybox_settings_social', 'mydybox_social_fb_client_secret', [ 'sanitize_callback' => 'sanitize_text_field' ] );

		// Logs
		register_setting( 'mydybox_settings_logs', 'mydybox_debug_log', [ 'sanitize_callback' => 'sanitize_text_field' ] );

		// Extension placeholders
		if ( is_plugin_active( 'taiwan-store-notifier/taiwan-store-notifier.php' ) ) {
			register_setting( 'mydybox_settings_notifier', 'mydybox_mitake_username', [ 'sanitize_callback' => 'sanitize_text_field' ] );
			register_setting( 'mydybox_settings_notifier', 'mydybox_mitake_password', [ 'sanitize_callback' => 'sanitize_text_field' ] );
			register_setting( 'mydybox_settings_notifier', 'mydybox_line_token', [ 'sanitize_callback' => 'sanitize_text_field' ] );
			register_setting( 'mydybox_settings_notifier', 'mydybox_admin_line_id', [ 'sanitize_callback' => 'sanitize_text_field' ] );
		}
		if ( is_plugin_active( 'taiwan-store-marketing/taiwan-store-marketing.php' ) ) {
			register_setting( 'mydybox_settings_marketing', 'mydybox_marketing_options', [ 'sanitize_callback' => 'sanitize_text_field' ] );
		}
	}

	/* ── Helpers ──────────────────────────────────────────────────────────── */

	private function opt( string $key, $default = '' ) {
		return get_option( $key, $default );
	}

	private function toggle( string $name, string $default = 'no' ): void {
		?>
		<label class="ts-switch">
			<input type="hidden" name="<?php echo esc_attr( $name ); ?>" value="no">
			<input type="checkbox" name="<?php echo esc_attr( $name ); ?>" value="yes"
				<?php checked( 'yes', $this->opt( $name, $default ) ); ?>>
			<span class="ts-slider"></span>
		</label>
		<?php
	}

	private function card_open( string $title, string $desc = '', string $modifier = '', string $icon = 'admin-settings', string $header_extra = '' ): void {
		?>
		<div class="ts-card <?php echo esc_attr( $modifier ); ?>">
			<div class="ts-card-header">
				<div>
					<h2 class="ts-card-title">
						<span class="ts-card-title-icon"><span class="dashicons dashicons-<?php echo esc_attr( $icon ); ?>"></span></span>
						<?php echo esc_html( $title ); ?>
					</h2>
					<?php if ( $desc ) : ?><p class="ts-card-desc"><?php echo esc_html( $desc ); ?></p><?php endif; ?>
				</div>
				<?php echo wp_kses_post( $header_extra ); ?>
			</div>
			<div class="ts-card-body">
		<?php
	}

	private function card_close(): void {
		echo '</div></div>';
	}

	private function field_open( string $label, string $sublabel = '' ): void {
		?>
		<div class="ts-field-row">
			<div class="ts-field-label">
				<?php echo esc_html( $label ); ?>
				<?php if ( $sublabel ) : ?><small><?php echo esc_html( $sublabel ); ?></small><?php endif; ?>
			</div>
			<div class="ts-field-control">
		<?php
	}

	private function field_close(): void {
		echo '</div></div>';
	}

	private function hint( string $text ): void {
		echo '<p class="ts-field-hint">' . esc_html( $text ) . '</p>';
	}

	private function test_mode_notice( string $merchant_id, string $portal_url, string $account, string $password ): void {
		?>
		<div class="ts-notice ts-notice--warning">
			<span>⚠️</span>
			<div>
				<strong><?php esc_html_e( '目前為測試模式', 'mydybox-taiwan-for-woocommerce' ); ?></strong> —
				MerchantID: <code><?php echo esc_html( $merchant_id ); ?></code>
				<?php esc_html_e( '後台', 'mydybox-taiwan-for-woocommerce' ); ?>:
				<a href="<?php echo esc_url( $portal_url ); ?>" target="_blank"><?php echo esc_html( $portal_url ); ?></a>
				（<?php echo esc_html( $account ); ?> / <?php echo esc_html( $password ); ?>）
			</div>
		</div>
		<?php
	}

	/* ── Main Render ──────────────────────────────────────────────────────── */

	public function render_page(): void {
		$active_type = sanitize_key( wp_unslash( $_GET['type'] ?? '' ) );         // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only tab param
		$active_tab  = sanitize_key( wp_unslash( $_GET['tab'] ?? ( $active_type ? 'rules' : 'general' ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$base_url    = admin_url( 'admin.php?page=mydybox-taiwan-for-woocommerce' );

		$extensions = apply_filters( 'mydybox_extension_tabs', [
			[ 'id' => 'marketing', 'name' => __( '行銷助手', 'mydybox-taiwan-for-woocommerce' ), 'path' => 'taiwan-store-marketing/taiwan-store-marketing.php' ],
			[ 'id' => 'notifier',  'name' => __( '通知助手', 'mydybox-taiwan-for-woocommerce' ), 'path' => 'taiwan-store-notifier/taiwan-store-notifier.php' ],
			[ 'id' => 'member',    'name' => __( '會員分級', 'mydybox-taiwan-for-woocommerce' ),    'path' => 'taiwan-store-member/taiwan-store-member.php' ],
			[ 'id' => 'group_buy', 'name' => __( '拼團購買', 'mydybox-taiwan-for-woocommerce' ),   'path' => 'taiwan-store-group-buy/taiwan-store-group-buy.php' ],
		] );

		$extension_tab_ids = apply_filters( 'mydybox_extension_tab_ids', [ 'member', 'group_buy' ] );
		$is_extension_tab  = in_array( $active_tab, $extension_tab_ids, true );
		?>
		<div class="wrap taiwan-store-admin-wrap">

			<!-- Header -->
			<header class="taiwan-store-header">
				<h1>
					<span class="dashicons dashicons-store"></span>
					<?php esc_html_e( '台灣商店：核心助手', 'mydybox-taiwan-for-woocommerce' ); ?>
					<span class="taiwan-store-version">v<?php echo esc_html( MYDYBOX_VERSION ); ?></span>
				</h1>
				<a href="<?php echo esc_url( add_query_arg( 'tab', 'manual', $base_url ) ); ?>" class="btn-white">
					<span class="dashicons dashicons-book-alt" style="font-size:16px;vertical-align:middle;margin-top:-2px;"></span>
					<?php esc_html_e( '使用手冊', 'mydybox-taiwan-for-woocommerce' ); ?>
				</a>
			</header>

			<!-- Tab bar -->
			<nav class="taiwan-store-tabs">
				<?php
				$core_tabs = [
					'general'  => [ 'label' => __( '一般設定', 'mydybox-taiwan-for-woocommerce' ),  'icon' => 'admin-generic' ],
					'checkout' => [ 'label' => __( '結帳設定', 'mydybox-taiwan-for-woocommerce' ),  'icon' => 'cart' ],
				];
				foreach ( $core_tabs as $tid => $t ) :
					$is_active = ( $active_tab === $tid );
					?>
					<a href="<?php echo esc_url( add_query_arg( 'tab', $tid, $base_url ) ); ?>"
					   class="taiwan-store-tab-link <?php echo $is_active ? 'active' : ''; ?>">
						<span class="dashicons dashicons-<?php echo esc_attr( $t['icon'] ); ?>"></span>
						<?php echo esc_html( $t['label'] ); ?>
					</a>
				<?php endforeach; ?>

				<?php
				$rule_tabs = [
					'payment'  => __( '付款規則', 'mydybox-taiwan-for-woocommerce' ),
					'shipping' => __( '運費規則', 'mydybox-taiwan-for-woocommerce' ),
					'cart'     => __( '購物車規則', 'mydybox-taiwan-for-woocommerce' ),
				];
				foreach ( $rule_tabs as $rtype => $rlabel ) :
					$is_active = ( $active_tab === 'rules' && $active_type === $rtype );
					?>
					<a href="<?php echo esc_url( add_query_arg( [ 'tab' => 'rules', 'type' => $rtype ], $base_url ) ); ?>"
					   class="taiwan-store-tab-link <?php echo $is_active ? 'active' : ''; ?>">
						<?php echo esc_html( $rlabel ); ?>
					</a>
				<?php endforeach; ?>

				<a href="<?php echo esc_url( add_query_arg( 'tab', 'social', $base_url ) ); ?>"
				   class="taiwan-store-tab-link <?php echo $active_tab === 'social' ? 'active' : ''; ?>">
					<span class="dashicons dashicons-share-alt"></span>
					<?php esc_html_e( '社群登入', 'mydybox-taiwan-for-woocommerce' ); ?>
				</a>

				<?php foreach ( $extensions as $ext ) : ?>
					<?php if ( is_plugin_active( $ext['path'] ) ) : ?>
						<a href="<?php echo esc_url( add_query_arg( 'tab', $ext['id'], $base_url ) ); ?>"
						   class="taiwan-store-tab-link <?php echo $active_tab === $ext['id'] ? 'active' : ''; ?>">
							<span class="dashicons dashicons-admin-plugins"></span>
							<?php echo esc_html( $ext['name'] ); ?>
							<span class="ts-pro-badge">PRO</span>
						</a>
					<?php endif; ?>
				<?php endforeach; ?>

				<a href="<?php echo esc_url( add_query_arg( 'tab', 'logs', $base_url ) ); ?>"
				   class="taiwan-store-tab-link <?php echo $active_tab === 'logs' ? 'active' : ''; ?>">
					<span class="dashicons dashicons-chart-area"></span>
					<?php esc_html_e( '系統紀錄', 'mydybox-taiwan-for-woocommerce' ); ?>
				</a>

<a href="<?php echo esc_url( add_query_arg( 'tab', 'help', $base_url ) ); ?>"
				   class="taiwan-store-tab-link <?php echo $active_tab === 'help' ? 'active' : ''; ?>">
					<span class="dashicons dashicons-editor-help"></span>
					<?php esc_html_e( '使用說明', 'mydybox-taiwan-for-woocommerce' ); ?>
				</a>

				</nav>

			<!-- Tab body -->
			<div class="taiwan-store-tab-body <?php echo $active_tab === 'rules' ? 'taiwan-store-tab-body--rules' : ''; ?>">
				<?php if ( ! $is_extension_tab && $active_tab !== 'help' && $active_tab !== 'manual' ) : ?>
				<form method="post" action="options.php">
					<?php
					$group = 'mydybox_settings_general';
					if ( $active_tab === 'checkout' )  $group = 'mydybox_settings_checkout';
					if ( $active_tab === 'social' )    $group = 'mydybox_settings_social';
					if ( $active_tab === 'logs' )      $group = 'mydybox_settings_logs';
					if ( $active_tab === 'notifier' )  $group = 'mydybox_settings_notifier';
					if ( $active_tab === 'marketing' ) $group = 'mydybox_settings_marketing';
		settings_fields( $group );
					?>
				<?php endif; ?>

				<!-- ── General ─────────────────────────────────────────────── -->
				<?php if ( $active_tab === 'general' ) : ?>
				<div class="taiwan-store-tab-content active">
					<div class="ts-settings-stack">

						<?php $this->card_open(
							__( '自訂訂單編號', 'mydybox-taiwan-for-woocommerce' ),
							__( '啟用後，新訂單將以「前綴 + 日期（YYYYMMDD）+ 流水號」格式顯示。', 'mydybox-taiwan-for-woocommerce' ),
							'', 'tag'
						); ?>
							<?php $this->field_open( __( '啟用自訂訂單編號', 'mydybox-taiwan-for-woocommerce' ) ); ?>
								<?php $this->toggle( 'mydybox_custom_order_number_enabled', 'yes' ); ?>
							<?php $this->field_close(); ?>

							<?php $this->field_open( __( '訂單編號前綴', 'mydybox-taiwan-for-woocommerce' ) ); ?>
								<input type="text" name="mydybox_order_number_prefix"
									value="<?php echo esc_attr( $this->opt( 'mydybox_order_number_prefix', 'TW' ) ); ?>"
									class="regular-text" placeholder="TW">
								<?php $this->hint( __( '例如填入 TW 則編號為 TW20260508-0001。', 'mydybox-taiwan-for-woocommerce' ) ); ?>
							<?php $this->field_close(); ?>

							<?php $this->field_open( __( '流水號位數', 'mydybox-taiwan-for-woocommerce' ) ); ?>
								<input type="number" name="mydybox_order_number_digits"
									value="<?php echo esc_attr( $this->opt( 'mydybox_order_number_digits', '4' ) ); ?>"
									min="1" max="10" class="small-text">
								<?php $this->hint( __( '流水號的最少顯示位數（不足時補零）。', 'mydybox-taiwan-for-woocommerce' ) ); ?>
							<?php $this->field_close(); ?>

							<?php $this->field_open( __( '編號隨機後綴', 'mydybox-taiwan-for-woocommerce' ) ); ?>
								<?php $this->toggle( 'mydybox_order_number_random_suffix' ); ?>
								<?php $this->hint( __( '啟用後會在流水號後方加 2 碼隨機英數（例：TW20240511-0001-X9），防範競爭對手推估單量。', 'mydybox-taiwan-for-woocommerce' ) ); ?>
							<?php $this->field_close(); ?>
						<?php $this->card_close(); ?>

						<?php $this->card_open(
							__( '結帳頁公告', 'mydybox-taiwan-for-woocommerce' ),
							__( '在結帳頁面頂端顯示重要公告（例如：連假出貨公告、全館免運提示）。', 'mydybox-taiwan-for-woocommerce' ),
							'ts-card--amber', 'megaphone'
						); ?>
							<?php $this->field_open( __( '啟用公告', 'mydybox-taiwan-for-woocommerce' ) ); ?>
								<?php $this->toggle( 'mydybox_checkout_announcement_enabled' ); ?>
							<?php $this->field_close(); ?>

							<?php $this->field_open( __( '公告內容', 'mydybox-taiwan-for-woocommerce' ) ); ?>
								<textarea name="mydybox_checkout_announcement_text" rows="3" class="large-text"
									placeholder="<?php esc_attr_e( '填入結帳頁公告內容…', 'mydybox-taiwan-for-woocommerce' ); ?>"
								><?php echo esc_textarea( $this->opt( 'mydybox_checkout_announcement_text' ) ); ?></textarea>
								<?php $this->hint( __( '支援 HTML 標籤。', 'mydybox-taiwan-for-woocommerce' ) ); ?>
							<?php $this->field_close(); ?>
						<?php $this->card_close(); ?>

						<?php $this->card_open(
							__( '已連線擴充外掛', 'mydybox-taiwan-for-woocommerce' ),
							__( '核心外掛會自動偵測並優化與下列擴充功能的相容性。', 'mydybox-taiwan-for-woocommerce' ),
							'ts-card--blue', 'admin-plugins'
						); ?>
							<div class="ts-ext-list">
								<?php
								$ext_list = [
									[ 'n' => '行銷助手 Pro',  'p' => 'taiwan-store-marketing/taiwan-store-marketing.php' ],
									[ 'n' => '通知助手 Pro',  'p' => 'taiwan-store-notifier/taiwan-store-notifier.php' ],
									[ 'n' => '會員分級 Pro',  'p' => 'taiwan-store-member/taiwan-store-member.php' ],
									[ 'n' => '拼團購買 Pro',  'p' => 'taiwan-store-group-buy/taiwan-store-group-buy.php' ],
								];
								foreach ( $ext_list as $e ) :
									$active = is_plugin_active( $e['p'] );
									?>
									<div class="ts-ext-item">
										<span class="ts-ext-name">Mydybox <?php echo esc_html( $e['n'] ); ?></span>
										<?php if ( $active ) : ?>
											<span class="ts-badge ts-badge--active ts-badge--dot"><?php esc_html_e( 'Running', 'mydybox-taiwan-for-woocommerce' ); ?></span>
										<?php else : ?>
											<span class="ts-badge ts-badge--inactive"><?php esc_html_e( 'Inactive', 'mydybox-taiwan-for-woocommerce' ); ?></span>
										<?php endif; ?>
									</div>
								<?php endforeach; ?>
							</div>
						<?php $this->card_close(); ?>

						<?php $this->card_open( __( '授權啟用', 'mydybox-taiwan-for-woocommerce' ), '', '', 'lock' ); ?>
							<?php $this->field_open( __( '授權碼', 'mydybox-taiwan-for-woocommerce' ), __( '填入後啟用自動更新與支援。', 'mydybox-taiwan-for-woocommerce' ) ); ?>
								<div class="ts-license-row">
									<input type="text" name="mydybox_license_key"
										value="<?php echo esc_attr( $this->opt( 'mydybox_license_key' ) ); ?>"
										class="regular-text" placeholder="TS-XXXX-XXXX">
								</div>
							<?php $this->field_close(); ?>
						<?php $this->card_close(); ?>

					</div>
					<div class="ts-form-footer"><?php submit_button( __( '儲存一般設定', 'mydybox-taiwan-for-woocommerce' ), 'primary', 'submit', false ); ?></div>
				</div>
				<?php endif; ?>

				<!-- ── Checkout ─────────────────────────────────────────────── -->
				<?php if ( $active_tab === 'checkout' ) : ?>
				<div class="taiwan-store-tab-content active">
					<div class="ts-settings-stack">

						<?php $this->card_open(
							__( '結帳欄位', 'mydybox-taiwan-for-woocommerce' ),
							'', '', 'editor-ul'
						); ?>
							<?php $this->field_open( __( '合併姓名欄位', 'mydybox-taiwan-for-woocommerce' ) ); ?>
								<?php $this->toggle( 'mydybox_checkout_name_consolidate', 'yes' ); ?>
								<?php $this->hint( __( '將「名字」和「姓氏」合併為單一「姓名」欄位。', 'mydybox-taiwan-for-woocommerce' ) ); ?>
							<?php $this->field_close(); ?>

							<?php $this->field_open( __( '顯示統一編號欄位', 'mydybox-taiwan-for-woocommerce' ) ); ?>
								<?php $this->toggle( 'mydybox_checkout_show_tax_id', 'yes' ); ?>
							<?php $this->field_close(); ?>

							<?php $this->field_open( __( '驗證統一編號格式', 'mydybox-taiwan-for-woocommerce' ) ); ?>
								<?php $this->toggle( 'mydybox_checkout_validate_tax_id', 'yes' ); ?>
								<?php $this->hint( __( '驗證統一編號格式（8 位數字，MOD11 加權）。', 'mydybox-taiwan-for-woocommerce' ) ); ?>
							<?php $this->field_close(); ?>

							<?php $this->field_open( __( '統編自動查詢公司名稱', 'mydybox-taiwan-for-woocommerce' ) ); ?>
								<?php $this->toggle( 'mydybox_checkout_lookup_tax_id', 'no' ); ?>
								<?php $this->hint( __( '輸入統編時自動帶入公司名稱（串接政府 GCIS API）。', 'mydybox-taiwan-for-woocommerce' ) ); ?>
							<?php $this->field_close(); ?>

							<?php $this->field_open( __( 'GCIS API UUID', 'mydybox-taiwan-for-woocommerce' ), __( '查詢失敗時更新此欄即可，無需改程式。', 'mydybox-taiwan-for-woocommerce' ) ); ?>
								<input type="text" name="mydybox_gcis_api_uuid"
									value="<?php echo esc_attr( $this->opt( 'mydybox_gcis_api_uuid', '236EE382-4942-41A9-BD03-CA0709025E7C' ) ); ?>"
									class="regular-text mono">
							<?php $this->field_close(); ?>

							<?php $this->field_open( __( '台灣手機號碼驗證', 'mydybox-taiwan-for-woocommerce' ) ); ?>
								<?php $this->toggle( 'mydybox_checkout_phone_validate', 'yes' ); ?>
								<?php $this->hint( __( '驗證手機格式（09xxxxxxxx）。', 'mydybox-taiwan-for-woocommerce' ) ); ?>
							<?php $this->field_close(); ?>

							<?php $this->field_open( __( '郵遞區號自動填入', 'mydybox-taiwan-for-woocommerce' ) ); ?>
								<?php $this->toggle( 'mydybox_checkout_postcode_autofill', 'yes' ); ?>
								<?php $this->hint( __( '選擇鄉鎮市區後自動填入郵遞區號。', 'mydybox-taiwan-for-woocommerce' ) ); ?>
							<?php $this->field_close(); ?>
						<?php $this->card_close(); ?>

						<?php $this->card_open(
							__( '進階結帳功能', 'mydybox-taiwan-for-woocommerce' ),
							'', '', 'admin-tools'
						); ?>
							<?php $this->field_open( __( '棄單提醒', 'mydybox-taiwan-for-woocommerce' ) ); ?>
								<?php $this->toggle( 'mydybox_checkout_abandoned_cart' ); ?>
							<?php $this->field_close(); ?>

							<?php $this->field_open( __( '提醒延遲時間（分鐘）', 'mydybox-taiwan-for-woocommerce' ) ); ?>
								<input type="number" name="mydybox_abandoned_cart_delay"
									value="<?php echo esc_attr( $this->opt( 'mydybox_abandoned_cart_delay', '60' ) ); ?>"
									min="10" max="1440" class="small-text">
								<?php $this->hint( __( '用戶離開結帳頁幾分鐘後發送提醒（預設 60 分鐘）。', 'mydybox-taiwan-for-woocommerce' ) ); ?>
							<?php $this->field_close(); ?>

							<?php $this->field_open( __( '發送 Email 提醒', 'mydybox-taiwan-for-woocommerce' ) ); ?>
								<?php $this->toggle( 'mydybox_abandoned_cart_email', 'yes' ); ?>
							<?php $this->field_close(); ?>

							<?php $this->field_open( __( 'Email 主旨', 'mydybox-taiwan-for-woocommerce' ) ); ?>
								<input type="text" name="mydybox_abandoned_cart_email_subject"
									value="<?php echo esc_attr( $this->opt( 'mydybox_abandoned_cart_email_subject', '您的購物車還在等您 🛒' ) ); ?>"
									class="large-text">
							<?php $this->field_close(); ?>

							<?php $this->field_open( __( 'Email 內容', 'mydybox-taiwan-for-woocommerce' ) ); ?>
								<textarea name="mydybox_abandoned_cart_email_body" rows="5" class="large-text"><?php echo esc_textarea( $this->opt( 'mydybox_abandoned_cart_email_body' ) ); ?></textarea>
								<?php $this->hint( __( '可用變數：{{recover_url}} 回購連結、{{email}} 用戶信箱、{{site_name}} 網站名稱。留空使用預設內容。', 'mydybox-taiwan-for-woocommerce' ) ); ?>
							<?php $this->field_close(); ?>

							<?php $this->field_open( __( '發送 LINE 推播', 'mydybox-taiwan-for-woocommerce' ) ); ?>
								<?php $this->toggle( 'mydybox_abandoned_cart_line' ); ?>
								<?php $this->hint( __( '需先於「社群登入」設定 LINE Messaging API Token，且用戶須曾用 LINE 登入。', 'mydybox-taiwan-for-woocommerce' ) ); ?>
							<?php $this->field_close(); ?>

							<?php $this->field_open( __( 'LINE 訊息內容', 'mydybox-taiwan-for-woocommerce' ) ); ?>
								<textarea name="mydybox_abandoned_cart_line_message" rows="3" class="large-text"><?php echo esc_textarea( $this->opt( 'mydybox_abandoned_cart_line_message', "您的購物車還有商品尚未結帳！\n點此回到購物車：{{recover_url}}" ) ); ?></textarea>
								<?php $this->hint( __( '可用變數：{{recover_url}}', 'mydybox-taiwan-for-woocommerce' ) ); ?>
							<?php $this->field_close(); ?>

							<?php $this->field_open( __( '結帳倒數計時器', 'mydybox-taiwan-for-woocommerce' ) ); ?>
								<?php $this->toggle( 'mydybox_checkout_countdown' ); ?>
							<?php $this->field_close(); ?>

							<?php $this->field_open( __( '倒數分鐘數', 'mydybox-taiwan-for-woocommerce' ) ); ?>
								<input type="number" name="mydybox_checkout_countdown_minutes"
									value="<?php echo esc_attr( $this->opt( 'mydybox_checkout_countdown_minutes', '15' ) ); ?>"
									min="1" max="60" class="small-text">
								<?php $this->hint( __( '結帳頁顯示的保留時間（預設 15 分鐘）。', 'mydybox-taiwan-for-woocommerce' ) ); ?>
							<?php $this->field_close(); ?>

							<?php $this->field_open( __( '置底加入購物車列', 'mydybox-taiwan-for-woocommerce' ) ); ?>
								<?php $this->toggle( 'mydybox_product_sticky_bar', 'yes' ); ?>
							<?php $this->field_close(); ?>
						<?php $this->card_close(); ?>

						<?php $this->card_open(
							__( '超商取貨（ECPay 物流）', 'mydybox-taiwan-for-woocommerce' ),
							__( '啟用後請至 WooCommerce → 運費 → 運送區域 新增「超商取貨」方式。', 'mydybox-taiwan-for-woocommerce' ),
							'ts-card--sky', 'store'
						); ?>
							<?php $this->field_open( __( '啟用超商取貨', 'mydybox-taiwan-for-woocommerce' ) ); ?>
								<?php $this->toggle( 'mydybox_cvs_enabled' ); ?>
								<?php $this->hint( __( '啟用後須至 WooCommerce → 運費 → 運送區域 新增「超商取貨」方式。', 'mydybox-taiwan-for-woocommerce' ) ); ?>
							<?php $this->field_close(); ?>

							<?php $this->field_open( __( '測試模式', 'mydybox-taiwan-for-woocommerce' ) ); ?>
								<?php $this->toggle( 'mydybox_cvs_test_mode', 'yes' ); ?>
								<?php $this->hint( __( '開啟時使用 ECPay Staging（MerchantID: 3002607），關閉後請填入正式帳號。', 'mydybox-taiwan-for-woocommerce' ) ); ?>
							<?php $this->field_close(); ?>

							<?php $this->field_open( __( '正式 MerchantID', 'mydybox-taiwan-for-woocommerce' ) ); ?>
								<input type="text" name="mydybox_cvs_merchant_id"
									value="<?php echo esc_attr( $this->opt( 'mydybox_cvs_merchant_id' ) ); ?>"
									class="regular-text"
									placeholder="<?php esc_attr_e( '測試模式時無需填寫', 'mydybox-taiwan-for-woocommerce' ); ?>">
							<?php $this->field_close(); ?>

							<?php $this->field_open( __( '正式 HashKey', 'mydybox-taiwan-for-woocommerce' ) ); ?>
								<input type="password" name="mydybox_cvs_hash_key"
									value="<?php echo esc_attr( $this->opt( 'mydybox_cvs_hash_key' ) ); ?>"
									class="regular-text">
							<?php $this->field_close(); ?>

							<?php $this->field_open( __( '正式 HashIV', 'mydybox-taiwan-for-woocommerce' ) ); ?>
								<input type="password" name="mydybox_cvs_hash_iv"
									value="<?php echo esc_attr( $this->opt( 'mydybox_cvs_hash_iv' ) ); ?>"
									class="regular-text">
							<?php $this->field_close(); ?>

							<?php if ( 'yes' === $this->opt( 'mydybox_cvs_test_mode', 'yes' ) ) : ?>
								<div style="padding:0 24px 16px;">
									<?php $this->test_mode_notice( '3002607', 'https://vendor-stage.ecpay.com.tw', 'stagetest3', 'test1234' ); ?>
								</div>
							<?php endif; ?>
						<?php $this->card_close(); ?>

						<?php $this->card_open(
							__( '超商取貨（藍新物流）', 'mydybox-taiwan-for-woocommerce' ),
							__( '啟用後請至 WooCommerce → 運費 → 運送區域 新增「超商取貨（藍新）」方式。', 'mydybox-taiwan-for-woocommerce' ),
							'ts-card--sky', 'store'
						); ?>
							<?php $this->field_open( __( '啟用藍新超商取貨', 'mydybox-taiwan-for-woocommerce' ) ); ?>
								<?php $this->toggle( 'mydybox_newebpay_cvs_enabled' ); ?>
								<?php $this->hint( __( '啟用後須至 WooCommerce → 運費 → 運送區域 新增「超商取貨（藍新）」方式。', 'mydybox-taiwan-for-woocommerce' ) ); ?>
							<?php $this->field_close(); ?>

							<?php $this->field_open( __( '測試模式', 'mydybox-taiwan-for-woocommerce' ) ); ?>
								<?php $this->toggle( 'mydybox_newebpay_cvs_test_mode', 'yes' ); ?>
								<?php $this->hint( __( '開啟時使用藍新測試環境，關閉後請填入正式帳號。', 'mydybox-taiwan-for-woocommerce' ) ); ?>
							<?php $this->field_close(); ?>

							<?php $this->field_open( __( '正式 MerchantID', 'mydybox-taiwan-for-woocommerce' ) ); ?>
								<input type="text" name="mydybox_newebpay_cvs_merchant_id"
									value="<?php echo esc_attr( $this->opt( 'mydybox_newebpay_cvs_merchant_id' ) ); ?>"
									class="regular-text"
									placeholder="<?php esc_attr_e( '測試模式時無需填寫', 'mydybox-taiwan-for-woocommerce' ); ?>">
							<?php $this->field_close(); ?>

							<?php $this->field_open( __( '正式 HashKey', 'mydybox-taiwan-for-woocommerce' ) ); ?>
								<input type="password" name="mydybox_newebpay_cvs_hash_key"
									value="<?php echo esc_attr( $this->opt( 'mydybox_newebpay_cvs_hash_key' ) ); ?>"
									class="regular-text">
							<?php $this->field_close(); ?>

							<?php $this->field_open( __( '正式 HashIV', 'mydybox-taiwan-for-woocommerce' ) ); ?>
								<input type="password" name="mydybox_newebpay_cvs_hash_iv"
									value="<?php echo esc_attr( $this->opt( 'mydybox_newebpay_cvs_hash_iv' ) ); ?>"
									class="regular-text">
							<?php $this->field_close(); ?>
						<?php $this->card_close(); ?>

					</div>
					<div class="ts-form-footer"><?php submit_button( __( '儲存結帳設定', 'mydybox-taiwan-for-woocommerce' ), 'primary', 'submit', false ); ?></div>
				</div>
				<?php endif; ?>

				<!-- ── Rules ─────────────────────────────────────────────────── -->
				<?php if ( $active_tab === 'rules' ) : ?>
				<div class="taiwan-store-tab-content active">
					<?php ( new \Mydybox\Admin\Rules_UI() )->render_rules_editor( $active_type ); ?>
				</div>
				<?php endif; ?>

				<!-- ── Social ─────────────────────────────────────────────────── -->
				<?php if ( $active_tab === 'social' ) : ?>
				<div class="taiwan-store-tab-content active">
					<div class="ts-settings-stack">
						<div class="ts-social-grid">

							<?php
							$social_providers = [
								[
									'title'   => 'LINE Login',
									'toggle'  => 'mydybox_social_line_enabled',
									'fields'  => [
										[ 'label' => 'Channel ID',          'name' => 'mydybox_social_line_client_id',     'type' => 'text' ],
										[ 'label' => 'Channel Secret',      'name' => 'mydybox_social_line_client_secret', 'type' => 'password' ],
										[ 'label' => 'Messaging API Token', 'name' => 'mydybox_social_line_token',         'type' => 'textarea',
										  'hint'  => '用於發送遺棄購物車提醒與訂單通知。' ],
									],
									'icon' => 'format-chat',
								],
								[
									'title'  => 'Google Login',
									'toggle' => 'mydybox_social_google_enabled',
									'fields' => [
										[ 'label' => 'Client ID',     'name' => 'mydybox_social_google_client_id',     'type' => 'text' ],
										[ 'label' => 'Client Secret', 'name' => 'mydybox_social_google_client_secret', 'type' => 'password' ],
									],
									'icon' => 'google',
								],
								[
									'title'  => 'Facebook Login',
									'toggle' => 'mydybox_social_fb_enabled',
									'fields' => [
										[ 'label' => 'App ID',     'name' => 'mydybox_social_fb_client_id',     'type' => 'text' ],
										[ 'label' => 'App Secret', 'name' => 'mydybox_social_fb_client_secret', 'type' => 'password' ],
									],
									'icon' => 'facebook-alt',
								],
							];
							foreach ( $social_providers as $sp ) :
								$toggle_html = '<label class="ts-switch"><input type="hidden" name="' . esc_attr( $sp['toggle'] ) . '" value="no"><input type="checkbox" name="' . esc_attr( $sp['toggle'] ) . '" value="yes" ' . checked( 'yes', $this->opt( $sp['toggle'] ), false ) . '><span class="ts-slider"></span></label>';
								$this->card_open( $sp['title'], '', 'ts-card--narrow', $sp['icon'], $toggle_html );
								foreach ( $sp['fields'] as $f ) :
									$this->field_open( $f['label'] );
									if ( $f['type'] === 'textarea' ) :
										?><textarea name="<?php echo esc_attr( $f['name'] ); ?>" rows="3"><?php echo esc_textarea( $this->opt( $f['name'] ) ); ?></textarea><?php
									else :
										?><input type="<?php echo esc_attr( $f['type'] ); ?>" name="<?php echo esc_attr( $f['name'] ); ?>" value="<?php echo esc_attr( $this->opt( $f['name'] ) ); ?>"><?php
									endif;
									if ( ! empty( $f['hint'] ) ) $this->hint( $f['hint'] );
									$this->field_close();
								endforeach;
								$this->card_close();
							endforeach;
							?>

						</div>

						<!-- Setup Guide -->
						<div class="ts-guide-card">
							<div class="ts-guide-card-header">
								<span class="dashicons dashicons-welcome-learn-more"></span>
								<h3><?php esc_html_e( '社群登入串接指南', 'mydybox-taiwan-for-woocommerce' ); ?></h3>
							</div>
							<div class="ts-guide-steps">
								<div class="ts-guide-step">
									<h4>Step 1: 取得憑證</h4>
									<ul>
										<li><strong>LINE:</strong> 前往 <a href="https://developers.line.biz/" target="_blank">LINE Developers</a> 建立 Provider 與 Channel。</li>
										<li><strong>Google:</strong> 前往 <a href="https://console.cloud.google.com/" target="_blank">Google Cloud</a> 建立 OAuth 2.0 憑證。</li>
										<li><strong>Facebook:</strong> 前往 <a href="https://developers.facebook.com/" target="_blank">Meta for Developers</a> 建立應用程式。</li>
									</ul>
								</div>
								<div class="ts-guide-step">
									<h4>Step 2: Callback URL</h4>
									<p><?php esc_html_e( '請將下列網址填入各平台的「重新導向 URI」欄位：', 'mydybox-taiwan-for-woocommerce' ); ?></p>
									<div class="ts-callback-box" style="margin-top:10px;">
										<div><strong>LINE:</strong> <code><?php echo esc_html( home_url( '/?taiwan_store_social=line' ) ); ?></code></div>
										<div><strong>Google:</strong> <code><?php echo esc_html( home_url( '/?taiwan_store_social=google' ) ); ?></code></div>
										<div><strong>FB:</strong> <code><?php echo esc_html( home_url( '/?taiwan_store_social=facebook' ) ); ?></code></div>
									</div>
								</div>
								<div class="ts-guide-step">
									<h4>Step 3: HTTPS</h4>
									<p><?php esc_html_e( '所有社群登入皆要求網站具備有效的 SSL 憑證（HTTPS）。若為本機測試，請確保環境支援 HTTPS 導向。', 'mydybox-taiwan-for-woocommerce' ); ?></p>
								</div>
							</div>
						</div>

					</div>
					<div class="ts-form-footer"><?php submit_button( __( '儲存社群設定', 'mydybox-taiwan-for-woocommerce' ), 'primary', 'submit', false ); ?></div>
				</div>
				<?php endif; ?>

				<!-- ── Logs ─────────────────────────────────────────────────── -->
				<?php if ( $active_tab === 'logs' ) : ?>
				<div class="taiwan-store-tab-content active">
					<div class="ts-settings-stack">

						<?php $this->card_open( __( '今日概覽', 'mydybox-taiwan-for-woocommerce' ), '', '', 'chart-area' ); ?>
							<div style="padding:20px 24px;">
								<div id="mydybox-taiwan-for-woocommerce-logs-root">
									<div class="mydybox-taiwan-for-woocommerce-spinner active"></div>
								</div>
							</div>
						<?php $this->card_close(); ?>

						<?php
						$debug_toggle = '<label class="ts-switch"><input type="hidden" name="mydybox_debug_log" value="no"><input type="checkbox" name="mydybox_debug_log" value="yes" ' . checked( 'yes', $this->opt( 'mydybox_debug_log' ), false ) . '><span class="ts-slider"></span></label>';
						$this->card_open( __( 'Debug 紀錄模式', 'mydybox-taiwan-for-woocommerce' ), __( '啟用後，詳細的 API 請求與錯誤訊息將記錄於 WooCommerce 系統日誌。', 'mydybox-taiwan-for-woocommerce' ), 'ts-card--amber', 'editor-code', $debug_toggle );
						?>
							<?php $this->field_open( __( '檢視系統日誌', 'mydybox-taiwan-for-woocommerce' ) ); ?>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-status&tab=logs' ) ); ?>" class="button button-secondary">
									<?php esc_html_e( '前往 WooCommerce 日誌', 'mydybox-taiwan-for-woocommerce' ); ?>
								</a>
							<?php $this->field_close(); ?>
						<?php $this->card_close(); ?>

					</div>
					<div class="ts-form-footer"><?php submit_button( __( '儲存日誌設定', 'mydybox-taiwan-for-woocommerce' ), 'primary', 'submit', false ); ?></div>
				</div>
				<?php endif; ?>

				<!-- ── Help ─────────────────────────────────────────────────── -->
				<?php if ( $active_tab === 'help' ) : ?>
				<div class="taiwan-store-tab-content active">

					<div class="ts-help-hero">
						<div class="ts-help-hero-text">
							<h2>👋 <?php esc_html_e( '需要什麼幫助？', 'mydybox-taiwan-for-woocommerce' ); ?></h2>
							<p><?php esc_html_e( '歡迎使用 Mydybox 系列外掛。我們提供專為台灣電商設計的一站式在地化工具，讓您的 WooCommerce 商店更符合台灣買家的使用習慣。', 'mydybox-taiwan-for-woocommerce' ); ?></p>
						</div>
					</div>

					<h3 class="ts-section-heading">
						<span class="dashicons dashicons-heart" style="color:var(--ts-red);"></span>
						<?php esc_html_e( '系統環境檢測', 'mydybox-taiwan-for-woocommerce' ); ?>
					</h3>

					<div class="ts-card ts-card--green" style="margin-bottom:28px;">
						<div class="ts-sys-grid">
							<?php
							$checks = [
								[ 'l' => 'HTTPS 加密',   's' => is_ssl(),                                           'm' => is_ssl() ? '已啟用' : '⚠ 未啟用（社群登入必須）' ],
								[ 'l' => 'PHP 版本',     's' => version_compare( PHP_VERSION, '7.4', '>=' ),        'm' => 'v' . PHP_VERSION ],
								[ 'l' => 'WooCommerce',  's' => class_exists( 'WooCommerce' ),                     'm' => class_exists( 'WooCommerce' ) ? 'v' . WC()->version : '⚠ 未安裝' ],
								[ 'l' => '記憶體上限',   's' => (int) ini_get( 'memory_limit' ) >= 256,            'm' => ini_get( 'memory_limit' ) . ( (int) ini_get( 'memory_limit' ) < 256 ? '（建議 256M+）' : '' ) ],
							];
							foreach ( $checks as $c ) :
								?>
								<div class="ts-sys-item">
									<div class="ts-sys-item-label"><?php echo esc_html( $c['l'] ); ?></div>
									<div class="ts-sys-item-value <?php echo $c['s'] ? 'pass' : 'warn'; ?>">
										<span class="dashicons <?php echo $c['s'] ? 'dashicons-yes-alt' : 'dashicons-warning'; ?>"></span>
										<?php echo esc_html( $c['m'] ); ?>
									</div>
								</div>
							<?php endforeach; ?>
						</div>
					</div>

					<h3 class="ts-section-heading">
						<span class="dashicons dashicons-book-alt" style="color:var(--ts-blue);"></span>
						<?php esc_html_e( '核心功能說明', 'mydybox-taiwan-for-woocommerce' ); ?>
					</h3>

					<div class="ts-help-grid">
						<?php
						$features = [
							[ 'icon' => 'cart',          'title' => '台灣在地化結帳',
								'desc' => [ '統一編號查詢：結帳時自動帶入公司名稱（串接政府 GCIS API），支援格式驗證。', '地址聯動：縣市 → 鄉鎮市區自動帶入郵遞區號，即時預覽完整地址。', '台式欄位：姓名整合單欄、行動電話 09xx 格式驗證、發票類型選擇。', '設定位置：台灣商店 → 結帳設定' ] ],
							[ 'icon' => 'money-alt',     'title' => 'ECPay 綠界金流',
								'desc' => [ '支援付款方式：信用卡（含分期）、ATM 轉帳、超商代碼、WebATM、TWQR。', '退款支援：可在 WooCommerce 訂單頁直接發起退款。', '設定位置：WooCommerce → 付款 → ECPay 綠界金流' ] ],
							[ 'icon' => 'store',         'title' => '超商取貨（ECPay 物流）',
								'desc' => [ '支援超商：7-ELEVEN、全家、萊爾富、OK（含 C2C 模式）。', '地圖選店：結帳時彈出 ECPay 地圖選擇門市，自動填入取貨資訊。', '設定位置：台灣商店 → 結帳設定 → 超商取貨' ] ],
							[ 'icon' => 'media-text',    'title' => 'ECPay 電子發票',
								'desc' => [ '支援發票類型：個人二聯式、手機載具、自然人憑證、愛心捐贈、公司三聯式。', '自動開立：訂單進入指定狀態時自動呼叫 ECPay B2C API 開立發票。', '設定位置：台灣商店 → 電子發票' ] ],
							[ 'icon' => 'admin-settings', 'title' => '視覺化規則引擎',
								'desc' => [ '付款規則：離島地址自動隱藏貨到付款、特定商品限用信用卡。', '運費規則：滿額免運、偏遠地區加收費用。', '購物車規則：最低金額門檻、阻止特定組合結帳。', '無需寫程式：可一鍵載入範例規則快速上手。' ] ],
							[ 'icon' => 'share-alt',     'title' => '社群登入',
								'desc' => [ '支援平台：LINE Login、Google、Facebook 一鍵登入。', '自動建立帳號：新用戶自動建立 WooCommerce 帳號，顯示名稱與頭像同步。', '設定位置：台灣商店 → 社群登入' ] ],
							[ 'icon' => 'email-alt',     'title' => '棄單回收',
								'desc' => [ '自動追蹤：用戶填入 email 後離開，系統記錄購物車內容。', 'Email 提醒：超過設定時間後自動寄送回購連結。', '設定位置：台灣商店 → 結帳設定 → 棄單提醒' ] ],
							[ 'icon' => 'products',      'title' => '訂單與頁面優化',
								'desc' => [ '台式訂單編號：自訂前綴 + YYYYMMDD + 流水號，支援隨機後綴。', '結帳頁公告：頂端顯示重要通知（物流異動、節慶公告）。', 'Blocks Checkout：支援 WooCommerce Blocks 區塊結帳頁。' ] ],
						];
						foreach ( $features as $f ) :
							?>
							<div class="ts-help-card">
								<div class="ts-help-card-header">
									<div class="ts-help-icon"><span class="dashicons dashicons-<?php echo esc_attr( $f['icon'] ); ?>"></span></div>
									<h2><?php echo esc_html( $f['title'] ); ?></h2>
								</div>
								<div class="ts-help-content">
									<?php foreach ( $f['desc'] as $line ) : ?>
										<p><?php echo esc_html( $line ); ?></p>
									<?php endforeach; ?>
								</div>
							</div>
						<?php endforeach; ?>
					</div>

					<h3 class="ts-section-heading">
						<span class="dashicons dashicons-admin-plugins" style="color:var(--ts-green);"></span>
						<?php esc_html_e( '擴充外掛', 'mydybox-taiwan-for-woocommerce' ); ?>
					</h3>

					<?php
					// Paid extensions are sold separately on the author's site. `purchase_url`
					// can be filtered to point each card at the right product page.
					$ext_showcase = apply_filters( 'mydybox_pro_extensions', [
						[ 'name' => 'Mydybox 行銷助手 Pro',
							'path' => 'taiwan-store-marketing/taiwan-store-marketing.php',
							'icon' => 'megaphone', 'tab' => 'marketing',
							'purchase_url' => '#',
							'desc' => [ '<strong>進階行銷規則：</strong>滿額折扣、贈品、買一送一（BOGO）、加價購、分類促銷。', '<strong>視覺行銷工具：</strong>全站活動橫幅、購物車進度條、限時倒數計時器。' ] ],
						[ 'name' => 'Mydybox 通知助手 Pro',
							'path' => 'taiwan-store-notifier/taiwan-store-notifier.php',
							'icon' => 'testimonial', 'tab' => 'notifier',
							'purchase_url' => '#',
							'desc' => [ '<strong>物流追蹤：</strong>支援 7-11 / 全家 / 黑貓狀態同步。', '<strong>全通路推播：</strong>自訂 LINE / SMS 訊息範本，內建測試發送中心。' ] ],
						[ 'name' => 'Mydybox 會員分級 Pro',
							'path' => 'taiwan-store-member/taiwan-store-member.php',
							'icon' => 'awards', 'tab' => 'member',
							'purchase_url' => '#',
							'desc' => [ '<strong>消費累積等級：</strong>依累積消費自動升等（一般 / 銀卡 / 金卡 / VIP 白金）。', '<strong>點數制度：</strong>每筆訂單完成後累積點數，My Account 顯示等級徽章。' ] ],
						[ 'name' => 'Mydybox 拼團購買 Pro',
							'path' => 'taiwan-store-group-buy/taiwan-store-group-buy.php',
							'icon' => 'groups', 'tab' => 'group_buy',
							'purchase_url' => '#',
							'desc' => [ '<strong>人數門檻優惠：</strong>設定達標人數後啟用優惠價，商品頁即時顯示拼團進度條。', '<strong>活動管理：</strong>後台統一管理拼團活動，支援截止時間與達標通知。' ] ],
					] );
					?>
					<div class="ts-help-grid">
						<?php foreach ( $ext_showcase as $ext ) :
							$is_active   = is_plugin_active( $ext['path'] );
							$card_class  = $is_active ? 'ts-help-card ts-help-card--active' : 'ts-help-card ts-help-card--inactive';
							$buy_url     = ! empty( $ext['purchase_url'] ) ? $ext['purchase_url'] : '#';
							$has_buy_url = $buy_url && '#' !== $buy_url;
							?>
							<div class="<?php echo esc_attr( $card_class ); ?>">
								<div class="ts-help-card-header">
									<div class="ts-help-icon"><span class="dashicons dashicons-<?php echo esc_attr( $ext['icon'] ); ?>"></span></div>
									<div>
										<h2><?php echo esc_html( $ext['name'] ); ?></h2>
										<?php if ( $is_active ) : ?>
											<span class="ts-badge ts-badge--active ts-badge--dot"><?php esc_html_e( '已啟用', 'mydybox-taiwan-for-woocommerce' ); ?></span>
										<?php else : ?>
											<span class="ts-badge ts-badge--premium"><?php esc_html_e( '付費購買', 'mydybox-taiwan-for-woocommerce' ); ?></span>
										<?php endif; ?>
									</div>
								</div>
								<div class="ts-help-content">
									<?php foreach ( $ext['desc'] as $p ) : ?>
										<p><?php echo wp_kses_post( $p ); ?></p>
									<?php endforeach; ?>
								</div>
								<?php if ( $is_active ) : ?>
									<a href="<?php echo esc_url( admin_url( 'admin.php?page=mydybox-taiwan-for-woocommerce&tab=' . $ext['tab'] ) ); ?>" class="button button-secondary button-small"><?php esc_html_e( '前往設定', 'mydybox-taiwan-for-woocommerce' ); ?></a>
								<?php elseif ( $has_buy_url ) : ?>
									<a href="<?php echo esc_url( $buy_url ); ?>" target="_blank" rel="noopener" class="button button-secondary button-small"><?php esc_html_e( '前往購買', 'mydybox-taiwan-for-woocommerce' ); ?> <span class="dashicons dashicons-external" style="font-size:14px;width:14px;height:14px;vertical-align:text-bottom;"></span></a>
								<?php else : ?>
									<span class="button button-secondary button-small" style="opacity:.7;cursor:default;" aria-disabled="true"><?php esc_html_e( '敬請期待', 'mydybox-taiwan-for-woocommerce' ); ?></span>
								<?php endif; ?>
							</div>
						<?php endforeach; ?>
					</div>

					<div class="ts-faq-section">
						<h3 class="ts-faq-section-title"><?php esc_html_e( '常見問題（FAQ）', 'mydybox-taiwan-for-woocommerce' ); ?></h3>
						<?php
						$faqs = [
							[ 'q' => '如何更新地址資料庫？',       'a' => '系統內建最新台灣縣市鄉鎮資料。未來行政區調整將透過外掛版本更新同步，不需手動操作。' ],
							[ 'q' => '社群登入需要 HTTPS 嗎？',    'a' => '是的，LINE、Google、Facebook 的 OAuth 回呼網址都必須使用 HTTPS，請確認網站已安裝 SSL 憑證。' ],
							[ 'q' => 'ECPay 測試模式怎麼使用？',    'a' => '金流與電子發票都內建測試模式，啟用後自動使用 ECPay Staging（商家 ID：3002607），無需申請帳號即可測試完整付款流程。' ],
							[ 'q' => '超商取貨需要申請什麼？',      'a' => '正式環境需向 ECPay 申請物流服務並取得商家 ID、HashKey、HashIV，填入台灣商店 → 結帳設定 → 超商取貨。測試時可開啟測試模式免申請。' ],
						];
						foreach ( $faqs as $faq ) : ?>
							<div class="ts-faq-item">
								<h4><span class="dashicons dashicons-info"></span><?php echo esc_html( $faq['q'] ); ?></h4>
								<p><?php echo esc_html( $faq['a'] ); ?></p>
							</div>
						<?php endforeach; ?>
					</div>

				</div>
				<?php endif; ?>

				<!-- ── Extension tabs ─────────────────────────────────────── -->
				<?php if ( $is_extension_tab ) : ?>
				<div class="taiwan-store-tab-content active">
				<?php endif; ?>

				<?php do_action( "mydybox_tab_content_{$active_tab}" ); ?>

				<?php if ( $is_extension_tab ) : ?></div><?php endif; ?>

				<!-- ── Manual ────────────────────────────────────────────── -->
				<?php if ( $active_tab === 'manual' ) : ?>
				<div class="taiwan-store-tab-content active">
					<?php
					require_once __DIR__ . '/class-manual.php';
					( new Manual() )->render();
					?>
				</div>
				<?php endif; ?>

				<?php if ( ! $is_extension_tab && $active_tab !== 'help' && $active_tab !== 'manual' ) : ?></form><?php endif; ?>
			</div><!-- .taiwan-store-tab-body -->
		</div><!-- .taiwan-store-admin-wrap -->
		<?php
	}
}
