<?php
/**
 * Uninstall Script.
 * Fired when the plugin is deleted.
 * Removes all settings and meta data if the user chooses to.
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

// Cleanup options
$taiwan_store_core_options = [
	'taiwan_store_core_license_key',
	'taiwan_store_core_enabled_modules',
	'taiwan_store_core_settings',
];

foreach ( $taiwan_store_core_options as $taiwan_store_core_option ) {
	delete_option( $taiwan_store_core_option );
}

// Cleanup transients
delete_transient( 'taiwan_store_core_activated' );
