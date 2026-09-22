# Live/Production Server Setup Guide — Carrygo

This guide covers **initial provisioning** of a fresh Ubuntu server to host Carrygo. It is a one-time setup; once complete, day-to-day releases follow [`deployment_guide.md`](./deployment_guide.md).

> [!IMPORTANT]
> This project does **not** use Laravel Reverb, Pusher, or any WebSocket broadcasting service (`BROADCAST_CONNECTION=log`). The Supervisor and reverse-proxy sections below intentionally omit a WebSocket daemon/upstream. If real-time features are added later, revisit this doc.

---

## 1. Architecture Overview

| Component | Value |
|---|---|
| App name | Carrygo |
| Production domain | `ngcarrygo.com` *(replace with actual domain)* |
| Server OS | Ubuntu LTS (22.04/24.04) |
| App directory | `/var/www/ncarrygo.com/html` |
| Web server | Nginx (PHP-FPM upstream) — see §6 for an Apache alternative |
| App runtime user | `www-data` |
| PHP version | 8.3+ (match local: `^8.3`, CI-tested to 8.5) |
| Database | MySQL 8.x |
| Queue driver | `database` (Supervisor-managed `queue:work` workers) |
| Broadcast / WebSockets | Not used in this project |
| Scheduler | Linux `cron` → `php artisan schedule:run` every minute |
| Deployment method | WinSCP / SFTP file upload — **no Git on the server** |
| Asset build | Compiled locally (`npm run build`) and uploaded as static files, or built on-server (see §5 note) |

**Port map:**

| Port | Purpose |
|---|---|
| 443 | Public HTTPS (Nginx/Apache, terminates SSL) |
| 80 | HTTP → redirects to 443 |
| 3306 | MySQL (localhost only — not exposed externally) |
| 9000 / `unix:/run/php/php8.3-fpm.sock` | PHP-FPM upstream (internal only) |

---

## 2. Prerequisites — Package Installation

Run as root or with `sudo`.

```bash
sudo apt update && sudo apt upgrade -y

# PHP 8.3 + required extensions (Ondřej Surý's PPA gives access to current/future PHP versions on Ubuntu)
sudo apt install -y software-properties-common
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update

sudo apt install -y php8.3 php8.3-fpm php8.3-cli php8.3-common \
    php8.3-mysql php8.3-mbstring php8.3-xml php8.3-bcmath \
    php8.3-curl php8.3-zip php8.3-gd php8.3-intl php8.3-opcache php8.3-readline

# Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
composer --version

# Node.js 22.x + npm (only needed if you plan to build assets on-server; see §5 note)
curl -fsSL https://deb.nodesource.com/setup_22.x | sudo -E bash -
sudo apt install -y nodejs

# MySQL
sudo apt install -y mysql-server
sudo mysql_secure_installation

# Supervisor (process manager for queue workers)
sudo apt install -y supervisor

# Web server (pick one — see §6)
sudo apt install -y nginx
# or: sudo apt install -y apache2 libapache2-mod-fcgid

# Unzip / Git (Git only needed for tooling, not deployment — no Git pulls happen on this server)
sudo apt install -y unzip git
```

Enable and start core services:

```bash
sudo systemctl enable --now php8.3-fpm mysql supervisor nginx
```

Create the database and an app-specific MySQL user:

```bash
sudo mysql -e "CREATE DATABASE carrygo CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
sudo mysql -e "CREATE USER 'carrygo_app'@'localhost' IDENTIFIED BY 'REPLACE_WITH_STRONG_PASSWORD';"
sudo mysql -e "GRANT ALL PRIVILEGES ON carrygo.* TO 'carrygo_app'@'localhost';"
sudo mysql -e "FLUSH PRIVILEGES;"
```

---

## 3. Directory Setup

```bash
sudo mkdir -p /var/www/ncarrygo.com/html
sudo chown -R $USER:www-data /var/www/ncarrygo.com/html
```

Upload the application into this directory via SFTP/WinSCP (see [`deployment_guide.md`](./deployment_guide.md) for the exact file list). After the first upload, jump to §7 for permissions.

---

## 4. Supervisor Daemon Configuration

Supervisor keeps the queue worker(s) running and auto-restarts them on crash or server reboot.

### 4.1 Queue worker

Create `/etc/supervisor/conf.d/carrygo-worker.conf`:

```ini
[program:carrygo-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/ncarrygo.com/html/artisan queue:work database --sleep=3 --tries=3 --max-time=3600 --timeout=90
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/ncarrygo.com/html/storage/logs/worker.log
stopwaitsecs=3600
```

