# cPanel Deployment Skill

Use this skill for deployment, live-server, Git pull, `.cpanel.yml`, and shared hosting issues.

## Goal
Provide simple safe commands for cPanel/shared hosting deployment without Docker unless requested.

## Common Commands
```bash
php artisan optimize:clear
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart
npm run build
```

## Procedure
1. Check server PHP version and Laravel requirements.
2. Pull latest code or verify cPanel Git deployment.
3. Run composer install if needed.
4. Run migrations safely.
5. Clear and rebuild Laravel caches.
6. Build Vite assets if needed.
7. Verify `public/build/manifest.json` exists.
8. Check storage link and permissions.

## Safety Rules
- Do not expose `.env` secrets.
- Do not run destructive DB commands on production.
- Use `--force` only for production migrations.
- Give exact terminal commands when user asks.
