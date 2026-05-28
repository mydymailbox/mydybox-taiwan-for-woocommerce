<?php
namespace Mydyma_TCS\Admin;

defined( 'ABSPATH' ) || exit;

class Rules_Ajax {

	public function boot(): void {
		add_action( 'wp_ajax_mydyma_tcs_get_rules', [ $this, 'get_rules' ] );
		add_action( 'wp_ajax_mydyma_tcs_save_rule', [ $this, 'save_rules' ] );
		add_action( 'wp_ajax_mydyma_tcs_get_components', [ $this, 'get_components' ] );
		add_action( 'wp_ajax_mydyma_tcs_import_samples', [ $this, 'import_samples' ] );
		add_action( 'wp_ajax_mydyma_tcs_delete_rule', [ $this, 'delete_rule' ] );
	}

	public function import_samples(): void {
		check_ajax_referer( 'mydyma_tcs_rules', 'nonce' );
		if ( ! current_user_can( 'manage_woocommerce' ) ) wp_send_json_error( 'Forbidden' );

		$current_hook = sanitize_key( $_POST['hook'] ?? '' );
		$keys_json    = sanitize_text_field( wp_unslash( $_POST['keys'] ?? '' ) );
		$keys_by_cat  = json_decode( $keys_json, true );

		if ( empty( $keys_by_cat ) || ! is_array( $keys_by_cat ) ) {
			wp_send_json_error( __( '無效的匯入請求', 'mydyma-taiwan-commerce-suite' ) );
		}

		$all_samples = include MYDYMA_TCS_DIR . 'includes/admin/data/sample-rules.php';
		$added_count = 0;

		foreach ( $keys_by_cat as $cat => $ids ) {
			if ( ! isset( $all_samples[ $cat ] ) ) continue;

			$existing_rules = get_option( "mydyma_tcs_rules_{$cat}", [] );
			$existing_ids   = wp_list_pluck( $existing_rules, 'id' );

			foreach ( $all_samples[ $cat ] as $sample ) {
				if ( in_array( $sample['id'], $ids, true ) ) {
					// 避免重複 ID
					$new_id = $sample['id'];
					if ( in_array( $new_id, $existing_ids, true ) ) {
						$new_id = $sample['id'] . '_' . time();
					}

					$new_rule = $sample;
					$new_rule['id']      = $new_id;
					$new_rule['enabled'] = false; // 匯入後預設停用，供用戶檢查
					
					$existing_rules[] = $new_rule;
					$added_count++;
				}
			}
			update_option( "mydyma_tcs_rules_{$cat}", $existing_rules );
		}

		// 返回目前頁面對應的規則列表
		$current_rules = get_option( "mydyma_tcs_rules_{$current_hook}", [] );
		wp_send_json_success( [ 
			'rules' => $current_rules, 
			'added' => $added_count 
		] );
	}

	public function get_rules(): void {
		check_ajax_referer( 'mydyma_tcs_rules', 'nonce' );
		if ( ! current_user_can( 'manage_woocommerce' ) ) wp_send_json_error( 'Forbidden' );

		$section = sanitize_key( $_POST['hook'] ?? '' );
		$rules   = get_option( "mydyma_tcs_rules_{$section}", [] );
		wp_send_json_success( $rules );
	}

	public function save_rules(): void {
		check_ajax_referer( 'mydyma_tcs_rules', 'nonce' );
		if ( ! current_user_can( 'manage_woocommerce' ) ) wp_send_json_error( 'Forbidden' );

		$section = sanitize_key( $_POST['hook'] ?? '' );
		$new_rule = json_decode( sanitize_text_field( wp_unslash( $_POST['rule'] ?? '{}' ) ), true );
		
		$rules = get_option( "mydyma_tcs_rules_{$section}", [] );
		
		// If rule has ID, update it; otherwise add as new
		if ( empty( $new_rule['id'] ) ) {
			$new_rule['id'] = uniqid( 'rule_' );
			$rules[] = $new_rule;
		} else {
			foreach ( $rules as $i => $r ) {
				if ( $r['id'] === $new_rule['id'] ) {
					$rules[ $i ] = $new_rule;
					break;
				}
			}
		}

		update_option( "mydyma_tcs_rules_{$section}", $rules );
		wp_send_json_success( [ 'rules' => $rules ] );
	}

	public function delete_rule(): void {
		check_ajax_referer( 'mydyma_tcs_rules', 'nonce' );
		if ( ! current_user_can( 'manage_woocommerce' ) ) wp_send_json_error( 'Forbidden' );

		$section = sanitize_key( $_POST['hook'] ?? '' );
		$rule_id = sanitize_text_field( wp_unslash( $_POST['rule_id'] ?? '' ) );

		if ( empty( $rule_id ) ) {
			wp_send_json_error( 'Missing rule_id' );
		}

		$rules = get_option( "mydyma_tcs_rules_{$section}", [] );
		$rules = array_values( array_filter( $rules, function( $r ) use ( $rule_id ) {
			return ( $r['id'] ?? '' ) !== $rule_id;
		} ) );
		
		update_option( "mydyma_tcs_rules_{$section}", $rules );
		wp_send_json_success( [ 'rules' => $rules ] );
	}

	public function get_components(): void {
		try {
			check_ajax_referer( 'mydyma_tcs_rules', 'nonce' );
			$engine = \Mydyma_TCS\Rule_Engine\Rule_Engine::instance();
			
			wp_send_json_success( [
				'conditions' => array_values( $engine->get_conditions() ),
				'actions'    => array_values( $engine->get_actions() ),
			] );
		} catch ( \Throwable $e ) {
			wp_send_json_error( $e->getMessage() );
		}
	}
}