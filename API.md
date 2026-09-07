# Company Suite API — Backend Dev Spec

Build these 48 endpoints. Everything else (Blade, JS, migrations, models) already exists.

## Ground rules

- Base URL: `/api`
- All requests/responses are JSON. Field names are **camelCase**.
- Auth is **session cookies** (not JWT). Passwords are **bcrypt**.
- 5 independent guards: `superadmin`, `company`, `station`, `go`, `erc`.
- Error shape (any non-2xx): `{ "error": "message" }` — one string, not an array.
- CSRF: state-changing requests send header `X-XSRF-TOKEN` (value = `XSRF-TOKEN` cookie, URL-decoded).

## Enums

- **TeamType:** `Station Team` · `ERC Team` · `GO Team` · `SAT - Volunteers`
- **PassengerStatus:** `Safe` · `Injured` · `Critical` · `Deceased` · `Unconfirmed`
- **IncidentStatus:** `Ongoing` · `Operation Completed`

## Business rules (non-negotiable)

1. `passenger_updates.submitted_by` and `relative_info_updates.updated_by_name` are **plain text** — don't normalise into FK-only. History must survive team-member deletion.
2. **ERC** endpoints must return **403** when `incident.status = "Operation Completed"`.
3. Guards are isolated — login on one does not authenticate on any other.
4. All company/team endpoints must scope by the caller's `companyId` — never trust an ID from the request path if it belongs to another company (return 404).

## Endpoint table

