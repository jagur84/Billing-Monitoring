# SE Billing — ISP Billing & Operations Platform

Laravel 11 + Blade + Tailwind billing platform for ISPs, inspired by sebilling.com.
Covers customer & package management, invoicing, Tripay online payments, MikroTik
auto isolir/restore, GenieACS device monitoring, technician ticketing, inventory,
and automated email/WhatsApp billing notifications with public payment links.

## Stack

- PHP 8.2-FPM + Nginx + MySQL 8 + Redis, all in Docker Compose
- Laravel 11, Breeze (Blade + Tailwind), Spatie Laravel-Permission (RBAC)
- `evilfreelancer/routeros-api-php` for MikroTik, custom Guzzle-based clients for
  Tripay and GenieACS (see `app/Services/`)
- Self-hosted WhatsApp engine (`docker/whatsapp/`, Node.js + Baileys) — no
  third-party WhatsApp API

## Getting started

```bash
cp .env.example .env
docker compose build
docker compose up -d
docker compose exec app composer install   # populates the vendor named volume
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
docker compose exec app chmod -R 777 storage bootstrap/cache
npm install && npm run build               # or: docker compose --profile dev-assets up node
```

App runs at **http://localhost:8090**. Seeded login: `admin@sebilling.test` / `password`
(role `super-admin`); a `teknisi@sebilling.test` / `password` technician account is
also seeded.

### Windows / Docker Desktop note

`vendor/` and `node_modules/` are mounted as **named Docker volumes**, not bind-mounted
from the host. Bind-mounting thousands of PHP files from a Windows path through
Docker Desktop's filesystem bridge made every request take 10–25s (opcache re-stats
every vendor file each request). Keeping `vendor/` on a native volume dropped that to
under a second. Whenever `composer.json`/`composer.lock` changes, re-run
`docker compose exec app composer install` (or `docker compose build app` for a fresh
image) — a plain `docker compose restart` will **not** pick up new packages since the
volume is stateful.

Whenever the `app` container is rebuilt or recreated, also run
`docker compose restart nginx` — nginx resolves the `app` hostname once at startup and
caches its IP, so after `app` gets a new container IP, nginx keeps talking to the old
one and every request 502s until it's restarted.

`storage/` and `bootstrap/cache/` are bind-mounted from the host, so any file created
via `docker compose exec app ...` (which runs as root) ends up **root-owned**, while
actual web requests run through php-fpm as `www-data` — that mismatch causes
`Permission denied` errors writing to `storage/logs/laravel.log`, sessions, or cached
views. If that happens, re-run:
```bash
docker compose exec app chmod -R 777 storage bootstrap/cache
```

## Mobile app (Flutter, Android)

A companion Android app lives in [`mobile/`](mobile/) — base URL setup, login, and
a permission-driven Dashboard + read-only lists (Pelanggan, Tagihan, Pembayaran,
Tiket, Paket, Inventaris, Pengeluaran & Operasional, Laporan Pendapatan), all backed
by the `routes/api.php` Sanctum token API. See **[`mobile/README.md`](mobile/README.md)**
for Flutter installation, `flutter run` instructions, which base URL to enter
depending on where the app runs (emulator vs. real device vs. internet), and a couple
of Windows-specific build gotchas already worked around in this repo.

## Production deployment (Ubuntu server + Docker)

Step-by-step for deploying this app to a fresh (or shared) Ubuntu server that
already has Docker + Docker Compose installed. This mirrors a real deployment done
against an Ubuntu 24.04 VPS that was **already running other services** (another
Docker stack on a different port, a system-level nginx on port 80) — the steps
below account for that shared-server reality rather than assuming a blank box.

### 1. SSH access

