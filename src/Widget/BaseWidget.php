<?php
/**
 * BaseWidget - Abstract base all Dashkit widget types extend.
 *
 * @package Nilambar\Dashkit
 */

declare(strict_types=1);

namespace Nilambar\Dashkit\Widget;

use Nilambar\Dashkit\Core\OptionsStore;

/**
 * Class BaseWidget
 *
 * Subclasses MUST implement:
 *   get_widget_name()     : string  — human-readable display name
 *   render()              : void
 *
 * Subclasses MAY override:
 *   get_default_options() : array
 *   get_options_schema()  : array
 *   get_actions()         : array
 *   on_save()             : void
 *   on_destroy()          : void
 *   enqueue_assets()      : void
 *
 * @since 1.0.0
 */
abstract class BaseWidget {

	/**
	 * Unique widget instance ID.
	 *
	 * @var string
	 */
	protected string $id;

	/**
	 * Admin page slug this widget belongs to.
	 *
	 * @var string
	 */
	protected string $page_slug;

	/**
	 * Zone slug within the page.
	 *
	 * @var string
	 */
	protected string $zone;

	/**
	 * Optional Remixicon name shown before the widget title (e.g. 'alarm-line').
	 *
	 * @var string
	 */
	protected string $icon = '';

	/**
	 * Merged widget options (defaults + saved).
	 *
	 * @var array<string, mixed>
	 */
	protected array $options;

	/**
	 * Options persistence store.
	 *
	 * @var OptionsStore
	 */
	protected OptionsStore $store;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param string $id        Widget ID.
	 * @param string $page_slug Page slug.
	 * @param string $zone      Zone slug.
	 */
	public function __construct( string $id, string $page_slug, string $zone = 'main' ) {
		$this->id        = $id;
		$this->page_slug = $page_slug;
		$this->zone      = $zone;
		$this->store     = new OptionsStore( $page_slug, $id );

		$widget_config = apply_filters( 'dashkit_widget_config_' . $this->id, $this->get_widget_config() );

		$this->options = array_merge(
			$this->get_default_options(),
			$this->store->load(),
			$widget_config,
		);
	}

	/**
	 * Return developer-controlled config for this widget (never user-editable).
	 *
	 * Values returned here are merged last in the constructor and stripped from
	 * any user save, so they always take precedence over stored user options.
	 * Override in subclasses to lock down specific keys. The filter
	 * dashkit_widget_config_{id} allows deployment-level overrides without
	 * editing PHP.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, mixed>
	 */
	public function get_widget_config(): array {
		return [];
	}

	/**
	 * Whether this widget should skip server-side data rendering and fetch rows via REST on page load.
	 *
	 * Enable by returning [ 'lazy' => true ] from get_widget_config() in a subclass.
	 *
	 * @since 1.0.0
	 */
	public function is_lazy(): bool {
		return (bool) ( $this->options['lazy'] ?? false );
	}

	/**
	 * Return the human-readable display name for this widget type.
	 *
	 * @since 1.0.0
	 */
	abstract public function get_widget_name(): string;

	/**
	 * Return the default option values for this widget.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, mixed>
	 */
	public function get_default_options(): array {
		return [];
	}

	/**
	 * Return the options schema used to build the options panel.
	 *
	 * @since 1.0.0
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function get_options_schema(): array {
		return [];
	}

	/**
	 * Render the widget body HTML.
	 *
	 * @since 1.0.0
	 */
	abstract public function render(): void;

	/**
	 * Return the list of available row actions.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public function get_actions(): array {
		return [];
	}

	/**
	 * Return data for the async /widget-data REST response. Override per widget type.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, mixed>
	 */
	public function get_rest_data(): array {
		return [];
	}

	/**
	 * Hook called after options are saved. Override in subclass.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $new_options The newly saved options.
	 */
	public function on_save( array $new_options ): void {}

	/**
	 * Hook called when the widget is destroyed. Override in subclass.
	 *
	 * @since 1.0.0
	 */
	public function on_destroy(): void {}

	/**
	 * Enqueue widget-specific assets. Override in subclass.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_assets(): void {}

	/**
	 * Merge and persist new options, excluding any widget-config keys.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $new_options Options to save.
	 */
	public function save_options( array $new_options ): void {
		$saveable = array_diff_key( $new_options, $this->get_widget_config() );
		$this->store->save( $saveable );
		$this->options = array_merge( $this->options, $saveable, $this->get_widget_config() );
		$this->on_save( $saveable );
	}

	/**
	 * Get the display name for this widget instance.
	 *
	 * @since 1.0.0
	 */
	public function get_name(): string {
		return $this->get_widget_name();
	}

