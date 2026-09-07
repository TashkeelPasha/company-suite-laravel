# Backend Developer Handoff Checklist

Hi 👋 — you're picking up the API for Company Suite. This is your one-page
punch list. Full spec is in **[`API_DOCUMENTATION.md`](API_DOCUMENTATION.md)**.

## 1. What already exists (in this repo)

- ✅ Laravel 11 project scaffold — you work inside it.
- ✅ **7 migrations** in `database/migrations/` — run `php artisan migrate --seed`.
- ✅ **7 Eloquent models** in `app/Models/` — ready to use, relations wired.
- ✅ **5 auth guards** in `config/auth.php` — `superadmin`, `company`, `station`, `go`, `erc`.
- ✅ **`role` middleware** at `App\Http\Middleware\EnsureRole` — wire it onto API routes.
- ✅ **`SuperAdminSeeder`** — default admin `admin@companysuite.local` / `ChangeMe1234!`.

## 2. What you build

Everything under `/api/*` — **48 endpoints** total. Full spec including request
bodies, response shapes, validation rules, HTTP codes and error strings is in
[`API_DOCUMENTATION.md`](API_DOCUMENTATION.md).

Suggested file layout (mirrors the 5 guards):

```
routes/api.php                             — all API routes
app/Http/Controllers/Api/
├── HealthController.php
├── SuperAdmin/
│   ├── AuthController.php
│   └── CompaniesController.php
├── Company/
│   ├── AuthController.php
│   ├── TeamMembersController.php
│   ├── PassengersController.php
│   └── IncidentsController.php
├── Station/
│   ├── AuthController.php
│   └── PassengersController.php
├── Go/
│   ├── AuthController.php
│   ├── IncidentsController.php
│   └── PassengersController.php
├── Erc/
│   ├── AuthController.php
│   ├── IncidentsController.php
│   └── PassengersController.php
├── WhoAmIController.php     ← GET /api/auth/whoami (returns whichever guard is active)
└── UploadsController.php
app/Http/Requests/…          — FormRequest per POST/PATCH endpoint
app/Http/Resources/…         — API resources to serialise DB snake_case → response camelCase
```

## 3. Non-negotiable business rules (read these!)

Copied here for prominence — re-read section 0.9 in `API_DOCUMENTATION.md`.

1. **Denormalised history.** `passenger_updates.submitted_by` and
   `relative_info_updates.updated_by_name` are stored as **text** on purpose.
   Do NOT normalise into a strict FK-only relation — history must survive
   team-member deletion.
2. **ERC access is gated by incident status.** ERC endpoints must return `403`
   for any incident where `status = "Operation Completed"`. See §10.5 and §10.7.
3. **Response field naming is `camelCase`.** DB columns are `snake_case`. Use
   `JsonResource` classes to translate on the way out. Requests are also
   `camelCase`.
4. **Errors are `{ "error": "message" }`.** Not arrays of validation errors —
   one string. The frontend surfaces it verbatim.
5. **Sessions, not JWT.** Login sets a session cookie. Logout invalidates it.
6. **Guard isolation.** A login as SuperAdmin does not authenticate the user as
   Company or Station. Each guard's login/logout/me must scope to its own
   session key.

## 4. Wiring your API to the existing Blade frontend

The frontend calls the API via `fetch` with:
- `credentials: 'include'` → cookies flow.
- `X-XSRF-TOKEN` header → the value of the `XSRF-TOKEN` cookie, URL-decoded.

For this to work you must:
- [ ] Enable Laravel's `EnsureFrontendRequestsAreStateful` (or wire CSRF into
      `/api/*` if you keep the API on the `api` middleware group).
- [ ] Confirm `SESSION_DOMAIN` and `SANCTUM_STATEFUL_DOMAINS` (if using Sanctum)
      cover the frontend's host.
- [ ] Ensure your login endpoints regenerate the session on success
      (`$request->session()->regenerate()`).

## 5. Whoami endpoint (please add — spec §2)

The frontend's role gate calls `GET /api/auth/whoami` on every dashboard load
to decide which route to bounce the user to. The original Node backend had this
convenience endpoint — please implement it. It should:

- Try each of the 5 guards in order.
- Return the first authenticated user, with a `role` field: `"superadmin"`,
  `"company"`, or `"team_member"`.
- Return `401` if no guard has a session.

## 6. Missing from the reference OpenAPI — please add

- `POST /api/go/auth/login` — spec was omitted in the original OpenAPI file. Add
  symmetrically with `station` and `erc`. Enforce `teamType = "GO Team"`.

## 7. Testing your API against the frontend

1. Run `composer install && php artisan migrate --seed`.
2. Run `npm install && npm run dev`.
3. Run `php artisan serve` (or point cPanel at `public/`).
4. Visit `/` and log in as `admin@companysuite.local` / `ChangeMe1234!`.
5. Try each dashboard end-to-end.

If a page shows a red banner or gets bounced to `/login`, open browser devtools
→ Network → see which request failed. The error string comes straight from your
API's `{ "error": "..." }` payload.

## 8. Deployment

See `DEPLOYMENT_CPANEL.md` for cPanel specifics. Key gotchas:
- cPanel needs `pdo_pgsql` for Postgres, or fall back to MySQL (fine — no
  Postgres-specific features are used).
- `storage:link` must be run so uploaded logos at
  `storage/app/public/*` resolve to `/api/uploads/*`.
- `SESSION_SECURE_COOKIE=true` requires HTTPS on the cPanel domain.

## 9. Ready-to-answer questions (bring to product / your team lead)

Section §15 of `API_DOCUMENTATION.md` — five open questions the spec doesn't
resolve. Answer these before starting the ERC or GO controllers so you don't
build the wrong filter.
