# Working rules for this repo

- Read `docs/JUICE_BAR_POS_BLUEPRINT.md`, `docs/DEVELOPMENT_ROADMAP.md` and `docs/DECISIONS.md` before changing code.
- Work one phase / slice at a time; update the roadmap and decisions docs in the same change.
- Money: `DECIMAL(12,2)` columns, `decimal:2` casts, and `App\Support\Money` (brick/math) for arithmetic. Never PHP floats.
- Prices, totals, discounts and tax are always recalculated on the server. The client sends IDs, quantities and options only.
- Order numbers come only from `App\Services\OrderNumberGenerator` inside a DB transaction.
- Timestamps are stored in UTC; business dates and displayed times use `config('pos.timezone')` (Asia/Dhaka).
- Order lines keep snapshots (product name, variant name, prices, add-on names); never read history from the live menu.
- Products/variants referenced by orders are deactivated, never hard-deleted.
- Business logic lives in `app/Services`; controllers stay thin; validation in Form Requests.
- Run `php artisan test` in `juice-bar-web/` and report real results.