	/**
	 * Get the widget ID.
	 *
	 * @since 1.0.0
	 */
	public function get_id(): string {
		return $this->id;
	}

	/**
	 * Get the zone slug this widget is assigned to.
	 *
	 * @since 1.0.0
	 */
	public function get_zone(): string {
		return $this->zone;
	}

	/**
	 * Get the page slug this widget belongs to.
	 *
	 * @since 1.0.0
	 */
	public function get_page_slug(): string {
		return $this->page_slug;
	}

	/**
	 * Get all current options.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, mixed>
	 */
	public function get_options(): array {
		return $this->options;
	}

	/**
	 * Get a single option value.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key      Option key.
	 * @param mixed  $fallback Value to return if the key is not set.
	 */
	public function get_option( string $key, mixed $fallback = null ): mixed {
		return $this->options[ $key ] ?? $fallback;
	}

	/**
	 * Get the database option key used by the options store.
	 *
	 * @since 1.0.0
	 */
	public function get_storage_key(): string {
		return $this->store->get_key();
	}

	/**
	 * Return the default value for a single option key.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key      Option key.
	 * @param mixed  $fallback Value to return when the key has no default.
	 */
	public function get_default( string $key, mixed $fallback = null ): mixed {
		return $this->get_default_options()[ $key ] ?? $fallback;
	}

	/**
	 * Return the type slug for this widget class.
	 *
	 * @since 1.0.0
	 */
	public static function get_type_slug(): string {
		return 'base';
	}

	/**
	 * Return HTML strings to render next to the widget title. Override in subclasses.
	 *
	 * Each entry is a raw HTML string — callers are responsible for escaping.
	 *
	 * @since 1.0.0
	 *
	 * @return string[]
	 */
	public function get_header_badges(): array {
		return [];
	}

	/**
	 * Render the standard widget header with name and options toggle.
	 *
	 * @since 1.0.0
	 */
	public function render_header(): void {
		$badges = $this->get_header_badges();
		?>
		<div class="dashkit-widget__header">
			<h3 class="dashkit-widget__title">
				<?php if ( $this->icon ) : ?>
					<i class="ri-<?php echo esc_attr( $this->icon ); ?>"></i>
				<?php endif; ?>
				<?php echo esc_html( $this->get_name() ); ?>
				<?php if ( ! empty( $badges ) ) : ?>
					<span class="dashkit-widget__badges">
						<?php foreach ( $badges as $badge ) : ?>
							<?php echo $badge; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php endforeach; ?>
					</span>
				<?php endif; ?>
			</h3>
			<div class="dashkit-widget__header-actions">
				<button class="dashkit-widget__options-toggle"
						title="<?php echo esc_attr__( 'Options', 'dashkit' ); ?>"
						data-dashkit-options-toggle>
					<i class="ri-settings-3-line"></i>
				</button>
			</div>
		</div>
		<?php
	}

	/**
	 * Render the options panel placeholder element.
	 *
	 * @since 1.0.0
	 */
	public function render_options_panel(): void {
		?>
		<div class="dashkit-options-panel"
			data-widget-id="<?php echo esc_attr( $this->id ); ?>"
			data-page-slug="<?php echo esc_attr( $this->page_slug ); ?>"
			data-schema="<?php echo esc_attr( wp_json_encode( $this->get_options_schema() ) ); ?>"
			data-options="<?php echo esc_attr( wp_json_encode( $this->options ) ); ?>"
			data-nonce="<?php echo esc_attr( wp_create_nonce( 'dashkit_options' ) ); ?>">
		</div>
		<?php
	}

	/**
	 * Render the outer widget shell and call render() inside it.
	 *
	 * @since 1.0.0
	 */
	public function render_shell(): void {
		$type = static::get_type_slug();
		?>
		<div class="dashkit-widget dashkit-widget--<?php echo esc_attr( $type ); ?>"
			id="dashkit-widget-<?php echo esc_attr( $this->id ); ?>"
			data-widget-id="<?php echo esc_attr( $this->id ); ?>"
			data-widget-type="<?php echo esc_attr( $type ); ?>"
			data-page-slug="<?php echo esc_attr( $this->page_slug ); ?>"
			data-zone="<?php echo esc_attr( $this->zone ); ?>"
			data-async-url="<?php echo esc_url( rest_url( 'dashkit/v1/widget-data/' . $this->id . '?page_slug=' . $this->page_slug ) ); ?>"
			<?php
			if ( $this->is_lazy() ) :
				?>
				data-lazy="1"<?php endif; ?>>
			<?php $this->render_header(); ?>
			<?php $this->render_options_panel(); ?>
			<?php $this->render(); ?>
		</div>
		<?php
	}
}