**Why these flags:**
- `--tries=3` — matches the app's queue retry expectations; failed jobs land in the `failed_jobs` table for inspection.
- `--max-time=3600` — recycles each worker process hourly to avoid memory creep, safer than `--max-jobs` for long-lived Laravel apps.
- `--timeout=90` — a job running longer than 90s is killed; raise this if a specific job legitimately needs longer.
- `stopwaitsecs=3600` **must** be ≥ your longest possible job runtime, so Supervisor doesn't SIGKILL a worker mid-job during a deploy restart.
- `numprocs=2` — two parallel workers; tune based on queue throughput (rewards/spin processing, leaderboard jobs, bid-closing jobs all flow through this queue per `routes/console.php`).

> [!NOTE]
> No WebSocket/Reverb Supervisor block is defined here — this project has no `laravel/reverb` dependency and broadcasts to the `log` driver only. Do not create a `carrygo-reverb` program unless Reverb is actually added to `composer.json`.

Apply the config:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start carrygo-worker:*
```

---

## 5. Cron Scheduler

The app defines several scheduled tasks in [`routes/console.php`](../routes/console.php): daily reward spins (`00:00`), a weekly leaderboard job (Sundays `23:55`), and an **every-minute** job that closes expired bids. All of these run through Laravel's scheduler, so the crontab entry below is required for the app to function correctly (not optional).

Edit the `www-data` crontab:

```bash
sudo crontab -u www-data -e
```

Add exactly this line:

```cron
* * * * * cd /var/www/ncarrygo.com/html && php artisan schedule:run >> /dev/null 2>&1
```

> [!WARNING]
> Do **not** use `php artisan schedule:work` in production. `schedule:work` is a foreground development helper meant for `php artisan serve`-style local dev — it isn't process-supervised, won't survive a reboot or crash, and duplicates what cron already does reliably. Always use `schedule:run` via cron on the server.

Verify cron picked it up:

```bash
sudo crontab -u www-data -l
```

---

## 6. Reverse Proxy / Web Server Configuration

Choose **one**. Both terminate SSL and proxy PHP to PHP-FPM; neither needs a WebSocket upstream since this project doesn't use Reverb/Pusher.

### Option A — Nginx (recommended)

`/etc/nginx/sites-available/ngcarrygo.com`:

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name ngcarrygo.com www.ngcarrygo.com;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name ngcarrygo.com www.ngcarrygo.com;

    root /var/www/ncarrygo.com/html/public;
    index index.php;

    ssl_certificate     /etc/letsencrypt/live/ngcarrygo.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/ngcarrygo.com/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    client_max_body_size 50M;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    access_log /var/log/nginx/ngcarrygo.com-access.log;
    error_log  /var/log/nginx/ngcarrygo.com-error.log;
}
```

Enable and reload:

```bash
sudo ln -s /etc/nginx/sites-available/ngcarrygo.com /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

### Option B — Apache

Requires `mod_proxy_fcgi`, `mod_rewrite`, `mod_ssl`:

```bash
sudo a2enmod proxy proxy_fcgi rewrite ssl headers
sudo systemctl restart apache2
```

`/etc/apache2/sites-available/ngcarrygo.com.conf`:

```apache
<VirtualHost *:80>
    ServerName ngcarrygo.com
    ServerAlias www.ngcarrygo.com
    Redirect permanent / https://ngcarrygo.com/
</VirtualHost>

<VirtualHost *:443>
    ServerName ngcarrygo.com
    ServerAlias www.ngcarrygo.com
    DocumentRoot /var/www/ncarrygo.com/html/public

    SSLEngine on
    SSLCertificateFile      /etc/letsencrypt/live/ngcarrygo.com/fullchain.pem
    SSLCertificateKeyFile   /etc/letsencrypt/live/ngcarrygo.com/privkey.pem

    <Directory /var/www/ncarrygo.com/html/public>
        AllowOverride All
        Require all granted
    </Directory>

    <FilesMatch \.php$>
        SetHandler "proxy:unix:/run/php/php8.3-fpm.sock|fcgi://localhost"
    </FilesMatch>

    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set X-Content-Type-Options "nosniff"

    ErrorLog  ${APACHE_LOG_DIR}/ngcarrygo.com-error.log
    CustomLog ${APACHE_LOG_DIR}/ngcarrygo.com-access.log combined
