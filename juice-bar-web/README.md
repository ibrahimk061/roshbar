# Juice Bar POS — Web (Laravel)

Laravel 13 · PHP 8.3+ · MySQL · Blade · Bootstrap 5 (served locally from `public/vendor`, no CDN).

## Run it locally

```bash
composer install
cp .env.example .env        # set your MySQL credentials (or DB_CONNECTION=sqlite)
php artisan key:generate
php artisan migrate --seed  # creates tables + a sample menu
php artisan storage:link    # product images
php artisan serve           # open http://127.0.0.1:8000/pos
```

Tests: `php artisan test` (uses in-memory SQLite).

## What is in this slice

| Screen | URL |
|---|---|
| POS (touch) | `/pos` |
| Orders list + reprint | `/orders` |
| Receipt (58mm print preview) | `/orders/{id}/receipt` |
| Products / Categories / Add-ons | `/admin/products`, `/admin/categories`, `/admin/addons` |
| Shop & receipt settings | `/admin/settings` |

See `../docs/DEVELOPMENT_ROADMAP.md` for what comes next and `../docs/DECISIONS.md` for defaults and open questions.

> There is no login yet (Phase 2). Only run this on a trusted machine.
