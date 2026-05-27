# cPanel Deployment Guide

## Prerequisites

- cPanel hosting with PHP 8.2+ (set via cPanel > MultiPHP Manager)
- MySQL database access in cPanel
- SSH access (recommended) or File Manager

## Step 1 — Create Database

1. cPanel → **MySQL Databases**
2. Create database: `youraccount_ksa`
3. Create user: `youraccount_ksauser` with strong password
4. Add user to database with **All Privileges**
5. Note the full database name and username (cPanel prepends your account name)

## Step 2 — Upload Project Files

### Option A — SSH (recommended)
```bash
ssh youraccount@yourdomain.com
cd ~/
git clone <repository> ksa-embassy
```

### Option B — File Manager
1. Zip the project excluding `node_modules`, `vendor`, `.git`
2. Upload zip to `public_html` or a subdirectory via File Manager
3. Extract in place

## Step 3 — Point Domain to /public

This is the most important cPanel step. The web root must point to `ksa-embassy/public`, not `ksa-embassy/`.

**Option A — Subdomain:**
1. cPanel → **Subdomains** → Create subdomain (e.g., `ksa.yourdomain.com`)
2. Set Document Root to: `/home/youraccount/ksa-embassy/public`

**Option B — Addon Domain:**
1. cPanel → **Addon Domains**
2. Set Document Root to: `/home/youraccount/ksa-embassy/public`

**Option C — Symlink (advanced):**
```bash
# SSH
ln -s ~/ksa-embassy/public ~/public_html/ksa
```

## Step 4 — Configure .env

```bash
# SSH
cd ~/ksa-embassy
cp .env.example .env
nano .env
```

Set these values:
```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://ksa.yourdomain.com

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=youraccount_ksa
DB_USERNAME=youraccount_ksauser
DB_PASSWORD=your_password

MAIL_MAILER=smtp
MAIL_HOST=mail.yourdomain.com
MAIL_PORT=587
MAIL_USERNAME=noreply@yourdomain.com
MAIL_PASSWORD=your_email_password
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="KSA Embassy File System"
```

## Step 5 — Install Dependencies via SSH

```bash
cd ~/ksa-embassy
composer install --no-dev --optimize-autoloader
php artisan key:generate
php artisan migrate --force
php artisan db:seed --force    # first deployment only
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### If no SSH — use cPanel Terminal

cPanel → **Terminal** (if available) and run the same commands.

### If no SSH and no Terminal — PHP Script Workaround

Upload a temporary `setup.php` to `public_html/setup.php`:
```php
<?php
// REMOVE THIS FILE AFTER USE — security risk
chdir(__DIR__ . '/../ksa-embassy');
echo shell_exec('php artisan migrate --force 2>&1');
echo shell_exec('php artisan config:cache 2>&1');
echo shell_exec('php artisan route:cache 2>&1');
```
Access once via browser, then **delete immediately**.

## Step 6 — Set File Permissions

Via SSH:
```bash
chmod -R 755 ~/ksa-embassy
chmod -R 775 ~/ksa-embassy/storage
chmod -R 775 ~/ksa-embassy/bootstrap/cache
```

Via cPanel File Manager:
- Select `storage/` → Change Permissions → 775 (recursive)
- Select `bootstrap/cache/` → Change Permissions → 775

## Step 7 — Set Up Cron

1. cPanel → **Cron Jobs**
2. Set: Every minute (`* * * * *`)
3. Command:
```
php /home/youraccount/ksa-embassy/artisan schedule:run >> /dev/null 2>&1
```

## Step 8 — Build Frontend Assets

If you have Node.js access (not available on all cPanel hosts):
```bash
npm install && npm run build
```

If Node.js is not available, **build locally** and upload the `public/build/` folder.

## Common cPanel Issues

| Problem | Solution |
|---------|----------|
| 500 error | Check `storage/logs/laravel.log`; ensure `APP_DEBUG=false` in prod |
| White screen | File permissions on `storage/` wrong — set to 775 |
| CSS/JS not loading | Run `npm run build` locally, upload `public/build/` |
| Email not sending | Check SMTP settings; try port 465 with SSL |
| Artisan commands fail | Use full PHP path: `/usr/local/bin/php artisan ...` |
| Session errors | Ensure `session` table exists: `php artisan session:table && php artisan migrate` |

## Verify Deployment

1. Visit `https://ksa.yourdomain.com/login` — login page loads
2. Login as super admin — dashboard works
3. Create a test agency and HR profile
4. Run: `php artisan app:check-subscriptions` — no errors