If you don't already have key-based SSH access to the server:
```bash
ssh-keygen -t ed25519 -f ~/.ssh/my_server_key -C "deploy-key" -N ""
ssh-copy-id -i ~/.ssh/my_server_key.pub -p 22 <user>@<server-ip>
# or, if ssh-copy-id / sshpass aren't available (e.g. Git Bash on Windows):
# append the contents of my_server_key.pub to ~/.ssh/authorized_keys on the
# server manually, over an existing password-authenticated session.
```
Then always connect with `ssh -i ~/.ssh/my_server_key -p 22 <user>@<server-ip>`.
Confirm Docker is already usable without `sudo` (the deploy user should be in the
`docker` group — `groups` should list `docker`) and check for **port conflicts**
with anything already running on the server before proceeding:
```bash
sudo ss -tlnp   # note anything already bound to 80, 443, 3306, 6379, 8090, 3010
```
This repo's default ports (`8090` app, `3306` MySQL, `6379` Redis — not published,
see below — `3010` WhatsApp) rarely collide with other stacks, but always verify.

### 2. Get the code onto the server

If the GitHub repo is **private**, HTTPS clone will fail on the server with
`fatal: could not read Username for 'https://github.com'` even though the exact
same URL works from a machine that already has cached GitHub credentials. Use a
**read-only Deploy Key** instead — it never leaves the server and can't push:
```bash
ssh-keygen -t ed25519 -f ~/.ssh/github_deploy_key -C "billing-monitoring-deploy" -N ""
cat ~/.ssh/github_deploy_key.pub   # paste this into GitHub →
# repo → Settings → Deploy keys → Add deploy key (leave "Allow write access" unchecked)
```
Then clone over SSH, pointing Git at that specific key:
```bash
GIT_SSH_COMMAND="ssh -i ~/.ssh/github_deploy_key -o StrictHostKeyChecking=accept-new" \
  git clone git@github.com:<owner>/<repo>.git ~/billing-monitoring
cd ~/billing-monitoring
```

### 3. Production `.env`

Copy `.env.example` to `.env` and change at least these from their local-dev
defaults:
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=http://<server-ip>:8090        # or https://your-domain once you have one
LOG_LEVEL=error
DB_PASSWORD=<generate a strong random value>
DB_ROOT_PASSWORD=<generate a strong random value>
```
Generate strong passwords with `openssl rand -base64 24 | tr -dc 'A-Za-z0-9' | head -c 24`.
Leave `MAIL_MAILER=log`, `TRIPAY_*`, etc. as placeholders — configure those later
through **Pengaturan Sistem** in the app itself (see "Settings UI" below) rather
than editing `.env` again.

### 4. Harden MySQL/WhatsApp exposure on a shared server

The default `docker-compose.yml` publishes MySQL on `3306` and the WhatsApp engine
on `3010` to the host, which is convenient for local dev but unnecessary (and
undesirable) on a public server — nothing outside the Docker network needs to
reach either directly (the app talks to both over the internal `billing-net`
network; the WhatsApp QR is already proxied through Laravel itself). Add a
server-only override (**not committed to git**) that drops both host bindings
entirely:
```bash
cat > docker-compose.override.yml << 'EOF'
services:
  mysql:
    ports: !override []
  whatsapp:
    ports: !override []
