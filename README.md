# NIMA DEV SMM

Basic panel skeleton: signup/login, dashboard, and an order form. Orders are
stored with status `pending` — no real SMM API is connected yet.

## Setup (InfinityFree / 000webhost)

1. Create a MySQL database in your hosting control panel.
2. Open phpMyAdmin, select your database, go to **Import**, and upload `schema.sql`.
3. Edit `includes/config.php` and fill in your real `DB_HOST`, `DB_NAME`,
   `DB_USER`, `DB_PASS` (your host's control panel shows these).
4. Upload all files to your hosting's `htdocs` (InfinityFree) or `public_html`
   (000webhost) folder — keep the folder structure as-is.
5. Visit your domain — you should land on the login page.

## What's included
- `register.php` / `login.php` / `logout.php` — accounts with hashed passwords
- `dashboard.php` — balance + recent orders
- `order.php` — order form, deducts balance, saves order as `pending`
- `services` table — 5 sample services with placeholder rates

## API provider (AmazingSMM) — now connected
`order.php` now actually calls the provider when an order is placed:
1. Order is saved locally as `pending`, balance is deducted.
2. `includes/smm_api.php` sends the order to `SMM_API_URL` with your `SMM_API_KEY`.
3. If the provider accepts it, the order is updated to `processing` and its
   `api_order_id` is stored.
4. If the provider rejects it (bad service ID, insufficient provider funds,
   invalid link, etc.), the order is marked `failed` and the user is
   **automatically refunded**.

### To finish setup
1. Sign up at your provider and add funds to your provider account (this is
   separate from your users' balances in your own database — your provider
   balance is what actually pays for the orders).
2. Get your **API key** from the provider dashboard and put it in
   `includes/config.php` → `SMM_API_KEY`.
3. Get the provider's real service list (Dashboard → API → services, or call
   `smm_service_list()`), and update the `services` table's
   `provider_service_id` and `rate_per_1000` for each row to match — this is
   what maps your panel's "Instagram Followers" to *their* actual service #.
4. Set your `rate_per_1000` a bit higher than the provider's rate if you plan
   to eventually resell — that difference is your margin.

## Still to add (ask me when ready)
- A cron/status-check page that automatically calls `smm_order_status()` on a
  schedule (right now, refreshing is manual via the Admin → Orders page).

## Funds top-up (bank transfer, admin-approved)
- `funds.php` — users see your bank details, submit an amount + bank
  reference number, and can optionally upload a receipt (JPG/PNG/PDF, max
  5MB). This creates a `pending` row in `fund_requests`.
- `admin-funds.php` — admin-only page to view all requests and
  Approve/Reject them. Approving instantly credits the user's `balance`.
- Edit `includes/config.php` and fill in `BANK_NAME`, `BANK_ACCOUNT_NAME`,
  `BANK_ACCOUNT_NUMBER`, `BANK_BRANCH` with your real bank details.
- **To make yourself an admin:** register a normal account first, then run
  this in phpMyAdmin (SQL tab):
  ```sql
  UPDATE users SET is_admin = 1 WHERE username = 'your_username';
  ```
- Uploaded receipts are stored in `uploads/receipts/` — a `.htaccess` there
  blocks any script files from being executed for safety.

## Admin panel
Once you're an admin, a new **Admin** link shows up on your dashboard:
- **Overview** (`admin.php`) — stats: users, orders, revenue, pending items.
- **Services** (`admin-services.php`) — add, edit, enable/disable, delete services.
- **Orders** (`admin-orders.php`) — every order across all users, with a
  "Refresh" button that pulls live status from the provider for that order.
- **Fund Requests** (`admin-funds.php`) — approve/reject top-ups.
- **Sync Services** (`admin-sync.php`) — pull the provider's full service
  list (real IDs, rates, min/max) and bulk import/update your `services`
  table, applying a markup % you choose as your resale margin.
