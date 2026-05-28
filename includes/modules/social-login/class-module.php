<?php
namespace Taiwan_Store_Core\Modules\Social_Login;

defined( 'ABSPATH' ) || exit;

/**
 * Social Login Module.
 * Integrates LINE, Google, and Facebook for quick registration and login.
 */
class Module {

	public function boot(): void {
		add_filter( 'woocommerce_get_settings_tw_core', [ $this, 'add_settings_fields' ], 10, 2 );
		add_action( 'taiwan_store_core_settings_before_output_social_login', [ $this, 'output_guide' ] );
		
		// Display hooks for My Account / Login
		// Removed woocommerce_before_customer_login_form to prevent duplication
		add_action( 'woocommerce_login_form_start', [ $this, 'display_login_buttons' ] );
		add_action( 'woocommerce_register_form_start', [ $this, 'display_login_buttons' ] );
		
		// Display hook for Checkout
		add_action( 'woocommerce_before_checkout_form', [ $this, 'display_login_buttons' ], 5 );
		
		add_action( 'init', [ $this, 'handle_oauth_callback' ] );
	}

	/**
	 * Output callback URL guide in settings page.
	 */
	public function output_guide(): void {
		$home_url = home_url( '/' );
		$callbacks = [
			'line'     => add_query_arg( 'taiwan_store_social', 'line', $home_url ),
			'google'   => add_query_arg( 'taiwan_store_social', 'google', $home_url ),
			'facebook' => add_query_arg( 'taiwan_store_social', 'facebook', $home_url ),
		];
		?>
		<div class="taiwan-store-core-social-guide" style="background:#fff; padding:20px; border:1px solid #e2e8f0; border-radius:12px; margin-top:20px; box-shadow:0 4px 6px -1px rgba(0,0,0,0.05);">
			<h3 style="margin-top:0; color:#1e293b;"><?php esc_html_e( 'Social Login Setup Guide', 'taiwan-store-core' ); ?></h3>
			<p style="color:#64748b; font-size:14px;"><?php esc_html_e( 'Please use the following URLs as the Callback URL or Redirect URI in your developer consoles:', 'taiwan-store-core' ); ?></p>
			<div class="taiwan-store-core-callback-box" style="background:#f8fafc; padding:15px; border-left:4px solid #3b82f6; border-radius:4px; font-family:monospace; font-size:13px; line-height:1.6;">
				<strong>LINE Callback:</strong> <code><?php echo esc_url( $callbacks['line'] ); ?></code><br>
				<strong>Google Redirect:</strong> <code><?php echo esc_url( $callbacks['google'] ); ?></code><br>
				<strong>Facebook Redirect:</strong> <code><?php echo esc_url( $callbacks['facebook'] ); ?></code>
			</div>
		</div>
		<?php
	}

	public function add_settings_fields( array $settings, string $current_section ): array {
		if ( 'social_login' !== $current_section ) return $settings;
		return [
			[ 'title' => __( 'LINE Login Settings', 'taiwan-store-core' ), 'type' => 'title', 'id' => 'taiwan_store_core_social_line_options' ],
			[ 'title' => __( 'Enable LINE', 'taiwan-store-core' ), 'id' => 'taiwan_store_core_social_line_enabled', 'type' => 'checkbox' ],
			[ 'title' => __( 'Channel ID', 'taiwan-store-core' ), 'id' => 'taiwan_store_core_social_line_client_id', 'type' => 'text' ],
			[ 'title' => __( 'Channel Secret', 'taiwan-store-core' ), 'id' => 'taiwan_store_core_social_line_client_secret', 'type' => 'password' ],
			[ 'type' => 'sectionend', 'id' => 'taiwan_store_core_social_line_options' ],
		];
	}

