# Server Commands Reference

## Artisan — Application

```bash
# Run the scheduler manually (test)
php artisan schedule:run

# List all scheduled tasks
php artisan schedule:list

# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Rebuild caches for production
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Run a specific scheduler command manually
php artisan app:check-subscriptions
php artisan app:check-license-expiry
php artisan app:check-passport-expiry
php artisan app:backup-database

# Backup with custom path
php artisan app:backup-database --path=/home/user/backups

# Run migrations (safe — skips already-run)
php artisan migrate --force

# Check migration status
php artisan migrate:status

# Rollback last batch (careful in production)
php artisan migrate:rollback

# List all routes
php artisan route:list

# List all commands
php artisan list

# Put site in maintenance mode
php artisan down --message="System maintenance — back soon." --retry=60

# Take site out of maintenance mode
php artisan up

# Generate app key (only if .env APP_KEY is empty)
php artisan key:generate

# Create storage symlink (run once after fresh deploy)
php artisan storage:link
```

## Artisan — Queue (if using queue workers)

```bash
# Start queue worker
php artisan queue:work --daemon

# Restart queue workers gracefully (after deploy)
php artisan queue:restart

# List failed jobs
php artisan queue:failed

# Retry a failed job
php artisan queue:retry <id>

# Flush all failed jobs
php artisan queue:flush
```

## Database

```bash
# Manual MySQL dump
mysqldump -u ksa_user -p ksa_embassy > backup_$(date +%F).sql

# Restore from dump
mysql -u ksa_user -p ksa_embassy < backup_2026-01-01.sql

# Connect to MySQL
mysql -u ksa_user -p ksa_embassy
```

## Log Files

```bash
# View Laravel log (last 100 lines)
tail -100 storage/logs/laravel.log

# Watch Laravel log in real time
tail -f storage/logs/laravel.log

# View scheduler output
tail -100 storage/logs/scheduler.log

# Clear Laravel log
> storage/logs/laravel.log
```

## File Permissions (Linux/cPanel)

```bash
# Fix permissions after upload
find . -type f -exec chmod 644 {} \;
find . -type d -exec chmod 755 {} \;
chmod -R 775 storage bootstrap/cache

# Check who owns files
ls -la storage/
```

## Composer & npm

```bash
# Install/update dependencies
composer install --no-dev --optimize-autoloader
composer update

# Rebuild frontend assets
npm install
npm run build

# Check outdated packages
composer outdated
npm outdated
```

## Cron — Scheduler Setup

```bash
# Edit crontab
crontab -e

# Add this single entry (all schedules are in routes/console.php)
* * * * * php /path/to/artisan schedule:run >> /dev/null 2>&1

# Verify cron is running
crontab -l
```

## SSL — Let's Encrypt (Nginx/Apache)

```bash
# Install
certbot --nginx -d yourdomain.com -d www.yourdomain.com

# Auto-renew test
certbot renew --dry-run

# Force renew
certbot renew --force-renewal
```
