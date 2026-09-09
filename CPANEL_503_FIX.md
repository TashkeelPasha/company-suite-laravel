# cPanel 503 — root cause and fix

**Cause:** the project was missing `app/Http/Controllers/Controller.php` (the conventional Laravel base controller). When Laravel's router resolves any controller class, it internally probes for this file. When it isn't there, PHP throws a fatal `Class "App\Http\Controllers\Controller" not found` and Apache/Passenger returns a **503**. See the Laravel log excerpt for the exact stack trace:

```
[2026-09-08 11:33:47] local.ERROR: Class "App\Http\Controllers\Controller" not found
  at app/Http/Controllers/ApiController.php:19
```

The fix has been committed on `main` and `tashkeel` — commit adds the missing `Controller.php` base class.

---

## Recovery steps for the backend dev (on cPanel)

Run these in cPanel's **Terminal** (or SSH). They only touch the Company Suite files — no other project or database is affected.

### 1. Pull the fix

```bash
cd ~/company-suite-laravel        # adjust path to wherever the app lives
git pull origin main               # or: git pull origin tashkeel
```

### 2. Clear every Laravel cache (very important — the old broken class was cached)

```bash
php artisan optimize:clear
```

That single command clears: config cache, route cache, view cache, event cache, compiled services, and the bootstrap manifest.

### 3. Regenerate autoloader (picks up the new Controller.php)

```bash
composer dump-autoload -o
```

### 4. Fix permissions on the writable dirs (must be 775 for cPanel)

```bash
chmod -R 775 storage bootstrap/cache
```

### 5. Re-cache for production

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 6. Test

- Visit `https://your-cpanel-domain/api/healthz` → should return `{"status":"ok"}`
- Visit `https://your-cpanel-domain/` → landing page loads
- Visit `https://your-cpanel-domain/login` → login form loads

If any of those still show 503, tail the Laravel log to see what changed:

```bash
tail -f storage/logs/laravel.log
```

---

## If the 503 persists — production `.env` checklist

The dev's zip shipped a **local** `.env`. Make sure the cPanel `.env` has these values, not local ones:

```env
APP_ENV=production
APP_DEBUG=false                              # true only while debugging — leaks stack traces
APP_URL=https://your-actual-cpanel-domain.com
APP_KEY=base64:...                           # keep the one already generated

DB_CONNECTION=mysql                          # or pgsql, per what cPanel gives you
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=<the-db-you-created-in-cpanel>
DB_USERNAME=<user>
DB_PASSWORD=<pass>

SESSION_DRIVER=file
SESSION_SECURE_COOKIE=true                   # HTTPS required
SESSION_SAME_SITE=lax
```

After editing `.env`, always re-run `php artisan config:cache`.

---

## Where to find errors on cPanel (for future 5xx incidents)

1. **cPanel dashboard → "Errors" (or "Metrics → Errors")** — Apache error log.
2. **cPanel dashboard → "File Manager"** → `company-suite-laravel/storage/logs/laravel.log` — right-click → View. This is the Laravel-specific log with full stack traces.
3. **Browser DevTools** (F12 in Brave/Chrome) → **Network** tab → click any red row → **Response** tab shows the raw server response.

---

## What was actually wrong (technical detail — safe to skip)

Laravel's `Illuminate\Routing\Router::gatherRouteMiddleware()` calls `is_a('App\Http\Controllers\Controller', ...)`. That triggers PHP's autoloader to try to include `app/Http/Controllers/Controller.php`. If the file doesn't exist, the include fails with a fatal error. This is a quirk of how Laravel's routing layer discovers controller-level middleware — it assumes the conventional base class exists.

Fresh installs from `laravel new` include this file. The scaffold in this repo was built by hand and skipped it — my fault, now fixed. Any future dev extending `App\Http\Controllers\Controller` (the standard `make:controller` template) will now work.
