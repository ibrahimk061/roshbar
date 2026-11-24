# Roshbar POS for SUNMI V2s

Android app (Java, no extra libraries except the SUNMI printer library) for the
SUNMI V2s handheld with the built-in 58 mm printer. It talks to the Laravel app
through [API v1](../docs/API.md).

## What it does

- Staff sign in with their own POS email and password. The token stays on the device until Sign out (90 days at most).
- Menu by category, sold-out items greyed out. Tap an item for size, sugar, ice, add-ons, quantity and a note.
- Cart with order type (Takeaway / Dine-in), discount (Tk or %, reason), and one payment (Cash with change, bKash/Nagad/Card with transaction ID).
- On Confirm the server makes the order and its number, and the receipt prints at once.
- No internet: the order is kept on the device and printed as "OFFLINE ORDER". It is sent every 30 seconds once online and gets its number then. Orders the server refuses stay in "Offline orders" with the reason.
- Menu ⋮: Today's orders (reprint), Offline orders, Refresh menu, Test print, Sign out.

Prices on the device are for the screen only. The server works out every total again.

## Get the APK

GitHub → **Actions** → **Android app (SUNMI)** → newest run → **Artifacts** → `roshbar-pos-apk`.
Unzip, copy `app-debug.apk` to the V2s, and open it (allow "install unknown apps").

## Build locally

Android Studio (or the Android SDK with platform 35): `cd sunmi-app && ./gradlew assembleDebug`.

## Notes

- The server must be https. Plain http is blocked so the sign-in token is never sent in the clear.
- On a phone without the SUNMI printer service everything works except printing.
- The APK is signed with the fixed debug key in `app/shop-debug.keystore`, so a newer build installs over the old one and offline orders are kept. Do not uninstall the app while it has offline orders. For a store-grade key, set `RB_KEYSTORE_*` and build `assembleRelease`.
