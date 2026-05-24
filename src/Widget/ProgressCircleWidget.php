<?php
/**
 * ProgressCircleWidget - SVG progress circle widget.
 *
 * @package Nilambar\Dashkit
 */

declare(strict_types=1);

namespace Nilambar\Dashkit\Widget;

/**
 * Class ProgressCircleWidget
 *
 * Renders a row of SVG progress circles, each with a value in the centre
 * and a caption below. Override get_items() to supply data.
 *
 * @since 1.0.0
 */
abstract class ProgressCircleWidget extends BaseWidget {

	/**
	 * Return the widget type slug.
	 *
	 * @since 1.0.0
	 */
	public static function get_type_slug(): string {
		return 'progress-circle';
	}

	/**
	 * Return developer-controlled config (items locked here, not user-editable).
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, mixed>
	 */
	public function get_widget_config(): array {
		return [
			'items' => $this->get_items(),
		];
	}

	/**
	 * Return the circle items to display.
	 *
	 * Override in subclasses to supply real data. Each item must have:
	 *   - value      (string) — text shown inside the circle
	 *   - caption    (string) — label shown below the circle
	 *   - percentage (int)    — 0–100, controls how much of the arc is filled
	 *
	 * @since 1.0.0
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function get_items(): array {
		return [];
	}

	/**
	 * Return default options.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, mixed>
	 */
	public function get_default_options(): array {
		return [];
	}

	/**
	 * Return the options schema.
	 *
	 * @since 1.0.0
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function get_options_schema(): array {
		return [];
	}

	/**
	 * Render the progress circle widget HTML.
	 *
	 * @since 1.0.0
	 */
	public function render(): void {
		$items = $this->get_option( 'items', [] );

		if ( empty( $items ) ) {
			return;
		}

		$radius        = 50;
		$circumference = round( 2 * M_PI * $radius, 3 );
		?>
		<div class="dashkit-progress-circle">
			<div class="dashkit-progress-circle__grid">
				<?php foreach ( $items as $item ) : ?>
					<?php
					$percentage = (float) ( $item['percentage'] ?? 0 );
					$value      = (string) ( $item['value'] ?? '' );
					$caption    = (string) ( $item['caption'] ?? '' );
					$offset     = round( $circumference - ( $percentage / 100 ) * $circumference, 3 );
					?>
					<div class="dashkit-progress-circle__item">
						<div class="dashkit-progress-circle__ring">
							<svg width="120" height="120" aria-hidden="true">
								<circle class="dashkit-progress-circle__track"
										cx="60" cy="60"
										r="<?php echo esc_attr( (string) $radius ); ?>"></circle>
								<circle class="dashkit-progress-circle__arc"
										cx="60" cy="60"
										r="<?php echo esc_attr( (string) $radius ); ?>"
										stroke-dasharray="<?php echo esc_attr( (string) $circumference ); ?>"
										stroke-dashoffset="<?php echo esc_attr( (string) $offset ); ?>"></circle>
							</svg>
							<div class="dashkit-progress-circle__value">
								<?php echo esc_html( $value ); ?>
							</div>
						</div>
						<?php if ( '' !== $caption ) : ?>
							<div class="dashkit-progress-circle__caption">
								<?php echo esc_html( $caption ); ?>
							</div>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
	}
}
