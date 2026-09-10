# Developer Guide — Company Suite (Laravel)

**Read this before making changes.** It covers what's where, how the system
fits together, and how to add / debug / deploy things without breaking
production.

Take 15 minutes to skim end-to-end. You'll save hours later.

---

## 1. What this project is

Airline **incident-response management system**, converted from a MERN app
([Amir-bss/Company-Suite](https://github.com/Amir-bss/Company-Suite)) to a
Laravel/Blade/Bootstrap stack. Same domain logic — different plumbing.

**Users** — 5 roles, each with their own login + dashboard:

| Role | Purpose |
|---|---|
| **SuperAdmin** | Creates/manages Companies. Platform admin. |
| **Company Admin** | Per-company. Manages Team Members, creates Incidents. |
| **Station Team** | On-the-ground passenger status updates. |
| **GO Team** | Full incident visibility + status updates. |
| **ERC Team** | Next-of-kin (relative info) collection. **Only sees Ongoing incidents.** |
| SAT - Volunteers | Sub-type of Station Team (same login/dashboard). |

Domain model:
```
Company ──< TeamMember (team_type: Station|GO|ERC|SAT)
Company ──< Incident ──< Passenger ──< PassengerUpdate (denormalised submitter)
                                    ──< RelativeInfoUpdate (denormalised submitter)
```

---

## 2. Stack & directory layout

**Backend:** Laravel 12 · PHP 8.3 · SQLite (production) · Eloquent · session cookies + CSRF (no JWT)
**Frontend:** Blade shells + Bootstrap 5 + vanilla JS + Vite bundling
**Deploy:** cPanel Git Version Control API + Vite build upload via GitHub Actions

```
company-suite-laravel/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Controller.php          # abstract base (REQUIRED — see §7 gotchas)
│   │   │   ├── PageController.php      # thin — renders Blade shells
│   │   │   └── ApiController.php       # 500+ lines — every JSON endpoint
│   │   └── Middleware/EnsureRole.php   # role-based access
│   └── Models/                          # 7 Eloquent models
│       ├── SuperAdmin.php · Company.php · TeamMember.php
│       ├── Incident.php · Passenger.php
│       └── PassengerUpdate.php · RelativeInfoUpdate.php
├── bootstrap/
│   ├── app.php                         # app config — 5 auth guards, `role` middleware alias
│   └── cache/                          # framework caches (auto-generated)
├── config/
│   ├── auth.php                        # 5 guards: superadmin, company, station, go, erc
│   ├── database.php                    # sqlite default
│   ├── session.php                     # cookie name 'company-suite-session'
│   └── filesystems.php                 # 'public' disk for uploads
├── database/
│   ├── migrations/                     # 7 tables, one per model
│   ├── seeders/
│   │   ├── DatabaseSeeder.php          # default entry
│   │   ├── SuperAdminSeeder.php        # seeds admin@companysuite.local
│   │   └── TestAccountsSeeder.php      # QA fixtures (2 companies, 6 team members, sample incident)
│   └── database.sqlite                 # DB (auto-created, gitignored)
├── public/
│   ├── index.php                       # Laravel front controller
│   ├── .htaccess                       # rewrites everything → index.php
│   └── build/                          # Vite output (uploaded during deploy)
├── resources/
│   ├── views/                          # 11 Blade pages
│   │   ├── layouts/app.blade.php       # base layout (Bootstrap + Vite)
│   │   ├── auth/login.blade.php        # unified login (all 5 roles)
│   │   ├── home.blade.php · errors/404.blade.php
│   │   ├── superadmin/{dashboard,settings}.blade.php
│   │   ├── company/{dashboard,incidents/{create,show}}.blade.php
│   │   └── {station,go,erc}/dashboard.blade.php
│   ├── sass/app.scss                   # Bootstrap import + brand tokens
│   └── js/
│       ├── app.js                      # entry — dispatches to per-page module
│       ├── api.js                      # fetch wrapper (session cookies + CSRF)
│       ├── auth-gate.js                # calls /api/auth/whoami — client-side role gate
│       ├── ui.js · topbar.js
│       └── pages/{login,superadmin,company-dashboard,...}.js
├── routes/
│   ├── web.php                         # 11 Blade routes → PageController
│   ├── api.php                         # ~50 API routes → ApiController
│   └── console.php
├── .cpanel.yml                         # deploy hook for cPanel Git Version Control
├── .github/
│   ├── workflows/{ci,deploy}.yml       # CI on every push, CD only on main
│   ├── CODEOWNERS · pull_request_template.md
├── API.md                              # source of truth for backend endpoints
├── DEVELOPER_GUIDE.md                  # this file
├── CONTRIBUTING.md                     # branch strategy + secrets list
├── CPANEL_503_FIX.md                   # historical — recovery notes
└── README.md                           # quick start
```

**Rule of thumb:** if you're editing outside `app/`, `resources/views/`, `resources/js/`, `routes/`, or `database/migrations/` — you're probably doing something wrong. Ask before continuing.

---

## 3. How auth works (this trips everyone up first)

### 5 guards, 5 sessions
`config/auth.php` defines 5 auth guards: `superadmin`, `company`, `station`, `go`, `erc`. Each uses Laravel's session driver but a different Eloquent provider. **Logging in as one role does NOT log you in as any other.**

### Login flow
1. User picks role in the `<select>` on `/login`, enters email + password
2. Frontend JS (`resources/js/pages/login.js`) posts to the matching endpoint (e.g. `/api/company/auth/login`)
3. `ApiController::companyLogin()` calls `Hash::check()` against `password_hash`, then `Auth::guard('company')->login($user)` and `$request->session()->regenerate()`
4. Response includes user data + role → frontend redirects to the correct dashboard

### Client-side role gate
Every dashboard page container has a `data-cs-role="..."` attribute. On page load, `resources/js/auth-gate.js` calls `GET /api/auth/whoami`. If it returns 401 → redirect to `/login`. If it returns a different role than the page requires → redirect to that role's dashboard.

### CSRF
- `resources/views/layouts/app.blade.php` embeds `<meta name="csrf-token" content="{{ csrf_token() }}">`
- `resources/js/api.js` reads it and sends **`X-CSRF-TOKEN`** header on every non-GET request
- Laravel's `VerifyCsrfToken` middleware compares. Mismatch → **HTTP 419 "Page Expired"**
- ⚠️ **Do NOT change to `X-XSRF-TOKEN`** — that header expects the DECRYPTED `XSRF-TOKEN` cookie value, not the raw meta value. See §7.

---

## 4. Backend architecture

### Routes → Controllers

**`routes/web.php`** — 11 GET routes for Blade pages, all → `PageController`. Each method just returns `view('...')`. **No business logic in web routes.**

**`routes/api.php`** — ~50 API routes, all wrapped in `Route::middleware('web')` (so sessions + CSRF work), grouped by role prefix:
- `/api/auth/*` → SuperAdmin auth
- `/api/companies/*` → SuperAdmin companies CRUD (middleware: `role:superadmin`)
- `/api/company/*` → Company Admin (middleware: `role:company`)
- `/api/station/*` → Station Team
- `/api/go/*` → GO Team
- `/api/erc/*` → ERC Team
- `/api/uploads/*` → File uploads (any authenticated role)

All routes → `ApiController::<method>`. **One controller, all endpoints** — deliberately monolithic for now; split when it hits 2000 lines.

### Models

7 Eloquent models in `app/Models/`. Notable:

- **`SuperAdmin`, `Company`, `TeamMember`** — implement `Authenticatable`. Override `getAuthPassword()` to return `password_hash` (Laravel expects `password` by default).
- **`Incident`** — has status constants `STATUS_ONGOING` / `STATUS_COMPLETED`.
- **`Passenger`** — nullable `incident_id` (legacy passengers exist without incidents).
- **`PassengerUpdate` / `RelativeInfoUpdate`** — **have `submitted_by` / `updated_by_name` as denormalised text.** DO NOT normalise into a strict FK-only relation. History must survive team-member deletion. This is intentional.

### Middleware

- **`web`** (Laravel default) — session, CSRF, cookies. Applied to ALL routes.
- **`role:<guard>[,<guard>...]`** — checks if the specified guard(s) are authenticated. Returns 401 if none. Defined in `app/Http/Middleware/EnsureRole.php`, aliased in `bootstrap/app.php`.

### Business rules the code enforces

1. **Denormalised history** (see above) — don't normalise `submitted_by`.
2. **ERC access is status-gated** — `ApiController::ercIncident()` returns 403 if `incident.status === 'Operation Completed'`. Same for `createRelativeInfo`.
3. **Guard isolation** — no shared login. A SuperAdmin session doesn't authenticate as a Company.
4. **Multi-tenancy scoping** — every Company/Team endpoint filters by the caller's `companyId`. If you add a new endpoint, remember to scope it.
5. **Latest-status computation** — for `IncidentPassenger`, `latestStatus` is newest overall; `latestGoStatus` / `latestStationStatus` / `latestSatStatus` are newest filtered by submitter's `team_type`.

### Enums (case-sensitive, exact match)

- **TeamType** — `"Station Team"` · `"ERC Team"` · `"GO Team"` · `"SAT - Volunteers"`
- **PassengerStatus** — `"Safe"` · `"Injured"` · `"Critical"` · `"Deceased"` · `"Unconfirmed"`
- **IncidentStatus** — `"Ongoing"` · `"Operation Completed"`

---

## 5. Frontend architecture

### The pattern
Every Blade page is a **shell** — mostly-empty markup with `data-cs-role="..."` attributes and empty tables. Real data loads client-side via `api.js` calling `/api/*` endpoints.

**Why this pattern:** we needed the backend dev to build APIs independently of frontend. Same API surface can drive a future mobile app without touching Blade.

### JS module dispatch

`resources/js/app.js` reads `data-cs-role` on `<body>` or the main container and dynamically imports the matching per-page module:

```js
// simplified
switch (root.dataset.csRole) {
    case 'superadmin':  import('./pages/superadmin.js').then(m => m.init...)
    case 'company':     import('./pages/company-dashboard.js').then(...)
    // etc.
}
```

Each per-page module in `resources/js/pages/` exports an `init<Page>()` function that:
1. Calls `guardPage(<role>)` from `auth-gate.js`
2. Sets up event listeners
3. Fetches initial data via `api.*`

### Adding a new page

1. Add a Blade file in `resources/views/<role>/<page>.blade.php` extending `layouts.app` with `data-cs-role="<role>"` on the container
2. Add a route in `routes/web.php` → `PageController@<methodName>` (which just returns `view(...)`)
3. Add a per-page JS module in `resources/js/pages/<page>.js`
4. Register it in the dispatch in `resources/js/app.js`
5. Run `npm run build` → commit `resources/*` files → push to `main`

### Adding a new API endpoint

1. Add the route in `routes/api.php` inside the `Route::middleware('web')` group, with appropriate `->middleware('role:<guard>')`
2. Add the method in `app/Http/Controllers/ApiController.php` — return `response()->json(...)` or `$this->error('...', 4xx)`
3. Update **`API.md`** with the new endpoint entry (path, method, guard, request/response shape)
4. Update the frontend JS module that consumes it
5. Commit + push to `main`

---

## 6. Deployment flow — what happens when you push

### Branches

| Branch | Trigger | Result |
|---|---|---|
| `main` | Push (direct or merge) | **CI runs → CD auto-deploys** to production |
| `develop` | Push | CI runs only. No deploy. |
| `feature/*` | Push | CI runs only. No deploy. |
| Any other | Push | CI runs only. No deploy. |

**All merges to `main` should go through a PR.** Branch protection should be enabled on GitHub for `main` requiring CI green + review before merge. See `CONTRIBUTING.md`.

### CI (`.github/workflows/ci.yml`) — runs on every push + PR

- PHP syntax lint (every `.php` in `app/ config/ database/ routes/ bootstrap/`)
- `composer install`
- `php artisan route:list` (catches missing controllers, class-not-found)
- `php artisan config:cache` (catches broken config)
- `php artisan migrate` on a fresh SQLite DB (catches broken migrations)
- `npm ci && npm run build` (Vite build succeeds, manifest exists)

**PR blocked from merge if CI fails.** Fix locally, push, wait for green.

### CD (`.github/workflows/deploy.yml`) — runs only on push to `main`

1. Vite build the production bundle in the Actions runner
2. Call `VersionControl/update` API → cPanel pulls latest git commit
3. Upload each `public/build/assets/*` file via `Fileman/upload_files`
4. Call `VersionControlDeployment/create` → cPanel runs `.cpanel.yml` tasks (composer install, artisan cache, migrate)
5. Health-check `/api/healthz` — if 503, auto-bounce PHP-FPM via WHM suspend/unsuspend

**Total deploy time: ~2 minutes.**

### Required GitHub secrets (add once)

Repo → Settings → Secrets and variables → Actions → New secret:

| Secret | Value |
|---|---|
| `CPANEL_HOST` | `stageserverofbss.com:2083` |
| `CPANEL_USER` | `bsstesting457` |
| `CPANEL_API_TOKEN` | (from vault) |
| `CPANEL_REPO_PATH` | `/home/bsstesting457/repositories/company-suite-laravel` |
| `WHM_HOST` | `stageserverofbss.com:2087` |
| `WHM_USER` | `bshsaad` |
| `WHM_API_TOKEN` | (from vault) |
| `APP_URL` | `https://companysuite.stageserverofbss.com` |

Get tokens from the encrypted vault: `bash ~/OneDrive/Documents/vault/cpannel/decrypt.sh`

### To deploy right now (before merging a PR)

Just merge to `main` → auto-deploys. That's it.

### To roll back a bad deploy

```bash
git checkout main
git pull
git revert -m 1 <bad-merge-sha>
git push
```
CD picks it up and re-deploys the previous state within ~2 minutes.

Manually if urgent:
```bash
# On your machine — call the cPanel API directly to force checkout of a known-good SHA
# See scripts/cpanel-rollback.sh (TODO — write this)
```

---

## 7. Gotchas — things that have already broken us

1. **`App\Http\Controllers\Controller` MUST exist** even if empty. Laravel's routing internals check for it. Without it → PHP fatal → **HTTP 503**. This IS in the repo now; don't delete it.

2. **CSRF header is `X-CSRF-TOKEN`** (matches raw meta value), NOT `X-XSRF-TOKEN` (which expects decrypted cookie value). Sending under the wrong name → **HTTP 419 Page Expired** on every POST.

3. **cPanel git pull silently drops all dotfiles** on this host — `.env`, `.htaccess`, `.github/`, `.cpanel.yml`. If you rely on any of these existing after a git pull, verify via the API. Workaround: `Fileman/save_file_content` to write them back.

4. **SSH shell is disabled** on this cPanel account — even though the key auths OK, the shell closes immediately. Never write scripts that assume SSH shell works. Use the `VersionControl/update` + `_deploy.php` pattern instead.

5. **`multiphp` feature is not enabled** — you can't switch PHP versions per subdomain via API. Everything runs on the account-default PHP version (currently 8.3).

6. **Account-wide PHP-FPM can wedge.** If ALL subdomains on the account start returning 503 "The server is temporarily busy" — LSAPI worker pool is stuck. Fix: suspend + unsuspend the account via reseller WHM (works with our `bshsaad` token). 8 seconds downtime. The CD workflow does this automatically on health check failure.

7. **PHP scripts with `exec()`, `shell_exec()`, `system()`, `passthru()`, `popen()` uploaded via API get blocked** by the classifier. If you need to run shell-adjacent things from a helper script, use PHP's built-in file/zip/process APIs instead (`ZipArchive`, `file_put_contents`, `Illuminate\Contracts\Console\Kernel::call('<artisan-command>')`).

8. **Never commit `.env`** — it's in `.gitignore`. Server has its own copy at `/home/bsstesting457/repositories/company-suite-laravel/.env` — edit via cPanel File Manager or write via `Fileman/save_file_content` API.

9. **Never edit existing migrations** — add a new one instead. Migrations run on server via `.cpanel.yml`; editing an old one would try to re-run it and fail.

10. **When Vite rebuild produces new file hashes** (e.g. `app-BsuYxPBg.js` → `app-XyzAbc12.js`), the CD workflow uploads new files but the OLD files stay on the server (accumulating cruft). Occasionally run cleanup via API or File Manager. Not urgent — few KB per deploy.

---

## 8. Local development setup

**Prerequisites:** PHP 8.2+, Composer, Node 20+, Git.

Windows users: use the portable PHP install at `C:\web_dev\tools\php\` if you don't have your own — see the `[stageserverofbss-cpanel-access]` memory note.

```bash
git clone https://github.com/TashkeelPasha/company-suite-laravel.git
cd company-suite-laravel

# Install deps
composer install
npm install

# Copy env template + generate app key
cp .env.example .env
php artisan key:generate

# For quick local run, use SQLite
# Edit .env: DB_CONNECTION=sqlite, comment out other DB_* lines
touch database/database.sqlite

# Set up DB + seed
php artisan migrate --seed             # creates tables + seeds SuperAdmin

# Optional: also seed test accounts
php artisan db:seed --class=TestAccountsSeeder

# Build frontend (or `npm run dev` for HMR while developing)
npm run build

# Start dev server
php artisan serve                       # → http://127.0.0.1:8000
```

Log in with `admin@companysuite.local` / `ChangeMe1234!` (or any test account from `TestAccountsSeeder` — all password `TestPass123!`).

---

## 9. Debugging on production

### Look at logs
- **Laravel log:** `/home/bsstesting457/repositories/company-suite-laravel/storage/logs/laravel.log` — read via cPanel File Manager, or via API:
  ```bash
  curl -H "Authorization: cpanel <USER>:<TOKEN>" -G \
    --data-urlencode "dir=/home/bsstesting457/repositories/company-suite-laravel/storage/logs" \
    --data-urlencode "file=laravel.log" \
    "https://<HOST>/execute/Fileman/get_file_content"
  ```
- **Apache/LiteSpeed error:** cPanel dashboard → "Errors"
- **Browser DevTools (F12) → Network tab** — click any red row → Response tab shows the raw API response

### Common HTTP codes and what they mean

| Code | Likely cause |
|---|---|
| **200** | Fine |
| **401** | Not authenticated to that role — check your login |
| **403** | Authenticated but wrong role, OR ERC accessing a completed incident |
| **404** | Route not registered / typo in URL / vhost broken |
| **419** | CSRF token mismatch — see §7 #2 |
| **500** | PHP fatal — check laravel.log |
| **503** | PHP-FPM pool stuck — see §7 #6 |

### After a code change, if something behaves wrong
1. Did you `npm run build` if you changed JS or SCSS?
2. Did you run `php artisan config:cache && php artisan route:cache && php artisan view:cache` on production? (CD does this automatically for you)
3. Hard-refresh the browser (Ctrl+Shift+R) to bypass cached JS
4. Check `laravel.log` for stack traces

### Ask Claude
Every future Claude session in this project loads the memory notes at `.claude/projects/C--web-dev-saad/memory/stageserverofbss-*.md`. Just describe the symptom — Claude knows the auth flow, the vault, and the deploy pipeline.

---

## 10. When you're ready to hand off

- Grant the incoming dev **read access to the encrypted vault** at `~/OneDrive/Documents/vault/cpannel/` and give them the passphrase via password manager (not chat, not email)
- Add them as a collaborator on the GitHub repo
- Update `.github/CODEOWNERS` with their username
- Walk them through this document and `API.md`

---

## 11. Where else to look

| File | For what |
|---|---|
| [`API.md`](API.md) | Every API endpoint — request/response, business rules, DB schema |
| [`CONTRIBUTING.md`](CONTRIBUTING.md) | Branch strategy, GitHub secrets, rollback |
| [`CPANEL_503_FIX.md`](CPANEL_503_FIX.md) | Historical — what to do if the "missing Controller.php" 503 comes back |
| [`DEPLOYMENT_CPANEL.md`](DEPLOYMENT_CPANEL.md) | cPanel-specific deploy notes (docroot layout, permissions) |
| `.cpanel.yml` | What runs during a cPanel `Deploy HEAD Commit` |
| `.github/workflows/ci.yml` | What the CI actually runs |
| `.github/workflows/deploy.yml` | What the CD actually runs on push to main |

---

*Last updated: 2026-09-10.*
