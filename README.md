# E-commence-Website

Stack update completed for backend migration:
- Backend: Laravel (`backend/`)
- Database: MySQL
- Deployment target: Railway

## Project Structure

- `backend/` Laravel application (web + API routes)
- `Users/`, `Templates/`, `DB/` legacy plain-PHP source kept for reference during migration

## Local Setup (Laravel Backend)

1. Install PHP dependencies:
   - `cd backend`
   - `php ..\\composer.phar install` (or `composer install` if Composer is global)
2. Create environment file:
   - `copy .env.example .env`
3. Generate app key:
   - `php artisan key:generate`
4. Configure MySQL values in `.env`.
5. Run migrations:
   - `php artisan migrate`
6. Start server:
   - `php artisan serve`

## Web Routes

- `/` home page
- `/shop` shop page
- `/contact` contact page

## Firebase Auth Setup (Legacy PHP Frontend)

This setup applies to the legacy frontend in `Users/` + `Templates/`.

1. Create a Firebase project and add a Web app.
2. In Firebase Console, enable `Authentication > Sign-in method > Email/Password`.
3. Copy config template:
   - `copy Auth\\firebase_config.example.php Auth\\firebase_config.php`
4. Open `Auth/firebase_config.php` and fill your Firebase Web app values.
5. Open:
   - `Auth/login.php` for sign-in
   - `Auth/register.php` for sign-up
   - `Users/Home.php` / `Users/shop.php` / `Users/contactUs.php` to see header auth state (`Login` / `Logout`)

## Railway MySQL Sync (Legacy PHP Frontend)

When a user signs in or registers through Firebase, the app syncs that user to MySQL tables:
- `users` (main app user table)
- `firebase_users` (auth mirror table)

1. Create `DB/railway_mysql_config.php` (ignored by git):
   - You can copy `DB/railway_mysql_config.example.php`.
2. Fill Railway credentials (`host`, `port`, `database`, `user`, `password`).
3. Auth sync endpoint:
   - `Auth/sync_user.php`
4. Client sync script:
   - `Assect/js/firebase-auth-pages.js`

## Legacy PHP Cart Flow

The customer cart currently lives in the legacy PHP frontend.

### Main files

- `Users/cart.php` renders the cart page and cart drawer content.
- `Users/includes/shop_backend.php` owns cart session storage, product lookups, quantity limits, totals, login redirects, and output helpers.
- `Templates/header.php` owns the cart icon, cart count badge, and iframe drawer shell.
- `Users/shop.php` and `Users/productDetail.php` add products to the cart through `shop_cart_add_item()`.
- `Users/checkout.php` reads the same cart details before order placement.
- `Users/chart.php` redirects old cart links to `Users/cart.php`.

### Request behavior

- `GET Users/cart.php` loads the current cart with `shop_cart_details($pdo)`.
- `GET Users/cart.php?drawer=1` renders the same cart for the header drawer iframe and keeps drawer navigation in drawer mode.
- `POST action=update_qty` updates one line through `shop_cart_set_quantity($pdo, $lineKey, $quantity)`.
- `POST action=remove_line` removes one line through `shop_cart_remove_line($lineKey)`.
- `POST action=clear_cart` clears the session cart through `shop_cart_clear()`.
- After each cart POST, the page redirects back to `cart.php` or `cart.php?drawer=1` to avoid duplicate form submissions.

### Cart storage and validation

- Cart lines are stored in the PHP session key `luvshop_cart`.
- Each line key uses the format `p{product_id}:v{variant_id}`.
- Cart quantities are normalized to at least `1` and capped by available stock and the app limit of `99`.
- `shop_cart_details()` revalidates each stored line against current product data. Missing or unavailable products are removed from the session cart.
- Subtotals and line totals are calculated from current product or variant prices, not stale session prices.

### Drawer and login flow

- The header opens `cart.php?drawer=1` inside an iframe when a cart drawer trigger is clicked.
- `Users/cart.php` posts a `luvshop:cart-count` message to the parent window so `Templates/header.php` can update the badge.
- Checkout links go to `Users/checkout.php` for logged-in users.
- Guests are sent to `Auth/login.php` with a safe redirect back to `Users/Home.php?open_cart=checkout`.
- When `open_cart=cart` or `open_cart=checkout` is present, the header script opens the drawer automatically.

## API Starter Routes

- `GET /api/health`
- `GET /api/products`
- `POST /api/contact`

## Railway Notes

- Railway config is in `backend/railway.json`.
- Railway start script is `backend/railway/start.sh`.
- Example Railway env vars are in `backend/.env.railway.example`.
