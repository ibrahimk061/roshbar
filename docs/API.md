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

- `size` 1 is the 24 px font (32 characters a line). `size` 2 is the 36 px font (21 characters a line).
- The server already pads and cuts each line to fit, so changing the receipt never needs an app update.
- "Tk" is printed in place of ৳, which printer fonts lack.
