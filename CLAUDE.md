# Dashkit

Reusable widget engine for WordPress admin pages. Bundles multiple copies safely via a version-election bootstrap in `init.php`.

## Package manager

Always use **pnpm** — never npm (creates conflicting `package-lock.json`).

## Project structure

```
src/
  API/        — REST route registration (REST_API.php)
  Core/       — Manager, Registry, PageContext, OptionsStore
  Widget/     — BaseWidget, TabularWidget, ChartWidget, ProgressCircleWidget
resources/
  js/         — dashkit.js (Vite entry)
  css/        — dashkit.css
assets/       — compiled output
```

Namespace root: `Nilambar\Dashkit\` (PSR-4, composer autoloaded).

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

### i18n
```bash
composer pot        # regenerate dashkit.pot
composer update-po  # sync .po files from .pot
composer make-mo    # compile .po → .mo
```

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

## PHP requirements

- PHP >= 8.0
- Coding standard: `ernilambar/coding-standard` (PHPCS)

## Definition of done

Before marking any task complete:
- Run `composer lint` and ensure it exits with no errors. Run `composer format` to resolve fixable PHPCS errors.
- Run `pnpm build` to bundle the assets.
- Run `pnpm format` to auto-format files with Prettier.
