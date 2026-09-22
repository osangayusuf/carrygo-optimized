# Local Development Setup Guide — Carrygo

This guide gets a new developer from a clean machine to a running local copy of **Carrygo** using [Laravel Herd](https://herd.laravel.com/), MySQL, and phpMyAdmin.

> [!NOTE]
> Commands are identical on Windows and macOS unless noted otherwise. Herd provides its own PHP, Nginx, and DNS resolution — you do not need to install PHP, a web server, or edit `/etc/hosts` separately.

---

## 1. Tech Stack

| Layer | Technology | Version |
|---|---|---|
| Language | PHP | `^8.3` (CI tested on 8.3, 8.4, 8.5) |
| Framework | Laravel | `^13.0` |
| Frontend bridge | Inertia.js (Laravel + Vue adapters) | `^3.0` |
| Frontend framework | Vue | `^3.5` |
| Language (frontend) | TypeScript | `^5.2` |
| Build tool | Vite | `^8.0` |
| CSS | Tailwind CSS | `^4.1` |
| Node.js | — | `22.x` (matches CI) |
| Database | MySQL | 8.x (via Herd) |
| DB Admin UI | phpMyAdmin | latest, self-hosted under Herd |
| Local dev environment | Laravel Herd | latest |
| Queue driver (local) | `database` | — |
| Broadcast driver (local) | `log` (no WebSocket/Reverb service in this project) | — |
| Test runner | Pest | `^4.4` (`pestphp/pest-plugin-laravel`) |
| Linting (PHP) | Laravel Pint | `^1.27` (`laravel` preset) |
| Linting (JS/TS) | ESLint + `vue-tsc` | `^9.17` / `^2.2` |
| Formatting | Prettier (+ Tailwind plugin) | `^3.4` |

---

## 2. Prerequisites

Install these before cloning the repository.

### 2.1 Git

- **macOS:** `xcode-select --install`, or `brew install git`
- **Windows:** [git-scm.com/download/win](https://git-scm.com/download/win)

### 2.2 Node.js & npm

Install Node **22.x** (matches the version CI runs against).

- Recommended: install via [nvm](https://github.com/nvm-sh/nvm) (macOS/Linux) or [nvm-windows](https://github.com/coreybutler/nvm-windows):

```bash
nvm install 22
nvm use 22
```

- Or download directly from [nodejs.org](https://nodejs.org/).

Verify:

```bash
node -v   # v22.x
npm -v
```

### 2.3 Laravel Herd

Herd bundles PHP, a local Nginx instance, and DNS for `.test` domains.

- **macOS:** download from [herd.laravel.com](https://herd.laravel.com/) and drag to Applications. Herd installs PHP, Composer, and Nginx for you.
- **Windows:** download the Windows installer from [herd.laravel.com/windows](https://herd.laravel.com/windows). Herd for Windows bundles PHP and Composer the same way.

After installing, open Herd and confirm it's running (menu bar icon on macOS, system tray on Windows).

---

## 3. Herd Configuration

### 3.1 Park your projects directory

Herd "parks" a folder — every subfolder becomes `<folder-name>.test` automatically.

- **macOS:** Herd parks `~/Herd` by default. Clone/copy this repo into `~/Herd/carrygo-optimized`.
- **Windows:** Herd parks `C:\Users\<User>\Herd` by default. Clone/copy this repo into `C:\Users\<User>\Herd\carrygo-optimized`.

If you keep projects elsewhere, add that folder in **Herd → Settings → Sites → + Park a Path**.

Once parked, the site is reachable at:

```
https://carrygo-optimized.test
```

(This matches `APP_URL` in the project's `.env`.)

### 3.2 PHP version

Herd ships multiple PHP versions. This project requires **PHP ^8.3**.

1. Open **Herd → Settings → PHP**.
2. Ensure **PHP 8.3** (or 8.4) is installed and available.
3. In **Herd → Sites**, click the `carrygo-optimized` site and set its **PHP version** explicitly to 8.3/8.4 if it isn't already using the global default.

Verify from a terminal:

```bash
php -v
```

### 3.3 Enable MySQL

1. Open **Herd → Settings → Services** (or the **Database** tab, depending on Herd version).
2. Toggle **MySQL** on. Herd will install and start a local MySQL server (default: `127.0.0.1:3306`, user `root`, empty password).

Verify:

```bash
mysql -u root -h 127.0.0.1 -e "SELECT VERSION();"
```

---

## 4. phpMyAdmin Setup via Herd

Herd doesn't ship phpMyAdmin, but since every folder under the parked directory becomes a site automatically, you can drop phpMyAdmin in as its own "project."

1. Download the latest phpMyAdmin release (`.zip`, "all languages") from [phpmyadmin.net/downloads](https://www.phpmyadmin.net/downloads/).
2. Extract it into your parked Herd folder and rename the extracted folder to `phpmyadmin`:
   - macOS: `~/Herd/phpmyadmin`
   - Windows: `C:\Users\<User>\Herd\phpmyadmin`
3. Inside that folder, copy `config.sample.inc.php` to `config.inc.php`.
4. Open `config.inc.php` and set a random 32-character `blowfish_secret`:

```php
$cfg['blowfish_secret'] = 'replace-with-a-random-32-char-string';
```

   Generate one quickly with:

```bash
php -r "echo bin2hex(random_bytes(16)), PHP_EOL;"
```

5. Confirm the MySQL connection block points at Herd's local MySQL:

```php
$cfg['Servers'][$i]['host'] = '127.0.0.1';
$cfg['Servers'][$i]['user'] = 'root';
$cfg['Servers'][$i]['password'] = '';
```

6. Herd auto-detects the new folder as a site. Visit:

```
http://phpmyadmin.test
```

7. Log in with user `root` and an empty password (Herd's default local MySQL credentials).

> [!TIP]
> If `phpmyadmin.test` 404s, restart Herd's Nginx from the Herd menu, or re-run Herd's "park" action on the parent folder.

---

## 5. Repository Setup & Initialization

### 5.1 Clone

```bash
cd ~/Herd   # or C:\Users\<User>\Herd on Windows
git clone <repository-url> carrygo-optimized
cd carrygo-optimized
```

### 5.2 Install dependencies

```bash
composer install
npm install
```

### 5.3 Environment file

```bash
cp .env.example .env
php artisan key:generate
```

Then edit `.env` and set at minimum:

```ini
APP_NAME=Carrygo
APP_URL=https://carrygo-optimized.test

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=carrygo
DB_USERNAME=root
DB_PASSWORD=
```

> [!IMPORTANT]
> `.env.example` defaults to `DB_CONNECTION=sqlite`. This project's actual local setup uses **MySQL** via Herd — switch it as shown above before migrating.

The project also ships a convenience Composer script that automates steps 5.2–5.3 plus a migrate + build (useful for CI-style bootstrapping, less so for day-to-day dev since it also runs `npm run build`):

```bash
composer run setup
```

---

## 6. Database & Data

### 6.1 Create the database

Via phpMyAdmin (`http://phpmyadmin.test`): **New** → name it `carrygo` (or match whatever you set in `DB_DATABASE`) → **Create**.

Or via CLI:

```bash
mysql -u root -h 127.0.0.1 -e "CREATE DATABASE carrygo CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

### 6.2 Run migrations (and seed)

```bash
php artisan migrate --seed
```

This creates the full schema — including the app's custom `carrygo_*` tables (users, reward wallet, achievements, leaderboard snapshots, etc.) — and seeds baseline data via `database/seeders`.

If you're instead importing an existing SQL dump (e.g. from a teammate or a staging snapshot), import it through phpMyAdmin's **Import** tab, or:

```bash
mysql -u root -h 127.0.0.1 carrygo < dump.sql
```

then run `php artisan migrate` afterward to apply anything newer than the dump.

---

## 7. Real-Time / WebSockets

This project does **not** currently use Laravel Reverb, Pusher, or any WebSocket broadcasting service. `BROADCAST_CONNECTION` is set to `log` locally, meaning broadcast events are written to the log file instead of pushed over a socket. No additional setup is required here — skip this section unless the project adds real-time features later.

---

## 8. Running the Application

The simplest option, since Herd's Nginx already serves the app over `https://carrygo-optimized.test`, is to just run the asset watcher and queue worker:

```bash
npm run dev
```

Then, in a second terminal, run the queue worker if you're testing anything that dispatches jobs (rewards, referral processing, etc.):

```bash
php artisan queue:listen --tries=1
```

Alternatively, use the project's all-in-one dev script, which runs `php artisan serve`, the queue listener, and `npm run dev` concurrently in one terminal (useful if you're **not** relying on Herd's Nginx, e.g. running outside a parked directory):

```bash
composer run dev
```

> [!NOTE]
> If you're using Herd's `https://carrygo-optimized.test` URL, you don't need `php artisan serve` — Herd's Nginx already serves PHP. `composer run dev` is mainly useful for non-Herd setups or CI-like environments.

---

## 9. Running Tests & Quality Checks

### 9.1 Full CI-equivalent check

```bash
composer run ci:check
```

This runs ESLint, Prettier check, `vue-tsc` type checking, and the full test suite in sequence — the same steps CI runs.

### 9.2 Individual checks

```bash
# PHP tests (Pest)
php artisan test
composer run test          # also clears config cache + runs Pint check first

# PHP linting (Pint, Laravel preset)
composer run lint          # auto-fixes
composer run lint:check    # check only, no changes

# JS/TS linting (ESLint)
npm run lint                # auto-fixes
npm run lint:check          # check only

# Formatting (Prettier)
npm run format               # auto-fixes
npm run format:check         # check only

# TypeScript type checking
npm run types:check
```

---

## 10. Troubleshooting & FAQs

**"Vite manifest not found" error in the browser**
The frontend assets haven't been built or the dev server isn't running. Run `npm run dev` (for local hot-reload) or `npm run build` (to generate `public/build` for a "production-like" local check).

**Browser shows a certificate warning on `https://carrygo-optimized.test`**
Herd self-signs certificates for parked `.test` domains. Open **Herd → Settings → General → "Install Herd's certificate"** (macOS) or the equivalent trust-certificate action in Herd for Windows, then restart your browser.

**`SQLSTATE[HY000] [2002] Connection refused` on migrate**
MySQL isn't running under Herd. Open **Herd → Settings → Services** and confirm MySQL is toggled on; also double check `DB_HOST=127.0.0.1` and `DB_PORT=3306` in `.env`.

**Port conflicts (3306, 80, 443)**
If another local MySQL/Nginx install (e.g. MAMP, XAMPP, Docker) is already bound to these ports, stop it or change Herd's service ports in **Herd → Settings → Services**.

**`composer install` fails on a PHP version mismatch**
Confirm the site's PHP version in **Herd → Sites → carrygo-optimized → PHP Version** is 8.3 or newer, and that your terminal's `php -v` matches (Herd's CLI `php` follows the *currently selected* version, which can differ from a specific site's pinned version).

**phpMyAdmin shows a blank page or "Incorrect configuration"**
Usually a missing or malformed `blowfish_secret`. Re-check step 4 under [phpMyAdmin Setup](#4-phpmyadmin-setup-via-herd) — the secret must be exactly present and a non-empty string.

**Changes to `.vue`/`.ts` files aren't reflecting**
Confirm `npm run dev` is still running in a terminal — Vite's dev server needs to stay active for HMR. A stale `npm run build` output in `public/build` will otherwise be served instead.
