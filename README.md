# Company Suite — Laravel

Airline incident-response management system. Blade + Bootstrap 5 + PHP frontend + Laravel API. Converted from [Amir-bss/Company-Suite](https://github.com/Amir-bss/Company-Suite) (MERN → Laravel 12).

**Live:** [https://companysuite.stageserverofbss.com/](https://companysuite.stageserverofbss.com/)

---

## Start here — pick your path

| I want to… | Read |
|---|---|
| **Understand the whole codebase** (architecture, folders, gotchas, workflow) | 📘 **[DEVELOPER_GUIDE.md](DEVELOPER_GUIDE.md)** — start here |
| Add or change an API endpoint | 📗 [API.md](API.md) — every endpoint spec + business rules + DB schema |
| Contribute (branches, PRs, GitHub secrets, rollback) | 📙 [CONTRIBUTING.md](CONTRIBUTING.md) |
| Deploy or debug cPanel-specific things | 📕 [DEPLOYMENT_CPANEL.md](DEPLOYMENT_CPANEL.md) · [CPANEL_503_FIX.md](CPANEL_503_FIX.md) |

---

## Run locally in 60 seconds

```bash
git clone https://github.com/TashkeelPasha/company-suite-laravel.git
cd company-suite-laravel
composer install && npm install
cp .env.example .env && php artisan key:generate
touch database/database.sqlite      # SQLite for local
php artisan migrate --seed          # creates tables + SuperAdmin
php artisan db:seed --class=TestAccountsSeeder   # optional: 2 companies + 6 team members
npm run build && php artisan serve  # → http://127.0.0.1:8000
```

Login: `admin@companysuite.local` / `ChangeMe1234!` (or any test account — password `TestPass123!`)

---

## Push → Deploy in one line

Push to `main` → CI runs → CD auto-deploys to production. That's the whole workflow.

Any other branch → CI runs only, no deploy. Merge into `main` via PR when ready.

Full details: [DEVELOPER_GUIDE.md § 6](DEVELOPER_GUIDE.md#6-deployment-flow--what-happens-when-you-push).

---

## Stack at a glance

- **Backend:** Laravel 12 · PHP 8.3 · SQLite (prod) · session cookies + CSRF (no JWT)
- **Frontend:** Blade shells + Bootstrap 5 + vanilla JS + Vite
- **5 auth roles:** SuperAdmin · Company Admin · Station Team · GO Team · ERC Team
- **48 API endpoints** — all documented in [API.md](API.md)
- **Deploy:** GitHub Actions → cPanel API (no SSH needed)

---

## Repo map

```
app/                     — Controllers (Page + Api), Middleware, Models
bootstrap/app.php        — 5 auth guards config
config/{auth,session,database,filesystems}.php
database/
├── migrations/          — 7 tables
└── seeders/             — SuperAdminSeeder + TestAccountsSeeder
public/
├── index.php            — Laravel front controller
├── .htaccess            — routing rewrites
└── build/               — Vite output (auto-uploaded by CD)
resources/
├── views/               — 11 Blade pages
├── sass/app.scss        — Bootstrap import
└── js/                  — api.js, auth-gate.js, ui.js + per-page modules
routes/
├── web.php              — 11 Blade routes → PageController
└── api.php              — ~50 API routes → ApiController
.github/workflows/       — ci.yml (all branches) + deploy.yml (main only)
.cpanel.yml              — cPanel deploy hook
```

Detailed layout with responsibility for every folder: [DEVELOPER_GUIDE.md § 2](DEVELOPER_GUIDE.md#2-stack--directory-layout).
