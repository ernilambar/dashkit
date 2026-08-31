<?php
/**
 * Strings - Central string configuration with overridable defaults.
 *
 * @package Nilambar\Dashkit
 */

declare(strict_types=1);

namespace Nilambar\Dashkit\Core;

/**
 * Class Strings
 *
 * Dashkit ships no text domain. Every user-facing string has an English
 * default here and can be overridden in one place with the dashkit_strings
 * filter:
 *
 *     add_filter( 'dashkit_strings', function ( array $strings ) {
 *         $strings['actions'] = 'Aktionen';
 *         return $strings;
 *     } );
 *
 * The filter receives the full defaults map; return the modified map or a
 * partial map of overrides. Missing keys fall back to defaults.
 *
 * Widget classes may additionally override the methods that return
 * individual strings (e.g. get_actions_label(), get_empty_message()).
 *
 * @since 1.0.0
 */
final class Strings {

	/**
	 * Return the default strings.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, string>
	 */
	public static function defaults(): array {
		return [
			// Widget chrome.
			'options'        => 'Options',

			// Table widget.
			'actions'        => 'Actions',
			'col_id'         => 'ID',
			'col_title'      => 'Title',
			'col_date'       => 'Date',
			'loading'        => 'Loading…',
			'no_data'        => 'No data found.',

			// JavaScript-facing strings, also exposed via dashkitConfig.i18n.
			'confirm_action' => 'Are you sure?',
			'action_failed'  => 'Action failed.',
			'action_done'    => 'Done.',
			'request_failed' => 'Request failed: %s',
			'items'          => 'items',
			'save_options'   => 'Save Changes',
			'saving'         => 'Saving…',
			'saved'          => 'Saved',
			'save_error'     => 'Error',
			'save_failed'    => 'Save failed.',
			'options_saved'  => 'Widget options saved.',
		];
	}

	/**
	 * Return all strings after applying the dashkit_strings filter.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, string>
	 */
	public static function all(): array {
		return array_merge(
			self::defaults(),
			(array) apply_filters( 'dashkit_strings', self::defaults() )
		);
	}

	/**
	 * Return a single string by key.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key String key.
	 */
	public static function get( string $key ): string {
		return self::all()[ $key ] ?? self::defaults()[ $key ] ?? '';
	}
}
