# Laravel PHP Project Starter

A clean Laravel 12 starter scaffold intended to be opened and edited in Visual Studio Code.

## Requirements

- PHP 8.2 or newer
- Composer
- Node.js and npm (for frontend asset building)
- PHP extensions required by Laravel

## Install

Open this folder in VS Code, then open **Terminal → New Terminal** and run:

```bash
composer install
```

Create your environment file and app key:

```bash
cp .env.example .env
php artisan key:generate
```

Create the SQLite database file (if it does not exist):

```bash
# macOS / Linux
touch database/database.sqlite

# Windows PowerShell
New-Item -ItemType File -Force database/database.sqlite
```

Run database migrations and seed the existing portfolio, product catalog, and homepage defaults:

```bash
php artisan migrate --seed
```

Install and build frontend assets:

```bash
npm install
npm run build
```

Start the Laravel development server:

```bash
php artisan serve
```

Visit http://127.0.0.1:8000

For frontend hot reload, use a second terminal:

```bash
npm run dev
```

## Admin access

The footer links open separate sign-in pages at `/admin/login` and `/superadmin/login`. Ensure the database configured in `.env` is running, then apply migrations before creating staff accounts:

```bash
php artisan migrate
php artisan staff:create superadmin
```

The command prompts for the initial SuperAdmin name, email, and a password of at least 12 characters. Password entry is hidden and passwords are hashed; no default credentials are included in the application. Only an authenticated SuperAdmin can nominate Admin accounts from the SuperAdmin dashboard. The bootstrap command cannot create Admin accounts.

Admins can manage portfolio projects, product details and order links, and quote inquiries. The production tracker assigns staff from Modeling through Shipped, locks past stages, and records each assignment change with its timestamp and editor. Order creation is protected against duplicate submissions from the same form, including retries with multiple attached reference files. Each inquiry creates an in-dashboard notification for every Admin and SuperAdmin account; clicking it opens the inquiry and marks it as read. Quote submissions are stored in the database; uploaded inspiration files remain private and can only be downloaded from the authenticated dashboard. SuperAdmins can also edit homepage copy, the logo, and the brand accent color.

For an existing installation, run the new migrations and initialize the default content once:

```bash
php artisan migrate
php artisan db:seed
php artisan storage:link
```

## Useful commands

```bash
php artisan make:controller ExampleController
php artisan make:model Example -m
php artisan make:migration create_examples_table
php artisan route:list
php artisan migrate
php artisan migrate:rollback
php artisan test
```

## Main folders

- `app/` — application PHP code (controllers, models, providers)
- `bootstrap/` — framework bootstrap
- `config/` — application configuration (Laravel defaults are supplied by the framework)
- `database/` — migrations, factories, seeders
- `public/` — web entry point
- `resources/` — Blade views, CSS, JavaScript
- `routes/` — web and console routes
- `storage/` — logs, cache, sessions, compiled views
- `tests/` — automated tests

This is a project scaffold, not an installed Laravel runtime. Composer downloads Laravel and its dependencies into `vendor/` during installation.
