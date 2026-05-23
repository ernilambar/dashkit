<?php
/**
 * Entry point.
 *
 * @package Dashkit
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

// Bootstrap class lives in the global namespace so every bundled copy of this
// file (across multiple plugins) shares the same candidate registry without
// relying on a PHP global variable.
if ( ! class_exists( 'DashkitBootstrap', false ) ) {
	/**
	 * Bootstrap class for version-election across multiple bundled copies.
	 *
	 * @since 1.0.0
	 */
	final class DashkitBootstrap {

		/**
		 * Version-to-directory map for all registered copies.
		 *
		 * @var array<string,string>
		 */
		private static array $candidates = [];

		/**
		 * Registers a copy of the library as a candidate for election.
		 *
		 * @since 1.0.0
		 *
		 * @param string $version Semantic version string.
		 * @param string $dir     Absolute path to the library root directory.
		 */
		public static function add_candidate( string $version, string $dir ): void {
			self::$candidates[ $version ] = $dir;
		}

		/**
		 * Elects the highest-version candidate, defines library constants, and wires up hooks.
		 *
		 * Hooked to plugins_loaded at priority 0 by the first init.php that runs.
		 *
		 * @since 1.0.0
		 */
		public static function elect(): void {
			uksort( self::$candidates, 'version_compare' );

			$winner_dir     = end( self::$candidates );
			$winner_version = key( self::$candidates );

			if ( ! defined( 'DASHKIT_LOADED_VERSION' ) ) {
				define( 'DASHKIT_LOADED_VERSION', $winner_version );
			}

			if ( ! defined( 'DASHKIT_DIR' ) ) {
				define( 'DASHKIT_DIR', $winner_dir );
			}

			if ( ! defined( 'DASHKIT_URL' ) ) {
				define( 'DASHKIT_URL', plugin_dir_url( $winner_dir . '/init.php' ) );
			}

			add_action( 'rest_api_init', [ 'Nilambar\\Dashkit\\API\\REST_API', 'register_routes' ] );
		}
	}
}

( static function () {
	$version = '1.0.0';

	DashkitBootstrap::add_candidate( $version, __DIR__ );

	if ( defined( 'DASHKIT_ELECTION_HOOKED' ) ) {
		return;
	}

	define( 'DASHKIT_ELECTION_HOOKED', true );

	add_action( 'plugins_loaded', [ 'DashkitBootstrap', 'elect' ], 0 );
} )();