| # | Method | Path | Guard | Request | Response | Notes |
|--:|---|---|---|---|---|---|
| 1 | GET | `/healthz` | none | — | `{status}` | |
| 2 | POST | `/auth/login` | none | `{email,password}` | `{email,role:"superadmin"}` | 401 on bad creds |
| 3 | POST | `/auth/logout` | superadmin | — | 204 | |
| 4 | GET | `/auth/me` | superadmin | — | `{email,role}` | 401 |
| 5 | PATCH | `/auth/password` | superadmin | `{currentPassword,newPassword≥8}` | `{email,role}` | 400 |
| 6 | GET | `/auth/whoami` | any | — | user + `role` | tries all 5 guards; 401 if none |
| 7 | GET | `/companies` | superadmin | — | `Company[]` | |
| 8 | POST | `/companies` | superadmin | `{name,email,password≥8,logoUrl?}` | `Company` 201 | 409 dup email |
| 9 | GET | `/companies/stats` | superadmin | — | `{totalCompanies}` | |
| 10 | GET | `/companies/{id}` | superadmin | — | `Company` | 404 |
| 11 | PATCH | `/companies/{id}` | superadmin | any of `{name,email,logoUrl}` | `Company` | 400/404/409 |
| 12 | DELETE | `/companies/{id}` | superadmin | — | 204 | cascade |
| 13 | PATCH | `/companies/{id}/password` | superadmin | `{newPassword≥8}` | `Company` | 404 |
| 14 | POST | `/company/auth/login` | none | `{email,password}` | `CompanyAdminUser` | 401 |
| 15 | POST | `/company/auth/logout` | company | — | 204 | |
| 16 | GET | `/company/auth/me` | company | — | `CompanyAdminUser` | 401 |
| 17 | GET | `/company/team-members` | company | — | `TeamMember[]` | scoped |
| 18 | POST | `/company/team-members` | company | `{fullName,email,password≥8,teamType}` | `TeamMember` 201 | 409 |
| 19 | PATCH | `/company/team-members/{id}` | company | any of `{fullName,email,password,teamType}` | `TeamMember` | 400/404/409 |
| 20 | DELETE | `/company/team-members/{id}` | company | — | 204 | |
| 21 | GET | `/company/passengers` | company | — | `Passenger[]` | legacy |
| 22 | POST | `/company/passengers` | company | `PassengerInput` (all fields, min 1) | `Passenger` 201 | legacy |
| 23 | DELETE | `/company/passengers/{id}` | company | — | 204 | |
| 24 | GET | `/company/incidents` | company | — | `Incident[]` (+`passengerCount`) | |
| 25 | POST | `/company/incidents` | company | `{flightNumber,fromLocation,toLocation,incidentDate,incidentTime}` | `Incident` 201 | default status `Ongoing` |
| 26 | GET | `/company/incidents/{id}` | company | — | `IncidentWithPassengers` | 404 |
| 27 | PATCH | `/company/incidents/{id}/status` | company | `{status}` | `Incident` | 400/404 |
| 28 | POST | `/company/incidents/{id}/passengers` | company | `{passengers:[{name,seatNumber,cnic?}]}` (min 1) | `{count}` 201 | bulk |
| 29 | POST | `/station/auth/login` | none | `{email,password}` | `StationTeamMember` | teamType must be `Station Team` or `SAT - Volunteers` |
| 30 | POST | `/station/auth/logout` | station | — | 204 | |
| 31 | GET | `/station/auth/me` | station | — | `StationTeamMember` | 401 |
| 32 | GET | `/station/passengers` | station | — | `Passenger[]` (+`latestStatus`,`latestStatusAt`) | |
| 33 | GET | `/station/passengers/{id}/updates` | station | — | `PassengerUpdate[]` newest-first | 404 |
| 34 | POST | `/station/passengers/{id}/updates` | station | `{status,remarks?}` | `PassengerUpdate` 201 | server fills `submittedBy` |
| 35 | POST | `/go/auth/login` | none | `{email,password}` | `StationTeamMember` | teamType must be `GO Team` |
| 36 | POST | `/go/auth/logout` | go | — | 204 | |
| 37 | GET | `/go/auth/me` | go | — | `StationTeamMember` | 401 |
| 38 | GET | `/go/incidents` | go | — | `Incident[]` | |
| 39 | GET | `/go/incidents/{id}/passengers` | go | — | `IncidentWithPassengers` | 404 |
| 40 | GET | `/go/passengers/{id}/updates` | go | — | `PassengerUpdate[]` | 404 |
| 41 | POST | `/go/passengers/{id}/updates` | go | `{status,remarks?}` | `PassengerUpdate` 201 | 400/404 |
| 42 | POST | `/erc/auth/login` | none | `{email,password}` | `StationTeamMember` | teamType must be `ERC Team` |
| 43 | POST | `/erc/auth/logout` | erc | — | 204 | |
| 44 | GET | `/erc/auth/me` | erc | — | `StationTeamMember` | 401 |
| 45 | GET | `/erc/incidents` | erc | — | `Incident[]` | **ongoing only** |
| 46 | GET | `/erc/incidents/{id}/passengers` | erc | — | `IncidentWithPassengers` | **403 if completed** |
| 47 | GET | `/erc/passengers/{id}/relative-info` | erc | — | `RelativeInfoUpdate[]` newest-first | 404 |
| 48 | POST | `/erc/passengers/{id}/relative-info` | erc | `{contactName,relationship,telephoneNumbers,address?}` | `RelativeInfoUpdate` 201 | **403 if incident completed** |
| 49 | GET | `/erc/passengers/{id}/updates` | erc | — | `PassengerUpdateWithTeam[]` (adds `teamType`) | read-only, 404 |
| 50 | POST | `/uploads` | any | `multipart form; field=file (image, ≤2MB)` | `{url:"/api/uploads/{name}"}` | 400 |
| 51 | GET | `/uploads/{filename}` | none | — | file | static |

## Response shapes

```ts
Company              = { id, name, email, logoUrl:string|null, createdAt }
CompanyAdminUser     = { id, name, email, logoUrl:string|null }
TeamMember           = { id, companyId, fullName, email, teamType, createdAt }
StationTeamMember    = { id, fullName, email, companyId, companyName, teamType }
Incident             = { id, companyId, flightNumber, fromLocation, toLocation,
                         incidentDate:"YYYY-MM-DD", incidentTime:"HH:MM",
                         status, passengerCount?, createdAt }
Passenger            = { id, companyId, name, seatNumber, flightNumber?, fromLocation?,
                         toLocation?, departureTime?, arrivalTime?, createdAt,
                         latestStatus?, latestStatusAt? }
IncidentPassenger    = { id, name, seatNumber, cnic:string|null,
                         latestStatus, latestGoStatus, latestStationStatus, latestSatStatus,
                         latestRelativeInfo:RelativeInfoUpdate|null, createdAt }
IncidentWithPassengers = Incident + { passengers: IncidentPassenger[] }
PassengerUpdate      = { id, passengerId, teamMemberId, submittedBy,
                         status, remarks:string|null, createdAt }
PassengerUpdateWithTeam = PassengerUpdate + { teamType }
RelativeInfoUpdate   = { id, passengerId, teamMemberId, updatedByName,
                         contactName, relationship, telephoneNumbers,
                         address:string|null, createdAt }
```

