# Company Suite — Laravel Frontend

Blade + Bootstrap 5 + PHP frontend for Company Suite, converted from the original
React/Express/Postgres MERN-ish app at
[Amir-bss/Company-Suite](https://github.com/Amir-bss/Company-Suite).

## What this repo is (and isn't)

- ✅ Laravel 11 shell that renders Blade pages using Bootstrap 5.
- ✅ Every page loads its data client-side by calling a JSON API.
- ✅ Ships **`API_DOCUMENTATION.md`** — the full spec the backend developer
     must implement.
- ✅ Ships **`database/migrations/`** — Eloquent-style migrations for the 7 tables.
- ✅ Ships **role-based auth guards** (`config/auth.php`) for 5 roles.
- ❌ Does NOT include the API controllers themselves — those are the backend
     developer's deliverable. They implement everything under `/api/*` as
     documented.

## Split of responsibilities

| | Frontend team (this repo) | Backend developer |
|---|---|---|
| Blade views & Bootstrap styling | ✅ | |
| Client-side JS (fetch, forms, modals) | ✅ | |
| Client-side role gate (`resources/js/auth-gate.js`) | ✅ | |
| API endpoint implementations under `/api/*` | | ✅ |
| Eloquent models, request validation, controllers | | ✅ |
| Session cookies + CSRF wiring | | ✅ |
| Database migrations & seeders | scaffolded here | run + own |
| cPanel deployment | joint | ✅ |

## File map

```
API_DOCUMENTATION.md           ← THE deliverable for the backend dev
HANDOFF_CHECKLIST.md           ← one-page checklist for the backend dev
DEPLOYMENT_CPANEL.md           ← cPanel deploy notes

app/
├── Http/
│   ├── Controllers/PageController.php     — thin, renders Blade shells only
│   └── Middleware/EnsureRole.php          — server-side role guard (unused by frontend, ready for API)
└── Models/                                 — 7 Eloquent models (shared with API)

config/auth.php                — 5 guards: superadmin, company, station, go, erc

database/migrations/           — 7 tables
database/seeders/SuperAdminSeeder.php

resources/
├── views/                     — Blade shells for all 11 pages
├── sass/app.scss              — Bootstrap 5 import + brand overrides
└── js/
    ├── app.js                 — page router (dispatches to per-page module)
    ├── api.js                 — fetch wrapper with credentials + CSRF
    ├── auth-gate.js           — client-side role gate (calls /api/auth/whoami)
    ├── ui.js                  — toast, spinner, status pill helpers
    ├── topbar.js              — populates topbar user info + logout
    └── pages/                 — per-page controllers
        ├── login.js
        ├── superadmin.js
        ├── company-dashboard.js
        ├── company-add-incident.js
        ├── company-incident-detail.js
        ├── station.js
        ├── go.js
        └── erc.js

routes/web.php                 — 11 GET routes, all render Blade shells
```

## Running locally

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm run dev     # Vite dev server
php artisan serve
```

The frontend will look for the API at `/api` on the same origin. To point at a
remote API during development, set `VITE_API_BASE_URL` in `.env`.

## Building for production

```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build       # writes to public/build
php artisan config:cache route:cache view:cache
```

Upload the resulting tree to cPanel per `DEPLOYMENT_CPANEL.md`.

## Deployment shape

Recommended: **single Laravel app on cPanel** hosting BOTH the Blade frontend and
the JSON API. That means one repo, one deploy, no CORS. The backend dev adds their
controllers under `routes/api.php` inside this same project.

If instead the API is deployed separately, set `VITE_API_BASE_URL` to its
absolute URL and enable CORS on the API side (`config/cors.php` + `withCredentials`
is already handled by `resources/js/api.js`).
