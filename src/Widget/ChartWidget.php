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
 * Override get_chart_data() to supply real datasets.
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
	 * Return data for the async /widget-data REST response.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, mixed>
	 */
	public function get_rest_data(): array {
		return [
			'chart_data' => $this->get_chart_data(),
		];
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
	 * Override to return real chart data.
	 *
	 * @since 1.0.0
	 *
	 * @return array{ labels: string[], datasets: array[] }
	 */
	public function get_chart_data(): array {
		return [
			'labels'   => [ 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun' ],
			'datasets' => [
				[
					'label' => 'Sample Data',
					'data'  => [ 12, 19, 8, 15, 24, 17 ],
				],
			],
		];
	}

	/**
	 * Render the canvas element. Override in subclasses to add extra data attributes.
	 *
	 * @since 1.0.0
	 */
	public function render_canvas(): void {
		$opts     = $this->options;
		$chart_id = 'dashkit-chart-' . esc_attr( $this->id );
		$data     = wp_json_encode( $this->get_chart_data() );
		?>
		<canvas id="<?php echo esc_attr( $chart_id ); ?>"
				class="dashkit-chart__canvas"
				data-chart-type="bar"
				data-chart-data="<?php echo esc_attr( $data ); ?>"
				style="max-height:<?php echo (int) $opts['height']; ?>px;">
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
			<div class="dashkit-chart__wrap">
				<?php $this->render_canvas(); ?>
			</div>
		</div>
		<?php
	}
}
