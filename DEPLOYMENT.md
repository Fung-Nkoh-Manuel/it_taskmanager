# IT TaskManager — Deployment Guide (Nginx + Ubuntu)

**Stack:** PHP 8.1+ · MySQL 8+ · Nginx · PHP-FPM  
**Architecture:** Pure PHP MVC · Single front controller

---

## Project Structure

```
it-taskmanager/
├── app/
│   ├── Controllers/       ← AuthController, TaskController, etc.
│   ├── Middleware/        ← AuthMiddleware (auth, roles, CSRF)
│   └── Models/            ← UserModel, TaskModel, etc.
├── config/
│   ├── app.php            ← App constants, timezone, error reporting
│   └── database.php       ← PDO singleton
├── public/                ← Web root (only this folder is public)
│   ├── index.php          ← Front controller / entry point
│   ├── uploads/           ← File attachments (auto-created)
│   └── css/ js/
├── routes/
│   ├── Router.php         ← Pattern-matching router
│   └── web.php            ← All route definitions
├── views/                 ← PHP view templates
│   ├── partials/          ← layout.php, error.php
│   ├── auth/              ← login.php, register.php
│   ├── dashboard/
│   ├── tasks/
│   ├── calendar/
│   ├── users/
│   ├── notifications/
│   ├── logs/
│   └── profile/
├── sql/
│   └── database.sql       ← Full schema + demo data
├── logs/                  ← PHP error logs (auto-created)
├── nginx.conf             ← Nginx server block template
└── php-fpm-pool.conf      ← PHP-FPM pool template
```

---

## Demo Accounts

| Username      | Password   | Role          |
|---------------|------------|---------------|
| `admin`       | `password` | Administrator |
| `technicien1` | `password` | Technician    |
| `technicien2` | `password` | Technician    |
| `user1`       | `password` | User          |

> ⚠️ Change all passwords before going to production.

---

## Part 1 — Local Development (Windows: XAMPP / Laragon)

### Step 1 — Copy files

```
XAMPP  → C:/xampp/htdocs/it-taskmanager/
Laragon→ C:/laragon/www/it-taskmanager/
```

### Step 2 — Create the database

1. Open phpMyAdmin → `http://localhost/phpmyadmin`
2. Create a new database named `it_taskmanager` (charset: utf8mb4)
3. Click **Import** → select `sql/database.sql` → Execute

### Step 3 — Configure the app

Edit `config/database.php`:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'it_taskmanager');
define('DB_USER', 'root');
define('DB_PASS', '');
```

Edit `config/app.php` — update `APP_URL`:
```php
define('APP_URL', getenv('APP_URL') ?: 'http://localhost/it-taskmanager/public');
```

### Step 4 — Rewrite rules

**XAMPP** — Apache uses the `.htaccess` approach. Create this file at the project root:

```apache
# /.htaccess  (project root — redirects into public/)
RewriteEngine On
RewriteRule ^$ public/ [L]
RewriteRule (.*) public/$1 [L]
```

And this one inside `public/`:
```apache
# /public/.htaccess
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^ index.php [QSA,L]
```

**Laragon** — Virtual hosts handle this automatically. Just visit `http://it-taskmanager.test/`.

### Step 5 — Access the app

```
http://localhost/it-taskmanager/public/
```

---

## Part 2 — Production Deployment on Ubuntu + Nginx

### 2.1 — System prerequisites

```bash
sudo apt update && sudo apt upgrade -y

# Install Nginx
sudo apt install -y nginx

# Install PHP 8.1 + extensions
sudo apt install -y php8.1-fpm php8.1-mysql php8.1-mbstring \
    php8.1-xml php8.1-curl php8.1-gd php8.1-zip php8.1-intl

# Install MySQL
sudo apt install -y mysql-server

# Check all services are running
sudo systemctl status nginx
sudo systemctl status php8.1-fpm
sudo systemctl status mysql
```

### 2.2 — MySQL: create database and user

```bash
sudo mysql -u root -p
```

```sql
CREATE DATABASE it_taskmanager CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE USER 'taskmanager_user'@'localhost' IDENTIFIED BY 'CHANGE_THIS_STRONG_PASSWORD';

GRANT ALL PRIVILEGES ON it_taskmanager.* TO 'taskmanager_user'@'localhost';

FLUSH PRIVILEGES;
EXIT;
```

