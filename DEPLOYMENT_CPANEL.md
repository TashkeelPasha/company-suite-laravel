# cPanel Deployment Notes

## Prerequisites (verify before quoting)

1. **PHP 8.2+** available in cPanel (Select PHP Version).
2. **`pdo_pgsql`** extension enabled — required if using Postgres.
   - If only MySQL is available, change `DB_CONNECTION=mysql` in `.env`.
   - No Postgres-specific SQL is used, so MySQL 8 works.
3. **Composer** available via SSH (`composer --version`).
4. **Node 20+** for building assets — usually run this locally, not on cPanel.
5. **HTTPS** — required if `SESSION_SECURE_COOKIE=true` (recommended).

## Layout on cPanel

The public webroot is usually `public_html`. Laravel needs its `public/` folder
served as the docroot.

Recommended:

```
/home/{cpanel_user}/
├── company-suite/            ← whole Laravel app
│   ├── app/  bootstrap/  config/  database/  resources/  routes/  storage/  vendor/
│   ├── .env
│   └── public/               ← DO NOT map this directly
└── public_html/              ← webroot
    ├── index.php             ← modified — points at ../company-suite/public
    ├── .htaccess             ← modified — front controller rewrite
    └── build/                ← symlink to ../company-suite/public/build
```

`public_html/index.php`:
```php
<?php
require __DIR__.'/../company-suite/public/index.php';
```

`public_html/.htaccess`:
```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteRule ^(.*)/$ /$1 [L,R=301]
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>
```

## First-time setup on server

Via SSH:

```bash
cd ~/company-suite
composer install --no-dev --optimize-autoloader
php artisan key:generate
php artisan storage:link
php artisan migrate --seed --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
chmod -R 775 storage bootstrap/cache
```

Build assets **locally**, then upload `public/build/`:

```bash
# on your machine
npm ci
npm run build
scp -r public/build cpaneluser@host:~/company-suite/public/
```

## `.env` for cPanel

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.example

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=your_db
DB_USERNAME=your_user
DB_PASSWORD=your_password

SESSION_DRIVER=cookie
SESSION_LIFETIME=1440
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax
SESSION_DOMAIN=.your-domain.example

FILESYSTEM_DISK=public
```

## Uploads

Company logos land at `storage/app/public/*`. `storage:link` maps them to
`public/storage/*`. The API documentation calls them `/api/uploads/{filename}`,
so add a route or an alias:

- Easiest: keep the API endpoint at `POST /api/uploads` and have the
  `UploadsController` return `{ url: "/api/uploads/{filename}" }`, and add a
  matching `Route::get('/api/uploads/{name}', ...)` that serves the file via
  `Storage::disk('public')->response($name)`.

## Session cookie gotchas

- `SESSION_SECURE_COOKIE=true` **requires** HTTPS. On a domain without a valid
  cert, login will silently fail (cookie never persists).
- `SESSION_SAME_SITE=lax` is required for CSRF to work with same-origin JSON
  requests.
- If the frontend and API are on **different** subdomains, set
  `SESSION_DOMAIN=.your-domain.example` (with the leading dot) so the cookie
  is shared.

## Log rotation

cPanel doesn't rotate `storage/logs/laravel.log` by default. Either:
- Set `LOG_CHANNEL=daily` in `.env` (Laravel rotates itself, keeps 7 days).
- Or add a cPanel cron: `find storage/logs -name '*.log' -mtime +30 -delete`.

## Sanity checklist

- [ ] Visit `/` — landing page renders.
- [ ] Visit `/up` — Laravel health check returns 200.
- [ ] Visit `/login` — form appears.
- [ ] Try SuperAdmin login → lands on `/dashboard`.
- [ ] `Network` tab shows `/api/auth/whoami` returning 200 with role.
- [ ] Upload a company logo → visible in the companies list.
- [ ] Log out → cookie cleared, redirected to `/login`.