EOF
echo 'docker-compose.override.yml' >> .gitignore
```
**Why not just bind them to `127.0.0.1` instead of removing the port entirely?**
That was the first thing tried, and it exposed a real bug on this Docker Compose
version: a service with a `127.0.0.1:host:container` port mapping would start and
pass its healthcheck, but silently **never get attached to the compose-managed
network** — `docker network inspect <project>_billing-net` would list every other
container except the ones with a loopback-bound port, and the `app`/`worker`
containers couldn't resolve `mysql` or `whatsapp` by hostname at all
(`getaddrinfo for mysql failed: Temporary failure in name resolution`). Dropping
the host port mapping entirely (rather than restricting it to loopback) sidesteps
the bug completely and is strictly more secure anyway. If you hit that exact
symptom — a container reachable/healthy on its own but missing from
`docker network inspect`, and dependent containers unable to resolve its
hostname — try removing the port mapping rather than narrowing it, and do a full
`docker compose down && docker compose up -d` (a partial `--force-recreate` of
just the affected services was not enough to fix it in testing; only removing
containers **and the network** and recreating both together did).

### 5. Build and start

```bash
docker compose build
docker compose up -d
docker compose ps   # everything should show "Up", mysql should show "(healthy)"
```
If `docker compose ps` shows containers cycling through `Restarting`, check
`docker compose logs <service> --tail=20` before assuming something is broken —
`worker`/`scheduler` restarting in a loop at this stage is expected and harmless,
they're just failing on a missing `vendor/autoload.php` until the next step runs.

### 6. Install dependencies, generate key, migrate

```bash
docker compose exec app composer install --optimize-autoloader
docker compose exec app php artisan key:generate --force
docker compose exec app php artisan migrate --seed --force
docker compose exec app chmod -R 777 storage bootstrap/cache
```
**If you want the seeded demo data** (sample customers/packages/invoices, same as
local dev), the seeders depend on `fakerphp/faker`, which is a `require-dev`
package — running `composer install --no-dev` (the usual production advice) makes
the seed step fail with `Class "Faker\Factory" not found`. Either run
`composer install` without `--no-dev` (what was done above), or skip `--seed` and
create your own first admin/data instead for a from-scratch production instance.

### 7. Build frontend assets

There's no need to install Node.js on the host — use a disposable container:
```bash
docker run --rm -v "$(pwd)":/var/www/html -w /var/www/html node:20-alpine \
  sh -c "npm install && npm run build"
```
This compiles Tailwind/Vite output into `public/build/`, which nginx serves
directly (no Node process needs to keep running in production, unlike the
`--profile dev-assets` Vite dev server used for local hot-reload).

### 8. Firewall

If the server runs `ufw`, confirm it isn't silently blocking the app **or**
silently letting other stuff through — Docker's own iptables rules bypass ufw's
default-deny policy for anything a container publishes to the host, so a port can
be reachable from the internet even with no explicit `ufw allow` rule for it (this
is exactly why step 4 removes MySQL/WhatsApp's host bindings rather than trusting
ufw to block them). Add an explicit rule for the app port either way, for
clarity:
```bash
sudo ufw allow 8090/tcp comment 'billing app'
```

### 9. Verify

```bash
curl -I http://localhost:8090/login          # from the server itself
curl -I http://<server-ip>:8090/login        # from your own machine, confirms it's actually public
```
Both should return `200`. Log in with the seeded `admin@sebilling.test` / `password`
(if you seeded demo data) and click around before considering the deploy done.

### Updating an existing deployment

Once the server is up and you push new commits, redeploy with this sequence —
safe to run in full every time, since `composer install`/`npm run build` are fast
no-ops when nothing relevant changed and `migrate` is a no-op with no new
migrations:

```bash
ssh -i ~/.ssh/my_server_key -p 22 <user>@<server-ip>
cd ~/billing-monitoring

# 1. Pull the new code (private repo → use the deploy key, same as the initial clone)
GIT_SSH_COMMAND="ssh -i ~/.ssh/github_deploy_key -o StrictHostKeyChecking=accept-new" \
  git pull origin main

# 2. Reinstall PHP deps (picks up composer.json/composer.lock changes)
docker compose exec app composer install --optimize-autoloader

# 3. Rebuild frontend assets (picks up any Blade/Tailwind/JS changes)
docker run --rm -v "$(pwd)":/var/www/html -w /var/www/html node:20-alpine \
  sh -c "npm install && npm run build"

# 4. Run any new migrations
docker compose exec app php artisan migrate --force

# 5. Clear stale compiled config/view/route caches
docker compose exec app php artisan config:clear
docker compose exec app php artisan view:clear
docker compose exec app php artisan route:clear

