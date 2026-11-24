# Decisions and open questions

## Defaults used in slice 1 (change any of these on request)

| Topic | Default | Where |
|---|---|---|
| Order number | `RB-DD-MM-YY-A0001`, 100 per letter block, after Z comes AA, AB… (no upper limit) | `OrderNumberGenerator` |
| Prefix | `RB`, editable in Admin → Settings (affects new orders only) | settings table |
| Business day | Starts at 00:00 Asia/Dhaka | `POS_DAY_START_HOUR` |
| Money | DECIMAL(12,2), BigDecimal math, HALF_UP rounding to 2 places | `App\Support\Money` |
| Line price | (variant price + sum of add-ons) × quantity | `OrderService` |
| Receipt currency | "Tk" on paper (thermal fonts often lack ৳), ৳ on screen | receipt view |
| Sugar / Ice defaults | 100% sugar, Regular ice | `config/pos.php` |
| Removed variations | Deactivated and renamed "(old #id)", never deleted | `ProductController` |
| Duplicate submit | Same cart UUID returns the same order | `OrderService` |
| Website + POS | One Laravel app, one product table (owner chose this on 2026-11-24) | — |
| Imported old admins | Become Owner, keep their password | `roshbar:import-legacy` |
| Roles | Owner: all · Manager: POS + admin, not staff · Cashier: POS + orders | `routes/web.php` |
| Brand colours | White + green everywhere (website and POS share #16834A); old pink/berry removed 2026-11-24. Website tokens in `public/site/css/app.css` `:root`, POS tokens in `public/css/app.css` | CSS |
| VAT | 0% until the owner sets it; can be added on top or included in prices (Settings) | `OrderService::totals` |
| Discount | ৳ or %, on the whole bill, reason required; cashier max 10% (Settings), manager/owner no limit | `OrderService` |
| Rounding | Discount % and VAT rounded HALF_UP to 2 decimals, VAT on the bill after discount | `OrderService` |
| Payments | Must equal the bill exactly; extra cash becomes change; no credit/due yet; one cash line per payment | `OrderService::preparePayments` |
| Status flow | Forward only, can skip steps; Completed needs full payment | `OrderStatusService` |
| Cancel | Owner/manager, reason required, unpaid orders only | `OrderStatusService` |
| Website | View only: no ordering and no contact form (owner, 2026-11-24) | — |
| Live data | Old site's `.env` and database are never changed; import only reads | — |

## Open questions for the owner

1. **Order number capacity** – Is AA0001… after Z0100 (2,600+ orders/day) fine, or should it wrap/fail?
2. **Business-day cutoff** – Does the shop stay open past midnight? If so, which hour should start the new day (e.g. 4 AM)?
3. **Costing method** – Latest purchase price, weighted average, or FIFO for ingredient cost? (Phase 4)
4. **VAT** – Is VAT charged? Inclusive or exclusive, and what rate? (Setting exists, default 0%)
5. **Offline conflicts** – If an offline order uses an item that was deactivated or re-priced meanwhile, accept at the offline price or reject? (Phase 7)
6. **Refund stock** – On refund, do ingredients go back to stock or count as wastage? (Phase 4)
7. **Prefix** – `RB` = Roshbar, assumed final.
8. **Deploy** – Same cPanel account as the old site? Then the domain's document root moves to `juice-bar-web/public` on switch-over day.
