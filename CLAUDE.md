# Dashkit

Reusable widget engine for WordPress admin pages. Bundles multiple copies safely via a version-election bootstrap in `init.php`.

## Commands

### JS
```bash
pnpm build       # production build
pnpm format      # prettier (js/css/json)
```

### PHP
```bash
composer lint    # parallel-lint + phpcs
composer format  # phpcbf auto-fix
```

### Strings

Dashkit ships no text domain. Every user-facing string has an English default in `src/Core/Strings.php` and is overridden with the `dashkit_strings` filter:

```php
add_filter( 'dashkit_strings', function ( array $strings ) {
    $strings['actions'] = 'Aktionen';
    return $strings;
} );
```

- When adding a new user-facing string, add its default to `Strings::defaults()` and read it back with `Strings::get( 'key' )`.
- JS-facing strings are exposed to the frontend via `dashkitConfig.i18n` (same keys as the strings config).
- Placeholders use `%s` (e.g. `request_failed`).

## Widget system

- **BaseWidget** — abstract base; subclasses must implement `get_widget_name()` and `render()`
- **TabularWidget** — extends BaseWidget; override `get_data()`, `get_columns_config()`, `format_cell()`, `get_actions()`
- **ChartWidget** — extends BaseWidget; must implement `get_data()` (returns `labels` + `datasets`) and `get_chart_type()` (e.g. `'bar'`, `'line'`)
- **ProgressCircleWidget** — extends BaseWidget; must implement `get_data()` (returns array of items with `value`, `caption`, `percentage`)
- `get_data()` is the convention for the primary data-supply method across all widget types — always name it `get_data()` in new widget types
- `get_default_options()` is optional — omit it if the widget has no default options
- `get_options_schema()` is optional — omit it if the widget has no user-editable options
- `get_widget_config()` returns developer-locked keys (merged last, never saved by the user)
- Lazy-load mode: return `['lazy' => true]` from `get_widget_config()`; JS fetches rows via REST after page load

## Quality gate

Before marking any task complete:
- Run `composer lint` and ensure it exits with no errors. Run `composer format` to resolve fixable PHPCS errors.
- Run `pnpm build` to bundle the assets.
- Run `pnpm format` to auto-format files with Prettier.