# 6. Rebuild the image only if the Dockerfile itself changed (new PHP extension,
#    base image bump, etc.) — otherwise skip straight to step 7
docker compose build app worker scheduler
docker compose up -d

# 7. worker/scheduler are long-running daemons that only load code once at
#    startup — restart them after EVERY deploy, code change or not, or they
#    keep running the previous version indefinitely
docker compose restart worker scheduler

# 8. Only needed if step 6 actually recreated the app container: nginx resolves
#    "app"'s IP once at startup and caches it, so a new app container ID means
#    stale requests/502s until nginx is restarted too
docker compose restart nginx
```

Steps 2, 3, and 6 are the only ones worth skipping deliberately (to save a minute
or two) if you're certain the deploy is Blade/PHP-logic-only with no new Composer
package, no frontend change, and no Dockerfile change — everything else in the
list has no downside to always running.

If a migration in the new code is destructive or the deploy is otherwise risky,
back up the database first:
```bash
docker compose exec -T mysql mysqldump -u root -p"$(grep DB_ROOT_PASSWORD .env | cut -d= -f2)" billing_monitoring > backup-$(date +%Y%m%d-%H%M%S).sql
```

### Going further

- **Real domain + HTTPS**: point a domain's DNS at the server, then either reuse
  an existing system nginx as a reverse proxy to `127.0.0.1:8090` with Certbot for
  the certificate, or see the "Temporary public domain (Cloudflare quick tunnel)"
  section above for a zero-config HTTPS option without owning a domain at all.
- **WhatsApp pairing**: still needs a one-time QR scan post-deploy — see
  "WhatsApp engine — installation & running it" above; nothing about it differs
  between local and production.
- **Change the seeded admin password** (or disable seeded accounts) before
  leaving a production deploy running long-term, since the default credentials
  are documented in this very README.

## Services

| Service     | Purpose                                              |
|-------------|-------------------------------------------------------|
| `app`       | PHP-FPM (Laravel)                                     |
| `nginx`     | Web server, port `8090` → `80`                         |
| `mysql`     | Database, exposed on `3306`                            |
| `redis`     | Cache, session, and queue driver                       |
| `worker`    | `php artisan queue:work` (Tripay/reminder/WhatsApp jobs) |
| `scheduler` | `php artisan schedule:work` (invoicing, isolir, etc.)  |
| `whatsapp`  | Self-hosted Baileys WhatsApp engine, `/qr` on port `3010` |
| `node`      | Vite dev server for asset watching (`--profile dev-assets`) |

**After changing any PHP code that `worker`/`scheduler` use (service providers,
Mailables, jobs, config), run `docker compose restart worker scheduler`** — both run
long-lived daemon processes (`queue:work`, `schedule:work`) that only load the
container's code once at startup; a plain code edit does not get picked up until
they're restarted, and you'll see stale-dependency or stale-class errors otherwise.

## Settings UI (super-admin only)

Everything that used to require editing `.env` + `artisan config:clear` can now be
managed at **Pengaturan Sistem** in the sidebar (visible to `super-admin` only):

| Page | What it configures |
|---|---|
| `/settings/smtp` | Mail driver, host/port/credentials, from address — plus a "Kirim Email Tes" button |
| `/settings/tripay` | Mode, merchant code, API key, private key, default payment method — plus "Tes Koneksi" (lists live payment channels) |
| `/settings/whatsapp` | Live connection status + embedded QR (no need to open port `3010` directly) |
| `/settings/billing` | `invoice_generate_days_before`, `isolation_grace_days`, and the day-offset for each of the 4 email / 3 WhatsApp reminder stages |
| `/settings/templates` | Subject + body for all 7 notification templates, with a `{{token}}` placeholder system |

Implementation: `settings` table (`App\Models\Setting`, JSON-encoded key/value, cached)
+ `App\Providers\SettingsServiceProvider::boot()`, which overwrites the relevant
`config()` values on every request **only when a DB setting exists** — every existing
`config('tripay.x')` / `config('mail.x')` / `config('billing.x')` call site keeps
working unmodified; `.env` remains the fallback default when nothing's been saved via
the UI. Because `worker`/`scheduler` are long-running processes, a setting saved via
the UI takes effect on their **next queued job / scheduled tick** without needing a
restart (unlike a code change, which does need one — see above).

Template bodies support `{{customer_name}}`, `{{invoice_number}}`, `{{pay_url}}`, etc.
(exact list shown per-template on the page) — plain text/Markdown only, no Blade/HTML,
rendered via `App\Services\Notifications\TemplateRenderer`.

## Scheduled jobs (routes/console.php)

- `invoices:generate-upcoming` — daily — generates each customer's next invoice
  `INVOICE_GENERATE_DAYS_BEFORE` days (default 7) before their due date, then emails
  an "invoice created" notification
- `invoices:mark-overdue` — daily — flips unpaid past due_date to overdue
- `notifications:send-reminders` — daily — 4-stage email (H-3, H, H+3, H+7) and
  3-stage WhatsApp (H, H+3, H+7) reminders per `config('billing.reminder_offsets')`,
  each stage sent at most once per invoice (tracked in `notification_logs`)
- `mikrotik:isolate-overdue` — daily — isolates customers overdue past grace period (`ISOLATION_GRACE_DAYS`)
- `mikrotik:restore-paid` — daily — safety-net restore sweep (primary restore path is the Tripay webhook)

## Notifications (email + WhatsApp)

- **Invoice created** — sent once, right when `invoices:generate-upcoming` creates
  the invoice.
- **Invoice reminders** — see the reminder schedule above. Both channels embed a
  signed, unauthenticated payment link (`/pay/{invoice}?expires=...&signature=...`,
  valid 14 days) so customers can pay without a staff login.
- **Ticket opened / closed** — sent from `TicketController::store()` and
  `TicketController::addLog()` (on transition into `resolved`/`closed`) to the
  customer's email and phone.
- All sends are deduplicated via `NotificationLog::record()` (unique on notifiable +
  channel + type), so re-running a sweep command never double-sends.

### WhatsApp engine — installation & running it

The engine is its own service (`docker/whatsapp/`, Node.js + `@whiskeysockets/baileys`
+ Express), built and started as part of the normal Docker Compose stack — there's
nothing to install separately outside Docker.

**1. Build & start**
```bash
docker compose build whatsapp   # only needed once, or after editing docker/whatsapp/*
docker compose up -d whatsapp   # already starts automatically with `docker compose up -d`
```
This creates the `billing-whatsapp` container and a named volume, `whatsapp_auth_data`,
that holds the Baileys login session (so you don't have to re-scan on every restart).

**2. Check it's actually running**
```bash
docker compose ps whatsapp                 # should show "Up"
docker compose logs whatsapp --tail=30      # look for "[whatsapp] engine listening on :3000"
curl http://localhost:3010/status           # {"connected": false, "hasQr": true} before pairing
```

**3. Pair a real WhatsApp number (one-time)** — two ways to view the QR, same result:
- **In-app (recommended):** log in as a `super-admin` and open **Pengaturan Sistem →
  WhatsApp** in the sidebar — the QR is embedded right there, proxied through Laravel
  (`GET /settings/whatsapp/qr` → `WhatsAppSettingsController::qr()`), so it works over
  whatever domain/tunnel the app itself is already reachable on.
- **Direct:** open **http://localhost:3010/qr** in a browser on the Docker host.

  Either way: on your phone, open **WhatsApp → Settings (⚙) → Linked Devices → Link a
  Device**, and scan. The page auto-refreshes every few seconds until it detects a
  successful pairing.

**4. Confirm it's linked**
```bash
curl http://localhost:3010/status   # {"connected": true, "hasQr": false}
```
or just reload the **Pengaturan Sistem → WhatsApp** page — the status dot turns green
and the "Terhubung" label appears.

**5. Send a manual test message** (useful for verifying end-to-end before relying on
the scheduled reminders):
```bash
docker compose exec app php artisan tinker --execute="
App\Jobs\SendWhatsAppMessage::dispatch('08xxxxxxxxxx', 'Tes dari SE Billing.');
"
docker compose logs worker --tail=5   # look for "SendWhatsAppMessage ... DONE"
```

**Re-pairing / resetting the session** — if the phone unlinks the device or you need a
fresh session, clear the volume and restart:
```bash
docker compose down whatsapp
docker volume rm billing-monitoring_whatsapp_auth_data
docker compose up -d whatsapp
```
Then scan the QR again (step 3).

**Important — read before relying on this in production:** this uses Baileys, an
*unofficial* WhatsApp Web library — not Meta's Business API. There's no vendor SLA or
support, and the linked number carries a real risk of being banned if used for
bulk/spammy sending. Use a dedicated business number (not a personal one), and keep
volume to transactional messages (reminders, ticket updates) rather than broadcast/marketing.

### Public payment link

`InvoiceService::publicPayUrl(Invoice $invoice)` generates the signed link; the
route (`public.invoice.pay` / `public.invoice.checkout` in `routes/web.php`) is
outside the `auth` middleware group and protected only by Laravel's `signed`
middleware — anyone with a valid, unexpired link can view and pay that one invoice,
no login needed. `PublicPaymentController` reuses
`InvoiceService::resolveTripayCheckoutUrl()`, the same logic the staff-facing
"Bayar via Tripay" button uses.

## Configuring integrations

### Tripay (payment gateway)

**Preferred: Pengaturan Sistem → Tripay** in the app (mode, merchant code, API key,
private key, default payment method, plus a "Tes Koneksi" button that lists live
payment channels to confirm the credentials actually work). Falls back to `.env`
(`config/tripay.php`) when nothing's saved via the UI:
```env
TRIPAY_MODE=sandbox              # "production" once live
TRIPAY_MERCHANT_CODE=T1234       # from the Tripay merchant dashboard
TRIPAY_API_KEY=xxxxxxxxxxxx
TRIPAY_PRIVATE_KEY=xxxxxxxxxxxx
```
(only needed if you're not using the Settings UI — if you do edit `.env` directly,
run `docker compose exec app php artisan config:clear` afterward)

1. Get credentials: sign in at [tripay.co.id](https://tripay.co.id) — note that Tripay
   may require a paid merchant deposit (observed: Rp 500rb) before sandbox/production
   API credentials are unlocked; the exact menu path to find them has been inconsistent
   across Tripay's own documentation, so check their dashboard/support directly rather
   than relying on a fixed menu path here.
2. Register the webhook callback URL in the Tripay dashboard — the exact URL is shown
   on the **Pengaturan Sistem → Tripay** page, or construct it yourself:
   ```
   https://<your-domain>/webhooks/tripay
   ```
   Tripay's servers must be able to reach that URL directly — `localhost` doesn't
   work, so you need a real domain or a temporary tunnel (see below) pointing at
   port `8090`.

### Temporary public domain (Cloudflare quick tunnel)

For webhook testing (Tripay) or letting someone else preview the app without deploying
it, expose local port `8090` with a free, account-less Cloudflare tunnel:

```bash
cloudflared tunnel --url http://localhost:8090
```

This prints a random `https://<random-words>.trycloudflare.com` URL. Two things need
to be set for it to actually work correctly, not just be reachable:

