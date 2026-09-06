# Dashkit

Reusable widget engine for WordPress admin pages. PHP 8.0+ backend with a Vite-built vanilla JS + Chart.js frontend.

## Setup

```bash
npm install
composer install
```

Requires Node.js >= 22 and PHP >= 8.0.

## Commands

```bash
npm run build          # Build JS/CSS assets via Vite (outputs to assets/)
npm run format         # Format JS/CSS/JSON with Prettier (WordPress config)
composer run lint      # Run PHP lint + PHPCS (WordPress coding standards)
composer run format    # Auto-fix PHP with PHP Code Beautifier
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

## Conventions

- **Namespace**: All PHP lives under `Nilambar\Dashkit` (PSR-4 mapped to `src/`).
- **Strict types**: Every PHP file opens with `declare(strict_types=1);`.
- **Widget architecture**: Extend `BaseWidget`, register via `Registry`, render into zones via `Manager`.
- **Bootstrap pattern**: `init.php` uses `DashkitBootstrap` for version election across bundled copies — never edit directly unless changing the election mechanism.
- **Asset build**: JS entry is `resources/js/dashkit.js`, CSS is `resources/css/dashkit.css`. Vite outputs to `assets/` as IIFE bundle.
- **WordPress standards**: All output must be escaped (`esc_attr`, `esc_html`, `esc_url`). Use `_doing_it_wrong()` for developer-facing errors.

## Quality gate

Before marking any task complete:
- Run `composer lint` and ensure it exits with no errors. Run `composer format` to resolve fixable PHPCS errors.
- Run `pnpm build` to bundle the assets.
- Run `pnpm format` to auto-format files with Prettier.
