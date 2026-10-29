# Production Deployment & Laragon Setup Guide

## 1. Laragon Environment Configuration

### Document Root Configuration
ToolLab Invoice is engineered with a secure `public/` webroot structure. Your web server must point its Document Root directly to `public/`, not the root repository directory.

#### Laragon Apache Virtual Host
Laragon automatically creates virtual hosts. Ensure your virtual host file (`C:\laragon\etc\apache2\sites-enabled\auto.toollab-invoice.test.conf`) points to `public`:
```apache
<VirtualHost *:80>
    DocumentRoot "C:/laragon/www/toollab-invoice/public"
    ServerName toollab-invoice.test
    ServerAlias *.toollab-invoice.test
    <Directory "C:/laragon/www/toollab-invoice/public">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

#### Apache `.htaccess` (Pre-configured in `public/.htaccess`)
```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^ index.php [L]
</IfModule>
```

#### Nginx Configuration (Optional alternative)
```nginx
server {
    listen 80;
    server_name toollab-invoice.test;
    root /laragon/www/toollab-invoice/public;

    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass 127.0.0.1:9000;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
    }
}
```

---

## 2. Recommended PHP 8.4 Settings (`php.ini`)

Ensure the following directives are configured in your active `php.ini`:
```ini
; Timezone
date.timezone = "Asia/Kolkata"

; Memory and Execution
memory_limit = 256M
max_execution_time = 60

; Uploads (if uploading receipts or attachments)
upload_max_filesize = 10M
post_max_size = 12M

; Required Extensions
extension=pdo_mysql
extension=mbstring
extension=openssl
extension=curl
extension=gd
```

---

## 3. Database Initialization & Seeding

```bash
# 1. Create database in MySQL
mysql -u root -e "CREATE DATABASE IF NOT EXISTS toollab_invoice CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 2. Run schema
mysql -u root toollab_invoice < database/schema.sql

# 3. Seed initial admin user, business profile, tax rates, and demo records
php database/seed.php
```

---

## 4. Recurring Billing Cron Job Setup

Configure a scheduled task to run the recurring invoice processor every midnight:

### Windows (Task Scheduler or Laragon)
Create a task running every day at 00:05:
- **Program**: `C:\laragon\bin\php\php-8.4.12-Win32-vs17-x64\php.exe`
- **Arguments**: `C:\laragon\www\toollab-invoice\bin\process-recurring-invoices.php`

### Linux Server (Crontab)
```cron
5 0 * * * /usr/bin/php /var/www/toollab-invoice/bin/process-recurring-invoices.php >> /var/log/recurring-invoices.log 2>&1
```

---

## 5. Production Readiness Checklist

Before moving to a public production server:
1. Set `APP_ENV=production` and `APP_DEBUG=false` in `.env`.
2. Configure `APP_URL=https://your-domain.in`.
3. Set `SESSION_SECURE=true` in `.env` so cookies are transmitted strictly over HTTPS.
4. Change the default administrator password immediately.
5. Setup automated nightly MySQL backups (`mysqldump -u root toollab_invoice > backup-$(date +%F).sql`).