	public function display_login_buttons(): void {
		if ( is_user_logged_in() ) return;
		
		$line_enabled   = get_option( 'ts_social_line_enabled' ) === 'yes';
		$google_enabled = get_option( 'ts_social_google_enabled' ) === 'yes';
		$fb_enabled     = get_option( 'ts_social_fb_enabled' ) === 'yes';

		if ( ! $line_enabled && ! $google_enabled && ! $fb_enabled ) return;
		
		echo '<div class="taiwan-store-core-social-login-wrap" style="margin: 20px 0; text-align:center; display:flex; flex-direction:column; gap:12px; align-items:center;">';
		echo '<div style="width: 100%; max-width: 300px; border-bottom: 1px solid #eee; margin-bottom: 5px; position: relative;"><span style="position: absolute; top: -10px; left: 50%; transform: translateX(-50%); background: #fff; padding: 0 10px; color: #999; font-size: 12px;">' . esc_html__( '使用社群帳號快速登入', 'taiwan-store-core' ) . '</span></div>';
		
		if ( $line_enabled ) {
			$line_url = add_query_arg( 'taiwan_store_social', 'line', home_url( '/' ) );
			$line_svg = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="currentColor" style="flex-shrink:0;"><path d="M24 10.304c0-5.232-5.383-9.5-12-9.5-6.617 0-12 4.268-12 9.5 0 4.69 4.263 8.605 10.008 9.341l-.708 2.626c-.114.423.51.701.815.399l3.812-3.774c4.686-.343 8.073-3.665 8.073-8.592zm-14.195 4.193h-2.196c-.302 0-.549-.248-.549-.551V9.324c0-.303.247-.551.549-.551.302 0 .549.248.549.551v3.978h1.647c.303 0 .549.247.549.551 0 .303-.246.551-.549.551zm3.627 0h-2.195c-.303 0-.551-.248-.551-.551V9.324c0-.303.248-.551.551-.551s.551.248.551.551v4.193c0 .303-.248.551-.551.551zm2.148 0c-.303 0-.551-.248-.551-.551V9.324c0-.303.248-.551.551-.551s.551.248.551.551v4.193c0 .303-.248.551-.551.551zm5.155-4.193h-2.195c-.303 0-.551-.248-.551-.551V9.324c0-.303.248-.551.551-.551s.551.248.551.551v3.978h1.647c.303 0 .549.247.549.551s-.246.551-.549.551zm-1.647-1.647h1.647c.303 0 .549.248.549.551 0 .303-.246.551-.549.551h-1.647c-.303 0-.551-.248-.551-.551s.248-.551.551-.551z"/></svg>';
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $line_svg is a hardcoded SVG string, not user input
			echo '<a href="' . esc_url( $line_url ) . '" class="taiwan-store-core-social-btn line" style="background:#06C755; color:#fff; width:280px; padding:12px; border-radius:8px; text-decoration:none; font-weight:600; font-size:15px; box-shadow:0 2px 5px rgba(0,0,0,0.1); text-align:center; display:flex; align-items:center; justify-content:center; gap:10px;">' . $line_svg . ' LINE ' . esc_html__( '快速登入', 'taiwan-store-core' ) . '</a>';
		}

		if ( $google_enabled ) {
			$google_url = add_query_arg( 'taiwan_store_social', 'google', home_url( '/' ) );
			$google_svg = '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 18 18" style="flex-shrink:0;"><path d="M17.64 9.2c0-.637-.057-1.251-.164-1.84H9v3.481h4.844c-.209 1.125-.843 2.078-1.796 2.717v2.258h2.908c1.702-1.567 2.684-3.874 2.684-6.615z" fill="#4285f4"/><path d="M9 18c2.43 0 4.467-.806 5.956-2.184l-2.908-2.259c-.806.54-1.837.86-3.048.86-2.344 0-4.328-1.584-5.036-3.711H.957v2.332A8.997 8.997 0 0 0 9 18z" fill="#34a853"/><path d="M3.964 10.706A5.41 5.41 0 0 1 3.682 9c0-.593.102-1.17.282-1.706V4.962H.957A8.996 8.996 0 0 0 0 9c0 1.452.348 2.827.957 4.038l3.007-2.332z" fill="#fbbc05"/><path d="M9 3.58c1.321 0 2.508.454 3.44 1.345l2.582-2.58C13.463.891 11.426 0 9 0A8.997 8.997 0 0 0 .957 4.962L3.964 7.294C4.672 5.167 6.656 3.58 9 3.58z" fill="#ea4335"/></svg>';
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $google_svg is a hardcoded SVG string, not user input
			echo '<a href="' . esc_url( $google_url ) . '" class="taiwan-store-core-social-btn google" style="background:#fff; color:#444; width:280px; padding:12px; border-radius:8px; text-decoration:none; font-weight:600; font-size:15px; box-shadow:0 2px 5px rgba(0,0,0,0.1); border:1px solid #ddd; text-align:center; display:flex; align-items:center; justify-content:center; gap:10px;">' . $google_svg . ' Google ' . esc_html__( '快速登入', 'taiwan-store-core' ) . '</a>';
		}

		if ( $fb_enabled ) {
			$fb_url = add_query_arg( 'taiwan_store_social', 'facebook', home_url( '/' ) );
			$fb_svg = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="currentColor" style="flex-shrink:0;"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.469h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>';
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $fb_svg is a hardcoded SVG string, not user input
			echo '<a href="' . esc_url( $fb_url ) . '" class="taiwan-store-core-social-btn facebook" style="background:#1877F2; color:#fff; width:280px; padding:12px; border-radius:8px; text-decoration:none; font-weight:600; font-size:15px; box-shadow:0 2px 5px rgba(0,0,0,0.1); text-align:center; display:flex; align-items:center; justify-content:center; gap:10px;">' . $fb_svg . ' Facebook ' . esc_html__( '快速登入', 'taiwan-store-core' ) . '</a>';
		}

		echo '</div>';
	}