1. **Point `APP_URL` at the tunnel URL**, then clear the config cache — otherwise
   Laravel keeps generating links (including the Tripay `return_url`) against
   `localhost:8090`:
   ```env
   APP_URL=https://<random-words>.trycloudflare.com
   ```
   ```bash
   docker compose exec app php artisan config:clear
   ```
2. **HTTPS is force-scheme'd in `AppServiceProvider::boot()`** whenever `APP_URL`
   starts with `https://`, for two reasons — both already wired up, no action needed
   unless you remove/replace the tunnel setup:
   - `URL::forceScheme('https')` — otherwise `asset()`/`@vite()` generate `http://`
     URLs on an `https://` page, which browsers block as mixed content (CSS/JS
     silently fail to load).
   - `$request->server->set('HTTPS', 'on')` — otherwise **signed URLs** (the public
     payment links, see below) 403 with "Invalid signature": the signature is
     computed treating the URL as `https://`, but validation reconstructs the
     incoming request's own URL, which looks like plain `http://` internally
     (cloudflared → nginx → php-fpm is unencrypted) unless the request is explicitly
     marked secure.

Caveats:
- The quick-tunnel URL is **temporary** — it changes every time `cloudflared` restarts,
  and Cloudflare gives no uptime guarantee for it. For a stable long-term URL, create a
  [named tunnel](https://developers.cloudflare.com/cloudflare-one/connections/connect-apps/)
  tied to a domain in your Cloudflare account instead.
- The moment you tunnel it, the app is genuinely public. Change the seeded admin
  password (or disable the seeded accounts) before leaving a tunnel open for long,
  since `admin@sebilling.test` / `password` is documented right here in this README.
- After switching `APP_URL` back to `http://localhost:8090` for local-only use, run
  `artisan config:clear` again — otherwise the forced HTTPS scheme sticks around and
  local links will point at `https://localhost:8090`, which has no valid cert.

### MikroTik

No `.env` setting needed — add routers directly under **Jaringan → MikroTik** in the
app (host/port/credentials, per-router, since a real deployment usually has more than
one router). Then set a customer's PPPoE username + router (on the customer edit
form) to enable isolir/restore for that customer.

