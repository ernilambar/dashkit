<?php
/**
 * PageContext - Per-page configuration for the widget manager.
 *
 * @package Nilambar\Dashkit
 */

declare(strict_types=1);

namespace Nilambar\Dashkit\Core;

/**
 * Class PageContext
 *
 * Defines which zones exist and what capability a user needs to see the page widgets.
 *
 * @since 1.0.0
 */
class PageContext {

	/**
	 * Page slug identifier.
	 *
	 * @var string
	 */
	private string $slug;

	/**
	 * Required WP capability to access this page.
	 *
	 * @var string
	 */
	private string $capability;

	/**
	 * Zone slugs registered for this page.
	 *
	 * @var string[]
	 */
	private array $zones;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param string               $slug   Page slug.
	 * @param array<string, mixed> $config Configuration array.
	 */
	public function __construct( string $slug, array $config = [] ) {
		$this->slug       = $slug;
		$this->capability = $config['capability'] ?? 'manage_options';
		$this->zones      = array_values( array_unique( $config['zones'] ?? [] ) );
	}

	/**
	 * Get the page slug.
	 *
	 * @since 1.0.0
	 */
	public function get_slug(): string {
		return $this->slug;
	}

	/**
	 * Get the required capability for this page.
	 *
	 * @since 1.0.0
	 */
	public function get_capability(): string {
		return $this->capability;
	}

	/**
	 * Check whether the current user has the required capability.
	 *
	 * @since 1.0.0
	 */
	public function current_user_can(): bool {
		return current_user_can( $this->capability );
	}

	/**
	 * Get all declared zone slugs.
	 *
	 * @since 1.0.0
	 *
	 * @return string[]
	 */
	public function get_zones(): array {
		return $this->zones;
	}

	/**
	 * Check whether a zone exists on this page.
	 *
	 * @since 1.0.0
	 *
	 * @param string $zone Zone slug.
	 */
	public function has_zone( string $zone ): bool {
		return in_array( $zone, $this->zones, true );
	}
}
