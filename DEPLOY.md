# Deploying SRM Fintech Solutions (production)

## Why there is no `.env` or database in GitHub
- `.env` holds passwords and secret keys. It is **never** committed — every server creates its own.
- The local SQLite test database is also not committed. Production uses **MySQL**.
- Use **`.env.production.example`** as the template for the server.

## Requirements
- PHP **8.2+** with extensions: `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`
- MySQL 8 / MariaDB 10.4+
- Composer 2
- HTTPS (SSL) on the domain — required, clients upload KYC documents
- Upload limit ≥ 40 MB (`upload_max_filesize`, `post_max_size`) for KYC videos

## Steps
```bash
# 1. Get the code
git clone https://github.com/Growmore8/P2P.git srm-fintech
cd srm-fintech

# 2. Install PHP packages (no dev tools on production)
composer install --no-dev --optimize-autoloader

# 3. Create the environment file and fill every "<-- CHANGE" value
cp .env.production.example .env
nano .env            # or edit in the hosting File Manager
php artisan key:generate

# 4. Create tables + first admin (uses ADMIN_EMAIL / ADMIN_PASSWORD from .env)
php artisan migrate --seed --force
#    ⚠ Do NOT run "db:seed --class=DemoSeeder" on production (it refuses anyway).

# 5. Permissions (Linux)
chmod -R 775 storage bootstrap/cache

# 6. Speed up
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

7. **Point the domain's document root to the `public/` folder** (not the project root).
   - cPanel/Hostinger: set the domain's root to `.../srm-fintech/public`
   - If you can't change it, ask before using workarounds — never expose `.env` publicly.

8. **Cron job** (every minute — expires unpaid orders):
   ```
   * * * * * cd /path/to/srm-fintech && php artisan schedule:run >> /dev/null 2>&1
   ```

## After the first deploy (in the admin panel)
1. Log in with `ADMIN_EMAIL` / `ADMIN_PASSWORD` at `https://yourdomain.com/login`
2. **Settings** → real WhatsApp, phone, support email, desk hours
3. **Currencies & Rates** → set INR buy / sell rates and limits
4. **Deposit Methods** → your real UPI ID / bank account → enable
5. **Networks & Wallets** → your real USDT deposit addresses (sell orders)
6. Test the mail: use **Forgot password** with the admin email — you should receive a code

## Updating later
```bash
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

## Backups (important)
- MySQL database — daily
- `storage/app/private/` — KYC documents, videos, payment proofs, ticket attachments

## Quick checks if something fails
| Symptom | Fix |
|---|---|
| 500 error, blank page | `storage/logs/laravel.log`; check `.env` DB values; `chmod -R 775 storage bootstrap/cache` |
| "No application encryption key" | `php artisan key:generate` then `php artisan config:cache` |
| Changes in `.env` not applied | `php artisan config:clear` (then `config:cache` again) |
| OTP / ticket emails not arriving | check MAIL_* values, port 465 + `smtps` or 587 + `smtp`; look in spam |
| KYC video upload fails | raise `upload_max_filesize` / `post_max_size` to 40M |
