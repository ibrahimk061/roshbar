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

## Next phases

| # | Phase | Main items |
|---|---|---|
| 2 | Login, roles, staff | Auth, Owner / Manager / Cashier roles + permissions, protect admin, user on orders, audit log |
| 3 | Payments & order flow | Cash / bKash / Nagad / Card, split & partial, change due on receipt, discount, VAT (after decision), order status flow (New → Preparing → Ready → Completed), cancel with reason, hold/resume |
| 4 | Ingredients, recipes, inventory | Units & conversions, recipe per variation, add-on consumption, stock deduction on sale, purchases, suppliers, wastage, costing |
| 5 | Expenses & cash sessions | Opening/closing cash, cash movements, expenses, reconciliation |
| 6 | Dashboard & reports | Daily/weekly/monthly sales, item sales, gross contribution, exports |
| 7 | Offline PWA | Service worker, IndexedDB queue, sync with UUIDs (order UUID already in place) |
| 8 | API v1 + SUNMI V2s Android app | Sanctum tokens, Java app, built-in printer |
| 9 | Security, deployment, backups | Hardening, production setup |

## Known limitations of slice 1

- No login: anyone who can open the URL can use the admin. Local / trusted machine only.
- No payment capture yet; orders are saved with status `new` and the receipt shows totals only.
- Discount and VAT fields exist (always 0) and are not editable yet.
- On phones the cart sits below the menu; the POS layout targets a tablet or counter screen first.
- Browser printing depends on the printer driver's paper size being set to 58 mm.
