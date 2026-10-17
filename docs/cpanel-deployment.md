# Deploying to Shared cPanel Hosting

The dev stack (`docker compose`) runs Laravel Horizon on Redis, file storage on
MinIO, and Docker service hostnames (`db`, `redis`) for everything. None of
that exists on typical shared cPanel hosting — no root/Docker, no persistent
background processes, no Redis, no object storage service. This app runs
there anyway, without code changes beyond what already ships:

- Every `Cache::tags()` call goes through `App\Support\CacheTags`, which
  emulates tagging with plain versioned keys on **any** cache driver — native
  Laravel tag support only exists on Redis/Memcached, not the database/file
  drivers shared hosting uses. Nothing in the app calls the raw
  `Cache::tags()` facade.
- `database/migrations` already ships the `cache`, `jobs`, and `sessions`
  tables Laravel's database-backed cache/queue/session drivers need — no
  extra migration to write.
- Every module reads/writes files through `Storage::disk('minio')` by name.
  `config/filesystems.php`'s `minio` disk definition falls back to a private
  local disk (`storage/app/private/files`) automatically whenever
  `AWS_ENDPOINT` is unset — object storage is optional, not required. Those
  files are never web-reachable by path; the app serves them through its own
  role-checked routes or short-lived signed links.
- Laravel Horizon is only used if `QUEUE_CONNECTION=redis`. Leaving it on the
  `database` driver means Horizon's package (and its dashboard) just sits
  unused — nothing tries to reach Redis.

Read this whole page before starting; the document-root step and the cron
step are both easy to get wrong on the first try.

## What you need from your host

- **PHP 8.3** — set via cPanel's *MultiPHP Manager* (or *Select PHP Version*).
  Confirm the following extensions are enabled there: `mbstring`, `openssl`,
  `pdo_mysql`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `fileinfo`,
  `gd` (image resizing/QR codes), `zip` (imports/exports), `curl` (payment
  gateways, SMS, LMS's Anthropic API calls).
- **A Terminal (or SSH)** — needed for Composer, `php artisan` commands, and
  `storage:link`. Most cPanel installs ship a *Terminal* app under
  *Advanced* even without shell/SSH access on the account. If your host has
  neither, you cannot self-serve this deployment — ask their support to run
  the Composer/artisan steps below for you, or to enable Terminal.
- **A MySQL database** — create one via *MySQL Databases*. cPanel prefixes
  both the database name and the database user with your account username
  (e.g. `cpaneluser_school`), and enforces a length limit on both — keep the
  names short.
- **mod_rewrite enabled** — virtually always true on cPanel/Apache; Laravel's
  `public/.htaccess` (already in this repo) depends on it for pretty URLs.

## 1. Get the code and dependencies onto the server

**With Terminal/SSH:**

```bash
git clone <your-repo-url> school-management-backend
cd school-management-backend
composer install --no-dev --optimize-autoloader
npm ci && npm run build   # only if you're building any local assets — the
                           # admin/public views load Bootstrap/DataTables/etc.
                           # from CDN, so this step is usually a no-op
```

**Without Terminal (or without Composer available on the host):** run
`composer install --no-dev --optimize-autoloader` on your own machine, then
upload the whole project — including the generated `vendor/` folder — via
File Manager or FTP. `vendor/` is large (tens of thousands of files); a zip
upload + "Extract" in File Manager is far faster than dragging files over
FTP one at a time.

## 2. Document root — the part most guides get vague about

Laravel's entry point is `public/index.php`. Everything else (`app/`,
`bootstrap/`, `config/`, `.env`, `vendor/`, …) must **not** be reachable over
the web — anyone who can fetch `.env` directly can read your DB credentials
and `APP_KEY`.

**If cPanel lets you set the domain's document root** (Domains → your domain
→ Document Root): point it at `.../school-management-backend/public`
directly. This is the clean option — do this if it's available, which it
increasingly is even on budget hosts for the account's primary domain.

**If you can't change the document root** (common for addon domains on
budget shared plans): keep the app outside `public_html/` entirely, e.g. at
`/home/cpaneluser/school-management-backend/`, then:

