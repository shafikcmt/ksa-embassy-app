# Backup & Restore Guide

## Automated Backups

The built-in scheduler runs `app:backup-database` daily at 02:00 and saves files to `storage/backups/`.

```bash
# Run manually
php artisan app:backup-database

# Custom path
php artisan app:backup-database --path=/home/user/backups
```

Backup files are named: `backup_YYYY-MM-DD_HHmmss.sql` (MySQL) or `.sqlite`.

## Manual MySQL Backup

```bash
mysqldump -u ksa_user -p ksa_embassy > backup_$(date +%F_%H%M).sql
```

With compression:
```bash
mysqldump -u ksa_user -p ksa_embassy | gzip > backup_$(date +%F_%H%M).sql.gz
```

## Manual SQLite Backup

```bash
cp database/database.sqlite storage/backups/backup_$(date +%F_%H%M).sqlite
```

## Restore MySQL

```bash
mysql -u ksa_user -p ksa_embassy < backup_2026-01-01.sql
```

From compressed:
```bash
gunzip < backup_2026-01-01.sql.gz | mysql -u ksa_user -p ksa_embassy
```

## Restore SQLite

```bash
cp storage/backups/backup_2026-01-01.sqlite database/database.sqlite
```

## What to Back Up

| Item | Location | Method |
|------|----------|--------|
| Database | MySQL / SQLite | `app:backup-database` or mysqldump |
| Uploaded files | `storage/app/` | Copy/rsync |
| Environment config | `.env` | Keep secure copy offline |
| Generated PDFs | `storage/app/public/` | Copy/rsync |

## Off-Server Backup (cPanel)

1. cPanel → **Backup** → Full Account Backup
2. Download `.tar.gz` — includes database and files
3. Schedule monthly full backups

## Backup Retention Policy

Recommended rotation (edit `BackupDatabase.php` to automate):

- Keep daily backups for 7 days
- Keep weekly backups for 4 weeks
- Keep monthly backups for 3 months

Manual cleanup:
```bash
# Delete backups older than 7 days
find storage/backups/ -name "*.sql" -mtime +7 -delete
find storage/backups/ -name "*.sqlite" -mtime +7 -delete
```

## Disaster Recovery Checklist

1. Restore latest database backup
2. Restore `.env` file (keep a secure offline copy)
3. Run `php artisan migrate --force` to apply any missing migrations
4. Run `php artisan storage:link`
5. Run `php artisan config:cache && php artisan route:cache`
6. Verify login and key pages work
7. Re-add cron entry for scheduler
