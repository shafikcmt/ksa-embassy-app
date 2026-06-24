# Deployment Guide — VPS / Shared Server

## Requirements

- PHP 8.2+ with extensions: mbstring, pdo, pdo_mysql, openssl, tokenizer, xml, ctype, json, bcmath, gd or imagick
- MySQL 8.0+
- Composer 2+
- Node.js 18+ (build only)
- Web server: Nginx or Apache

## 1. Upload Files

```bash
# Clone or upload project to server
git clone <repository> /var/www/ksa-embassy
# OR upload via FTP/SFTP and extract
```

## 2. Configure Environment

```bash
cd /var/www/ksa-embassy
cp .env.example .env
php artisan key:generate
```

Edit `.env`:
```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ksa_embassy
DB_USERNAME=ksa_user
DB_PASSWORD=your_secure_password

MAIL_MAILER=smtp
MAIL_HOST=smtp.yourdomain.com
MAIL_PORT=587
MAIL_USERNAME=noreply@yourdomain.com
MAIL_PASSWORD=your_mail_password
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="VisaDeskPro"
```

## 3. Install Dependencies

```bash
composer install --no-dev --optimize-autoloader
npm install && npm run build
```

## 4. Database Setup

```bash
php artisan migrate --force
php artisan db:seed --force   # only on first deployment
```

## 5. Storage & Permissions

```bash
php artisan storage:link
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
mkdir -p storage/backups storage/logs
```

## 6. Optimize for Production

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

## 7. Configure Web Server

### Nginx

```nginx
server {
    listen 80;
    server_name yourdomain.com;
    root /var/www/ksa-embassy/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

### Apache (.htaccess already included in `/public`)

Ensure `mod_rewrite` is enabled:
```bash
a2enmod rewrite
```

## 8. Set Up Cron Scheduler

```bash
crontab -e
```
Add:
```
* * * * * php /var/www/ksa-embassy/artisan schedule:run >> /dev/null 2>&1
```

## 9. SSL (recommended)

```bash
apt install certbot python3-certbot-nginx
certbot --nginx -d yourdomain.com
```

## Deploying Updates

```bash
cd /var/www/ksa-embassy
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
npm run build
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart
```
