# API v1 (SUNMI app)

Base URL: `https://roshbar.com/api/v1`. All bodies are JSON. Send `Accept: application/json`.
Every route except `/login` needs `Authorization: Bearer <token>`.

| Method | Path | What it does |
|---|---|---|
| POST | `/login` | `{email, password, device_name}` → `{token, user}`. 5 wrong passwords block that email + IP for 15 minutes. Switched-off staff cannot sign in. |
| GET | `/me` | The signed-in staff member. |
| POST | `/logout` | Removes this device's token. |
| GET | `/menu` | Categories, products (sizes, add-ons), payment methods, sugar/ice/order-type options, VAT setting. |
| POST | `/orders/quote` | Same cart as `/orders` without `uuid`/`payments` → totals. |
| POST | `/orders` | Places the order. Same body as the web POS (`uuid`, `order_type`, `items[]`, `discount`, `payments[]`, optional `created_offline_at`). `201` new, `200` when that `uuid` was already sent (safe to retry). Returns the order and `receipt`. |
| GET | `/orders/today` | Today's orders (newest first) for reprinting. |
| GET | `/orders/{id}/receipt` | The order and its `receipt` lines. |

## Rules

- The device sends IDs, quantities and options only. Prices, discounts, VAT and change are worked out on the server; any price the device sends is ignored.
- Discount limits, payment checks and the order number are the same as the web POS.
- A token lasts 90 days (`SANCTUM_EXPIRATION`, in minutes). Switching a staff member off removes their token on the next request.
- Sign-ins and sign-outs on the app show in Activity.

## Receipt lines

`receipt` is a list the app prints line by line on the 58 mm built-in printer (384 dots):

```json
{"text": "RB-25-11-26-A0001", "align": "center", "bold": true, "size": 2}
{"divider": true}
```

- `size` 1 is normal text, `size` 2 is big text (the total), `size` 3 is extra big (the order's last part, e.g. `A0001`, on its own line under `RB-24-11-26`).
- Two-sided lines (label + amount) also have `left` and `right`. The app draws `left` at the left edge and `right` at the right edge.
- `text` is the same line padded for a 32-column (`size` 1) or 21-column (`size` 2) plain-text printer.
- The app draws the whole receipt as one image (384 dots wide), so Bangla and ৳ print correctly.
- Receipt words come from `config/pos.php` `receipt_labels` in `POS_RECEIPT_LANG` (Bangla by default). `/menu` sends them as `receipt_labels` for receipts the app makes offline.
