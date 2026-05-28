<?php
namespace Taiwan_Store_Core;

defined( 'ABSPATH' ) || exit;

/**
 * Compatibility Layer.
 * Aliases legacy WC_TW_Core classes to the new Taiwan_Store_Core namespace.
 */
class Compatibility {

	public static function register(): void {
		// Rule Engine Core
		self::alias( 'Rule_Engine\Rule_Engine' );
		self::alias( 'Rule_Engine\Context' );
		self::alias( 'Rule_Engine\Rule' );
		
		// Interfaces
		self::alias( 'Rule_Engine\Condition' );
		self::alias( 'Rule_Engine\Action' );
	}

	private static function alias( string $new_suffix, ?string $old_suffix = null ): void {
		$old_class = 'WC_TW_Core\\' . ( $old_suffix ?? $new_suffix );
		$new_class = 'Taiwan_Store_Core\\' . $new_suffix;

		if ( ! class_exists( $old_class ) && ! interface_exists( $old_class ) ) {
			if ( class_exists( $new_class ) || interface_exists( $new_class ) ) {
				class_alias( $new_class, $old_class );
			}
		}
	}
}
Compatibility::register();