</VirtualHost>
```

Enable and reload:

```bash
sudo a2ensite ngcarrygo.com.conf
sudo apache2ctl configtest
sudo systemctl reload apache2
```

### SSL certificates (either option)

```bash
sudo apt install -y certbot python3-certbot-nginx   # or python3-certbot-apache
sudo certbot --nginx -d ngcarrygo.com -d www.ngcarrygo.com   # or --apache
```

---

## 7. Production `.env` Checklist

Create `/var/www/ncarrygo.com/html/.env` (uploaded manually once via SFTP — never overwritten by later deploys, see [`deployment_guide.md`](./deployment_guide.md)).

```ini
APP_NAME=Carrygo
APP_ENV=production
APP_KEY=                          # generate on server: php artisan key:generate
APP_DEBUG=false
APP_URL=https://ngcarrygo.com

LOG_CHANNEL=stack
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=carrygo
DB_USERNAME=carrygo_app
DB_PASSWORD=REPLACE_WITH_STRONG_PASSWORD

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_SECURE_COOKIE=true        # required in production, served over HTTPS

BROADCAST_CONNECTION=log          # no Reverb/Pusher in this project
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database

CACHE_STORE=database

MAIL_MAILER=smtp                  # replace `log` with a real transactional provider in production
MAIL_HOST=
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_FROM_ADDRESS="hello@ngcarrygo.com"
MAIL_FROM_NAME="${APP_NAME}"
```

Checklist:

- [ ] `APP_ENV=production`, `APP_DEBUG=false` — **never** leave debug mode on in production (leaks stack traces).
- [ ] `APP_KEY` generated fresh on the server, **not copied** from local/staging.
- [ ] `SESSION_SECURE_COOKIE=true` since the app is served over HTTPS.
- [ ] Database credentials point at the dedicated `carrygo_app` MySQL user created in §2 (not `root`).
- [ ] Real mail transport configured (`MAIL_MAILER`) — `log`/`array` are dev-only.
- [ ] `.env` file permissions locked down: `chmod 640` and owned by `www-data:www-data`.

---

## 8. Permissions

Laravel needs write access to `storage/` and `bootstrap/cache/`:

```bash
cd /var/www/ncarrygo.com/html
sudo chown -R www-data:www-data storage bootstrap/cache
sudo find storage -type d -exec chmod 775 {} \;
sudo find storage -type f -exec chmod 664 {} \;
sudo chmod -R 775 bootstrap/cache
```

The application (and thus its runtime user) should own the whole tree consistently to avoid mixed-ownership permission errors:

```bash
sudo chown -R www-data:www-data /var/www/ncarrygo.com/html
```

---

## 9. Health Checks & Troubleshooting

The app exposes Laravel's built-in health check at `/up` (configured in `bootstrap/app.php`). Confirm it returns `200`:

```bash
curl -I https://ngcarrygo.com/up
```

| Check | Command |
|---|---|
| Queue workers running | `sudo supervisorctl status carrygo-worker:*` |
| PHP-FPM running | `systemctl status php8.3-fpm` |
| Nginx/Apache running | `systemctl status nginx` (or `apache2`) |
| Ports listening | `sudo ss -tlnp \| grep -E ':443\|:80\|:3306'` |
| Cron installed | `sudo crontab -u www-data -l` |
| App error log | `tail -f /var/www/ncarrygo.com/html/storage/logs/laravel.log` |
| Worker log | `tail -f /var/www/ncarrygo.com/html/storage/logs/worker.log` |
| Web server error log | `tail -f /var/log/nginx/ngcarrygo.com-error.log` |
| MySQL reachable | `mysql -u carrygo_app -p -h 127.0.0.1 carrygo -e "SELECT 1;"` |

**Common issues:**

- **502 Bad Gateway** — PHP-FPM isn't running or the socket path in the vhost doesn't match `php8.3-fpm.sock`. Check `systemctl status php8.3-fpm` and confirm the socket path with `php -v` / `php-fpm8.3 -tt`.
- **500 error, blank page, `APP_DEBUG=false`** — check `storage/logs/laravel.log` first. Usually a missing `APP_KEY`, bad DB credentials, or a permissions issue on `storage/`.
- **Queued jobs never run** — `supervisorctl status` to confirm workers are `RUNNING`; if `FATAL`/`BACKOFF`, check `worker.log` for the actual PHP error (often a missing `.env` value after a fresh deploy).
- **Scheduled tasks (spins, leaderboard, bid expiry) not firing** — confirm the cron line exists for `www-data` (§5) and that `storage/logs/laravel.log` shows scheduler activity; also check server timezone matches expectations (`timedatectl`).
- **CSS/JS 404s** — `public/build/` wasn't uploaded or `npm run build` wasn't run before upload. See [`deployment_guide.md`](./deployment_guide.md) §"Asset handling".