Import the schema:
```bash
mysql -u taskmanager_user -p it_taskmanager < /var/www/it-taskmanager/sql/database.sql
```

### 2.3 — Deploy application files

```bash
# Upload your project (via git, SCP, rsync, etc.)
# Example with git:
cd /var/www
sudo git clone https://github.com/yourrepo/it-taskmanager.git

# Or via SCP from your local machine:
# scp -r ./it-taskmanager user@your-server:/var/www/

# Set ownership
sudo chown -R www-data:www-data /var/www/it-taskmanager

# Set permissions
sudo find /var/www/it-taskmanager -type d -exec chmod 755 {} \;
sudo find /var/www/it-taskmanager -type f -exec chmod 644 {} \;

# Uploads and logs folders need write access
sudo chmod -R 775 /var/www/it-taskmanager/public/uploads
sudo chmod -R 775 /var/www/it-taskmanager/logs

# Make sure these directories exist
sudo mkdir -p /var/www/it-taskmanager/public/uploads
sudo mkdir -p /var/www/it-taskmanager/logs
sudo chown -R www-data:www-data /var/www/it-taskmanager/public/uploads
sudo chown -R www-data:www-data /var/www/it-taskmanager/logs
```

### 2.4 — PHP-FPM pool configuration

```bash
# Copy the pool config
sudo cp /var/www/it-taskmanager/php-fpm-pool.conf \
        /etc/php/8.1/fpm/pool.d/taskmanager.conf

# Edit it — set your real DB password and domain
sudo nano /etc/php/8.1/fpm/pool.d/taskmanager.conf
```

Key values to update in `taskmanager.conf`:
```ini
env[APP_URL]  = https://taskmanager.yourdomain.com
env[DB_USER]  = taskmanager_user
env[DB_PASS]  = CHANGE_THIS_STRONG_PASSWORD
env[DB_NAME]  = it_taskmanager
```

Reload PHP-FPM:
```bash
sudo systemctl reload php8.1-fpm
```

### 2.5 — Nginx server block

```bash
# Copy the Nginx config
sudo cp /var/www/it-taskmanager/nginx.conf \
        /etc/nginx/sites-available/taskmanager

# Edit it — set your real domain name
sudo nano /etc/nginx/sites-available/taskmanager
```

Change this line to your actual domain:
```nginx
server_name taskmanager.yourdomain.com;
```

Enable the site:
```bash
# Enable by symlinking
sudo ln -s /etc/nginx/sites-available/taskmanager \
           /etc/nginx/sites-enabled/taskmanager

# Disable default site (optional but recommended)
sudo rm -f /etc/nginx/sites-enabled/default

# Test configuration — must say "syntax is ok"
sudo nginx -t

# Reload Nginx
sudo systemctl reload nginx
```

### 2.6 — SSL certificate with Let's Encrypt

```bash
# Install Certbot
sudo apt install -y certbot python3-certbot-nginx

# Issue certificate (replace with your real domain)
sudo certbot --nginx -d taskmanager.yourdomain.com

# Certbot automatically edits your nginx.conf to add HTTPS.
# Auto-renewal is set up automatically — verify it:
sudo certbot renew --dry-run
```

After SSL is set up, go back to `nginx.conf` and uncomment the HTTP→HTTPS redirect:
```nginx
return 301 https://$host$request_uri;
```

Then reload Nginx:
```bash
sudo systemctl reload nginx
```

### 2.7 — Set production environment variables

The PHP-FPM pool config (`php-fpm-pool.conf`) already injects environment variables via `env[KEY] = VALUE`. The app reads them with `getenv()`. Double-check these in `/etc/php/8.1/fpm/pool.d/taskmanager.conf`:

```ini
env[APP_ENV]      = production         ← disables error display
env[APP_URL]      = https://taskmanager.yourdomain.com
env[APP_TIMEZONE] = Africa/Douala      ← or your timezone
env[DB_HOST]      = localhost
env[DB_NAME]      = it_taskmanager
env[DB_USER]      = taskmanager_user
env[DB_PASS]      = your_strong_password
```

### 2.8 — Firewall

```bash
# Allow HTTP and HTTPS through UFW
sudo ufw allow 'Nginx Full'
sudo ufw enable
sudo ufw status
```

### 2.9 — Verify everything works