	public function handle_oauth_callback(): void {
		if ( ! isset( $_GET['taiwan_store_social'] ) ) return;
		$provider = sanitize_key( $_GET['taiwan_store_social'] );

		if ( ! isset( $_GET['code'] ) ) {
			if ( 'line' === $provider ) $this->redirect_to_line_authorize();
			if ( 'google' === $provider ) $this->redirect_to_google_authorize();
			if ( 'facebook' === $provider ) $this->redirect_to_fb_authorize();
			return;
		}

		// Verify state for all providers
		if ( ! isset( $_GET['state'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['state'] ) ), 'social_login_state' ) ) {
			wp_die( 'Security check failed.' );
		}

		if ( 'line' === $provider ) $this->process_line_login( sanitize_text_field( wp_unslash( $_GET['code'] ) ) );
		if ( 'google' === $provider ) $this->process_google_login( sanitize_text_field( wp_unslash( $_GET['code'] ) ) );
		if ( 'facebook' === $provider ) $this->process_fb_login( sanitize_text_field( wp_unslash( $_GET['code'] ) ) );
	}

	private function redirect_to_line_authorize(): void {
		$client_id = get_option( 'ts_social_line_client_id' );
		if ( ! $client_id ) wp_die( 'LINE Channel ID not set.' );
		$redirect_uri = add_query_arg( 'taiwan_store_social', 'line', home_url( '/' ) );
		$state = wp_create_nonce( 'social_login_state' );
		$url = 'https://access.line.me/oauth2/v2.1/authorize?' . http_build_query( [ 'response_type' => 'code', 'client_id' => $client_id, 'redirect_uri' => $redirect_uri, 'state' => $state, 'scope' => 'profile openid email' ] );
		wp_safe_redirect( $url );
		exit;
	}

	private function redirect_to_google_authorize(): void {
		$client_id = get_option( 'ts_social_google_client_id' );
		if ( ! $client_id ) wp_die( 'Google Client ID not set.' );
		$redirect_uri = add_query_arg( 'taiwan_store_social', 'google', home_url( '/' ) );
		$state = wp_create_nonce( 'social_login_state' );
		$url = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query( [ 'response_type' => 'code', 'client_id' => $client_id, 'redirect_uri' => $redirect_uri, 'state' => $state, 'scope' => 'openid profile email' ] );
		wp_safe_redirect( $url );
		exit;
	}

	private function redirect_to_fb_authorize(): void {
		$client_id = get_option( 'ts_social_fb_client_id' );
		if ( ! $client_id ) wp_die( 'Facebook App ID not set.' );
		$redirect_uri = add_query_arg( 'taiwan_store_social', 'facebook', home_url( '/' ) );
		$state = wp_create_nonce( 'social_login_state' );
		$url = 'https://www.facebook.com/v12.0/dialog/oauth?' . http_build_query( [ 'client_id' => $client_id, 'redirect_uri' => $redirect_uri, 'state' => $state, 'scope' => 'email' ] );
		wp_safe_redirect( $url );
		exit;
	}

	private function process_line_login( string $code ): void {
		$client_id     = get_option( 'ts_social_line_client_id' );
		$client_secret = get_option( 'ts_social_line_client_secret' );
		$redirect_uri  = add_query_arg( 'taiwan_store_social', 'line', home_url( '/' ) );

		$response = wp_remote_post( 'https://api.line.me/oauth2/v2.1/token', [ 'body' => [ 'grant_type' => 'authorization_code', 'code' => $code, 'redirect_uri' => $redirect_uri, 'client_id' => $client_id, 'client_secret' => $client_secret ] ] );
		if ( is_wp_error( $response ) ) wp_die( 'Token request failed.' );
		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( empty( $data['id_token'] ) ) wp_die( 'Authorization failed.' );

		$parts   = explode( '.', $data['id_token'] );
		$payload = json_decode( base64_decode( str_pad( strtr( $parts[1], '-_', '+/' ), strlen( $parts[1] ) % 4, '=', STR_PAD_RIGHT ) ), true );

		// Fetch profile for display name and avatar
		$profile_resp = wp_remote_get( 'https://api.line.me/v2/profile', [
			'headers' => [ 'Authorization' => 'Bearer ' . $data['access_token'] ],
		] );
		$profile = ! is_wp_error( $profile_resp ) ? json_decode( wp_remote_retrieve_body( $profile_resp ), true ) : [];

		$this->finish_login( 'line', $payload['sub'], $payload['email'] ?? '', $profile['displayName'] ?? '', $profile['pictureUrl'] ?? '' );
	}

	private function process_google_login( string $code ): void {
		$client_id     = get_option( 'ts_social_google_client_id' );
		$client_secret = get_option( 'ts_social_google_client_secret' );
		$redirect_uri  = add_query_arg( 'taiwan_store_social', 'google', home_url( '/' ) );

		$response = wp_remote_post( 'https://oauth2.googleapis.com/token', [ 'body' => [ 'grant_type' => 'authorization_code', 'code' => $code, 'redirect_uri' => $redirect_uri, 'client_id' => $client_id, 'client_secret' => $client_secret ] ] );
		if ( is_wp_error( $response ) ) wp_die( 'Token request failed.' );
		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( empty( $data['id_token'] ) ) wp_die( 'Authorization failed.' );

		$parts   = explode( '.', $data['id_token'] );
		$payload = json_decode( base64_decode( str_pad( strtr( $parts[1], '-_', '+/' ), strlen( $parts[1] ) % 4, '=', STR_PAD_RIGHT ) ), true );

		$this->finish_login( 'google', $payload['sub'], $payload['email'] ?? '', $payload['name'] ?? '', $payload['picture'] ?? '' );
	}

	private function process_fb_login( string $code ): void {
		$client_id     = get_option( 'ts_social_fb_client_id' );
		$client_secret = get_option( 'ts_social_fb_client_secret' );
		$redirect_uri  = add_query_arg( 'taiwan_store_social', 'facebook', home_url( '/' ) );

		$response = wp_remote_get( 'https://graph.facebook.com/v12.0/oauth/access_token?' . http_build_query( [ 'client_id' => $client_id, 'client_secret' => $client_secret, 'redirect_uri' => $redirect_uri, 'code' => $code ] ) );
		if ( is_wp_error( $response ) ) wp_die( 'Token request failed.' );
		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( empty( $data['access_token'] ) ) wp_die( 'Authorization failed.' );

		$user_resp = wp_remote_get( 'https://graph.facebook.com/me?fields=id,name,email,picture.width(200)&access_token=' . $data['access_token'] );
		$user_data = json_decode( wp_remote_retrieve_body( $user_resp ), true );
		$this->finish_login( 'facebook', $user_data['id'], $user_data['email'] ?? '', $user_data['name'] ?? '', $user_data['picture']['data']['url'] ?? '' );
	}

	private function finish_login( string $provider, string $social_id, string $email, string $display_name = '', string $avatar_url = '' ): void {
		$meta_key = "_taiwan_store_core_{$provider}_user_id";
		$users    = get_users( [ 'meta_key' => $meta_key, 'meta_value' => $social_id, 'number' => 1 ] );

		if ( ! empty( $users ) ) {
			$wp_user = $users[0];
		} else {
			// Try to match by email first
			$wp_user = $email ? get_user_by( 'email', $email ) : null;

			if ( ! $wp_user ) {
				// Generate a unique username; fall back to random if no email
				$base_username = $email ? strstr( $email, '@', true ) : "{$provider}_{$social_id}";
				$username      = $base_username;
				$suffix        = 1;
				while ( username_exists( $username ) ) {
					$username = $base_username . $suffix++;
				}

				$user_email  = $email ?: "{$provider}_{$social_id}@noreply.invalid";
				$user_id_new = wp_create_user( $username, wp_generate_password(), $user_email );
				if ( is_wp_error( $user_id_new ) ) wp_die( 'User creation failed: ' . esc_html( $user_id_new->get_error_message() ) );
				$wp_user = get_user_by( 'id', $user_id_new );
			}

			update_user_meta( $wp_user->ID, $meta_key, $social_id );
		}

		// Always keep display name and avatar fresh
		if ( $display_name && $display_name !== $wp_user->display_name ) {
			wp_update_user( [ 'ID' => $wp_user->ID, 'display_name' => $display_name ] );
		}
		if ( $avatar_url ) {
			update_user_meta( $wp_user->ID, "_taiwan_store_core_{$provider}_avatar", $avatar_url );
		}

		wp_set_current_user( $wp_user->ID );
		wp_set_auth_cookie( $wp_user->ID );
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- wp_login is a core WP hook, not a custom one
		do_action( 'wp_login', $wp_user->user_login, $wp_user );
		wp_safe_redirect( wc_get_page_permalink( 'myaccount' ) );
		exit;
	}
}