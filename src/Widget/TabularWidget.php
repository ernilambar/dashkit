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
 *   get_rows()           — supply your data
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
	 * Return default options.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, mixed>
	 */
	public function get_default_options(): array {
		return [
			'per_page' => 10,
		];
	}

	/**
	 * Return developer-controlled config that always overrides stored options.
	 *
	 * Override in subclasses to enable filterable, pagination, etc.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, mixed>
	 */
	public function get_widget_config(): array {
		return [
			'columns'    => $this->get_active_columns(),
			'filterable' => false,
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
	 * Return the options schema.
	 *
	 * @since 1.0.0
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function get_options_schema(): array {
		return [
			$this->get_common_field( 'per_page' ),
		];
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
		$rows    = $this->get_rows();
		$actions = $this->get_actions();

		$css = 'dashkit-table-widget';
		if ( $opts['striped'] ) {
			$css .= ' dashkit-table--striped';
		}
		?>
		<div class="<?php echo esc_attr( $css ); ?>">

			<?php if ( ! empty( $rows ) ) : ?>

				<?php if ( $opts['filterable'] ) : ?>
					<div class="dashkit-table__toolbar">
						<input
							type="search"
							class="dashkit-table__search"
							placeholder="<?php echo esc_attr__( 'Search…', 'dashkit' ); ?>"
							data-dashkit-table-search
						/>
						<span class="dashkit-table__count" data-dashkit-row-count>
							<?php echo count( $rows ); ?> <?php echo esc_html__( 'items', 'dashkit' ); ?>
						</span>
					</div>
				<?php endif; ?>

				<div class="dashkit-table__wrap">
					<table class="dashkit-table"
							data-per-page="<?php echo (int) $opts['per_page']; ?>"
							data-widget-id="<?php echo esc_attr( $this->id ); ?>"
							data-page-slug="<?php echo esc_attr( $this->page_slug ); ?>"
							data-rest-nonce="<?php echo esc_attr( wp_create_nonce( 'wp_rest' ) ); ?>">
						<thead>
							<tr>
								<?php foreach ( $columns as $col ) : ?>
									<th scope="col">
										<?php echo esc_html( $col['label'] ); ?>
									</th>
								<?php endforeach; ?>
								<?php if ( ! empty( $actions ) ) : ?>
									<th class="dashkit-table__th--actions">
										<?php echo esc_html( $this->get_actions_label() ); ?>
									</th>
								<?php endif; ?>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $rows as $row ) : ?>
								<tr data-row-id="<?php echo esc_attr( $row['id'] ?? '' ); ?>">
									<?php foreach ( $columns as $col ) : ?>
										<td data-col="<?php echo esc_attr( $col['key'] ); ?>">
											<?php echo $this->format_cell( $col['key'], $row[ $col['key'] ] ?? '', $row ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
										</td>
									<?php endforeach; ?>

									<?php if ( ! empty( $actions ) ) : ?>
										<td class="dashkit-table__actions">
											<?php $this->render_row_actions( $row, $actions ); ?>
										</td>
									<?php endif; ?>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>

				<?php if ( $opts['pagination'] ) : ?>
					<div class="dashkit-table__pagination tablenav" data-dashkit-pagination
						data-total="<?php echo count( $rows ); ?>"
						data-per-page="<?php echo (int) $opts['per_page']; ?>">
					</div>
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
	private function render_lazy_shell(): void {
		$opts    = $this->options;
		$columns = $this->get_visible_columns();
		$actions = $this->get_actions();

		$css = 'dashkit-table-widget';
		if ( $opts['striped'] ) {
			$css .= ' dashkit-table--striped';
		}
		?>
		<div class="<?php echo esc_attr( $css ); ?>">

			<?php if ( $opts['filterable'] ) : ?>
				<div class="dashkit-table__toolbar">
					<input
						type="search"
						class="dashkit-table__search"
						placeholder="<?php echo esc_attr__( 'Search…', 'dashkit' ); ?>"
						data-dashkit-table-search
					/>
					<span class="dashkit-table__count" data-dashkit-row-count></span>
				</div>
			<?php endif; ?>

			<div class="dashkit-table__wrap">
				<table class="dashkit-table"
						data-per-page="<?php echo (int) $opts['per_page']; ?>"
						data-widget-id="<?php echo esc_attr( $this->id ); ?>"
						data-page-slug="<?php echo esc_attr( $this->page_slug ); ?>"
						data-rest-nonce="<?php echo esc_attr( wp_create_nonce( 'wp_rest' ) ); ?>">
					<thead>
						<tr>
							<?php foreach ( $columns as $col ) : ?>
								<th scope="col">
									<?php echo esc_html( $col['label'] ); ?>
								</th>
							<?php endforeach; ?>
							<?php if ( ! empty( $actions ) ) : ?>
								<th class="dashkit-table__th--actions">
									<?php echo esc_html( $this->get_actions_label() ); ?>
								</th>
							<?php endif; ?>
						</tr>
					</thead>
					<tbody>
						<tr class="dashkit-table__loading">
							<td colspan="<?php echo count( $columns ) + ( ! empty( $actions ) ? 1 : 0 ); ?>">
								<?php echo esc_html__( 'Loading…', 'dashkit' ); ?>
							</td>
						</tr>
					</tbody>
				</table>
			</div>

			<?php if ( $opts['pagination'] ) : ?>
				<div class="dashkit-table__pagination tablenav" data-dashkit-pagination
					data-total="0"
					data-per-page="<?php echo (int) $opts['per_page']; ?>">
				</div>
			<?php endif; ?>

		</div>
		<?php
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
	public function get_rows(): array {
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
		$rows    = $this->get_rows();
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
				$rendered['_actions_html'] = ob_get_clean();
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

		foreach ( $actions as $key => $action ) :
			$icon = $action['icon'] ?? '';

			if ( 'link' === ( $action['type'] ?? '' ) ) :
				$url = is_callable( $action['url'] ?? null )
					? ( $action['url'] )( $row )
					: ( $action['url'] ?? '' );
				?>
				<a href="<?php echo esc_url( $url ); ?>"
					class="dashkit-action dashkit-action--<?php echo esc_attr( $key ); ?>"
					title="<?php echo esc_attr( $action['title'] ); ?>">
					<?php if ( $icon ) : ?>
						<i class="ri-<?php echo esc_attr( $icon ); ?>"></i>
					<?php endif; ?>
					<span class="dashkit-action__label"><?php echo esc_html( $action['title'] ); ?></span>
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
						title="<?php echo esc_attr( $action['title'] ); ?>"
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
					<span class="dashkit-action__label"><?php echo esc_html( $action['title'] ); ?></span>
				</button>
				<?php
			endif;
		endforeach;
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
	 * Return a reusable field definition for a named common field type.
	 *
	 * Reduces boilerplate in get_options_schema() implementations. Supported
	 * types: 'per_page', 'ordering'.
	 *
	 * @since 1.0.0
	 *
	 * @param string               $type    Field type key.
	 * @param array<string, mixed> $options Overrides merged into the returned definition.
	 * @return array<string, mixed>
	 */
	protected function get_common_field( string $type, array $options = [] ): array {
		$definitions = [
			'per_page' => [
				'key'     => 'per_page',
				'label'   => __( 'Rows per Page', 'dashkit' ),
				'type'    => 'buttonset',
				'choices' => [
					[
						'value' => 5,
						'label' => '5',
					],
					[
						'value' => 10,
						'label' => '10',
					],
					[
						'value' => 20,
						'label' => '20',
					],
					[
						'value' => 30,
						'label' => '30',
					],
				],
			],
			'ordering' => [
				'key'     => 'ordering',
				'label'   => __( 'Ordering', 'dashkit' ),
				'type'    => 'buttonset',
				'choices' => [
					[
						'value' => 'DESC',
						'label' => __( 'Descending', 'dashkit' ),
					],
					[
						'value' => 'ASC',
						'label' => __( 'Ascending', 'dashkit' ),
					],
				],
			],
		];

		$base = $definitions[ $type ] ?? [];
		return array_merge( $base, $options );
	}
}
