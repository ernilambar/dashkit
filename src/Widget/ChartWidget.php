<?php
/**
 * ChartWidget - Chart.js-powered chart widget.
 *
 * @package Nilambar\Dashkit
 */

declare(strict_types=1);

namespace Nilambar\Dashkit\Widget;

/**
 * Class ChartWidget
 *
 * Subclasses must implement get_data() to supply datasets.
 *
 * @since 1.0.0
 */
abstract class ChartWidget extends BaseWidget {

	/**
	 * Return the widget type slug.
	 *
	 * @since 1.0.0
	 */
	public static function get_type_slug(): string {
		return 'chart';
	}

	/**
	 * Return developer-controlled chart config (not user-editable).
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, mixed>
	 */
	public function get_widget_config(): array {
		return [
			'height' => 300,
		];
	}

	/**
	 * Return data for the async /widget-data REST response.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, mixed>
	 */
	public function get_rest_data(): array {
		return [
			'chart_data' => $this->get_data(),
		];
	}

	/**
	 * Return the chart type (bar, line, etc.).
	 *
	 * @since 1.0.0
	 */
	abstract public function get_chart_type(): string;

	/**
	 * Return chart data. Must be implemented by subclasses.
	 *
	 * @since 1.0.0
	 *
	 * @return array{ labels: string[], datasets: array[] }
	 */
	abstract public function get_data(): array;

	/**
	 * Render the canvas element. Override in subclasses to add extra data attributes.
	 *
	 * @since 1.0.0
	 */
	public function render_canvas(): void {
		$chart_id = 'dashkit-chart-' . esc_attr( $this->id );
		$data     = wp_json_encode( $this->get_data() );
		?>
		<canvas id="<?php echo esc_attr( $chart_id ); ?>"
				class="dashkit-chart__canvas"
				data-chart-type="<?php echo esc_attr( $this->get_chart_type() ); ?>"
				data-chart-data="<?php echo esc_attr( $data ); ?>">
		</canvas>
		<?php
	}

	/**
	 * Render the chart widget HTML.
	 *
	 * @since 1.0.0
	 */
	public function render(): void {
		?>
		<div class="dashkit-chart-widget">
			<div class="dashkit-chart__wrap" style="height:<?php echo (int) $this->options['height']; ?>px;">
				<?php $this->render_canvas(); ?>
			</div>
		</div>
		<?php
	}
}
