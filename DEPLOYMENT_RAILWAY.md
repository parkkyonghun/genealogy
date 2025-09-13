# Railway Deployment Configuration for Genealogy Laravel Application

## Introduction
This document outlines the complete configuration for deploying the Laravel-based genealogy system to Railway. The application is a standard Laravel 11 project with PHP 8.3, Livewire, Jetstream, Filament, Spatie packages (e.g., activitylog, backup, medialibrary), multilingual support, and Vite for asset compilation. Deployment leverages Railway's PHP buildpack, automatic environment variable handling (e.g., DATABASE_URL), and optional services like PostgreSQL.

The design assumes:
- GitHub repository linked to Railway for CI/CD.
- Production environment (APP_ENV=production).
- Database: PostgreSQL (recommended for Railway; MySQL alternative possible).
- Queue: Database-driven (no Redis required).
- Storage: Local filesystem (public disk for media; consider S3 for production scalability).
- No custom buildpacks needed; uses heroku/php-apache.

This spec does not include implementation; it provides the blueprint for `railway.json` and setup steps.

## Prerequisites
1. **Railway Account and CLI**: Sign up at railway.app, install the Railway CLI (`npm i -g @railway/cli`), and link your project (`railway login`).
2. **GitHub Integration**: Push the repository to GitHub and connect it via Railway dashboard (New Project > Deploy from GitHub repo).
3. **Environment Setup**:
   - Generate and set `APP_KEY` (run `php artisan key:generate --show` locally and add to Railway variables).
   - Prepare `.env.example` with production defaults (e.g., APP_DEBUG=false).
   - Ensure `storage/` and `bootstrap/cache/` are writable (Railway handles via ephemeral filesystem; use persistent volumes if needed for media).
4. **Database Preparation**: Locally, update `config/database.php` if switching to PostgreSQL (already supported). Run migrations and seeders locally for testing.
5. **Asset Compilation**: Confirm `npm run build` works locally to bundle CSS/JS from `resources/` to `public/build/`.

