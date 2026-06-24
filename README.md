# VisaDeskPro

A multi-tenant SaaS application for Saudi Arabia recruitment agencies to manage HR candidate files, embassy submission lists, subscriptions, and document generation (PDF/print).

## Tech Stack

- **Backend:** Laravel 12, PHP 8.2+
- **Database:** MySQL 8+ (SQLite supported for local dev)
- **Frontend:** Bootstrap 5, Blade templates, Bootstrap Icons
- **PDF:** barryvdh/laravel-dompdf
- **Auth:** Laravel Breeze (session-based)
- **Permissions:** spatie/laravel-permission

## Roles

| Role | Description |
|------|-------------|
| Super Admin | Full platform control — manages agencies, plans, subscriptions, global settings |
| Agency Admin | Manages own agency's HR profiles, agents, embassy lists, settings |
| Agency User | Limited to assigned HR and document operations |

## Local Development Setup

```bash
composer install
npm install && npm run build
cp .env.example .env
# Edit .env: set DB_CONNECTION=sqlite for quick start, or configure MySQL
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
php artisan serve
```

Default super admin: `superadmin@system.local` / `password`

## Key Routes

| Path | Description |
|------|-------------|
| `/login` | Agency login |
| `/dashboard` | Agency dashboard |
| `/hr` | HR profile list |
| `/hr?filter=passport_expiring` | Passports expiring within 6 months |
| `/embassy-lists` | Embassy submission lists |
| `/settings` | Agency settings (profile, print, notifications) |
| `/super-admin/dashboard` | Super admin panel |
| `/super-admin/agencies` | Manage agencies |
| `/super-admin/plans` | Manage subscription plans |
| `/super-admin/settings` | Global system settings |

## Scheduler Commands

| Command | Schedule | Description |
|---------|----------|-------------|
| `app:check-subscriptions` | Daily 00:05 | Mark expired subscriptions, send expiry emails |
| `app:check-license-expiry` | Daily 00:10 | Send agency license expiry alerts |
| `app:check-passport-expiry` | Weekly Mon 08:00 | Send HR passport expiry alerts |
| `app:backup-database` | Daily 02:00 | Create DB backup to `storage/backups/` |

Add one cron entry on the server:
```
* * * * * php /path/to/artisan schedule:run >> /dev/null 2>&1
```

## Deployment

| Document | Purpose |
|----------|---------|
| [DEPLOYMENT.md](DEPLOYMENT.md) | Standard VPS/server deployment |
| [CPANEL_DEPLOYMENT.md](CPANEL_DEPLOYMENT.md) | cPanel shared hosting step-by-step |
| [SERVER_COMMANDS.md](SERVER_COMMANDS.md) | Common artisan & maintenance commands |
| [BACKUP_AND_RESTORE.md](BACKUP_AND_RESTORE.md) | Backup and restore procedures |
| [PRODUCTION_CHECKLIST.md](PRODUCTION_CHECKLIST.md) | Pre-launch production checklist |



# VisaDeskPro Instructions

This is a Laravel 12 project for KSA Embassy file, HR/candidate record, Agent, Embassy List, License and print/PDF generation.

## Main Goal
Fix print templates and UI without breaking existing operations.

## Important Rules
- Do not delete existing data.
- Do not remove existing routes, controllers, models, views or operations.
- Do not create duplicate fields.
- Inspect migrations/models/controllers before adding any field.
- If a field is missing, suggest safe additive migration first.
- Browser preview and downloaded PDF must use the same Blade layout.
- Keep Agent CRUD, HR CRUD, Embassy List, License and P.C. Verify working.
- Do not change database in print layout phases unless absolutely required.

## Reference Files
Reference files are inside:
- docs/references/embassy-list-reference.pdf
- docs/references/ksa-complete-file-reference-0001.pdf
- docs/images/embassy-list-reference-0001.jpg
- docs/images/embassy-list-reference-0002.jpg
- docs/images/ksa-application-reference-0001.jpg
- docs/images/ksa-application-reference-0002.jpg
- docs/images/ksa-application-reference-0003.jpg
- docs/images/ksa-application-reference-0004.jpg

## Work Order
1. Inspect current code first.
2. Give audit and changed-file plan.
3. Wait for approval.
4. Start Phase 1 only.
5. After every phase, list changed files and test URLs.

## Print Requirements
- Application File must match reference PDF.
- Embassy List must match reference PDF.
- A4 exact fit.
- No page cutting.
- Arabic text must render correctly.
- Barcode must show in browser preview and downloaded PDF.
