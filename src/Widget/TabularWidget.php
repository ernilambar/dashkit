<?php
/**
 * TabularWidget - Data grid widget with sorting, search, pagination, and row actions.
 *
 * @package Nilambar\Dashkit
 */

declare(strict_types=1);

namespace Nilambar\Dashkit\Widget;

/**
 * Class TabularWidget
 *
 * Subclass and override:
 *   get_data()           — supply your data
 *   get_columns_config() — define column keys and labels
 *   format_cell()        — custom cell rendering
 *   get_actions()        — declare available row actions
 *
 * @since 1.0.0
 */
abstract class TabularWidget extends BaseWidget {

	/**
	 * Return the widget type slug.
	 *
	 * @since 1.0.0
	 */
	public static function get_type_slug(): string {
		return 'tabular';
	}

	/**
	 * Return developer-controlled config that always overrides stored options.
	 *
	 * Override in subclasses to enable pagination, etc.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, mixed>
	 */
	public function get_widget_config(): array {
		return [
			'columns'    => $this->get_active_columns(),
			'striped'    => true,
			'pagination' => false,
		];
	}

	/**
	 * Return the column keys that are active (shown) by default.
	 *
	 * Override in a subclass when you want to display only a subset of the
	 * columns defined in get_columns_config() without touching the full config.
	 *
	 * @since 1.0.0
	 *
	 * @return string[]
	 */
	public function get_active_columns(): array {
		return array_column( $this->get_columns_config(), 'key' );
	}

