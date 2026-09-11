# Dashkit

Reusable widget engine for WordPress admin pages.

## Requirements

- PHP >= 8.0

## Usage

This is a Composer package. Add the VCS repository to the consuming project's `composer.json`:

```json
{
  "repositories": [
    {
      "type": "vcs",
      "url": "https://github.com/ernilambar/dashkit"
    }
  ]
}
```

Then install it:

```bash
composer require ernilambar/dashkit
```

Bundle this library in your plugin and `require` its `init.php`. `DashkitBootstrap` elects the highest version across all bundled copies and defines `DASHKIT_DIR`, `DASHKIT_URL`, `DASHKIT_LOADED_VERSION`.

```php
use Nilambar\Dashkit\Core\Manager;
use Nilambar\Dashkit\Core\PageContext;
use Nilambar\Dashkit\Core\Registry;

Registry::instance()->register_type( 'orders', MyOrdersWidget::class );

$context = new PageContext( 'my-page', [
	'capability' => 'manage_options',
	'zones'      => [ 'top', 'main' ],
] );

$manager = new Manager( $context );
$manager->add_widget( 'orders', 'recent-orders', 'main' );
$manager->init();

// in your admin page markup
$manager->render_layout();
```

## Widget types

| Slug              | Base class             | Implement                                                              |
| :---------------- | :--------------------- | :--------------------------------------------------------------------- |
| `tabular`         | `TabularWidget`        | `get_data()`, `get_columns_config()`, `format_cell()`, `get_actions()` |
| `chart`           | `ChartWidget`          | `get_data()` (`labels` + `datasets`), `get_chart_type()`               |
| `progress-circle` | `ProgressCircleWidget` | `get_data()` (`value`, `caption`, `percentage`)                        |

All types extend `BaseWidget` and require `get_widget_name()` and `render()`. Add `get_options_schema()` for a user-editable options panel, or `get_widget_config()` for developer-locked values. Return `[ 'lazy' => true ]` from `get_widget_config()` to load rows via REST after page load.

## License

[MIT](https://opensource.org/licenses/MIT)