## Services
- **Database**: PostgreSQL (Railway's managed service).
  - Add via Railway dashboard: New > Database > PostgreSQL.
  - Railway auto-generates `DATABASE_URL` (e.g., `postgres://user:pass@host:port/db`).
  - Alternative: MySQL if preferred, but PostgreSQL is more seamless on Railway.
- **Redis (Optional)**: Not required (queues use database). If enabling for caching/queues:
  - Add Redis service in Railway.
  - Set `QUEUE_CONNECTION=redis`, `REDIS_URL` from service variables.
- **Other**: No additional services (e.g., no email/SMS; use Railway's env for mail config).

## Environment Variables
Set these in Railway dashboard (Variables tab). Core Laravel vars plus app-specific:

| Variable | Value/Source | Description |
|----------|--------------|-------------|
| `APP_NAME` | Genealogy System | App name for logs/errors. |
| `APP_ENV` | production | Environment (forces production mode). |
| `APP_KEY` | Base64-generated key | From `php artisan key:generate --show`. Required for encryption. |
| `APP_DEBUG` | false | Disable debug in production. |
| `APP_URL` | https://${RAILWAY_STATIC_URL} | App URL (Railway provides domain). |
| `LOG_CHANNEL` | stack | Logging channel (or 'single' for files). |
| `LOG_DEPRECATIONS_CHANNEL` | null | Deprecations logging. |
| `LOG_LEVEL` | error | Production log level. |
| `DB_CONNECTION` | pgsql | Use PostgreSQL. |
| `DB_URL` | ${DATABASE_URL} | Auto from Railway DB service. |
| `QUEUE_CONNECTION` | database | Default; uses DB for jobs. |
| `SESSION_DRIVER` | database | Or 'redis' if using Redis. |
| `CACHE_DRIVER` | database | Or 'redis'. |
| `FILESYSTEM_DISK` | public | For media library (uploads to storage/app/public). |
| `MEDIA_DISK` | public | Spatie medialibrary config. |
| `BACKUP_DISK` | local | For Spatie backups (or s3). |
| `MAIL_MAILER` | smtp | If email needed; configure SMTP vars. |
| `TELESCOPE_ENABLED` | false | Disable if using (dev tool). |

Additional app-specific (from configs):
- `LOCALE_DEFAULT`: km (or en; from app.php).
- Any Filament/Jetstream secrets if customized.

## Configuration File: railway.json
Place `railway.json` in the project root. This defines build, deploy, and restart behaviors. Railway uses JSON or TOML; JSON shown below.

```json
{
  "$schema": "https://railway.app/railway.schema.json",
  "build": {
    "builder": "NIXPACKS",
    "buildCommand": "composer install --no-dev --optimize-autoloader && npm ci --only=production && npm run build && php artisan config:cache && php artisan route:cache && php artisan view:cache"
  },
  "deploy": {
    "startCommand": "vendor/bin/heroku-php-apache2 public/",
    "restartPolicyType": "ON_FAILURE",
    "restartPolicyMaxRetries": 3
  },
  "healthcheckPath": "/health",  // Optional: Add a /health route in Laravel for checks.
  "autoDeploy": true
}
```

- **Build Command Explanation**:
  - `composer install --no-dev --optimize-autoloader`: Installs PHP deps without dev tools, optimizes autoloader.
  - `npm ci --only=production`: Installs Node deps (Tailwind, Vite) without dev.
  - `npm run build`: Compiles assets (Vite builds CSS/JS to public/build/).
  - `php artisan config:cache && route:cache && view:cache`: Optimizes for production (caches config/routes/views).

- **Start Command**: Uses Apache via Heroku buildpack (serves from public/ index.php).

Alternative: Use `railway.toml` if preferred:
```
[build]
  builder = "NIXPACKS"
  buildCommand = "composer install --no-dev --optimize-autoloader && npm ci --only=production && npm run build && php artisan config:cache && php artisan route:cache && php artisan view:cache"

[deploy]
  startCommand = "vendor/bin/heroku-php-apache2 public/"
  restartPolicyType = "ON_FAILURE"
  restartPolicyMaxRetries = 3
```

## Build and Deployment Process
1. **Push to GitHub**: Trigger auto-deploy on Railway.
2. **Build Phase** (Nixpacks detects PHP/Node):
   - Installs PHP 8.3, Composer, Node 20 (from .nvmrc).
   - Runs build command (above).
   - Publishes assets (e.g., vendor publish for packages like Filament).
3. **Deploy Phase**:
   - Starts Apache server pointing to public/.
   - Railway injects env vars (e.g., DATABASE_URL).
4. **Post-Deploy Hook** (add in Railway dashboard or via CLI):
   - Run migrations: `php artisan migrate --force`.
   - Optional: Seed data if needed (`php artisan db:seed --class=SettingSeeder`).
   - Clear caches if issues: `php artisan optimize:clear`.

To run post-deploy: Use Railway's "Deploy Hooks" or add a custom script in build (but migrations should run after DB is ready).

## Migration and Seeding
- Migrations: 20+ tables (users, teams, people, couples, media, etc.). Run post-deploy to create schema.
- Seeding: Essential seeders (e.g., SettingSeeder, GenderSeeder). Run conditionally in production.
- Command: `php artisan migrate --force --seed` (use --seed only if initial deploy).
- Handle: In Railway, use a deploy hook script or manual run via `railway run`.

## Potential Issues and Mitigations
1. **File Permissions**: Storage/media uploads may fail (ephemeral FS). Mitigation: Use Railway volumes for /storage, or switch to S3 (set AWS vars).
2. **Asset Compilation Failures**: Vite/Tailwind issues if Node version mismatch. Mitigation: Pin Node in .nvmrc (20.x), test build locally.
3. **Database Connection**: Ensure DB service is linked before deploy. Test with `railway run php artisan migrate`.
4. **Multilingual/Localization**: Lang files (km.json, etc.) are static; no issues. Ensure APP_LOCALE in env.
5. **Queue Workers**: Database queues work out-of-box. For background jobs (e.g., backups), add a worker service: `php artisan queue:work`.
6. **Media Library**: Spatie uses local disk; uploads to storage/app/public (symlink to public/storage). Run `php artisan storage:link` in build if needed.
7. **Backup Package**: Spatie backups to local; configure to S3 for prod.
8. **Performance**: Cache everything; monitor Railway metrics. Scale horizontally if traffic grows.
9. **Security**: Set SESSION_SECURE_COOKIE=true for HTTPS. Review Jetstream/Fortify for auth.
10. **Costs**: Railway free tier limited; Postgres adds ~$5/mo. Monitor usage.

## Testing the Deployment
1. Deploy to staging branch first.
2. Verify: Access app URL, check logs (`railway logs`), run `railway shell` for artisan commands.
3. Health Check: Add a simple route (e.g., Route::get('/health', fn() => 'OK');) and set in config.

This configuration ensures a robust, production-ready deployment. Adjust based on specific needs (e.g., add Redis if queues scale).