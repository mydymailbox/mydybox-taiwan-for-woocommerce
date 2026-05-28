<?php
namespace Taiwan_Store_Core\Modules\Checkout_Tw;

defined( 'ABSPATH' ) || exit;

/**
 * Locale Module.
 * Overrides WooCommerce default checkout fields for Taiwan specific labels and ordering.
 */
class Locale {

	public function boot(): void {
		add_filter( 'woocommerce_default_address_fields', [ $this, 'override_default_fields' ] );
		add_filter( 'woocommerce_billing_fields', [ $this, 'override_billing_fields' ], 999 );
		add_filter( 'woocommerce_shipping_fields', [ $this, 'override_shipping_fields' ], 999 );
		add_filter( 'woocommerce_checkout_fields', [ $this, 'global_reorder_fields' ], 999 );
		add_filter( 'woocommerce_states', [ $this, 'register_tw_states' ] );
		
		// Additional Checkout Translations
		add_filter( 'woocommerce_checkout_fields', [ $this, 'override_order_notes' ], 20 );
		add_filter( 'woocommerce_order_button_text', [ $this, 'override_order_button_text' ] );
		add_filter( 'gettext', [ $this, 'translate_checkout_strings' ], 20, 3 );
		
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_cascading_scripts' ] );
	}

	public function enqueue_cascading_scripts(): void {
		if ( ! is_checkout() ) return;

		$districts = include __DIR__ . '/data/tw-districts.php';
		$postcodes = include __DIR__ . '/data/tw-postcodes.php';

		wp_add_inline_script( 'jquery', "
			jQuery(document).ready(function($) {
				var twDistricts = " . json_encode( $districts ) . ";
				var twPostcodes = " . json_encode( $postcodes ) . ";

				function updateDistricts(type) {
					var state = $('#' + type + '_state').val();
					var \$citySelect = $('#' + type + '_city');
					var currentCity = \$citySelect.val();

					if (!twDistricts[state]) {
						if (\$citySelect.is('select')) {
							\$citySelect.replaceWith('<input type=\"text\" class=\"input-text\" name=\"' + type + '_city\" id=\"' + type + '_city\" value=\"' + currentCity + '\">');
						}
						return;
					}

					var options = '<option value=\"\">" . esc_js( __( '─ 請選擇 ─', 'taiwan-store-core' ) ) . "</option>';
					$.each(twDistricts[state], function(k, v) {
						options += '<option value=\"' + k + '\"' + (k === currentCity ? ' selected' : '') + '>' + v + '</option>';
					});

					if (\$citySelect.is('input')) {
						\$citySelect.replaceWith('<select name=\"' + type + '_city\" id=\"' + type + '_city\" class=\"select\">' + options + '</select>');
					} else {
						\$citySelect.html(options);
					}
				}

				$('body').on('change', 'select.state_select', function() {
					var type = $(this).attr('id').replace('_state', '');
					updateDistricts(type);
				});

				$('body').on('change', 'select[id$=\"_city\"]', function() {
					var type = $(this).attr('id').replace('_city', '');
					var state = $('#' + type + '_state').val();
					var city = $(this).val();
					if (twPostcodes[state] && twPostcodes[state][city]) {
						$('#' + type + '_postcode').val(twPostcodes[state][city]).trigger('change');
					}
				});

				// Init on load — use both ready and updated_checkout to cover classic + AJAX refresh
				updateDistricts('billing');
				updateDistricts('shipping');
				$(document.body).on('updated_checkout', function() {
					updateDistricts('billing');
					updateDistricts('shipping');
				});
			});
		" );
	}

	public function override_default_fields( $fields ): array {
		$fields['state']['label']        = __( '縣市', 'taiwan-store-core' );
		$fields['state']['priority']     = 50;
		
		$fields['city']['label']         = __( '鄉鎮市區', 'taiwan-store-core' );
		$fields['city']['priority']      = 60;
		
		$fields['postcode']['label']     = __( '郵遞區號', 'taiwan-store-core' );
		$fields['postcode']['priority']  = 45;
		
		$fields['address_1']['label']    = __( '地址', 'taiwan-store-core' );
		$fields['address_1']['priority'] = 70;
		
		$fields['address_2']['priority'] = 80;
		
		return $fields;
	}

	public function override_billing_fields( $fields ): array {
		// Respect Name Consolidation setting (if added later, default to yes)
		$consolidate = 'yes' === get_option( 'ts_checkout_name_consolidate', 'yes' );
		$is_autofill = 'yes' === get_option( 'ts_checkout_postcode_autofill', 'yes' );

		$tw = [
			'country' => [
				'priority' => 5,
				'class'    => [ 'form-row-wide' ],
			],
			'last_name' => [
				'label'    => $consolidate ? __( '姓名', 'taiwan-store-core' ) : __( '姓氏', 'taiwan-store-core' ),
				'priority' => 10,
				'class'    => $consolidate ? [ 'form-row-wide' ] : [ 'form-row-first' ],
			],
			'first_name' => $consolidate ? [ 'type' => 'hidden', 'default' => '-' ] : [
				'label'    => __( '名字', 'taiwan-store-core' ),
				'priority' => 15,
				'class'    => [ 'form-row-last' ],
			],
			'phone' => [
				'label'             => __( '行動電話', 'taiwan-store-core' ),
				'required'          => true,
				'priority'          => 20,
				'class'             => [ 'form-row-first' ],
				'custom_attributes' => [
					'inputmode' => 'numeric',
					'pattern'   => '[0-9\-]*',
				],
			],
			'email' => [
				'label'    => __( '電子郵件', 'taiwan-store-core' ),
				'priority' => 30,
				'class'    => [ 'form-row-last' ],
			],
			'postcode' => [
				'label'       => __( '郵遞區號', 'taiwan-store-core' ),
				'placeholder' => $is_autofill ? __( '自動帶入', 'taiwan-store-core' ) : '',
				'required'    => false,
				'priority'    => 45,
				'class'       => [ 'form-row-wide' ],
				'custom_attributes' => $is_autofill ? [ 'readonly' => 'readonly' ] : [],
			],
			'state' => [
				'label'    => __( '縣市', 'taiwan-store-core' ),
				'required' => true,
				'priority' => 50,
				'class'    => [ 'form-row-first' ],
			],
			'city' => [
				'type'     => 'select',
				'label'    => __( '鄉鎮市區', 'taiwan-store-core' ),
				'required' => true,
				'priority' => 60,
				'class'    => [ 'form-row-last' ],
				'options'  => [ '' => __( '─ 請選擇 ─', 'taiwan-store-core' ) ],
			],
			'address_1' => [
				'label'       => __( '路名／街道', 'taiwan-store-core' ),
				'placeholder' => __( '例如：中山北路二段', 'taiwan-store-core' ),
				'priority'    => 70,
				'class'       => [ 'form-row-first' ],
			],
			'address_2' => [
				'label'       => __( '巷弄號碼／樓層', 'taiwan-store-core' ),
				'placeholder' => __( '例如：12巷3弄5號4樓', 'taiwan-store-core' ),
				'required'    => false,
				'priority'    => 80,
				'class'       => [ 'form-row-last' ],
			],
		];

		foreach ( $tw as $key => $val ) {
			if ( isset( $fields[ 'billing_' . $key ] ) ) {
				$fields[ 'billing_' . $key ] = array_merge( $fields[ 'billing_' . $key ], $val );
			}
		}

		// Extra safety: If consolidated, remove first_name entirely to prevent labels from showing
		if ( $consolidate ) {
			unset( $fields['billing_first_name'] );
		}

		return $fields;
	}

	public function override_shipping_fields( $fields ): array {
		$consolidate = 'yes' === get_option( 'ts_checkout_name_consolidate', 'yes' );

		$tw = [
			'last_name' => [
				'label'    => $consolidate ? __( '姓名', 'taiwan-store-core' ) : __( '姓氏', 'taiwan-store-core' ),
				'priority' => 10,
				'class'    => $consolidate ? [ 'form-row-wide' ] : [ 'form-row-first' ],
			],
			'first_name' => $consolidate ? [ 'type' => 'hidden', 'default' => '-' ] : [
				'label'    => __( '名字', 'taiwan-store-core' ),
				'priority' => 15,
				'class'    => [ 'form-row-last' ],
			],
			'postcode' => [
				'label'    => __( '郵遞區號', 'taiwan-store-core' ),
				'required' => false,
				'priority' => 35,
				'class'    => [ 'form-row-wide' ],
			],
			'state' => [
				'label'    => __( '縣市', 'taiwan-store-core' ),
				'required' => true,
				'priority' => 40,
				'class'    => [ 'form-row-first' ],
			],
			'city' => [
				'type'     => 'select',
				'label'    => __( '鄉鎮市區', 'taiwan-store-core' ),
				'required' => true,
				'priority' => 50,
				'class'    => [ 'form-row-last' ],
				'options'  => [ '' => __( '─ 請選擇 ─', 'taiwan-store-core' ) ],
			],
			'address_1' => [
				'label'       => __( '路名／街道', 'taiwan-store-core' ),
				'placeholder' => __( '例如：中山北路二段', 'taiwan-store-core' ),
				'priority'    => 60,
				'class'       => [ 'form-row-first' ],
			],
			'address_2' => [
				'label'       => __( '巷弄號碼／樓層', 'taiwan-store-core' ),
				'placeholder' => __( '例如：12巷3弄5號4樓', 'taiwan-store-core' ),
				'required'    => false,
				'priority'    => 70,
				'class'       => [ 'form-row-last' ],
			],
		];

		foreach ( $tw as $key => $val ) {
			if ( isset( $fields[ 'shipping_' . $key ] ) ) {
				$fields[ 'shipping_' . $key ] = array_merge( $fields[ 'shipping_' . $key ], $val );
			}
		}

		if ( $consolidate ) {
			unset( $fields['shipping_first_name'] );
		}

		// Sort fields by priority to ensure correct order
		uasort( $fields, function( $a, $b ) {
			$a_prio = isset( $a['priority'] ) ? (int) $a['priority'] : 0;
			$b_prio = isset( $b['priority'] ) ? (int) $b['priority'] : 0;
			return $a_prio <=> $b_prio;
		});

		return $fields;
	}

	public function override_order_notes( $fields ): array {
		if ( isset( $fields['order']['order_comments'] ) ) {
			$fields['order']['order_comments']['label']       = __( '訂單備註', 'taiwan-store-core' );
			$fields['order']['order_comments']['placeholder'] = __( '關於您的訂單的備註，例如：送貨時的特殊注意事項。', 'taiwan-store-core' );
		}
		return $fields;
	}

	public function override_order_button_text(): string {
		return __( '立即結帳', 'taiwan-store-core' );
	}

	/**
	 * Translate standard WooCommerce checkout strings that are often missing in Taiwan.
	 */
	public function translate_checkout_strings( $translated_text, $text, $domain ) {
		if ( 'woocommerce' !== $domain && 'taiwan-store-core' !== $domain ) return $translated_text;

		$map = [
			'Billing details'               => '帳單資訊',
			'Ship to a different address?'  => '運送到不同的地址？',
			'Your order'                    => '您的訂單',
			'Apply coupon'                  => '使用優惠券',
			'I have read and agree to the website terms and conditions' => '我已閱讀並同意網站的條款與細節',
		];

		return $map[$text] ?? $translated_text;
	}

	public function global_reorder_fields( $fields ): array {
		foreach ( [ 'billing', 'shipping' ] as $group ) {
			if ( ! isset( $fields[$group] ) ) continue;
			
			$prefix = $group . '_';
			$order = [
				$prefix . 'country',
				$prefix . 'last_name',
				$prefix . 'first_name',
				$prefix . 'phone',
				$prefix . 'email',
				$prefix . 'postcode',
				$prefix . 'state',
				$prefix . 'city',
				$prefix . 'address_1',
				$prefix . 'address_2',
			];
			// address_1 and address_2 are side-by-side, no reordering needed beyond priority

			$ordered_fields = [];
			foreach ( $order as $key ) {
				if ( isset( $fields[$group][$key] ) ) {
					$ordered_fields[$key] = $fields[$group][$key];
					unset( $fields[$group][$key] );
				}
			}

			// Merge back any remaining fields (like custom fields)
			$fields[$group] = array_merge( $ordered_fields, $fields[$group] );

			// Still apply uasort as a backup
			uasort( $fields[$group], function( $a, $b ) {
				$a_p = isset( $a['priority'] ) ? (int) $a['priority'] : 100;
				$b_p = isset( $b['priority'] ) ? (int) $b['priority'] : 100;
				if ( $a_p === $b_p ) return 0;
				return ( $a_p < $b_p ) ? -1 : 1;
			});
		}
		return $fields;
	}

	public function register_tw_states( $states ): array {
		$states['TW'] = include __DIR__ . '/data/tw-states.php';
		return $states;
	}
}