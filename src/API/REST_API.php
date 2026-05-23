<?php
/**
 * REST_API - Registers and handles all Dashkit REST endpoints.
 *
 * @package Nilambar\Dashkit
 */

declare(strict_types=1);

namespace Nilambar\Dashkit\API;

use Nilambar\Dashkit\Core\OptionsStore;
use Nilambar\Dashkit\Widget\BaseWidget;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Class REST_API
 *
 * Registers and handles all Dashkit REST endpoints.
 *
 * @since 1.0.0
 */
class REST_API {

	/**
	 * Register all Dashkit REST routes.
	 *
	 * @since 1.0.0
	 */
	public static function register_routes(): void {
		register_rest_route(
			'dashkit/v1',
			'/options',
			[
				'methods'             => 'POST',
				'callback'            => [ self::class, 'save_options' ],
				'permission_callback' => fn() => current_user_can( 'manage_options' ),
				'args'                => [
					'widget_id' => [
						'required'          => true,
						'sanitize_callback' => 'sanitize_key',
					],
					'page_slug' => [
						'required'          => true,
						'sanitize_callback' => 'sanitize_key',
					],
					'options'   => [ 'required' => true ],
				],
			]
		);

		register_rest_route(
			'dashkit/v1',
			'/widget-data/(?P<widget_id>[a-z0-9\-]+)',
			[
				'methods'             => 'GET',
				'callback'            => [ self::class, 'widget_data' ],
				'permission_callback' => fn() => current_user_can( 'manage_options' ),
				'args'                => [
					'page_slug' => [
						'required'          => true,
						'sanitize_callback' => 'sanitize_key',
					],
				],
			]
		);
	}

	/**
	 * Handle POST /dashkit/v1/options — save widget options.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request REST request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function save_options( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$store = new OptionsStore(
			$request->get_param( 'page_slug' ),
			$request->get_param( 'widget_id' )
		);
		$store->save( (array) $request->get_param( 'options' ) );
		return rest_ensure_response( [ 'success' => true ] );
	}

	/**
	 * Handle GET /dashkit/v1/widget-data/{widget_id} — return async widget data.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request REST request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function widget_data( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$widget_id = $request->get_param( 'widget_id' );
		$page_slug = $request->get_param( 'page_slug' );

		$widget = apply_filters( "dashkit_async_widget_{$page_slug}", null, $widget_id );

		if ( ! $widget instanceof BaseWidget ) {
			return new WP_Error( 'widget_not_found', 'Widget not found.', [ 'status' => 404 ] );
		}

		return rest_ensure_response( $widget->get_rest_data() );
	}
}
