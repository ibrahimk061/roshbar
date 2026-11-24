# Deploying Roshbar (website + POS) to cPanel

This guide moves roshbar.com from the old plain-PHP site to the Laravel app in
`juice-bar-web/`. **Nothing here has been run on the live server.** The owner
decides when to switch. The old site, its `.env` and its database stay as they
are until the switch, so going back is always possible.

## 0. Before you start

- cPanel with PHP **8.3 or 8.4** (MultiPHP Manager), Composer, Terminal (or SSH).
- PHP extensions: `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `fileinfo`, `zlib`.
- Pick a quiet time (shop closed). The switch itself takes about 10 minutes.

## 1. Back up the old site first

1. cPanel → **Backup** → download a full home-directory backup and a database backup of the old site's database.
2. Keep both off the server (your computer or Google Drive).

## 2. New database (do not reuse the old one)

1. cPanel → **MySQL Databases** → create a new database, e.g. `USER_roshbar_pos`.
2. Create a new user with a long password and give it **ALL PRIVILEGES** on that database only.
3. The old site's database is only **read** once by the import command (step 6).

## 3. Upload the code (next to the old site, not over it)

```bash
cd ~
git clone https://github.com/ibrahimk061/roshbar.git roshbar-app
cd roshbar-app/juice-bar-web
composer install --no-dev --optimize-autoloader
cp .env.production.example .env
php artisan key:generate
```

Edit `.env` (cPanel File Manager → Edit):

- `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`: the **new** database from step 2.
- `LEGACY_DB_*`: the **old** site's database (read-only use). Copy the values from the old site's config by hand. Do not copy the old `.env` file itself.
- `LEGACY_UPLOADS_PATH`: the folder that holds the old site's product photos.
- `SEED_OWNER_EMAIL` / `SEED_OWNER_PASSWORD`: your own login. Use a strong password, never `changeme123`.

## 4. Create the tables and the first owner

```bash
php artisan migrate --force
php artisan db:seed --class=OwnerSeeder --force
php artisan storage:link
chmod -R 775 storage bootstrap/cache
```

Do **not** run the demo menu seeder on the live server. The real menu comes from the import (step 6) or is typed in Admin → Products.

## 5. Speed up

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Run these again after every `.env` change or code update.

## 6. Bring over the old site's data (read-only)

```bash
php artisan roshbar:import-legacy --dry-run   # shows what would be copied, writes nothing
php artisan roshbar:import-legacy             # copies menu, sizes, photos, messages, admin logins
```

The command only reads the old database. Running it twice does not create duplicates.

## 7. Check before switching

```bash
php artisan roshbar:check
```

Every row must say **OK**. Then open the new app on a temporary address (cPanel → **Domains** → create a subdomain such as `new.roshbar.com` with document root `~/roshbar-app/juice-bar-web/public`) and check:

- [ ] Website pages (Home, Menu, a product, About, Contact) in English and Bangla
- [ ] `/login` works with your owner account
- [ ] POS: take a test order and print a receipt on the 58 mm printer
- [ ] Reports and Expenses open
- [ ] Cancel the test order (Orders → order → Cancel)

## 8. Switch roshbar.com (the only step customers notice)

1. cPanel → **Domains** → roshbar.com → **Manage** → change the document root to `roshbar-app/juice-bar-web/public`.
2. cPanel → **SSL/TLS Status**: make sure roshbar.com has a valid certificate (AutoSSL).
3. Open https://roshbar.com and https://roshbar.com/login.

The old site's files stay where they were.

## 9. Nightly backups

cPanel → **Cron Jobs** → add (every minute):

```
* * * * * cd ~/roshbar-app/juice-bar-web && php artisan schedule:run >> /dev/null 2>&1
```

- This runs `roshbar:backup` every night at 23:45 Dhaka time.
- Backups go to `storage/app/backups/`, gzipped, and the last 30 days are kept.
- Download one now and then to keep a copy off the server.
- Test it once by hand: `php artisan roshbar:backup`.

## 10. Updating later

```bash
cd ~/roshbar-app && git pull
cd juice-bar-web
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

## Going back (rollback)

1. Change roshbar.com's document root back to the old site's folder.
2. The old site, its `.env` and its database were never changed, so it works as before.

Orders taken in the new POS stay in the new database.

## Security notes

- `APP_DEBUG=false` on the live server, always. Debug pages show passwords.
- The session cookie is https-only and encrypted (`.env.production.example`).
- Pages send `X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy` and, over https, `Strict-Transport-Security`.
- Login is blocked for 15 minutes after 5 wrong passwords.
- The owner can see sign-ins and sensitive actions in **Activity**.
- Give each staff member their own login. Switch off people who leave (Staff → Active).
