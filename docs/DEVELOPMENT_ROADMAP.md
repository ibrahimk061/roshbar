# Development roadmap

The blueprint's phase order (section 12.2) is kept, but we started with a thin
end-to-end slice so the counter flow can be seen early.

## ✅ Slice 1 — Menu → cart → receipt (done)

- Laravel 13 project in `juice-bar-web/`, Bootstrap 5 + icons served locally, design tokens in `public/css/app.css`.
- Menu: categories, products (SKU, image, active, available/sold-out, display order), variations with their own price, add-ons with price, per-product allowed add-ons. Admin screens for all of them. Sample menu seeder.
- POS screen (`/pos`): category tabs, search, product grid, item modal (size, sugar 0–100%, ice, add-ons, note, quantity), cart with +/−/remove, takeaway / dine-in, cart kept in the browser if the page reloads.
- Place order: server re-prices everything (BigDecimal), validates availability and allowed add-ons, saves order + line snapshots, idempotent by client UUID.
- Official order number `RB-DD-MM-YY-A0001` (100 per letter block, A…Z, AA…), per business date in Asia/Dhaka, row-locked sequence.
- Receipt: 58 mm thermal layout, browser print, preview opens right after checkout; reprint from `/orders`.
- 29 automated tests (order numbers, midnight boundary, pricing, tampering, idempotency, snapshots, admin CRUD, receipt).

## ✅ Slice 2 — roshbar.com website + staff login (done)

- Old core-PHP roshbar.com site ported into the same Laravel app: Home (featured, seasonal by month, categories, visit), Menu with category filter, Drink detail, Our story, Contact (honeypot + 1 msg/min), English/বাংলা toggle with Bangla digits, SEO tags, same design.
- One product table for website and POS: products got Bangla name/description, ingredients, colour, garnish, calories, seasonal months, featured, "show on website". Sizes on the website = POS variations.
- Staff login at `/login` (5 failures → 15 min block), roles Owner / Manager / Cashier, every POS/admin route protected. Owner manages staff.
- Admin: website messages (unread, filters, call/WhatsApp), shop details (both languages, map, WhatsApp) shared by website and receipt.
- `php artisan roshbar:import-legacy` (read-only, repeatable, `--dry-run`).
- 47 automated tests.

## Next phases

| # | Phase | Main items |
|---|---|---|
| 2 | Staff extras | Audit log, own-password change screen, staff name on receipt (login + roles already done in slice 2) |
| 3 | Payments & order flow | Cash / bKash / Nagad / Card, split & partial, change due on receipt, discount, VAT (after decision), order status flow (New → Preparing → Ready → Completed), cancel with reason, hold/resume |
| 4 | Ingredients, recipes, inventory | Units & conversions, recipe per variation, add-on consumption, stock deduction on sale, purchases, suppliers, wastage, costing |
| 5 | Expenses & cash sessions | Opening/closing cash, cash movements, expenses, reconciliation |
| 6 | Dashboard & reports | Daily/weekly/monthly sales, item sales, gross contribution, exports |
| 7 | Offline PWA | Service worker, IndexedDB queue, sync with UUIDs (order UUID already in place) |
| 8 | API v1 + SUNMI V2s Android app | Sanctum tokens, Java app, built-in printer |
| 9 | Security, deployment, backups | Hardening, production setup |

## Known limitations

- No payment capture yet; orders are saved with status `new` and the receipt shows totals only.
- Discount and VAT fields exist (always 0) and are not editable yet.
- On phones the cart sits below the menu; the POS layout targets a tablet or counter screen first.
- Browser printing depends on the printer driver's paper size being set to 58 mm.
- Website prices are shown rounded to whole taka (as on the old site); the POS and receipt use exact paisa.
- Imported contact-message times assume the old server stored Asia/Dhaka time.
