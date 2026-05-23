<?php
/**
 * OptionsStore - Persists widget options to wp_options.
 *
 * @package Nilambar\Dashkit
 */

declare(strict_types=1);

namespace Nilambar\Dashkit\Core;

/**
 * Class OptionsStore
 *
 * Key format: dashkit_{page_slug}_{widget_id}
 *
 * @since 1.0.0
 */
class OptionsStore {

	/**
	 * The wp_options key used to store this widget's options.
	 *
	 * @var string
	 */
	private string $option_key;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param string $page_slug Page slug.
	 * @param string $widget_id Widget ID.
	 */
	public function __construct( string $page_slug, string $widget_id ) {
		$this->option_key = 'dashkit_'
			. sanitize_key( $page_slug ) . '_'
			. sanitize_key( $widget_id );
	}

	/**
	 * Merge $new_options on top of existing saved options and persist.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $new_options Options to merge and save.
	 */
	public function save( array $new_options ): void {
		$merged = array_merge( $this->load(), $new_options );
		update_option( $this->option_key, $merged, false );
	}

	/**
	 * Load saved options from the database.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, mixed>
	 */
	public function load(): array {
		$data = get_option( $this->option_key, [] );
		return is_array( $data ) ? $data : [];
	}

	/**
	 * Replace all saved options with the given array.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $options Options to store.
	 */
	public function replace( array $options ): void {
		update_option( $this->option_key, $options, false );
	}

	/**
	 * Delete the stored options entry.
	 *
	 * @since 1.0.0
	 */
	public function delete(): void {
		delete_option( $this->option_key );
	}

	/**
	 * Get the option key used in the database.
	 *
	 * @since 1.0.0
	 */
	public function get_key(): string {
		return $this->option_key;
	}
}
