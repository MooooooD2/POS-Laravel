# 🚀 Production Deployment Guide — POS Laravel System

## Prerequisites
- PHP 8.2+
- MySQL 8.0+
- Composer 2.x
- Node.js 20+ & npm

---

## Step-by-Step Deployment

### 1. Upload & Configure
```bash
# Clone or upload files to your server
git clone <repo> /var/www/pos
cd /var/www/pos

# Copy and fill in environment
cp .env.example .env
nano .env   # Set DB credentials, APP_URL, passwords
```

### 2. Install Dependencies
```bash
composer install --no-dev --optimize-autoloader
npm install && npm run build
```

### 3. Generate Key & Migrate
```bash
php artisan key:generate
php artisan migrate --force
php artisan db:seed --force   # Creates roles, permissions, initial users
```

### 4. Storage & Cache
```bash
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

### 5. Permissions
```bash
chown -R www-data:www-data /var/www/pos
chmod -R 755 /var/www/pos/storage /var/www/pos/bootstrap/cache
```

---

## ⚠️ Critical Checklist Before Going Live

- [ ] `APP_DEBUG=false` in `.env`
- [ ] `APP_ENV=production` in `.env`
- [ ] `SESSION_ENCRYPT=true` in `.env`
- [ ] Database user is **not** root — create a dedicated `pos_user`
- [ ] **Change all default passwords** immediately after first login
- [ ] HTTPS is enabled (SSL certificate installed)
- [ ] Firewall: only port 443 (HTTPS) and 22 (SSH) open
- [ ] Database port 3306 NOT exposed to the internet
- [ ] `php artisan config:cache` run after any `.env` change
- [ ] Daily backups of the database configured

---

## Nginx Config (recommended)
```nginx
server {
    listen 443 ssl;
    server_name yourdomain.com;
    root /var/www/pos/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    add_header X-XSS-Protection "1; mode=block";

    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

---

## Scheduled Tasks (Cron)
```bash
# Add to crontab (crontab -e)
* * * * * cd /var/www/pos && php artisan schedule:run >> /dev/null 2>&1
```

---

## Default Credentials (CHANGE IMMEDIATELY)
| Role      | Username    | Password (from .env)        |
|-----------|-------------|-----------------------------|
| Admin     | `admin`     | `ADMIN_PASSWORD` value      |
| Cashier   | `cashier`   | `CASHIER_PASSWORD` value    |
| Warehouse | `warehouse` | `WAREHOUSE_PASSWORD` value  |
