# Local Testing — Bug Fix Workflow

**Read this before pushing.** Every bug fix should be reproduced locally, fixed locally, and verified locally BEFORE it hits `main`. Skipping local testing has broken production twice already (see [`CPANEL_503_FIX.md`](CPANEL_503_FIX.md)).

The whole loop below takes 5–15 minutes per bug. Do NOT skip.

---

## The 6-step loop for every bug fix

```
1. Reproduce locally  →  2. Isolate  →  3. Fix  →  4. Verify locally
       ↑                                                    ↓
       └──── if not reproducible: gather more info ────  5. PR  →  6. Deploy on merge
```

## 0. First time only — set up local env

Do this once. Then skip to Step 1 for every bug.

```bash
# Clone (or pull latest main if you already have it)
git clone https://github.com/TashkeelPasha/company-suite-laravel.git
cd company-suite-laravel
git checkout main && git pull

# Install deps (PHP + JS)
composer install
npm install

# Env — LOCAL config, never commit this file
cp .env.example .env
php artisan key:generate

# Local DB — SQLite, no MySQL/Postgres needed
# In .env: DB_CONNECTION=sqlite (already the default)
touch database/database.sqlite

# Seed EVERYTHING (SuperAdmin + 2 test companies + 6 test team members + sample incident)
php artisan migrate --seed
php artisan db:seed --class=TestAccountsSeeder

# Build frontend
npm run build

# Or during active dev — Vite dev server with hot reload:
npm run dev &

# Start Laravel dev server
php artisan serve   # → http://127.0.0.1:8000
```

Login with any account (all passwords: `TestPass123!` except SuperAdmin = `ChangeMe1234!`):

| Role | Email |
|---|---|
| SuperAdmin | `admin@companysuite.local` |
| Company Admin (Acme) | `acme@test.com` |
| Station Team | `station.a@test.com` |
| ERC Team | `erc.a@test.com` |
| GO Team | `go.a@test.com` |
| SAT Volunteer | `sat.a@test.com` |
| Beta Company | `beta@test.com` |

---

## Step 1 — Reproduce the bug on your machine

**Never fix a bug you haven't seen with your own eyes.** If the report is "login is broken" — log in with the affected role locally FIRST.

Match production as closely as possible:
- Same browser (Brave/Chrome)
- Same login role
- Same page URL (adapted to `http://127.0.0.1:8000`)
- If DB-dependent — same test data (re-seed if needed: `php artisan db:seed --class=TestAccountsSeeder`)

**Can't reproduce?** Ask the reporter for:
- Screenshot / screen recording
- Browser DevTools → Network tab → HAR file
- The exact URL + steps
- Which role they were logged in as
- Browser + OS

Do NOT push a "fix" for a bug you can't reproduce. You'd be flying blind.

---

## Step 2 — Isolate the layer

Open the browser DevTools (F12) BEFORE reproducing. Watch:

| Layer | Where to look |
|---|---|
| **JS / frontend** | Console tab — red errors, stack traces |
| **API layer** | Network tab — click any red row → Response tab shows Laravel's error payload |
| **Backend / PHP** | `storage/logs/laravel.log` — `tail -f storage/logs/laravel.log` in another terminal |
| **DB** | `sqlite3 database/database.sqlite` → run `.schema`, `SELECT`s |

