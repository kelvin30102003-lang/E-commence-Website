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

## API Starter Routes

- `GET /api/health`
- `GET /api/products`
- `POST /api/contact`

## Railway Notes

- Railway config is in `backend/railway.json`.
- Railway start script is `backend/railway/start.sh`.
- Example Railway env vars are in `backend/.env.railway.example`.
