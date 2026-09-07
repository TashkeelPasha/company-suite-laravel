# Changes from the original MERN-ish app

This document exists because "MERN → Laravel" isn't quite what happened.
It's here so nobody's surprised.

## Terminology

The original repo is described as MERN. It isn't. The actual stack was:

| Layer | Original | This project |
|---|---|---|
| DB | **PostgreSQL** (Neon) | PostgreSQL (or MySQL if cPanel lacks `pdo_pgsql`) |
| Backend | Express 5 + TypeScript + Drizzle ORM | Laravel 11 + Eloquent |
| Frontend | React 19 + Vite + Radix UI + Tailwind 4 | Blade + Bootstrap 5 |
| API contract | OpenAPI 3.1 spec (Orval codegen → React Query hooks) | Same contract, hand-written in JS `fetch` calls |
| Sessions | `express-session` (cookies, bcrypt) | Laravel session guards (cookies, bcrypt) |
| File uploads | `multer` | `Storage::disk('public')` |

Nothing about MongoDB or Next.js — those aren't in play at all.

## What was NOT ported

- The React app (`artifacts/superadmin`) — replaced with Blade templates.
- The `mockup-sandbox` Vite preview app — dropped, not needed in production.
- The `orval` codegen pipeline — dropped, replaced with hand-written fetch
  calls in `resources/js/`.
- TanStack Query cache — replaced with per-page state and manual refresh.
- Radix UI components — Bootstrap 5 covers 90% (modal, dropdown, tabs, toast,
  accordion). No parity for command palette / drawer / combobox — none of
  those are actually used by the original pages.

## What WAS carried over faithfully

- The 7-table schema (see `database/migrations/`).
- Role semantics: SuperAdmin, Company, Station, GO, ERC, plus SAT-Volunteers
  as an alternate Station Team type.
- Denormalised `submitted_by` / `updated_by_name` fields in the update tables.
- Session cookie auth with 5 independent guards.
- The API contract itself — same URLs, same request/response shapes.
- xlsx bulk import and export (via SheetJS in the browser — same lib as
  original).
- The default super-admin seed convention.

## Deliberate differences

1. **Uploads endpoint returns `{ url }` instead of `{ logoUrl }`** — the
   frontend uses it more generically. Only affects upload response shape.
2. **`GET /api/auth/whoami`** is called out as a required addition — the
   original had it, the reference OpenAPI omitted it.
3. **`POST /api/go/auth/login`** is called out as a required addition — the
   reference OpenAPI omitted it (probably an oversight in the original spec).
4. **No client-side codegen.** The React app generated typed hooks from the
   OpenAPI spec. Blade + JS has no such pipeline — API changes have to be
   reflected manually in `resources/js/pages/*.js`.
