# Deployment Guide — Carrygo (WinSCP / SFTP Routine)

This guide covers **routine deployments** of Carrygo to the production server via WinSCP/SFTP file upload. It assumes the server was already provisioned per [`live_setup_guide.md`](./live_setup_guide.md) — there is **no Git on the server**; every release is a manual file transfer followed by a server-side finalize script.

---

## 1. WinSCP / SFTP Upload Guidelines

### 1.1 What to upload

Upload the whole application tree **except** the paths in the "never overwrite" list below. In practice, for a typical change set:

| Change type | What to upload |
|---|---|
| PHP code (`app/`, `routes/`, `config/`, etc.) | The changed files/folders |
| New/changed migrations | `database/migrations/*.php` |
| Composer dependency change | `composer.json`, `composer.lock` (then run `composer install` on server — see §3) |
| Frontend/UI change | `public/build/` (compiled locally — see §1.3), plus `resources/` if you also want the source in sync |
| New npm dependency | `package.json`, `package-lock.json` |

### 1.2 Files & directories to NEVER overwrite

> [!CAUTION]
> Overwriting any of these will break the live site, wipe live data, or leak secrets.

| Path | Why |
|---|---|
| `.env` | Holds production `APP_KEY`, DB credentials, mail credentials. Overwriting with your local `.env` (or `.env.example`) will point production at your dev database or blank out the app key, invalidating every active session and encrypted value. |
| `storage/` | Contains live logs, cached views, session files, and user-uploaded content (`storage/app/public`, `storage/app/private`). Overwriting wipes live data; wrong permissions here break the whole app (see §5 step 9). |
| `bootstrap/cache/*.php` | Holds the server's compiled config/route/event cache (`config.php`, `routes-v7.php`, `packages.php`, `services.php`). These are **regenerated on the server** by `artisan optimize` (§5 step 5) — uploading your local versions can inject local paths/config into production. |
| `.git/` (if present at all) | Deployment is SFTP-only; there is no Git checkout on the server. Don't upload a `.git` folder — it's dead weight and a potential info leak if the web root is ever misconfigured. |
| `node_modules/`, `vendor/` | Never upload these — they're platform/architecture-specific and huge. Install on the server instead (§5 steps 2 and, if needed, an npm install) or ship prebuilt output only (`public/build/` for frontend; `vendor/` is always rebuilt server-side via Composer). |

### 1.3 Handling assets: compile locally vs. on the server

**Recommended: compile locally, upload the output.**

```bash
npm run build
```

This produces `public/build/` (JS/CSS bundles + `manifest.json`). Upload that folder as-is. This keeps Node.js off the production server entirely and matches the "no build tooling in prod" spirit of an SFTP-only deploy.

**Alternative: build on the server.** Only do this if the server has Node 22 installed (see `live_setup_guide.md` §2) and you've uploaded updated `resources/`, `package.json`, and `package-lock.json`. Then run `npm ci && npm run build` as part of the server-side routine (already included as an optional step in `post-deploy.sh` below, commented out by default).

---

## 2. Quick Action Cheatsheet (TL;DR)

SSH in after your WinSCP upload finishes, then:

```bash
cd /var/www/ncarrygo.com/html

php artisan down --secret="$(openssl rand -hex 16)"

composer install --no-dev --optimize-autoloader --no-interaction

php artisan migrate --force

php artisan optimize:clear
php artisan optimize
php artisan view:cache

php artisan queue:restart
sudo supervisorctl restart carrygo-worker:*

sudo systemctl reload nginx   # or: sudo systemctl reload apache2

sudo chown -R www-data:www-data storage bootstrap/cache

php artisan up
```