## Times

- `createdAt` → ISO-8601 with tz (`2026-09-07T12:34:56.000Z`)
- `incidentDate` → `YYYY-MM-DD` (no time)
- `incidentTime` → `HH:MM` (24-hour, no seconds)
- `departureTime`/`arrivalTime` on Passenger → ISO-8601 or `null`

## `latest*Status` on `IncidentPassenger` — how to compute

- `latestStatus` = newest `passenger_updates.status` overall for that passenger
- `latestGoStatus` = newest one where the submitter's `teamType = "GO Team"`
- `latestStationStatus` = newest where `teamType = "Station Team"`
- `latestSatStatus` = newest where `teamType = "SAT - Volunteers"`
- `null` if there are no updates in that bucket

## Database schema

Already scaffolded in `database/migrations/` — `php artisan migrate` creates all 7 tables. Shape reference:

```sql
super_admins
  id             bigserial PK
  email          varchar UNIQUE NOT NULL
  password_hash  varchar NOT NULL          -- bcrypt
  remember_token varchar NULL

companies
  id             bigserial PK
  name           varchar NOT NULL
  email          varchar UNIQUE NOT NULL
  password_hash  varchar NOT NULL          -- bcrypt
  logo_url       varchar NULL              -- "/api/uploads/{name}"
  remember_token varchar NULL
  created_at     timestamptz DEFAULT now()

team_members
  id             bigserial PK
  company_id     bigint NOT NULL  → companies(id) ON DELETE CASCADE
  full_name      varchar NOT NULL
  email          varchar UNIQUE NOT NULL   -- unique across ALL companies
  password_hash  varchar NOT NULL          -- bcrypt
  team_type      varchar NOT NULL          -- "Station Team" | "ERC Team" | "GO Team" | "SAT - Volunteers"
  remember_token varchar NULL
  created_at     timestamp DEFAULT now()

incidents
  id             bigserial PK
  company_id     bigint NOT NULL  → companies(id) ON DELETE CASCADE
  flight_number  varchar NOT NULL
  from_location  varchar NOT NULL
  to_location    varchar NOT NULL
  incident_date  date NOT NULL             -- "YYYY-MM-DD"
  incident_time  varchar NOT NULL          -- "HH:MM"
  status         varchar NOT NULL DEFAULT 'Ongoing'   -- "Ongoing" | "Operation Completed"
  created_at     timestamp DEFAULT now()

passengers
  id             bigserial PK
  company_id     bigint NOT NULL  → companies(id) ON DELETE CASCADE
  incident_id    bigint NULL      → incidents(id) ON DELETE CASCADE  -- NULL for legacy passengers
  name           varchar NOT NULL
  seat_number    varchar NOT NULL
  cnic           varchar NULL
  flight_number  varchar NULL              -- legacy per-passenger fields, only for direct-add
  from_location  varchar NULL
  to_location    varchar NULL
  departure_time timestamp NULL
  arrival_time   timestamp NULL
  created_at     timestamp DEFAULT now()

passenger_updates
  id             bigserial PK
  passenger_id   bigint NOT NULL  → passengers(id) ON DELETE CASCADE
  team_member_id bigint NOT NULL  → team_members(id) ON DELETE CASCADE
  submitted_by   varchar NOT NULL          -- DENORMALISED — copy of full_name at submit time
  status         varchar NOT NULL          -- Safe | Injured | Critical | Deceased | Unconfirmed
  remarks        text NULL
  created_at     timestamp DEFAULT now()
  INDEX (passenger_id, created_at)         -- for "latest status" lookups

relative_info_updates
  id                bigserial PK
  passenger_id      bigint NOT NULL  → passengers(id) ON DELETE CASCADE
  team_member_id    bigint NOT NULL  → team_members(id) ON DELETE CASCADE
  updated_by_name   varchar NOT NULL       -- DENORMALISED — copy of full_name at submit time
  contact_name      varchar NOT NULL
  relationship      varchar NOT NULL
  telephone_numbers text NOT NULL          -- free-form: commas / newlines / multiple numbers
  address           text NULL
  created_at        timestamp DEFAULT now()
  INDEX (passenger_id, created_at)
```