### GenieACS

Not yet in the Settings UI — still `.env`-only, read by `config/genieacs.php`:
```env
GENIEACS_BASE_URL=http://genieacs:7557   # NBI, default port 7557
GENIEACS_USERNAME=
GENIEACS_PASSWORD=
```
Run `docker compose exec app php artisan config:clear` after changing these.

### Reminder & billing schedule

**Preferred: Pengaturan Sistem → Tagihan & Reminder** in the app — sets
`invoice_generate_days_before`, `isolation_grace_days`, and the day-offset for each of
the 4 email / 3 WhatsApp reminder stages, without touching `.env` or restarting anything
(both `worker` and `scheduler` pick up a Settings-UI change on their next tick — see
"Settings UI" above).

Underlying config (`config/billing.php`, fallback when nothing's saved via the UI):
```php
'invoice_generate_days_before' => 7,  // env: INVOICE_GENERATE_DAYS_BEFORE
'isolation_grace_days' => 3,          // env: ISOLATION_GRACE_DAYS
'reminder_offsets' => [
    'email' => [3 => 'reminder_h-3', 0 => 'reminder_h0', -3 => 'reminder_h+3', -7 => 'reminder_h+7'],
    'whatsapp' => [0 => 'reminder_h0', -3 => 'reminder_h+3', -7 => 'reminder_h+7'],
],
```
Keys are days relative to `due_date` (positive = before, negative = after); values are
the `NotificationLog` type used for deduplication. The actual wording per stage is
editable at **Pengaturan Sistem → Template Notifikasi** (see "Settings UI" above) —
defaults live in `App\Services\Notifications\TemplateRenderer` and
`App\Services\Notifications\ReminderStageMessages`.

---

None of Tripay/MikroTik/GenieACS/WhatsApp can be exercised fully live from a dev
sandbox without real credentials/hardware/a scanned device — the service classes
(`app/Services/Payment/TripayService.php`, `app/Services/Mikrotik/MikrotikService.php`,
`app/Services/GenieAcs/GenieAcsService.php`, `app/Services/WhatsApp/WhatsAppService.php`)
are the integration points to test once you have them.
