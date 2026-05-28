<?php
namespace Mydyma_TCS\Modules\Checkout_Tw;

defined( 'ABSPATH' ) || exit;

/**
 * Invoice Export Module.
 * Allows bulk exporting of Taiwan invoice data to CSV.
 */
class Invoice_Export {

	private const ACTION_GENERIC = 'mydyma_tcs_export_invoice_csv';

	public function boot(): void {
		if ( ! is_admin() ) return;
		add_filter( 'bulk_actions-edit-shop_order', [ $this, 'add_bulk_actions' ] );
		add_filter( 'woocommerce_order_list_table_bulk_actions', [ $this, 'add_bulk_actions' ] );
		add_filter( 'handle_bulk_actions-edit-shop_order', [ $this, 'handle_bulk_actions' ], 10, 3 );
		add_action( 'woocommerce_order_list_table_custom_bulk_action', [ $this, 'handle_hpos_bulk_actions' ], 10, 2 );

		// Global export handler from Dashboard
		add_action( 'admin_init', [ $this, 'handle_global_export' ] );
	}

	public function handle_global_export(): void {
		if ( sanitize_text_field( wp_unslash( $_GET['page'] ?? '' ) ) === 'mydyma-taiwan-commerce-suite' && sanitize_text_field( wp_unslash( $_GET['action'] ?? '' ) ) === 'export-invoices' ) {
			check_admin_referer( -1, '_wpnonce' ); // Or implement a proper nonce if needed
			$orders = wc_get_orders( [ 'limit' => 500, 'status' => [ 'wc-processing', 'wc-completed' ] ] );
			$ids    = array_map( function( $o ) { return $o->get_id(); }, $orders );
			$this->output_csv( $ids );
			exit;
		}
	}

	public function add_bulk_actions( array $actions ): array {
		$actions[ self::ACTION_GENERIC ] = __( 'Export Invoice: Generic (CSV)', 'mydyma-taiwan-commerce-suite' );
		return $actions;
	}

	public function handle_bulk_actions( string $redirect_to, string $action, array $post_ids ): string {
		if ( $action !== self::ACTION_GENERIC ) return $redirect_to;
		$this->output_csv( array_map( 'absint', $post_ids ) );
		exit;
	}

	public function handle_hpos_bulk_actions( string $action, array $order_ids ): void {
		if ( $action !== self::ACTION_GENERIC ) return;
		$this->output_csv( array_map( 'absint', $order_ids ) );
		exit;
	}

	private function output_csv( array $order_ids ): void {
		$filename = 'invoice-export-' . gmdate( 'Ymd-His' ) . '.csv';
		while ( ob_get_level() ) ob_end_clean();
		header( 'Content-Type: text/csv; charset=UTF-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );
		echo "\xEF\xBB\xBF"; // UTF-8 BOM

		$out = fopen( 'php://output', 'w' );
		fputcsv( $out, [
			__( 'Order Number', 'mydyma-taiwan-commerce-suite' ),
			__( 'Date', 'mydyma-taiwan-commerce-suite' ),
			__( 'Customer Name', 'mydyma-taiwan-commerce-suite' ),
			__( 'Email', 'mydyma-taiwan-commerce-suite' ),
			__( 'Invoice Type', 'mydyma-taiwan-commerce-suite' ),
			__( 'Carrier/Donation Code', 'mydyma-taiwan-commerce-suite' ),
			__( 'Tax ID', 'mydyma-taiwan-commerce-suite' ),
			__( 'Company Title', 'mydyma-taiwan-commerce-suite' ),
			__( 'Total Amount', 'mydyma-taiwan-commerce-suite' ),
		] );

		foreach ( $order_ids as $order_id ) {
			$order = wc_get_order( $order_id );
			if ( ! $order ) continue;
			fputcsv( $out, [
				$order->get_order_number(),
				$order->get_date_created() ? $order->get_date_created()->date( 'Y-m-d H:i:s' ) : '',
				trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() ),
				$order->get_billing_email(),
				(string) $order->get_meta( 'billing_mydyma_tcs_invoice_type' ),
				(string) $order->get_meta( 'billing_mydyma_tcs_carrier_number' ),
				(string) $order->get_meta( 'billing_mydyma_tcs_company_tax_id' ),
				(string) $order->get_meta( 'billing_mydyma_tcs_company_title' ),
				$order->get_total(),
			] );
		}
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- writing to php://output stream, WP_Filesystem not applicable
		fclose( $out );
	}
}