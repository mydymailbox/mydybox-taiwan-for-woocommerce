<?php
namespace Mydybox\Modules\Checkout_Tw;

defined( 'ABSPATH' ) || exit;

/**
 * Validation Module.
 * Validates Taiwan-specific checkout fields.
 */
class Validation {

	public function boot(): void {
		add_action( 'woocommerce_after_checkout_validation', [ $this, 'validate_fields' ], 10, 2 );
	}

	public function validate_fields( array $data, \WP_Error $errors ): void {
		$type    = $data['billing_mydybox_invoice_type'] ?? '';
		$tax_id  = $data['billing_mydybox_company_tax_id'] ?? '';
		$title   = $data['billing_mydybox_company_title'] ?? '';
		$phone   = $data['billing_phone'] ?? '';

		// 1. Tax ID Validation (Only if enabled in settings)
		if ( 'company' === $type && 'yes' === get_option( 'mydybox_checkout_show_tax_id', 'yes' ) ) {
			if ( empty( $tax_id ) ) {
				$errors->add( 'billing_mydybox_company_tax_id', __( 'Please enter your Company Tax ID.', 'mydybox-taiwan-for-woocommerce' ) );
			} elseif ( 'yes' === get_option( 'mydybox_checkout_validate_tax_id', 'yes' ) ) {
				if ( ! $this->is_valid_taiwan_tax_id( $tax_id ) ) {
					$errors->add( 'billing_mydybox_company_tax_id', __( '統一編號格式或檢查碼錯誤，請輸入有效的 8 碼統一編號。', 'mydybox-taiwan-for-woocommerce' ) );
				}
			}

			if ( empty( $title ) ) {
				$errors->add( 'billing_mydybox_company_title', __( 'Please enter your Company Title.', 'mydybox-taiwan-for-woocommerce' ) );
			}
		}

		// 2. Phone Validation (Only if enabled)
		if ( 'yes' === get_option( 'mydybox_checkout_phone_validate', 'yes' ) && ! empty( $phone ) ) {
			if ( ! preg_match( '/^09[0-9]{8}$/', $phone ) ) {
				$errors->add( 'billing_phone', __( 'Invalid phone format. Should be 09xxxxxxxx.', 'mydybox-taiwan-for-woocommerce' ) );
			}
		}
	}

	/**
	 * Taiwan Tax ID (GUI) MOD11 Checksum Validation.
	 */
	private function is_valid_taiwan_tax_id( string $tax_id ): bool {
		if ( ! preg_match( '/^\d{8}$/', $tax_id ) ) return false;

		$weights = [ 1, 2, 1, 2, 1, 2, 4, 1 ];
		$sum     = 0;

		for ( $i = 0; $i < 8; $i++ ) {
			$prod = (int) $tax_id[$i] * $weights[$i];
			$sum += floor( $prod / 10 ) + ( $prod % 10 );
		}

		if ( $sum % 10 === 0 ) return true;

		// Special case: if 7th digit is 7
		if ( $tax_id[6] === '7' && ( $sum + 1 ) % 10 === 0 ) return true;

		return false;
	}
}