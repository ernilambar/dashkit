<?php
/**
 * Registry - Central store for widget type registrations.
 *
 * @package Nilambar\Dashkit
 */

declare(strict_types=1);

namespace Nilambar\Dashkit\Core;

/**
 * Class Registry
 *
 * Singleton. Holds the map of type_slug => class_name.
 *
 * @since 1.0.0
 */
class Registry {

	/**
	 * Singleton instance.
	 *
	 * @var Registry|null
	 */
	private static ?Registry $instance = null;

	/**
	 * Registered type slugs mapped to class names.
	 *
	 * @var array<string, class-string>
	 */
	private array $types = [];

	/**
	 * Private constructor to enforce singleton pattern.
	 *
	 * @since 1.0.0
	 */
	private function __construct() {}

	/**
	 * Return the singleton instance.
	 *
	 * @since 1.0.0
	 */
	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Register a widget type.
	 *
	 * @since 1.0.0
	 *
	 * @param string       $type_slug  Unique slug, e.g. 'table'.
	 * @param class-string $class_name Class extending BaseWidget.
	 */
	public function register_type( string $type_slug, string $class_name ): void {
		if ( ! class_exists( $class_name ) ) {
			_doing_it_wrong(
				__METHOD__,
				esc_html( sprintf( "Dashkit: Widget class '%s' does not exist.", $class_name ) ),
				'1.0.0'
			);
			return;
		}
		$this->types[ $type_slug ] = $class_name;
	}

	/**
	 * Get the class name for a registered widget type.
	 *
	 * @since 1.0.0
	 *
	 * @param string $type_slug Widget type slug.
	 * @return class-string|null
	 */
	public function get_type( string $type_slug ): ?string {
		return $this->types[ $type_slug ] ?? null;
	}

	/**
	 * Get all registered type slugs.
	 *
	 * @since 1.0.0
	 *
	 * @return string[]
	 */
	public function get_all_types(): array {
		return array_keys( $this->types );
	}

	/**
	 * Deregister a widget type by slug.
	 *
	 * @since 1.0.0
	 *
	 * @param string $type_slug Widget type slug.
	 */
	public function deregister_type( string $type_slug ): void {
		unset( $this->types[ $type_slug ] );
	}

	/**
	 * Check whether a widget type slug is registered.
	 *
	 * @since 1.0.0
	 *
	 * @param string $type_slug Widget type slug.
	 */
	public function has_type( string $type_slug ): bool {
		return isset( $this->types[ $type_slug ] );
	}
}
