# Production Checklist

Use this before going live or after every major deployment.

## Environment

- [ ] `APP_ENV=production`
- [ ] `APP_DEBUG=false`
- [ ] `APP_KEY` is set (non-empty)
- [ ] `APP_URL` is set to the correct HTTPS domain
- [ ] `.env` is NOT committed to version control
- [ ] `.env.example` is committed and up to date

## Database

- [ ] `DB_CONNECTION=mysql` (not sqlite)
- [ ] Database exists and credentials are correct
- [ ] `php artisan migrate --force` ran without errors
- [ ] `php artisan migrate:status` shows all migrations as `Ran`
- [ ] Database user has only necessary privileges (SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER on the app DB)

## Security

- [ ] `APP_DEBUG=false` (never true in production — exposes stack traces)
- [ ] HTTPS is enforced (SSL certificate installed)
- [ ] `storage/` and `bootstrap/cache/` are NOT web-accessible
- [ ] `/public` is the only web-accessible directory
- [ ] `php artisan key:generate` was run (APP_KEY filled)
- [ ] Default Laravel routes (`/`) return your app, not the Laravel welcome page
- [ ] Super admin password changed from default seed password

## Email

- [ ] `MAIL_MAILER=smtp` (not `log`)
- [ ] SMTP credentials tested: send test email
- [ ] `MAIL_FROM_ADDRESS` is a real deliverable address
- [ ] SPF/DKIM records set on mail domain (reduces spam score)

## Performance & Caching

- [ ] `php artisan config:cache` — no errors
- [ ] `php artisan route:cache` — no errors
- [ ] `php artisan view:cache` — no errors
- [ ] `php artisan event:cache` — no errors
- [ ] `composer install --no-dev --optimize-autoloader` was used

## Storage & Files

- [ ] `php artisan storage:link` was run
- [ ] `storage/` directory is writable (chmod 775)
- [ ] `bootstrap/cache/` is writable (chmod 775)
- [ ] `storage/backups/` directory exists and is writable

## Scheduler / Cron

- [ ] Cron entry added: `* * * * * php /path/to/artisan schedule:run >> /dev/null 2>&1`
- [ ] `php artisan schedule:list` shows 4 commands
- [ ] `php artisan app:check-subscriptions` runs without errors
- [ ] `php artisan app:backup-database` creates a file in `storage/backups/`

## Frontend

- [ ] `npm run build` was run (or `public/build/` uploaded)
- [ ] CSS and JS load correctly in browser
- [ ] No browser console errors on login/dashboard

## Functional Tests (Manual)

- [ ] Login page loads at `/login`
- [ ] Super admin can log in at `/login` → redirects to `/super-admin/dashboard`
- [ ] Agency user can log in → redirects to `/dashboard`
- [ ] Create HR profile → saved correctly
- [ ] Download PDF → file opens correctly
- [ ] Embassy list create/finalize → works
- [ ] Settings page saves correctly
- [ ] Subscription expired page shows for expired agencies
- [ ] Notice bell shows count when active notices exist

## Logs

- [ ] `storage/logs/laravel.log` exists and is writable
- [ ] No critical errors in `laravel.log` after initial load
- [ ] `LOG_LEVEL=error` in production (not `debug`)

## Post-Deployment

- [ ] Verify cron is running (`crontab -l`)
- [ ] Monitor `storage/logs/scheduler.log` after first cron run
- [ ] Take first manual backup: `php artisan app:backup-database`
- [ ] Document super admin credentials securely (offline)
