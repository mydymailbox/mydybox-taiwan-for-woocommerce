<?php
namespace Mydybox\Modules\Abandoned_Cart;

defined( 'ABSPATH' ) || exit;

/**
 * Sends Email and LINE reminders for abandoned carts.
 */
class Notifier {

	public function boot(): void {
		add_action( 'mydybox_abandoned_cart_reminder', [ $this, 'send' ] );
	}

	public function send( object $row ): void {
		$this->send_email( $row );
		$this->send_line( $row );
	}

	private function send_email( object $row ): void {
		if ( 'yes' !== get_option( 'mydybox_abandoned_cart_email', 'yes' ) ) return;

		$subject  = get_option( 'mydybox_abandoned_cart_email_subject', __( '您的購物車還在等您 🛒', 'mydybox-taiwan-for-woocommerce' ) );
		$body_tpl = get_option( 'mydybox_abandoned_cart_email_body', '' );

		$recover_url = add_query_arg( [
			'mydybox_recover_cart' => base64_encode( $row->email ),
			'mydybox_rc_nonce'     => wp_create_nonce( 'mydybox_recover_' . $row->email ),
		], wc_get_cart_url() );

		if ( ! $body_tpl ) {
			$body_tpl = sprintf(
				/* translators: %1$s is the recover URL, %2$s is the site name */
				__( "您好，\n\n您有一個尚未完成的訂單，購物車內的商品正在等您！\n\n點擊以下連結回到購物車完成結帳：\n%1\$s\n\n如有任何問題，歡迎聯絡我們。\n\n%2\$s 敬上", 'mydybox-taiwan-for-woocommerce' ),
				'{{recover_url}}',
				get_bloginfo( 'name' )
			);
		}

		$body = str_replace(
			[ '{{recover_url}}', '{{email}}', '{{site_name}}' ],
			[ $recover_url, $row->email, get_bloginfo( 'name' ) ],
			$body_tpl
		);

		$headers = [ 'Content-Type: text/plain; charset=UTF-8' ];
		wp_mail( $row->email, $subject, $body, $headers );
	}

	private function send_line( object $row ): void {
		if ( 'yes' !== get_option( 'mydybox_abandoned_cart_line', 'no' ) ) return;
		if ( ! $row->user_id ) return;

		$line_token = get_option( 'mydybox_social_line_token' );
		if ( ! $line_token ) return;

		// Get LINE user ID stored by social login module
		$line_user_id = get_user_meta( $row->user_id, '_mydybox_line_user_id', true );
		if ( ! $line_user_id ) return;

		$recover_url = add_query_arg( [
			'mydybox_recover_cart' => base64_encode( $row->email ),
			'mydybox_rc_nonce'     => wp_create_nonce( 'mydybox_recover_' . $row->email ),
		], wc_get_cart_url() );

		$msg_tpl = get_option( 'mydybox_abandoned_cart_line_message', __( "您的購物車還有商品尚未結帳！\n點此回到購物車：{{recover_url}}", 'mydybox-taiwan-for-woocommerce' ) );
		$message = str_replace( '{{recover_url}}', $recover_url, $msg_tpl );

		wp_remote_post( 'https://api.line.me/v2/bot/message/push', [
			'headers' => [
				'Content-Type'  => 'application/json',
				'Authorization' => 'Bearer ' . $line_token,
			],
			'body' => wp_json_encode( [
				'to'       => $line_user_id,
				'messages' => [ [ 'type' => 'text', 'text' => $message ] ],
			] ),
		] );
	}
}
