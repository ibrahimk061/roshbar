# Roshbar — website + POS (Laravel)

One Laravel app serves both:

- **roshbar.com** public website: Home, Menu, Drink detail, Our story, Contact (English / বাংলা)
- **Staff area** behind `/login`: touch POS, orders and receipts, menu admin, website messages, shop settings, staff accounts

The website and the POS read the **same product table**, so a price or a sold-out change shows up everywhere at once.

Laravel 13 · PHP 8.3+ · MySQL · Blade · Bootstrap 5 for staff screens (served from `public/vendor`).

## Run it locally

```bash
composer install
cp .env.example .env              # set MySQL credentials (or DB_CONNECTION=sqlite)
php artisan key:generate
php artisan migrate --seed        # starter menu + an owner account (password printed once)
php artisan storage:link          # product photos
php artisan serve                 # website: http://127.0.0.1:8000  ·  staff: /login
```

Set `SEED_OWNER_EMAIL` / `SEED_OWNER_PASSWORD` before seeding to choose the first owner login.

Tests: `php artisan test` (in-memory SQLite).

## URLs

| Who | URL | What |
|---|---|---|
| Public | `/`, `/menu`, `/menu/{slug}`, `/about`, `/contact` | Website (`?lang=bn` / `?lang=en`) |
| Staff | `/login` (old `/admin/login` redirects here) | Sign in |
| All staff | `/pos`, `/orders`, `/orders/{id}/receipt` | POS, order list, 58 mm receipt |
| Owner, Manager | `/admin/products`, `/admin/categories`, `/admin/addons`, `/admin/messages`, `/admin/settings` | Menu + website content, contact messages, shop details |
| Owner | `/admin/users` | Staff accounts and roles |

## Moving the live site's data in

`php artisan roshbar:import-legacy` copies drinks, sizes/prices, categories, shop details, contact
messages and admin users from the old roshbar.com database. It only **reads** the old database.
Fill `LEGACY_DB_*` (and `LEGACY_UPLOADS_PATH` for photos) in `.env` from the old site's `.env`,
run with `--dry-run` first to see the counts, then without it. Running it twice does not duplicate anything.
Old admin passwords keep working (same bcrypt hash); imported admins become **Owner**.

## Code map

```
app/Http/Controllers/Site/      public website
app/Services/Site/Catalog.php   menu → website rows
resources/views/site/           website templates (ported from the old site, plain PHP views)
resources/site-lang/            website text, en + bn
public/site/                    website css/js/img
app/Support/site_helpers.php    t(), tr(), num(), money(), setting() … used by the website views
app/Services/OrderService.php   POS order creation (server-side pricing)
app/Console/Commands/ImportLegacySite.php
```
