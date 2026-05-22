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

## API Starter Routes

- `GET /api/health`
- `GET /api/products`
- `POST /api/contact`

## Railway Notes

- Railway config is in `backend/railway.json`.
- Railway start script is `backend/railway/start.sh`.
- Example Railway env vars are in `backend/.env.railway.example`.
