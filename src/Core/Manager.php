<?php
/**
 * Manager - Orchestrates widget lifecycle for one admin page.
 *
 * @package Nilambar\Dashkit
 */

declare(strict_types=1);

namespace Nilambar\Dashkit\Core;

use Nilambar\Dashkit\Widget\BaseWidget;

/**
 * Class Manager
 *
 * Usage (inside an admin page class):
 *
 *   $manager = new Manager( $context );
 *   $manager->add_widget( 'table', 'orders-table', 'main' );
 *   $manager->add_widget( 'stat',  'total-orders', 'top' );
 *   $manager->init();
 *
 *   // In page HTML:
 *   $manager->render_layout();
 *
 * @since 1.0.0
 */
class Manager {

	/**
	 * Page context configuration.
	 *
	 * @var PageContext
	 */
	private PageContext $context;

	/**
	 * Widget type registry.
	 *
	 * @var Registry
	 */
	private Registry $registry;

	/**
	 * Zone widget instances keyed by zone slug.
	 *
	 * @var array<string, BaseWidget[]>
	 */
	private array $zones = [];

	/**
	 * All widget instances keyed by widget ID.
	 *
	 * @var array<string, BaseWidget>
	 */
	private array $widgets = [];

	/**
	 * Whether init() has already been called.
	 *
	 * @var bool
	 */
	private bool $initialised = false;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param PageContext $context Page context configuration.
	 */
	public function __construct( PageContext $context ) {
		$this->context  = $context;
		$this->registry = Registry::instance();
	}

	/**
	 * Add a widget to a zone on this page.
	 *
	 * @since 1.0.0
	 *
	 * @param string               $type_slug Widget type slug.
	 * @param string               $widget_id Unique widget ID.
	 * @param string               $zone      Zone slug.
	 * @param array<string, mixed> $overrides Option overrides to persist.
	 */
	public function add_widget(
		string $type_slug,
		string $widget_id,
		string $zone,
		array $overrides = []
	): void {
		if ( ! $this->context->current_user_can() ) {
			return;
		}

		if ( ! $this->context->has_zone( $zone ) ) {
			_doing_it_wrong(
				__METHOD__,
				esc_html( sprintf( "Dashkit: Zone '%s' is not declared for page '%s'.", $zone, $this->context->get_slug() ) ),
				esc_html( DASHKIT_LOADED_VERSION )
			);
			return;
		}

		$class = $this->registry->get_type( $type_slug );
		if ( ! $class ) {
			_doing_it_wrong(
				__METHOD__,
				esc_html( sprintf( "Dashkit: Widget type '%s' is not registered.", $type_slug ) ),
				esc_html( DASHKIT_LOADED_VERSION )
			);
			return;
		}

		$widget = new $class( $widget_id, $this->context->get_slug(), $zone );

		if ( ! empty( $overrides ) ) {
			$widget->save_options( $overrides );
		}

		$this->widgets[ $widget_id ] = $widget;
		$this->zones[ $zone ][]      = $widget;
	}

	/**
	 * Initialize asset enqueuing and async widget filter.
	 *
	 * @since 1.0.0
	 */
	public function init(): void {
		if ( $this->initialised ) {
			return;
		}
		$this->initialised = true;

		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_shared_assets' ] );

		foreach ( $this->widgets as $widget ) {
			$widget->enqueue_assets();
		}

		$page_slug = $this->context->get_slug();
		add_filter(
			"dashkit_async_widget_{$page_slug}",
			function ( $current, string $widget_id ) {
				return $this->widgets[ $widget_id ] ?? $current;
			},
			10,
			2
		);
	}

	/**
	 * Enqueue shared admin CSS and JS assets.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_shared_assets(): void {
		wp_enqueue_style(
			'remixicon',
			DASHKIT_URL . 'third-party/remixicon/remixicon.css',
			[],
			'4.9.1'
		);
		wp_enqueue_style(
			'dashkit-admin',
			DASHKIT_URL . 'assets/dashkit.css',
			[ 'remixicon' ],
			DASHKIT_LOADED_VERSION
		);
		wp_enqueue_script(
			'dashkit-admin',
			DASHKIT_URL . 'assets/dashkit.js',
			[],
			DASHKIT_LOADED_VERSION,
			true
		);
		wp_add_inline_script(
			'dashkit-admin',
			'var dashkitConfig = ' . wp_json_encode(
				[
					'restUrl' => rest_url( 'dashkit/v1' ),
					'nonce'   => wp_create_nonce( 'wp_rest' ),
					'version' => DASHKIT_LOADED_VERSION,
					'i18n'    => [
						'confirmAction' => __( 'Are you sure?', 'dashkit' ),
						'actionFailed'  => __( 'Action failed.', 'dashkit' ),
						'actionDone'    => __( 'Done.', 'dashkit' ),
						'requestFailed' => __( 'Request failed:', 'dashkit' ),
						'items'         => __( 'items', 'dashkit' ),
						'saveOptions'   => __( 'Save Options', 'dashkit' ),
						'saving'        => __( 'Saving…', 'dashkit' ),
						'saved'         => __( '✓ Saved', 'dashkit' ),
						'saveError'     => __( '✕ Error', 'dashkit' ),
						'saveFailed'    => __( 'Save failed.', 'dashkit' ),
						'optionsSaved'  => __( 'Widget options saved.', 'dashkit' ),
					],
				]
			) . ';',
			'before'
		);
	}

	/**
	 * Render all zones in a responsive layout.
	 *
	 * One populated zone = 100% width. Multiple zones = 2-column CSS grid.
	 *
	 * @since 1.0.0
	 */
	public function render_layout(): void {
		$active = array_values(
			array_filter(
				$this->context->get_zones(),
				fn( string $zone ) => ! empty( $this->zones[ $zone ] )
			)
		);

		if ( empty( $active ) ) {
			return;
		}

		$modifier = count( $active ) === 1 ? 'single' : 'multi-col';
		echo '<div class="dashkit-layout dashkit-layout--' . esc_attr( $modifier ) . '">'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		foreach ( $active as $zone ) {
			$this->render_zone( $zone );
		}
		echo '</div>';
	}

	/**
	 * Render the HTML wrapper and all widgets within a zone.
	 *
	 * @since 1.0.0
	 *
	 * @param string $zone Zone slug.
	 */
	public function render_zone( string $zone ): void {
		$widgets = $this->zones[ $zone ] ?? [];
		if ( empty( $widgets ) ) {
			return;
		}

		echo '<div class="dashkit-zone dashkit-zone--' . esc_attr( $zone ) . '">'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		foreach ( $widgets as $widget ) {
			$widget->render_shell();
		}
		echo '</div>';
	}

	/**
	 * Get a widget instance by ID.
	 *
	 * @since 1.0.0
	 *
	 * @param string $widget_id Widget ID.
	 */
	public function get_widget( string $widget_id ): ?BaseWidget {
		return $this->widgets[ $widget_id ] ?? null;
	}

	/**
	 * Get all widget instances keyed by widget ID.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, BaseWidget>
	 */
	public function get_all_widgets(): array {
		return $this->widgets;
	}

	/**
	 * Get the page context for this manager.
	 *
	 * @since 1.0.0
	 */
	public function get_context(): PageContext {
		return $this->context;
	}
}