	/**
	 * Render the table widget HTML.
	 *
	 * @since 1.0.0
	 */
	public function render(): void {
		if ( $this->is_lazy() ) {
			$this->render_lazy_shell();
			return;
		}

		$opts    = $this->options;
		$columns = $this->get_visible_columns();
		$rows    = $this->get_data();
		$actions = $this->get_actions();
		?>
		<div class="<?php echo esc_attr( $this->get_table_css() ); ?>">

			<?php if ( ! empty( $rows ) ) : ?>

				<div class="dashkit-table__wrap">
					<?php $this->render_table_open(); ?>
						<?php $this->render_thead( $columns, $actions ); ?>
						<tbody class="dashkit-table__tbody">
							<?php foreach ( $rows as $row ) : ?>
								<tr class="dashkit-table__row <?php echo esc_attr( $this->get_row_class( $row ) ); ?>" data-row-id="<?php echo esc_attr( $row['id'] ?? '' ); ?>">
									<?php foreach ( $columns as $col ) : ?>
										<td class="dashkit-table__cell dashkit-table__col--<?php echo esc_attr( $col['key'] ); ?>" data-col="<?php echo esc_attr( $col['key'] ); ?>">
											<?php echo $this->format_cell( $col['key'], $row[ $col['key'] ] ?? '', $row ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
										</td>
									<?php endforeach; ?>

									<?php if ( ! empty( $actions ) ) : ?>
										<td class="dashkit-table__cell dashkit-table__actions">
											<?php $this->render_row_actions( $row, $actions ); ?>
										</td>
									<?php endif; ?>
								</tr>
							<?php endforeach; ?>
						</tbody>
						<?php
						$footer = $this->get_table_footer();
						if ( '' !== $footer ) :
							?>
						<tfoot class="dashkit-table__tfoot">
							<tr><td colspan="<?php echo $this->get_total_columns( $columns, $actions ); ?>"><?php echo $footer; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td></tr>
						</tfoot>
						<?php endif; ?>
					</table>
				</div>

				<?php if ( $opts['pagination'] ) : ?>
					<?php $this->render_pagination( count( $rows ) ); ?>
				<?php endif; ?>

			<?php else : ?>
				<?php $empty_message = $this->get_empty_message(); ?>
				<?php if ( '' !== $empty_message ) : ?>
					<p class="dashkit-table__empty"><?php echo esc_html( $empty_message ); ?></p>
				<?php endif; ?>
			<?php endif; ?>

		</div>
		<?php
	}

	/**
	 * Render the widget shell without fetching rows, for lazy-load mode.
	 *
	 * Outputs the full table structure (header + empty tbody) so the JS
	 * auto-fetch can populate it after page load.
	 *
	 * @since 1.0.0
	 */
	protected function render_lazy_shell(): void {
		$opts    = $this->options;
		$columns = $this->get_visible_columns();
		$actions = $this->get_actions();
		?>
		<div class="<?php echo esc_attr( $this->get_table_css() ); ?>">

			<div class="dashkit-table__wrap">
				<?php $this->render_table_open(); ?>
					<?php $this->render_thead( $columns, $actions ); ?>
					<tbody class="dashkit-table__tbody">
						<tr class="dashkit-table__loading">
							<td colspan="<?php echo (int) $this->get_total_columns( $columns, $actions ); ?>">
								<?php echo esc_html__( 'Loading…', 'dashkit' ); ?>
							</td>
						</tr>
					</tbody>
				</table>
			</div>

			<?php if ( $opts['pagination'] ) : ?>
				<?php $this->render_pagination( 0 ); ?>
			<?php endif; ?>

		</div>
		<?php
	}

	/**
	 * Return the CSS class string for the widget wrapper.
	 *
	 * @since 1.0.0
	 */
	private function get_table_css(): string {
		$css = 'dashkit-table-widget';
		if ( $this->options['striped'] ) {
			$css .= ' dashkit-table--striped';
		}
		return $css;
	}

	/**
	 * Render the opening <table> tag with all data attributes.
	 *
	 * Caller is responsible for closing </table>.
	 *
	 * @since 1.0.0
	 */
	private function render_table_open(): void {
		?>
		<table class="dashkit-table"
				data-widget-id="<?php echo esc_attr( $this->id ); ?>"
				data-page-slug="<?php echo esc_attr( $this->page_slug ); ?>"
				data-rest-nonce="<?php echo esc_attr( wp_create_nonce( 'wp_rest' ) ); ?>">
		<?php
	}

	/**
	 * Render the table <thead> block.
	 *
	 * @since 1.0.0
	 *
	 * @param array<int, array<string, string>>   $columns Visible columns.
	 * @param array<string, array<string, mixed>> $actions Active row actions.
	 */
	private function render_thead( array $columns, array $actions ): void {
		?>
		<thead class="dashkit-table__thead">
			<tr class="dashkit-table__header-row">
				<?php foreach ( $columns as $col ) : ?>
					<th scope="col" class="dashkit-table__th dashkit-table__col--<?php echo esc_attr( $col['key'] ); ?>"
					<?php
					if ( ! empty( $col['width'] ) ) :
						?>
						style="width:<?php echo esc_attr( $col['width'] ); ?>"<?php endif; ?>>
						<?php echo esc_html( $col['label'] ); ?>
					</th>
				<?php endforeach; ?>
				<?php if ( ! empty( $actions ) ) : ?>
					<th class="dashkit-table__th dashkit-table__th--actions">
						<?php echo esc_html( $this->get_actions_label() ); ?>
					</th>
				<?php endif; ?>
			</tr>
		</thead>
		<?php
	}

	/**
	 * Render the pagination controls block.
	 *
	 * @since 1.0.0
	 *
	 * @param int $total Total row count.
	 */
	private function render_pagination( int $total ): void {
		?>
		<div class="dashkit-table__pagination tablenav" data-dashkit-pagination
			data-total="<?php echo (int) $total; ?>">
		</div>
		<?php
	}

	/**
	 * Return total column count including the actions column.
	 *
	 * @since 1.0.0
	 *
	 * @param array<int, array<string, string>>   $columns Visible columns.
	 * @param array<string, array<string, mixed>> $actions Active row actions.
	 */
	private function get_total_columns( array $columns, array $actions ): int {
		return count( $columns ) + ( ! empty( $actions ) ? 1 : 0 );
	}

	/**
	 * Return the actions column header label. Override in subclass.
	 *
	 * @since 1.0.0
	 */
	public function get_actions_label(): string {
		return __( 'Actions', 'dashkit' );
	}

	/**
	 * Return data rows. Override in subclass.
	 *
	 * @since 1.0.0
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function get_data(): array {
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
			'rows'        => $this->get_rendered_rows(),
			'columns'     => $this->get_visible_columns(),
			'has_actions' => ! empty( $this->get_actions() ),
		];
	}

	/**
	 * Return rendered rows for the async response.
	 *
	 * @since 1.0.0
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function get_rendered_rows(): array {
		$columns = $this->get_visible_columns();
		$rows    = $this->get_data();
		$actions = $this->get_actions();
		$result  = [];

		foreach ( $rows as $row ) {
			$rendered = [ '_id' => $row['id'] ?? '' ];
			foreach ( $columns as $col ) {
				$rendered[ $col['key'] ] = $this->format_cell( $col['key'], $row[ $col['key'] ] ?? '', $row );
			}
			if ( ! empty( $actions ) ) {
				ob_start();
				$this->render_row_actions( $row, $actions );
				$rendered['_actions_html'] = (string) ob_get_clean();
			}
			$result[] = $rendered;
		}

		return $result;
	}

	/**
	 * Return column configuration. Override in subclass.
	 *
	 * @since 1.0.0
	 *
	 * @return array<int, array<string, string>>
	 */
	public function get_columns_config(): array {
		return [
			[
				'key'   => 'id',
				'label' => __( 'ID', 'dashkit' ),
			],
			[
				'key'   => 'title',
				'label' => __( 'Title', 'dashkit' ),
			],
			[
				'key'   => 'date',
				'label' => __( 'Date', 'dashkit' ),
			],
		];
	}

	/**
	 * Format a cell value for display. Override in subclass.
	 *
	 * @since 1.0.0
	 *
	 * @param string               $col_key Column key.
	 * @param mixed                $value   Raw cell value.
	 * @param array<string, mixed> $row     Full row data.
	 */
	public function format_cell( string $col_key, mixed $value, array $row ): string {
		return esc_html( (string) $value );
	}

	/**
	 * Render action buttons for a single table row.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed>            $row     Row data.
	 * @param array<int, array<string,mixed>> $actions Active actions for this row.
	 */
	protected function render_row_actions( array $row, array $actions ): void {
		$row_id = (int) ( $row['id'] ?? 0 );
		?>
		<div class="dashkit-actions">
		<?php
		foreach ( $actions as $key => $action ) :
			$icon  = $action['icon'] ?? '';
			$title = $action['title'] ?? '';
			?>
			<div class="dashkit-action-wrap dashkit-action-wrap--<?php echo esc_attr( $key ); ?>">
			<?php
			if ( 'link' === ( $action['type'] ?? '' ) ) :
				$url = is_callable( $action['url'] ?? null )
					? ( $action['url'] )( $row )
					: ( $action['url'] ?? '' );
				?>
				<a href="<?php echo esc_url( $url ); ?>"
					class="dashkit-action dashkit-action--<?php echo esc_attr( $key ); ?>"
					title="<?php echo esc_attr( $title ); ?>">
					<?php if ( $icon ) : ?>
						<i class="ri-<?php echo esc_attr( $icon ); ?>"></i>
					<?php endif; ?>
					<span class="dashkit-action__label"><?php echo esc_html( $title ); ?></span>
				</a>
				<?php
			else :
				$after        = esc_attr( $action['after'] ?? 'notify' );
				$confirm      = ! empty( $action['confirm'] ) ? 'true' : 'false';
				$extra        = esc_attr( $action['class'] ?? '' );
				$endpoint_url = ! empty( $action['endpoint_url'] ) ? esc_url( $action['endpoint_url'] ) : '';
				$method       = strtoupper( $action['method'] ?? 'POST' );
				?>
				<button type="button"
						class="dashkit-action dashkit-action--<?php echo esc_attr( $key ); ?> <?php echo $extra; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>"
						title="<?php echo esc_attr( $title ); ?>"
						data-dashkit-action="<?php echo esc_attr( $key ); ?>"
						data-row-id="<?php echo absint( $row_id ); ?>"
						data-after="<?php echo $after; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>"
						data-confirm="<?php echo $confirm; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>"
						data-method="<?php echo esc_attr( $method ); ?>"
						<?php if ( $endpoint_url ) : ?>
						data-endpoint-url="<?php echo $endpoint_url; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>"
						<?php endif; ?>>
					<?php if ( $icon ) : ?>
						<i class="ri-<?php echo esc_attr( $icon ); ?>"></i>
					<?php endif; ?>
					<span class="dashkit-action__label"><?php echo esc_html( $title ); ?></span>
				</button>
				<?php
			endif;
			?>
			</div>
			<?php
		endforeach;
		?>
		</div>
		<?php
	}

	/**
	 * Return only the visible (selected) columns from config.
	 *
	 * @since 1.0.0
	 *
	 * @return array<int, array<string, string>>
	 */
	public function get_visible_columns(): array {
		$visible    = $this->options['columns'] ?? $this->get_active_columns();
		$config_map = array_column( $this->get_columns_config(), null, 'key' );
		return array_values(
			array_filter(
				array_map( fn( $key ) => $config_map[ $key ] ?? null, $visible ),
				fn( $col ) => null !== $col
			)
		);
	}

	/**
	 * Return the message shown when there are no rows. Override in subclass.
	 *
	 * @since 1.0.0
	 */
	public function get_empty_message(): string {
		return __( 'No data found.', 'dashkit' );
	}

	/**
	 * Return extra CSS classes for a table row. Override in subclass.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $row Row data.
	 */
	public function get_row_class( array $row ): string {
		return '';
	}

	/**
	 * Return HTML for the table footer row. Return empty string to omit the footer.
	 *
	 * @since 1.0.0
	 */
	public function get_table_footer(): string {
		return '';
	}
}
