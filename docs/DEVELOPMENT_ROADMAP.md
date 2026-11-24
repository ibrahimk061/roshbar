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

## ✅ Phase 3 — Payments & order flow (done)

- Payment methods table (Cash, bKash, Nagad, Card, Bank; on/off in Settings). bKash/Nagad need the TrxID.
- POS checkout: server quote → discount (৳ or %, reason required, cashier limit) → VAT (0% default, on top or included) → pay by one or more methods; cash shows change with quick buttons; "Pay later" for dine-in.
- Payments are stored per order (`order_payments`) with tendered cash, change, reference and who received it. Payments must add up exactly to the bill (no customer credit yet).
- Order flow New → Preparing → Ready → Completed (steps can be skipped forward; completing needs full payment), every change in `order_status_histories`.
- Cancel: owner/manager only, reason required, unpaid orders only (paid orders wait for refunds). The order number is kept, never reused.
- Order detail page (items, payments, history, take payment, cancel); order list filters (open, status, not paid).
- Receipt shows discount, VAT, each payment, cash received, change, PAID/DUE/CANCELLED, served by.
- Website is view-only: the contact form was removed (owner's decision).
- 57 automated tests.

## ✅ Phase 3b — Hold / resume (done)

- POS "Hold" parks the current cart with an optional name (e.g. "Table 3"); no order number is used.
- "Held" button shows the count and a list (name, items, type, time, who, total at today's prices) with Resume / Delete.
- Resume removes the held cart in a locked transaction, so two tills cannot resume the same one. If the cart on screen has items it is held first. Items no longer on the menu are left out with a notice.
- Refunds/returns are **not** built: the owner decided the shop gives no refunds. Paid orders cannot be cancelled.
- 63 automated tests.

## Next phases

| # | Phase | Main items |
|---|---|---|
| 2 | Staff extras | Audit log, own-password change screen, staff name on receipt (login + roles already done in slice 2) |
| 4 | Ingredients, recipes, inventory | Units & conversions, recipe per variation, add-on consumption, stock deduction on sale, purchases, suppliers, wastage, costing |
| 5 | Expenses & cash sessions | Opening/closing cash, cash movements, expenses, reconciliation |
| 6 | Dashboard & reports | Daily/weekly/monthly sales, item sales, gross contribution, exports |
| 7 | Offline PWA | Service worker, IndexedDB queue, sync with UUIDs (order UUID already in place) |
| 8 | API v1 + SUNMI V2s Android app | Sanctum tokens, Java app, built-in printer |
| 9 | Security, deployment, backups | Hardening, production setup |

## Known limitations

- Paid orders cannot be cancelled (no refunds by the owner's rule).
- On phones the cart sits below the menu; the POS layout targets a tablet or counter screen first.
- Browser printing depends on the printer driver's paper size being set to 58 mm.
- Website prices are shown rounded to whole taka (as on the old site); the POS and receipt use exact paisa.
- Imported contact-message times assume the old server stored Asia/Dhaka time.