**Common categorisation:**
| Symptom | Likely layer |
|---|---|
| Nothing renders / white page | JS error (console) OR PHP 500 |
| `419 Page Expired` | CSRF header — see [`DEVELOPER_GUIDE.md § 7 gotcha 2`](DEVELOPER_GUIDE.md#7-gotchas--things-that-have-already-broken-us) |
| `401` on every request | Not logged in / session expired |
| `403` | Wrong role for that endpoint |
| `404` | Route not registered OR frontend calling wrong URL |
| Data displays wrong values | Backend logic OR view template |
| Form submit does nothing | JS handler missing event listener |
| Migration fails on `php artisan migrate` | Broken migration — check file syntax |

---

## Step 3 — Fix on a branch

Never commit to `main` directly. Always work on a branch.

```bash
git checkout main && git pull
git checkout -b fix/short-descriptive-name
# e.g. fix/station-login-419
# e.g. fix/incident-list-shows-wrong-company

# ... make your edit(s) ...

# Test after every change (see Step 4 before committing)
```

Branch naming:
- `fix/<name>` — bug fix
- `feature/<name>` — new feature
- `chore/<name>` — cleanup, docs, tooling

---

## Step 4 — Verify locally — the "would I merge this?" checklist

After the code change, verify BEFORE committing:

### 4.1 PHP is syntactically valid
```bash
# Lint every touched PHP file
find app/ config/ database/ routes/ bootstrap/ -name '*.php' -newer .git/HEAD | \
  xargs -n1 php -l
```

### 4.2 Laravel boots + routes resolve
```bash
php artisan route:list --except-vendor | head -20
# If this errors → PHP fatal at boot. Fix before committing.
```

### 4.3 Migrations still work (from a clean DB)
```bash
rm database/database.sqlite && touch database/database.sqlite
php artisan migrate --seed
php artisan db:seed --class=TestAccountsSeeder
# Any red error here means your migration or seeder is broken.
```

### 4.4 Frontend builds
```bash
npm run build
# Vite must exit 0. If it fails, JS syntax issue.
```

### 4.5 Manual smoke test in the browser

`php artisan serve` and hit **every route touched by your change** in Brave:
- Load the page
- Open DevTools Console — no red errors
- Click every button, submit every form the change touches
- Log out and log in as a DIFFERENT role — does the change still work? (Multi-tenancy check)

### 4.6 Test with the affected user role
If the bug is Station-only, log in as `station.a@test.com`. If it's Company Admin, log in as `acme@test.com`. Don't test only as SuperAdmin — SuperAdmin has different views.

### 4.7 If you changed the DB schema
- Add a NEW migration file — never edit an existing one
- Migration must be **reversible** (implement `down()`)
- Test: `php artisan migrate:rollback` then `php artisan migrate` — no errors

### 4.8 If you added/changed an API endpoint
- Update [`API.md`](API.md) — the endpoint table row + response shape
- If it's a new business rule, add it to `API.md § Business rules`

### 4.9 If you added a new .env variable
- Add it to `.env.example` with a sane default
- Document what it does in a comment

---

## Step 5 — Push and open a PR

```bash
git add <only-the-files-you-touched>
git status  # verify — never commit .env, vendor/, node_modules/, database/*.sqlite
git commit -m "fix: <short description of what was broken and how you fixed it>"
git push -u origin fix/short-descriptive-name
```

**Commit message format:**
- `fix: <what was broken> — <how you fixed it>` (one line, under 72 chars)
- Longer body if the fix is non-obvious

**PR body:** Use the template at `.github/pull_request_template.md`. Fill in:
- What changed
- How to reproduce the bug (before)
- How to verify the fix (after)
- Screenshots for UI changes

**Wait for CI to go green** ✅ before requesting review. If CI is red, click the failing check → read the log → fix locally → push again.

---

## Step 6 — Merge → auto-deploy

Once approved + CI green:
1. Merge the PR (Squash & merge preferred — keeps `main` history clean)
2. GitHub Actions `deploy.yml` fires automatically
3. Watch the Actions tab — deploy takes ~2 minutes
4. When it goes green, hit the live URL and verify the fix

---

## Common bugs and where to look

### "419 Page Expired" on login or any form
- Client sent wrong CSRF header. See [`DEVELOPER_GUIDE.md § 3`](DEVELOPER_GUIDE.md#3-how-auth-works-this-trips-everyone-up-first).
- Check `resources/js/api.js` — line should say `headers['X-CSRF-TOKEN']`, NOT `X-XSRF-TOKEN`
- Hard-refresh browser (Ctrl+Shift+R) if you just rebuilt Vite

### 401 immediately after login
- Session cookie not being set / read
- Check `config/session.php` — cookie name matches on both client and server
- Check `.env` — `SESSION_SECURE_COOKIE=false` for HTTP local dev (must be `true` on prod HTTPS)

### 403 on ERC endpoints for a specific incident
- ERC access is gated by incident status. If `incident.status = 'Operation Completed'`, ERC gets 403 by design.
- Set the incident back to `Ongoing` (log in as Company Admin → click Reopen)

### "Company Admin sees another company's data"
- Multi-tenancy bug! Check the endpoint in `ApiController` — every Company/Team query MUST filter by `Auth::guard('company')->id()` (or the equivalent for the role).
- Reproduce: log in as `acme@test.com`, check what appears; log out, log in as `beta@test.com`, verify Beta only sees Beta's stuff.

### Blade page loads but data table stays empty ("Loading...")
- Open DevTools → Network → filter to `/api/*` — is the API call being made?
- If not: JS module didn't init. Check `resources/js/app.js` dispatch + the page's `data-cs-role` attribute
- If yes and it's 200 with empty array: seed data missing. Run `php artisan db:seed --class=TestAccountsSeeder`
- If yes and it errored: check the response body in Network tab

### `Class "App\Http\Controllers\Controller" not found`
- The base Controller abstract class is missing. It MUST exist at `app/Http/Controllers/Controller.php`. See [`CPANEL_503_FIX.md`](CPANEL_503_FIX.md).

### Local `php artisan serve` says "PORT 8000 already in use"
- Kill the previous instance: `netstat -ano | grep :8000` (Windows) or `lsof -i :8000` (Unix), then kill the PID
- Or use a different port: `php artisan serve --port=8001`

### Vite build says "manifest.json is missing"
- You forgot to run `npm run build`. Or `public/build/` was gitignored and deleted. Run `npm run build` again.

---

## The golden rule

**If you can't reproduce the bug locally, you can't fix it.** Get the reporter to give you enough info to reproduce first. Never push a speculative fix — you'll create three more bugs and lose their trust.

---

## Quick smoke script (optional)

Save this as `scripts/smoke.sh` and run before every push:

```bash
#!/bin/bash
set -e
echo "→ PHP lint..."
find app/ config/ database/ routes/ bootstrap/ -name '*.php' | xargs -n1 php -l > /dev/null
echo "→ Routes..."
php artisan route:list --except-vendor > /dev/null
echo "→ Migrate (fresh)..."
rm -f database/database.sqlite && touch database/database.sqlite
php artisan migrate --seed --force > /dev/null
php artisan db:seed --class=TestAccountsSeeder --force > /dev/null
echo "→ Vite build..."
npm run build > /dev/null
echo "→ Config cache..."
php artisan config:cache > /dev/null
echo "✓ All smoke tests passed. Safe to push."
```

`bash scripts/smoke.sh` and if it passes, `git push`. 30 seconds of insurance.