```bash
# Check Nginx is running
sudo systemctl status nginx

# Check PHP-FPM is running
sudo systemctl status php8.1-fpm

# Check MySQL is running
sudo systemctl status mysql

# Tail Nginx error log for live debugging
sudo tail -f /var/log/nginx/taskmanager_error.log

# Tail PHP error log
sudo tail -f /var/www/it-taskmanager/logs/php_errors.log
```

Open your browser: `https://taskmanager.yourdomain.com`  
Login: `admin` / `password`

---

## Part 3 — Security Checklist (Production)

| Item | Action |
|------|--------|
| Default passwords | Change all 4 demo account passwords immediately |
| DB credentials | Use a dedicated MySQL user, not root |
| APP_ENV | Must be `production` to hide error details |
| File permissions | `uploads/` and `logs/` writable by www-data only |
| SSL/HTTPS | Always enforce via `certbot --nginx` |
| Nginx blocks | Sensitive dirs (`app/`, `config/`, `sql/`) return 404 |
| PHP-FPM | `expose_php = off` hides PHP version |
| uploads/ | PHP execution blocked by Nginx inside that folder |
| HTTPS redirect | HTTP → HTTPS 301 redirect active |
| HSTS | `Strict-Transport-Security` header in HTTPS block |

---

## Part 4 — Troubleshooting

| Symptom | Fix |
|---------|-----|
| 502 Bad Gateway | `sudo systemctl restart php8.1-fpm` — check socket path in nginx.conf |
| 404 on all pages | Confirm `try_files $uri $uri/ /index.php?$query_string` is in Nginx config |
| Blank white page | Set `APP_ENV=development` temporarily; tail PHP error log |
| DB connection error | Verify `env[DB_*]` values in `php-fpm-pool.conf`; reload FPM |
| Uploads failing | `sudo chmod 775 /var/www/it-taskmanager/public/uploads` |
| CSRF token invalid | Clear browser cookies and retry; check session config |
| Calendar is empty | Tasks need a `start_date` value set to appear on the calendar |
| Permission denied | `sudo chown -R www-data:www-data /var/www/it-taskmanager` |

---

## Part 5 — Useful Commands Reference

```bash
# Reload Nginx after config changes
sudo systemctl reload nginx

# Restart PHP-FPM after pool config changes
sudo systemctl restart php8.1-fpm

# MySQL console
sudo mysql -u taskmanager_user -p it_taskmanager

# Re-import schema (wipes all data)
mysql -u taskmanager_user -p it_taskmanager < /var/www/it-taskmanager/sql/database.sql

# Check Nginx config syntax
sudo nginx -t

# View live Nginx errors
sudo tail -f /var/log/nginx/taskmanager_error.log

# View live PHP errors
sudo tail -f /var/www/it-taskmanager/logs/php_errors.log

# Renew SSL certificate
sudo certbot renew

# Check open ports
sudo ss -tlnp | grep -E '80|443|3306'
```

---

## Part 6 — API Reference

Base URL: `https://taskmanager.yourdomain.com/api`

**Authentication:** Active web session, or Bearer token:
```
Authorization: Bearer <md5(user_id + email + password_hash)>
```

| Method | Endpoint | Description | Role required |
|--------|----------|-------------|---------------|
| GET | `/api/tasks` | List tasks (filters: status, priority, search, page) | Any |
| GET | `/api/tasks/{id}` | Task detail with comments, history, attachments | Any |
| POST | `/api/tasks` | Create a task | Tech / Admin |
| POST | `/api/tasks/{id}` | Update a task | Tech / Admin |
| POST | `/api/tasks/{id}/status` | Quick status change | Tech / Admin |
| POST | `/api/tasks/{id}/delete` | Delete a task | Admin |

```bash
# Example: list in-progress tasks
curl -H "Authorization: Bearer <token>" \
     "https://taskmanager.yourdomain.com/api/tasks?status=en_cours"

# Example: create a task
curl -X POST \
     -H "Authorization: Bearer <token>" \
     -H "Content-Type: application/json" \
     -d '{"title":"Deploy update","priority":"haute","status":"a_faire"}' \
     "https://taskmanager.yourdomain.com/api/tasks"
```

---

*IT TaskManager — Pure PHP MVC · Nginx · PHP-FPM · MySQL*
