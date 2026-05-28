<?php
/**
 * Uninstall Script.
 * Fired when the plugin is deleted.
 * Removes all settings and meta data if the user chooses to.
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

// Cleanup options
$mydyma_tcs_options = [
	'mydyma_tcs_license_key',
	'mydyma_tcs_enabled_modules',
	'mydyma_tcs_settings',
];

foreach ( $mydyma_tcs_options as $mydyma_tcs_option ) {
	delete_option( $mydyma_tcs_option );
}

// Cleanup transients
delete_transient( 'mydyma_tcs_activated' );