Or, once [`post-deploy.sh`](#5-automated-server-script-post-deploysh) is in place:

```bash
cd /var/www/ncarrygo.com/html && ./post-deploy.sh
```

---

## 3. Detailed Step-by-Step Server Actions

Run these **after** the WinSCP/SFTP upload completes, over SSH, from the app root (`/var/www/ncarrygo.com/html`).

### Step 1 — Maintenance mode on

```bash
php artisan down --secret="$(openssl rand -hex 16)" --retry=60
```

`--secret` gives you a bypass URL (`https://ngcarrygo.com/<secret>`) to preview the site while it's down; note the generated secret from the command output. `--retry=60` tells clients to retry after 60s.

### Step 2 — Install dependencies

```bash
composer install --no-dev --optimize-autoloader --no-interaction
```

`--no-dev` excludes dev tooling (Pest, Pint, Boost, Sail) from the production autoloader. `--optimize-autoloader` builds a classmap for faster autoloading.

### Step 3 — Database migrations

```bash
php artisan migrate --force
```

`--force` is required because `APP_ENV=production` otherwise prompts for confirmation, which would hang a non-interactive SSH session.

### Step 4 — Asset compilation

If you compiled locally (recommended, §1.3), this step is already done — just confirm `public/build/manifest.json` exists post-upload:

```bash
test -f public/build/manifest.json && echo "OK: build manifest present" || echo "MISSING build assets"
```

If building on the server instead:

```bash
npm ci
npm run build
```

### Step 5 — Cache optimization

```bash
php artisan optimize:clear   # clear any stale cached config/routes/views from the previous release
php artisan optimize         # rebuild config + route cache (writes into bootstrap/cache/)
php artisan view:cache       # precompile Blade views
```

### Step 6 — Restart background services

```bash
php artisan queue:restart              # signals workers to finish their current job, then exit
sudo supervisorctl restart carrygo-worker:*
```

`queue:restart` alone isn't enough on its own to guarantee workers picked up new code if Supervisor doesn't cycle them — the explicit `supervisorctl restart` ensures the worker processes are actually replaced.

> [!NOTE]
> There is no `carrygo-reverb` service to restart — this project doesn't run Laravel Reverb or any WebSocket daemon (`BROADCAST_CONNECTION=log`). If that changes in the future, add its restart here and in `post-deploy.sh`.

### Step 7 — Reload the web server / PHP-FPM

```bash
sudo systemctl reload php8.3-fpm
sudo systemctl reload nginx        # or: sudo systemctl reload apache2
```

`reload` (not `restart`) applies config changes without dropping in-flight connections.

### Step 8 — Maintenance mode off

```bash
php artisan up
```

### Step 9 — Permissions verification

```bash
sudo chown -R www-data:www-data storage bootstrap/cache
sudo find storage -type d -exec chmod 775 {} \;
sudo find storage -type f -exec chmod 664 {} \;
```

Uploads via SFTP often land owned by your SFTP user, not `www-data` — skipping this step is the single most common cause of a post-deploy 500 error.

---

## 4. Automated Server Script (`post-deploy.sh`)

Save this at `/var/www/ncarrygo.com/html/post-deploy.sh` (upload it once via SFTP, then reuse it for every release). It encodes steps 1–9 above, fails fast on any error (`set -e`), and always brings the site back up — even if a step fails — via a `trap`.

```bash
#!/usr/bin/env bash
#
# post-deploy.sh — run on the production server after every WinSCP/SFTP upload.
# Usage: cd /var/www/ncarrygo.com/html && ./post-deploy.sh

set -e
set -o pipefail

APP_DIR="/var/www/ncarrygo.com/html"
PHP_FPM_SERVICE="php8.3-fpm"
WEB_SERVER_SERVICE="nginx"          # change to "apache2" if using Apache
SUPERVISOR_WORKER_GROUP="carrygo-worker"
BUILD_ON_SERVER=false               # set true only if Node is installed on this server

cd "$APP_DIR"

echo "==> Generating maintenance-mode bypass secret"
MAINTENANCE_SECRET="$(openssl rand -hex 16)"
echo "    Bypass URL: https://ngcarrygo.com/${MAINTENANCE_SECRET}"

# Always try to bring the site back up, even if something below fails.
trap 'echo "==> Ensuring maintenance mode is lifted"; php artisan up || true' EXIT

echo "==> [1/9] Enabling maintenance mode"
php artisan down --secret="${MAINTENANCE_SECRET}" --retry=60

echo "==> [2/9] Installing Composer dependencies (production)"
composer install --no-dev --optimize-autoloader --no-interaction

echo "==> [3/9] Running database migrations"
php artisan migrate --force

echo "==> [4/9] Verifying frontend build output"
if [ "$BUILD_ON_SERVER" = true ]; then
    echo "    Building assets on server"
    npm ci
    npm run build
fi
if [ ! -f "public/build/manifest.json" ]; then
    echo "    ERROR: public/build/manifest.json is missing. Did you upload the compiled assets?"
    exit 1
fi

echo "==> [5/9] Rebuilding caches"
php artisan optimize:clear
php artisan optimize
php artisan view:cache

echo "==> [6/9] Restarting queue workers"
php artisan queue:restart
sudo supervisorctl restart "${SUPERVISOR_WORKER_GROUP}:*"

echo "==> [7/9] Reloading PHP-FPM and web server"
sudo systemctl reload "${PHP_FPM_SERVICE}"
sudo systemctl reload "${WEB_SERVER_SERVICE}"

echo "==> [8/9] Fixing storage/cache permissions"
sudo chown -R www-data:www-data storage bootstrap/cache
sudo find storage -type d -exec chmod 775 {} \;
sudo find storage -type f -exec chmod 664 {} \;

echo "==> [9/9] Disabling maintenance mode"
php artisan up

trap - EXIT
echo "==> Deployment complete."
```

Make it executable once:

```bash
chmod +x post-deploy.sh
```

Run it after every upload:

```bash
./post-deploy.sh
```

> [!TIP]
> Because of the `trap`, if any step fails, the script still runs `php artisan up` on exit so the site doesn't stay stuck in maintenance mode — but you should still investigate the failure (check `storage/logs/laravel.log`) before assuming the release is healthy.

---

## 5. Post-Deploy Health Check Matrix

Run these immediately after `post-deploy.sh` completes.

| Check | Command | Expected result |
|---|---|---|
| Site responds | `curl -o /dev/null -s -w "%{http_code}\n" https://ngcarrygo.com/up` | `200` |
| Homepage loads | `curl -o /dev/null -s -w "%{http_code}\n" https://ngcarrygo.com/` | `200` |
| Queue workers healthy | `sudo supervisorctl status carrygo-worker:*` | All `RUNNING`, not `FATAL`/`BACKOFF` |
| PHP-FPM active | `systemctl is-active php8.3-fpm` | `active` |
| Web server active | `systemctl is-active nginx` (or `apache2`) | `active` |
| Ports listening | `sudo ss -tlnp \| grep -E ':443\|:80'` | 443 and 80 bound |
| No fresh errors in app log | `tail -n 50 storage/logs/laravel.log` | No new stack traces since deploy time |
| No fresh errors in worker log | `tail -n 50 storage/logs/worker.log` | No fatal errors since restart |
| Migrations applied | `php artisan migrate:status \| tail -n 5` | Latest migration shows `Ran` |
| Cron still scheduled | `sudo crontab -u www-data -l` | `schedule:run` entry present |

If any check fails, see the troubleshooting section in [`live_setup_guide.md`](./live_setup_guide.md#9-health-checks--troubleshooting) before deciding whether to roll back.

---

## 6. Rollback Routine

If a deploy causes errors, roll back in this order:

### Step 1 — Immediate mitigation

```bash
php artisan down --secret="$(openssl rand -hex 16)" --retry=60
```

Buys time to investigate without users hitting the broken release.

### Step 2 — Revert application files

Since there's no Git on the server, file rollback depends on your upload discipline:

- **If you keep a timestamped backup before each deploy** (recommended — see tip below), restore it via WinSCP: upload the previous release's files back over the current ones, again respecting the "never overwrite" list in §1.2 (i.e. don't restore an old `.env` or `storage/` over the current ones).
- **If you don't have a backup**, re-upload the previously known-good commit's files from your local Git history:

```bash
# Locally, check out the last good commit into a temp worktree, then upload its contents via WinSCP
git worktree add /tmp/carrygo-rollback <last-good-commit-sha>
```

> [!TIP]
> Before every deploy, snapshot the current release server-side so rollback doesn't depend on your local machine:
> ```bash
> sudo tar -czf /var/backups/carrygo-$(date +%Y%m%d-%H%M%S).tar.gz \
>     --exclude='storage' --exclude='.env' -C /var/www/ncarrygo.com html
> ```
> Restoring is then just `sudo tar -xzf /var/backups/carrygo-<timestamp>.tar.gz -C /var/www/ncarrygo.com` (still excluding `.env`/`storage` from being clobbered — those live outside the tarball already).

### Step 3 — Roll back migrations (only if the deploy included schema changes that broke things)

```bash
php artisan migrate:status         # confirm which batch was just applied
php artisan migrate:rollback --step=1 --force
```

> [!WARNING]
> Only roll back migrations if you're certain the new migration is the cause **and** no production data was written against the new schema yet. Rolling back a migration that dropped/renamed a column with live data in it is destructive and may not be reversible — take a database backup/export first if there's any doubt:
> ```bash
> mysqldump -u carrygo_app -p carrygo > /var/backups/carrygo-db-$(date +%Y%m%d-%H%M%S).sql
> ```

### Step 4 — Reset caches and restart services

```bash
composer install --no-dev --optimize-autoloader --no-interaction
php artisan optimize:clear
php artisan optimize
php artisan view:cache

php artisan queue:restart
sudo supervisorctl restart carrygo-worker:*

sudo systemctl reload php8.3-fpm
sudo systemctl reload nginx   # or apache2
```

### Step 5 — Verify and bring back up

Re-run the [health check matrix](#5-post-deploy-health-check-matrix) above, then:

```bash
php artisan up
```

Confirm `https://ngcarrygo.com/up` returns `200` before considering the rollback complete.
