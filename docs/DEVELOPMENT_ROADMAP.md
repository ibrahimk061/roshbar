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

## ✅ Phase 6 — Sales report and daily expenses (done)

The owner dropped ingredients, recipes and stock ("only items, price, qty") and asked for a sales report and daily expenses. Cash-drawer sessions are not built.

- **Expenses** (all staff, nav "Expenses"): amount, category (Fruit & bazar, Ice, Milk, Cups & packaging, Rent, Electricity, Salary, Transport, Other; owner/manager can add or hide), paid by, note. Cashiers add today's expenses only and see today; owner/manager can pick a past date, edit and delete.
- **Sales report** (owner/manager, nav "Reports"): Today / Yesterday / Last 7 days / This month / Last month or any date range. Shows sales, expenses, sales − expenses, average order, items sold (item, size, qty, amount) and add-ons, discounts/VAT, payments by method, not-paid amount, a by-day table (sales, expenses, sales − expenses) and sales by staff. Cancelled orders are counted but left out of totals.
- **Excel (CSV)** download of the same report (UTF-8 with BOM so Excel shows it right).
- 70 automated tests.

## ✅ Phase 2b — Staff extras (done)

- **Change password**: every staff member, from the name menu (current password, new twice, min 8).
- **Switched-off staff** are signed out on their next click (not only at the next login).
- **Activity log** (owner only, name menu → Activity), filter by staff and action. It records:
  - sign-ins, wrong passwords for known accounts, and sign-outs
  - discounts, cancellations and later payments
  - expenses added, edited or deleted
  - product added or price changes
  - settings changes
  - staff added or edited
  - own password changes

  Rows are append-only.
- Staff name on the receipt was already there ("Served by").
- 76 automated tests.

## ✅ Phase 7 — Offline POS (done)

- A service worker (`public/sw.js`) keeps the POS page and its files. If the internet drops, the POS still opens and works on that device, as long as it was opened online once.
- Taking an order offline:
  - The payment screen shows totals worked out on the device and an "Offline" note.
  - Confirming saves the order on the device with a temporary number (OFF-001…) and prints an offline receipt that says the order number comes later.
- Sending offline orders:
  - Orders are sent oldest first when the connection is back, and every 20 s.
  - The same uuid means a re-send never makes a duplicate.
  - An expired CSRF token is refreshed automatically (`/pos/csrf`). If the session was signed out, the POS asks to sign in again.
- An offline order keeps the time it was taken, and its business date comes from that time, so a 23:30 order sent next morning counts for the night before. The server trusts this time only within the last 3 days and not in the future. The order is marked `was_offline` and stores `synced_at`.
- Prices and discount rules are checked again on the server when the order arrives (see DECISIONS). An order the server refuses (for example, an item removed from the menu) stays in the "Offline orders" list with the reason, where you can Retry or Delete it.
- The pill at the top of the cart shows Offline / To send / Sending with a count.
- 80 automated tests.

## 🟡 Phase 9 prep — Deployment ready (built, not deployed)

- Step-by-step cPanel guide: [DEPLOY.md](DEPLOY.md). Nothing has been run on the live server.
- `php artisan roshbar:check` lists what must be right before going live (debug off, https, key, database, writable folders).
- `php artisan roshbar:backup` saves a gzipped database copy to `storage/app/backups/` and keeps 30 days. It runs every night at 23:45 Dhaka time once the cPanel cron for `schedule:run` is added.
- Every page sends security headers (nosniff, SAMEORIGIN, Referrer-Policy, Permissions-Policy, and HSTS over https).
- `.env.production.example` holds placeholders only.
- 84 automated tests.

## 🟡 Phase 8 — API v1 + SUNMI V2s app (in progress)

- API v1 for the app is done: token sign-in, menu, quote, place order (safe to retry), today's orders, receipt lines. See [API.md](API.md).
- The web POS and the app share one menu builder (`PosMenu`), so both always show the same items.
- Receipt lines for the built-in printer are made on the server (`ReceiptLines`).
- Android app in `sunmi-app/` (Java): sign-in, menu, item options, cart, discount, payment, printing on the built-in printer, reprint of today's orders, and an offline queue that sends orders when the internet is back. See [sunmi-app/README.md](../sunmi-app/README.md).
- GitHub Actions builds the APK on every change to `sunmi-app/` (Actions → Android app (SUNMI) → Artifacts).
- Still to try on the real V2s: printing, paper width, and the offline flow.
- 93 automated tests.

## Next phases

| # | Phase | Main items |
|---|---|---|
| 8 | API v1 + SUNMI V2s Android app | Sanctum tokens, Java app, built-in printer |
| 9 | Go live | Follow DEPLOY.md when the owner says so |

## Known limitations

- Paid orders cannot be cancelled (no refunds by the owner's rule).
- On phones the cart sits below the menu; the POS layout targets a tablet or counter screen first.
- Browser printing depends on the printer driver's paper size being set to 58 mm.
- Website prices are shown rounded to whole taka (as on the old site); the POS and receipt use exact paisa.
- Imported contact-message times assume the old server stored Asia/Dhaka time.