**Cascade summary:** delete a company → wipes its team_members, incidents, passengers, and all updates. Delete a team_member → wipes their `passenger_updates` and `relative_info_updates` rows, **but** `submitted_by` / `updated_by_name` text on other rows survives (that's the whole point of denormalising them).

**Column naming reminder:** DB is `snake_case`, API is `camelCase`. Use a `JsonResource` per model to translate.

## Default seed

Seeder must create SuperAdmin `admin@companysuite.local` / `ChangeMe1234!` (already scaffolded — `php artisan db:seed`).

---

## End-to-end setup (after `git pull`)

### One-time in cPanel UI

1. Create a Postgres (or MySQL) DB + user. Note credentials.
2. **Select PHP Version:** 8.2+. Enable extensions: `pdo_pgsql` (or `pdo_mysql`), `mbstring`, `openssl`, `fileinfo`, `tokenizer`, `zip`, `curl`, `bcmath`.
3. Point the domain docroot at `.../company-suite-laravel/public/` (or use the wrapper in [`DEPLOYMENT_CPANEL.md`](DEPLOYMENT_CPANEL.md)).
4. Enable HTTPS (required — `SESSION_SECURE_COOKIE=true`).

### On your local machine

```bash
git clone https://github.com/TashkeelPasha/company-suite-laravel.git
cd company-suite-laravel
npm ci && npm run build         # produces public/build/ (Vite bundle)
```

Upload the whole tree to cPanel via SFTP or git — but **skip** `node_modules/` and `vendor/` (installed on server).

### On the cPanel server (SSH)

```bash
cd ~/company-suite-laravel
cp .env.example .env
# edit .env: set APP_URL, DB_CONNECTION, DB_DATABASE, DB_USERNAME, DB_PASSWORD
```

Then this one-liner does the rest — installs deps, keys, links storage, creates all 7 tables, seeds SuperAdmin, caches config/routes/views, fixes permissions:

```bash
composer install --no-dev -o && php artisan key:generate && php artisan storage:link && php artisan migrate --seed --force && php artisan config:cache && php artisan route:cache && php artisan view:cache && chmod -R 775 storage bootstrap/cache
```

### Verify it's live

- Visit `/` → landing page
- Visit `/login` → login form
- Login as `admin@companysuite.local` / `ChangeMe1234!`
- Visit `/dashboard` → companies list loads (empty until you add one)
- Change the default password immediately via `/settings`

### On every subsequent deploy

```bash
git pull
# rebuild assets locally: npm run build && upload public/build/
composer install --no-dev -o && php artisan migrate --force && php artisan config:cache && php artisan route:cache && php artisan view:cache
```

### What you (backend dev) still need to build

The frontend Blade + JS consumes 48 JSON endpoints under `/api/*`. **None of them exist yet.** The endpoint table above is your build list. Suggested file layout:

```
routes/api.php                         ← wire everything here
app/Http/Controllers/Api/
├── SuperAdmin/{Auth,Companies}Controller.php
├── Company/{Auth,TeamMembers,Passengers,Incidents}Controller.php
├── Station/{Auth,Passengers}Controller.php
├── Go/{Auth,Incidents,Passengers}Controller.php
├── Erc/{Auth,Incidents,Passengers}Controller.php
├── WhoAmIController.php                ← tries all 5 guards, returns first authenticated
└── UploadsController.php
app/Http/Requests/…                    ← FormRequest per POST/PATCH
app/Http/Resources/…                   ← JsonResource to serialise snake_case → camelCase
```

Enable Laravel Sanctum's `EnsureFrontendRequestsAreStateful` on the `api` group so session cookies + CSRF flow from Blade → API on the same origin.
