# Backend (Laravel + MySQL + Railway)

This directory contains the Laravel backend for the e-commerce project.

## Requirements

- PHP 8.2+
- Composer 2+
- MySQL 8+

## Run Locally

1. Install dependencies:
   - `composer install`
2. Create environment file:
   - `cp .env.example .env` (Linux/macOS)
   - `copy .env.example .env` (Windows)
3. Generate app key:
   - `php artisan key:generate`
4. Configure MySQL credentials in `.env`.
5. Run migrations:
   - `php artisan migrate`
6. Start server:
   - `php artisan serve`

## Routes

Web routes:
- `/`
- `/shop`
- `/contact`

API routes:
- `GET /api/health`
- `GET /api/products`
- `POST /api/contact`

## Railway Deployment

- Railway config: `railway.json`
- Startup script: `railway/start.sh`
- Example env file: `.env.railway.example`

Important environment values on Railway:
- `APP_KEY`
- `APP_URL=https://${{RAILWAY_PUBLIC_DOMAIN}}`
- `DB_CONNECTION=mysql`
- `DB_HOST=${{MySQL.MYSQLHOST}}`
- `DB_PORT=${{MySQL.MYSQLPORT}}`
- `DB_DATABASE=${{MySQL.MYSQLDATABASE}}`
- `DB_USERNAME=${{MySQL.MYSQLUSER}}`
- `DB_PASSWORD=${{MySQL.MYSQLPASSWORD}}`

If deploying from a monorepo root, set service root directory to `backend` in Railway.