1. Copy every file from `school-management-backend/public/` into
   `public_html/` (or the addon domain's own folder) — `index.php`,
   `.htaccess`, `favicon.ico`, `robots.txt`, everything.
2. Edit the copied `public_html/index.php` — it has two lines that `require`
   paths relative to itself:

   ```php
   require __DIR__.'/../vendor/autoload.php';
   $app = require_once __DIR__.'/../bootstrap/app.php';
   ```

   Change `__DIR__.'/../'` to the real absolute path of the app root, e.g.:

   ```php
   require '/home/cpaneluser/school-management-backend/vendor/autoload.php';
   $app = require_once '/home/cpaneluser/school-management-backend/bootstrap/app.php';
   ```

Either way, the app root itself (wherever it lives) must **not** sit inside
`public_html/` unless its document root was pointed at `public/` per the
first option — otherwise the whole codebase, `.env` included, is directly
web-accessible.

## 3. Configure `.env`

Copy `.env.cpanel.example` (not `.env.example`, which targets the Docker
stack) to `.env` in the app root, and fill in the placeholders: `APP_URL`,
the `DB_*` block from your cPanel MySQL database, and the `MAIL_*` block.
Leave the commented-out `AWS_*` block alone unless you're deliberately
pointing at S3-compatible object storage — see the comment in that file.

Then generate the app key:

```bash
php artisan key:generate
```

## 4. Database

```bash
php artisan migrate --force
php artisan db:seed --force   # optional — only if you want the shipped demo/reference data
```

`--force` is required because `APP_ENV=production` blocks destructive
artisan commands from running interactively without it.

## 5. Storage symlink

Uploaded files (student/staff photos, documents, ID card batches, generated
PDFs, admission uploads, website media, …) live in private storage under
`storage/app/private` and are streamed by the app, not served directly. Only
the site logo, favicon and share image live under `storage/app/public` and
are served through a symlink at `public/storage`:

```bash
php artisan storage:link
```

This needs Terminal/SSH — cPanel's File Manager cannot create real symlinks.
If you used the "copy `public/` contents into `public_html/`" layout from
step 2, the symlink still needs to land inside that `public_html/` copy, not
the app's own `public/` folder — run the command from the app root as shown
above; Laravel creates the link at the path configured in
`config/filesystems.php`'s `links` array (`public_path('storage')`), which
resolves relative to wherever `public/` actually is for that request.

## 6. Cron — the scheduler and the queue worker

Shared hosting has no Supervisor and no persistent Horizon process, so both
of Laravel's background pieces run as short-lived cron jobs instead. Add
both under cPanel's *Cron Jobs*, every minute:

```cron
* * * * * php /home/cpaneluser/school-management-backend/artisan schedule:run >> /dev/null 2>&1
* * * * * php /home/cpaneluser/school-management-backend/artisan queue:work --stop-when-empty --max-time=50 --tries=3 >> /dev/null 2>&1
```

- The first line drives everything in `routes/console.php` (currently just
  `attendance:auto-close`, every 30 minutes — Laravel's scheduler itself
  handles not running it more often than that even though cron fires every
  minute).
- The second line processes queued jobs — ID card batch generation, data
  imports, SMS batch sending, LMS AI-check calls, admission email
  notifications, etc. `--stop-when-empty` makes each invocation exit as soon
  as the queue is drained instead of idling, and `--max-time=50` caps each
  run under a minute so consecutive cron firings never overlap or pile up.
  Use the exact path to your PHP 8.3 binary if `php` on the cron `PATH`
  resolves to an older default (check via *MultiPHP Manager* → the CLI
  version note, or ask your host — some cPanel installs need
  `/usr/local/bin/php83` or similar instead of bare `php`).

## 7. File permissions

`storage/` and `bootstrap/cache/` must be writable by whichever user PHP
actually runs as. On cPanel (suPHP/PHP-FPM running as your own account user)
this is usually already correct after upload, but if you hit "permission
denied" writing logs, cache, or sessions:

```bash
chmod -R 775 storage bootstrap/cache
```

## 8. Production performance caching

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

**Gotcha:** `config:cache` bakes the current `.env` values into a single
cached file — any `.env` edit after this point has *no effect* until you
run `php artisan config:clear` (or re-run `config:cache`). This trips people
up after a "quick .env tweak" on a live site that mysteriously doesn't take.

## 9. PHP limits worth raising

Shared hosting defaults are usually tuned for brochure sites, not batch PDF
generation or spreadsheet imports. Check/raise these in *MultiPHP INI
Editor*:

- `upload_max_filesize` / `post_max_size` — student/staff photos, admission
  photos, website media uploads default to small caps (2M/8M) on many hosts.
- `max_execution_time` — the ID Card module already chunks batches at 200
  cards per PDF specifically to bound how long a single request/job runs, but
  a very low limit (some hosts default to 30s) can still be tight for large
  data imports or exam-seating generation. 120s is a reasonable target if
  your host allows it.
- `memory_limit` — 256M minimum; DomPDF rendering (admit cards, testimonials,
  ID cards, reports) and `maatwebsite/excel` imports are the most
  memory-hungry paths in the app.

## 10. HTTPS

Enable *AutoSSL* (free Let's Encrypt, usually on by default on modern
cPanel) or install your own certificate, then make sure `APP_URL` in `.env`
uses `https://`. Several modules generate absolute URLs from `APP_URL`
directly (public-site SEO tags, storage disk URLs, admission form links in
SMS/email), so a mismatched scheme here shows up as mixed-content warnings
or broken links rather than a hard error.

## Updating an existing installation

"Paste the new version over the old one" is exactly the wrong mental model
for a live install — a wholesale file replace risks deleting uploaded files
that were never in git (`storage/app/public`/`storage/app/private` — photos,
ID card batches, website media, generated PDFs), overwriting your real
`.env` with a template, and serving a torn mix of old/new files to whoever
happens to load the site mid-update. See `docs/vps-deployment.md`'s
"Updating an existing installation" section for the full explanation of
each failure mode and the safe order of operations (maintenance mode → back
up the DB → update code → `composer install` → migrate → rebuild caches →
smoke-test → maintenance mode off) — it applies here too, only the *how you
get new code onto the server* step differs without git/SSH:

1. On your own machine, run `composer install --no-dev --optimize-autoloader`
   against the new version and zip the result (or just the files that
   changed, if you're confident tracking that by hand — zipping everything
   is safer).
2. Upload the zip to a **new, separate folder** next to the live app (never
   extract directly over it) and extract it there.
3. Copy the live `.env` into the new folder — do not use the template.
4. Copy (not move, until you've confirmed the new version works)
   `storage/app/public` and `storage/app/private` from the live install into
   the new folder, so uploaded files carry over.
5. Put the live site in maintenance mode: if you have Terminal access,
   `php artisan down --secret=updating`; if not, temporarily point the
   domain's document root at a static "back in a few minutes" HTML page via
   cPanel's Domains screen, or accept a short window of errors for a small
   site — there's no File-Manager-only equivalent of `artisan down`.
6. Back up the database — phpMyAdmin's Export, or `mysqldump` if Terminal
   is available.
7. Swap the folders: rename the live app folder aside (e.g. add `-old`),
   rename the new folder into its place. A rename is much closer to atomic
   than copying files in one by one, which minimizes the torn-update window
   from step 3 above.
8. If Terminal is available: `php artisan migrate --force`, then
   `php artisan config:clear && php artisan config:cache && php artisan route:cache && php artisan view:cache`.
   Without Terminal, you cannot run migrations at all on most shared
   hosts — this is the single biggest reason to prefer a host with a
   Terminal app over one without, once you're maintaining a live site
   rather than just standing one up once.
9. **Updating from a version before 1.5.0:** uploads used to be kept in
   `storage/app/public`, readable by anyone with the link. Move them to
   private storage once: `php artisan storage:make-uploads-private`
   (add `--dry-run` first to see the list). Until you do, older photos and
   documents won't open. Without Terminal, do the same by hand in File
   Manager: move every folder inside `storage/app/public` **except** `site`
   and `admissions` into `storage/app/private/files` (create it if needed).
10. Load `/api/v2/health` yourself and confirm `"status":"ok"` before
   pointing anyone else at the site again (`php artisan up` if you used
   maintenance mode).
11. Once you're confident the update is good, delete the `-old` folder.

If your host's Terminal does have `git` available, `scripts/deploy.sh` (see
`docs/vps-deployment.md`) automates the whole maintenance-mode/backup/
migrate/cache/health-check sequence in one command — worth checking for
before doing this by hand every time.

## What deliberately still needs Redis (optional, VPS-cPanel only)

If your host is a VPS running WHM/cPanel with full SSH access rather than
budget shared hosting, you can install Redis and Supervisor and run the
stack much closer to the Docker setup: `QUEUE_CONNECTION=redis`,
`CACHE_STORE=redis`, `SESSION_DRIVER=redis`, and `php artisan horizon` under
Supervisor instead of the cron-based queue worker in step 6. `predis/predis`
is already a Composer dependency, so `REDIS_CLIENT=predis` works without
needing the `redis` PECL extension compiled — useful if your VPS image
doesn't have it. None of this is necessary for a correctly working
deployment; it's a throughput/latency upgrade for larger schools, not a
requirement.

## Troubleshooting

| Symptom | Likely cause |
|---|---|
| Blank page / 500 with no detail | `APP_DEBUG=false` hides the real error — temporarily set it to `true`, reload, read the error, then set it back to `false`. Also check `storage/logs/laravel.log`. |
| "vendor/autoload.php not found" | Composer dependencies weren't installed/uploaded, or `public/index.php`'s paths (step 2) weren't updated for the copied-`public/`-contents layout. |
| `.env` changes don't seem to apply | Config was cached (step 8) — run `php artisan config:clear`. |
| Site logo/favicon 404 | `storage:link` (step 5) wasn't run, or was run before the document-root layout was finalized and points at the wrong `public/`. |
| Photos/documents uploaded before updating to 1.5.0 don't open | They're still in `storage/app/public` — run `php artisan storage:make-uploads-private` (Updating, step 9). |
| Scheduled/queued things never happen | Cron jobs (step 6) aren't installed, use the wrong PHP path, or point at the wrong `artisan` path. Check cPanel's cron job email output (sent to the account's contact email by default) for the actual error. |
| Emails never send | `MAIL_MAILER=log` (the Docker default) was carried over instead of using `.env.cpanel.example`'s SMTP block — logged emails write to `storage/logs/laravel.log`, not your inbox. |
| `/api/v2/health` shows `"version":"unknown"` | The `VERSION` file is missing or its contents got mangled during upload (e.g. line-ending conversion, accidental edit) — it must be a plain semver string like `1.3.3`, nothing else. |
| `/api/v2/health` shows `"version_verified":false` | The `VERSION` value doesn't match a real tagged release reachable from the code currently on disk — either it was hand-edited, or the wrong commit/tag got deployed. Run `php artisan version:verify` for a fuller explanation. `null` instead of `false` is expected and fine here if this install has no `.git` directory (a zip upload, per this doc) — that's "can't check", not "wrong". |
