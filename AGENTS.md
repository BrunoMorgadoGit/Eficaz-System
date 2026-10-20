# Repository Guidelines

## Project Structure & Module Organization

This Laravel 12 application keeps the B2B portal in one repository. HTTP controllers, requests, and middleware live in `app/Http/`; business rules live in `app/Services/`, and Eloquent models in `app/Models/`. Define web routes in `routes/web.php`. Blade pages and reusable components are in `resources/views/`; CSS, JavaScript, and fonts are under `resources/`, with public assets in `public/`. Database changes belong in `database/migrations/`, demo data in `database/seeders/`, and PHPUnit tests in `tests/Unit/` or `tests/Feature/`.

## Build, Test, and Development Commands

Use PHP 8.2+, Composer, Node.js 22+, and the local MySQL service. Run `composer install` and `npm ci` to install locked dependencies. Copy `.env.example` to `.env`, start MySQL with `docker compose up -d`, then run `php artisan key:generate` and `php artisan migrate --seed` for a local demo database. `composer dev` starts the Laravel server and Vite together; `npm run build` compiles production assets. `composer test` clears cached configuration and runs the test suite.

## Coding Style & Naming Conventions

Follow `.editorconfig`: UTF-8, LF endings, a final newline, four-space indentation, and two spaces in YAML. Keep PHP classes under the existing `App\` PSR-4 namespace; use descriptive names such as `OrderService`, `StoreProductRequest`, and `OrderController`. Follow existing Blade component names such as `status-badge.blade.php`. Check PHP style with `vendor/bin/pint --test`; run `vendor/bin/pint path/to/file.php` to format a changed PHP file.

## Testing Guidelines

PHPUnit 11 discovers `*Test.php` files in `tests/Unit/` and `tests/Feature/`. Name methods by behavior, for example `test_roles_are_separated_at_the_route_level`. Add feature coverage for routes, authorization, validation, and cart-to-order behavior; use unit tests for isolated pricing rules. Run `composer test` before opening a PR. Tests use in-memory SQLite, so validate MySQL-specific behavior separately. No coverage percentage is configured.

## Commit & Pull Request Guidelines

Recent commits use short prefixes such as `docs:` and `refactor:`; use a concise `type: summary` subject when practical. Target `develop` for integration work. In a PR, explain the behavior changed, link an issue when available, list the commands run, and include screenshots for Blade or CSS changes. Call out migrations or configuration changes explicitly.

## Configuration & Security

Keep local credentials in ignored `.env` files and update `.env.example` when configuration requirements change. Demo accounts and seeded data are for local use. Preserve the existing authentication, CSRF protection, and `ADMIN`/`REVENDEDOR` route boundaries when changing portal flows.
