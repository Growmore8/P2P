# SRM Fintech Solutions — P2P USDT Platform

Laravel 12 app: public website + customer P2P desk (buy/sell USDT) + admin panel.

## Features

**Customers**
- Sign up / log in (rate-limited)
- KYC: personal details + PAN, Aadhaar front/back, selfie upload → admin approves/rejects. Trading is locked until KYC is approved. Only the last 4 digits of Aadhaar are stored.
- Payout accounts (UPI / bank) for sell orders
- **Buy USDT**: enter INR → choose network + own wallet + deposit method → order page shows the exact amount, UPI QR / bank details and a countdown → customer submits UTR + screenshot
- **Sell USDT**: enter USDT → choose payout account → order page shows your deposit address + QR → customer submits the tx hash
- Order history, live status steps, activity timeline, explorer links

**Admin panel** (`/admin`)
- Dashboard: payments to verify, KYC queue, today's volume
- Orders: filter / search → **Accept payment** → **Mark completed** (with tx hash / payout UTR) or **Reject** with a reason. Warns about duplicate UTRs and unverified KYC.
- KYC review: view documents, checklist, duplicate-PAN warning, approve / reject with a reason
- Deposit methods: add UPI IDs (optional QR) and bank accounts, set min/max limits, enable/disable
- Networks & wallets: fee per network, your USDT deposit address for sell orders, buy/sell toggles
- Rates & settings: buy/sell rate, min/max order, TDS %, payment window, contact details, announcement bar, pause trading
- Users: search, view, block/unblock

**Safety built in:** prices are always calculated on the server (the browser can't change the rate), status changes lock the database row so two admins can't act on the same order twice, uploads go to private storage served only to the owner or an admin, CSRF protection, and blocked users are logged out. Unpaid orders expire automatically.

## Run locally

```bash
cd srm-fintech
php artisan migrate --seed     # creates tables, admin, networks, sample deposit methods
php artisan serve              # http://127.0.0.1:8000
```

Default admin: `admin@srmfintech.local` / `ChangeMe@123`. **Change this before going live.** Set `ADMIN_EMAIL`, `ADMIN_PHONE` and `ADMIN_PASSWORD` in `.env` before seeding.

The sample deposit methods are **disabled**. Edit them with your real UPI ID and bank account in **Admin → Deposit Methods**, then enable them. Set your USDT deposit addresses in **Admin → Networks & Wallets** to enable sell orders.

Run the tests with `php artisan test`.

## Use MySQL instead of SQLite

In `.env`:
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=srm_p2p
DB_USERNAME=your_user
DB_PASSWORD=your_password
```
Then run `php artisan migrate --seed`.

## Deploy (shared hosting such as Hostinger, or a VPS)

1. Upload the project and point the domain's document root to the project's `public/` folder.
2. Set `.env`: `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://yourdomain.com`, plus the MySQL details and admin details.
3. Run `composer install --no-dev --optimize-autoloader`, `php artisan migrate --seed --force`, then `php artisan config:cache route:cache view:cache`.
4. Add a cron job that runs every minute so unpaid orders expire:
   `* * * * * cd /path/to/srm-fintech && php artisan schedule:run >> /dev/null 2>&1`
   (Orders also expire when someone opens them, so the app still works without the cron.)
5. Use **HTTPS**, because KYC documents are uploaded.
6. Make sure `storage/` and `bootstrap/cache/` are writable. **Back up** the database and `storage/app/private` (KYC files) regularly.

## Where things are

| What | Where |
|---|---|
| Routes | `routes/web.php` |
| Pricing logic | `app/Services/Pricing.php` |
| Order statuses & flow | `app/Models/Order.php` |
| Customer controllers | `app/Http/Controllers/*.php` |
| Admin controllers | `app/Http/Controllers/Admin/*.php` |
| Pages / templates | `resources/views/` (`pages/`, `user/`, `admin/`) |
| Styles / JS | `public/css/style.css`, `public/css/app.css`, `public/js/site.js` |
| Default settings | `app/Models/Setting.php` (`DEFAULTS`) |

## Order statuses

`pending_payment` → (customer submits UTR / tx hash) → `payment_submitted` → (admin accepts) → `processing` → (admin enters tx hash / UTR) → `completed`

Other end states: `rejected` (admin), `cancelled` (customer, before paying), `expired` (payment window passed).
