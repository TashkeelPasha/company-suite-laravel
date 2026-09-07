# API Documentation — Company Suite

**Audience:** Backend developer building the Laravel API on cPanel.
**Frontend team:** consumes these endpoints from Blade pages via JavaScript (`fetch` / `axios`).
**Base URL:** `/api` (mounted on the same origin as the frontend).

---

## 0. Global conventions

### 0.1 Transport
- All requests and responses are **JSON** (`Content-Type: application/json`).
- All responses include CORS headers if the frontend is served from a different origin. Same-origin deploy on cPanel is recommended — CORS is then a non-issue.
- All state-changing requests must include a **CSRF token** header when called from the Blade frontend:
  ```
  X-XSRF-TOKEN: <value from the XSRF-TOKEN cookie, URL-decoded>
  ```
  Use Laravel's `EnsureFrontendRequestsAreStateful` (Sanctum-style) OR the built-in `VerifyCsrfToken` middleware.
- All authenticated requests must include the session cookie. The frontend sends `credentials: 'include'` on every request.

### 0.2 Sessions & auth
- Auth is **session-cookie based** (NOT JWT).
- Passwords are stored as **bcrypt** hashes (Laravel default `Hash::make`).
- On successful login, the API creates a session and sets the session cookie. On logout, it invalidates the session.
- There are **5 independent auth guards** — a user logged in as one role is not authenticated as any other.

| Guard name | Role identifier in response | Login endpoint | `whoami` endpoint |
|---|---|---|---|
| `superadmin` | `superadmin` | `POST /api/auth/login` | `GET /api/auth/me` |
| `company` | `company` | `POST /api/company/auth/login` | `GET /api/company/auth/me` |
| `station` | `team_member` (teamType = "Station Team" or "SAT - Volunteers") | `POST /api/station/auth/login` | `GET /api/station/auth/me` |
| `go` | `team_member` (teamType = "GO Team") | `POST /api/go/auth/login` | `GET /api/go/auth/me` |
| `erc` | `team_member` (teamType = "ERC Team") | `POST /api/erc/auth/login` | `GET /api/erc/auth/me` |

> **Note:** The current Node backend has a single `/api/auth/whoami` endpoint that returns whichever role's session is active. Recommended for the Laravel API to expose the same convenience endpoint under `GET /api/auth/whoami` in addition to the per-role `me` endpoints — the frontend uses it for the client-side role gate.

### 0.3 Standard error response
Every non-2xx response returns:
```json
{ "error": "Human-readable message" }
```
Do **not** return arrays of validation errors — the frontend expects one string. If Laravel validation fails, join the messages into a single string, or return the first one.

### 0.4 HTTP status codes used
| Code | Meaning |
|---|---|
| 200 | OK (with body) |
| 201 | Created (with body) |
| 204 | No Content (successful, no body — logout, delete) |
| 400 | Validation error / bad request |
| 401 | Not authenticated |
| 403 | Authenticated but forbidden (wrong role, or incident closed) |
| 404 | Resource not found |
| 409 | Conflict (usually duplicate email) |
| 500 | Server error (generic) |

### 0.5 File uploads (Company logos)
- Company logos are uploaded via a **separate multipart endpoint** and then referenced by URL on subsequent `POST /api/companies` or `PATCH /api/companies/{id}` calls.
- Uploaded files are served from `/api/uploads/{filename}` (Laravel `storage:link` + `Storage::disk('public')`).

### 0.6 Timestamps
- All `createdAt` / `updatedAt` fields are **ISO-8601 strings with timezone** (e.g. `2026-09-07T12:34:56.000Z`).
- `incidentDate` is a plain date string `YYYY-MM-DD` (no time).
- `incidentTime` is a plain time string `HH:MM` (24-hour, no seconds).
- `departureTime` / `arrivalTime` on Passenger are ISO-8601 date-time OR `null`.

### 0.7 Field naming
- **Request bodies and response bodies use `camelCase`** (matches the existing frontend contract).
- Laravel devs: configure your models with `$appends`, accessors, or a resource layer to serialise DB `snake_case` columns as `camelCase`. Or use the [`spatie/laravel-json-api-paginator`] pattern or a simple `JsonResource` class per model.

### 0.8 Enums (fixed vocabularies)
- **`TeamType`** — one of: `"Station Team"`, `"ERC Team"`, `"GO Team"`, `"SAT - Volunteers"` (strings, exact match, case-sensitive).
- **`PassengerStatus`** — one of: `"Safe"`, `"Injured"`, `"Critical"`, `"Deceased"`, `"Unconfirmed"`.
- **`IncidentStatus`** — one of: `"Ongoing"`, `"Operation Completed"`.

### 0.9 Business rules the schema doesn't show
1. **Denormalised history.** `passenger_updates.submitted_by` and `relative_info_updates.updated_by_name` are stored as plain text on purpose, so history survives if the team member is deleted. **Do not "normalise" them into foreign-key-only relations.**
2. **ERC access is gated by incident status.** ERC Team can only view or modify data for incidents where `status = "Ongoing"`. If the incident is `"Operation Completed"`, return `403`.
3. **GO / Station Team share the same login flow but different endpoints.** They authenticate via their respective `/auth/login` routes; the API rejects a Station Team member trying to log in via `/go/auth/login`, and vice versa.
4. **Passengers can be legacy (no incident) OR incident-linked.** `passengers.incident_id` is nullable. Legacy passengers were added directly by Company Admin before incidents existed. New passengers created via `POST /api/company/incidents/{id}/passengers` have `incidentId` set.
5. **Passenger latest status** is computed from `passenger_updates` — the newest row (by `created_at`) wins. When returning `IncidentPassenger`, also expose per-team latest statuses (`latestGoStatus`, `latestStationStatus`, `latestSatStatus`) filtered by the submitting team member's `teamType`.

---

## 1. Auth (SuperAdmin) — 4 endpoints

### 1.1 `POST /api/auth/login`
Login as SuperAdmin.

**Request body:**
```json
{ "email": "admin@example.com", "password": "secret1234" }
```

**Response 200:**
```json
{
  "email": "admin@example.com",
  "role": "superadmin"
}
```

**Errors:**
- `401` — invalid credentials

---

### 1.2 `POST /api/auth/logout`
Log out the current SuperAdmin session.

**Request body:** none.
**Response 204:** empty body.

---

### 1.3 `GET /api/auth/me`
Get the current SuperAdmin.

**Response 200:**
```json
{ "email": "admin@example.com", "role": "superadmin" }
```

**Errors:**
- `401` — not authenticated

---

### 1.4 `PATCH /api/auth/password`
Change the SuperAdmin's password.

**Request body:**
```json
{ "currentPassword": "old", "newPassword": "atleast8chars" }
```

**Validation:** `newPassword` min length 8.

**Response 200:**
```json
{ "email": "admin@example.com", "role": "superadmin" }
```

**Errors:**
- `400` — invalid current password OR new password too short

---

## 2. Universal whoami (recommended addition)

### 2.1 `GET /api/auth/whoami`
Returns whichever role's session is active. Used by the frontend's role-gate JS to decide which dashboard to redirect to on page load.

**Response 200 (SuperAdmin):**
```json
{ "email": "admin@example.com", "role": "superadmin" }
```
**Response 200 (Company Admin):**
```json
{ "id": 1, "name": "Airline X", "email": "ops@x.com", "logoUrl": "/api/uploads/x.png", "role": "company" }
```
**Response 200 (Team Member — station/go/erc):**
```json
{
  "id": 7,
  "fullName": "Jane Doe",
  "email": "jane@x.com",
  "companyId": 1,
  "companyName": "Airline X",
  "teamType": "Station Team",
  "role": "team_member"
}
```

**Errors:**
- `401` — no session on any of the 5 guards

---

## 3. Companies (SuperAdmin only) — 7 endpoints

Guard: **`superadmin`**.

### 3.1 `GET /api/companies`
List all companies.

**Response 200:**
```json
[
  {
    "id": 1,
    "name": "Airline X",
    "email": "ops@x.com",
    "logoUrl": "/api/uploads/x.png",
    "createdAt": "2026-09-01T10:00:00.000Z"
  }
]
```

---

### 3.2 `POST /api/companies`
Create a company.

**Request body:**
```json
{
  "name": "Airline Y",
  "email": "ops@y.com",
  "password": "atleast8chars",
  "logoUrl": "/api/uploads/y.png"
}
```

**Validation:**
- `name` required, min length 1
- `email` required, valid email format, unique across companies
- `password` required, min length 8
- `logoUrl` optional, may be `null`

**Response 201:** the created `Company` object (same shape as list item, without `password`).

**Errors:**
- `400` — validation error
- `409` — email already exists

---

### 3.3 `GET /api/companies/stats`
Aggregate stats for the SuperAdmin dashboard.

**Response 200:**
```json
{ "totalCompanies": 12 }
```

---

### 3.4 `GET /api/companies/{id}`
Get one company.

**Response 200:** `Company` object.

**Errors:** `404`.

---

### 3.5 `PATCH /api/companies/{id}`
Update company fields (any subset of `name`, `email`, `logoUrl`).

**Request body:**
```json
{ "name": "New Name", "email": "new@x.com", "logoUrl": null }
```

**Response 200:** updated `Company`.

**Errors:** `400`, `404`, `409`.

---

### 3.6 `DELETE /api/companies/{id}`
Delete a company. Cascade-delete all related team_members, incidents, passengers, updates.

**Response 204.**

---

### 3.7 `PATCH /api/companies/{id}/password`
Force-reset a company's password (no old-password check required — SuperAdmin action).

**Request body:**
```json
{ "newPassword": "atleast8chars" }
```

**Response 200:** the `Company` object.

**Errors:** `404`.

---

## 4. Company Admin auth — 3 endpoints

Guard: **`company`**.

### 4.1 `POST /api/company/auth/login`
**Request:** `{ "email": "ops@x.com", "password": "secret1234" }`
**Response 200:**
```json
{ "id": 1, "name": "Airline X", "email": "ops@x.com", "logoUrl": "/api/uploads/x.png" }
```
**Errors:** `401`.

### 4.2 `POST /api/company/auth/logout` — `204`.
### 4.3 `GET /api/company/auth/me` — same shape as login response, or `401`.

---

## 5. Team members (Company Admin) — 4 endpoints

Guard: **`company`**. All operations scoped automatically to `companyId` from the session.

### 5.1 `GET /api/company/team-members`
List team members for the logged-in company.

**Response 200:**
```json
[
  {
    "id": 7,
    "companyId": 1,
    "fullName": "Jane Doe",
    "email": "jane@x.com",
    "teamType": "Station Team",
    "createdAt": "2026-09-01T10:00:00.000Z"
  }
]
```

### 5.2 `POST /api/company/team-members`
Create a team member.

**Request body:**
```json
{
  "fullName": "Jane Doe",
  "email": "jane@x.com",
  "password": "atleast8chars",
  "teamType": "Station Team"
}
```

**Validation:**
- `fullName` required, min 1
- `email` required, unique across ALL team_members (not just this company)
- `password` required, min 8
- `teamType` required, must be one of the enum values

**Response 201:** `TeamMember` object.
**Errors:** `400`, `409`.

### 5.3 `PATCH /api/company/team-members/{id}`
Update fields. Any subset of `fullName`, `email`, `password`, `teamType`. Only allowed if the team member belongs to the caller's company (else `404`).

**Response 200:** updated `TeamMember`.
**Errors:** `400`, `404`, `409`.

### 5.4 `DELETE /api/company/team-members/{id}`
Delete a team member. Scoped to caller's company (else `404`). Cascade-deletes their `passenger_updates` and `relative_info_updates`, but the denormalised `submittedBy` / `updatedByName` text survives.

**Response 204.**

---

## 6. Company Admin — passengers (legacy path) — 3 endpoints

Guard: **`company`**. These are the LEGACY endpoints that let a Company Admin add passengers directly (without an incident). Kept for backwards compatibility — new workflow uses `POST /api/company/incidents/{id}/passengers` instead.

### 6.1 `GET /api/company/passengers` → `Passenger[]`
### 6.2 `POST /api/company/passengers`
**Request body:**
```json
{
  "name": "John Smith",
  "seatNumber": "12A",
  "flightNumber": "PK301",
  "fromLocation": "KHI",
  "toLocation": "LHE",
  "departureTime": "2026-09-01T08:00:00Z",
  "arrivalTime": "2026-09-01T10:00:00Z"
}
```
All fields required, min length 1 (dates as ISO strings).
**Response 201:** `Passenger` object.

### 6.3 `DELETE /api/company/passengers/{id}` → `204`, or `404`.

---

## 7. Incidents (Company Admin) — 5 endpoints

Guard: **`company`**. All operations scoped to caller's `companyId`.

### 7.1 `GET /api/company/incidents`
List all incidents for the company, with passenger count.

**Response 200:**
```json
[
  {
    "id": 42,
    "companyId": 1,
    "flightNumber": "PK301",
    "fromLocation": "KHI",
    "toLocation": "LHE",
    "incidentDate": "2026-09-01",
    "incidentTime": "08:30",
    "status": "Ongoing",
    "passengerCount": 187,
    "createdAt": "2026-09-01T09:00:00.000Z"
  }
]
```

### 7.2 `POST /api/company/incidents`
**Request body:**
```json
{
  "flightNumber": "PK301",
  "fromLocation": "KHI",
  "toLocation": "LHE",
  "incidentDate": "2026-09-01",
  "incidentTime": "08:30"
}
```
All fields required, min length 1. Default `status = "Ongoing"`.
**Response 201:** the created `Incident`.

### 7.3 `GET /api/company/incidents/{id}`
Get one incident with full passenger list (including per-team latest statuses and latest relative info).

**Response 200:** `IncidentWithPassengers` — see §12 for shape.
**Errors:** `404`.

### 7.4 `PATCH /api/company/incidents/{id}/status`
**Request body:** `{ "status": "Operation Completed" }`
**Response 200:** updated `Incident`.
**Errors:** `400`, `404`.

### 7.5 `POST /api/company/incidents/{id}/passengers`
Bulk-create passengers from a parsed Excel file. Frontend parses the .xlsx client-side and posts the array.

**Request body:**
```json
{
  "passengers": [
    { "name": "John Smith", "seatNumber": "12A", "cnic": "42101-1234567-8" },
    { "name": "Jane Doe",   "seatNumber": "12B", "cnic": "42101-9876543-2" }
  ]
}
```
`passengers` array, min 1 item. Each item: `name` (required, min 1), `seatNumber` (required, min 1), `cnic` (optional string).

**Response 201:**
```json
{ "count": 2 }
```

---

## 8. Station Team — 5 endpoints

Guard: **`station`** (matches `teamType = "Station Team"` OR `"SAT - Volunteers"`).

### 8.1 `POST /api/station/auth/login` — same body as SuperAdmin login. Response is `StationTeamMember` (see §12).
### 8.2 `POST /api/station/auth/logout` → `204`.
### 8.3 `GET /api/station/auth/me` → `StationTeamMember` or `401`.

### 8.4 `GET /api/station/passengers`
List passengers for the caller's company, with the latest status per passenger.

**Response 200:** array of `Passenger` (each includes `latestStatus` and `latestStatusAt`).

### 8.5 `GET /api/station/passengers/{id}/updates`
Get full update history for one passenger. Newest first.
**Response 200:** array of `PassengerUpdate`. `404` if passenger not found (or belongs to different company).

### 8.6 `POST /api/station/passengers/{id}/updates`
Add a status update.

**Request body:**
```json
{ "status": "Safe", "remarks": "Confirmed at the counter" }
```
`status` required (enum). `remarks` optional string.
The server sets `submittedBy` from the caller's `fullName` and stores `teamMemberId`.

**Response 201:** the created `PassengerUpdate`.
**Errors:** `400`, `404`.

---

## 9. GO Team — 6 endpoints

Guard: **`go`** (matches `teamType = "GO Team"`).

### 9.1 `POST /api/go/auth/login` — spec was omitted in the reference OpenAPI; **backend must add this**, symmetrically with station/erc. Body = `LoginInput`, response = `StationTeamMember`. Enforce `teamType = "GO Team"`.
### 9.2 `POST /api/go/auth/logout` → `204`.
### 9.3 `GET /api/go/auth/me` → `StationTeamMember`.

### 9.4 `GET /api/go/incidents`
List incidents for the caller's company. Unlike ERC, GO sees BOTH `Ongoing` and `Operation Completed` (verify with product).

**Response 200:** array of `Incident`.

### 9.5 `GET /api/go/incidents/{id}/passengers`
Get one incident with full passenger list. Same shape as `IncidentWithPassengers`.
**Errors:** `404`.

### 9.6 `GET /api/go/passengers/{id}/updates` — same shape as §8.5.
### 9.7 `POST /api/go/passengers/{id}/updates` — same shape as §8.6.

---

## 10. ERC Team — 8 endpoints

Guard: **`erc`** (matches `teamType = "ERC Team"`).

### 10.1 `POST /api/erc/auth/login` → `StationTeamMember`, `401`.
### 10.2 `POST /api/erc/auth/logout` → `204`.
### 10.3 `GET /api/erc/auth/me` → `StationTeamMember`, `401`.

### 10.4 `GET /api/erc/incidents`
List **ongoing only** incidents for the caller's company.
**Response 200:** array of `Incident`.

### 10.5 `GET /api/erc/incidents/{id}/passengers`
Get incident with passengers, statuses, and latest relative info per passenger.
**Errors:**
- `403` — incident is `"Operation Completed"`, ERC no longer has access.
- `404` — not found.

### 10.6 `GET /api/erc/passengers/{id}/relative-info`
Full relative-info history for a passenger. Newest first.
**Response 200:** array of `RelativeInfoUpdate`.
**Errors:** `404`.

### 10.7 `POST /api/erc/passengers/{id}/relative-info`
Add a new relative-info entry for a passenger.

**Request body:**
```json
{
  "contactName": "Sara Smith",
  "relationship": "Wife",
  "telephoneNumbers": "+92 300 1234567, +92 21 3456789",
  "address": "House 12, Street 3, Karachi"
}
```
`contactName`, `relationship`, `telephoneNumbers` required (min 1). `address` optional.
Server sets `updatedByName` from the caller's `fullName` and stores `teamMemberId`.

**Response 201:** the created `RelativeInfoUpdate`.
**Errors:** `400`, `403` (incident completed), `404`.

### 10.8 `GET /api/erc/passengers/{id}/updates`
Read-only view of ALL status updates from every team for one passenger. Newest first.
**Response 200:** array of `PassengerUpdateWithTeam` (adds `teamType` to each row).
**Errors:** `404`.

---

## 11. Uploads — 1 endpoint

Guard: **any authenticated role** (or restrict to `superadmin` + `company` — used only for company logos).

### 11.1 `POST /api/uploads`
**Request:** `multipart/form-data` with a single field `file` (image, ≤ 2 MB, `image/*`).

**Response 200:**
```json
{ "url": "/api/uploads/abcd1234.png" }
```
Save to `storage/app/public/` and expose via `storage:link` so `/api/uploads/{name}` resolves to the file.

**Errors:**
- `400` — no file, wrong MIME, too large.

### 11.2 `GET /api/uploads/{filename}` — static file (served by Laravel's public disk, no code needed).

---

## 12. Response schemas (types reference)

### `Company`
```ts
{
  id: number,
  name: string,
  email: string,
  logoUrl: string | null,
  createdAt: string    // ISO-8601
}
```

### `CompanyAdminUser` (login/whoami response for the `company` guard)
```ts
{
  id: number,
  name: string,
  email: string,
  logoUrl: string | null
}
```

### `TeamMember`
```ts
{
  id: number,
  companyId: number,
  fullName: string,
  email: string,
  teamType: "Station Team" | "ERC Team" | "GO Team" | "SAT - Volunteers",
  createdAt: string
}
```

### `StationTeamMember` (login/whoami response for station/go/erc guards)
```ts
{
  id: number,
  fullName: string,
  email: string,
  companyId: number,
  companyName: string,
  teamType: string   // one of the enum values
}
```

### `Incident`
```ts
{
  id: number,
  companyId: number,
  flightNumber: string,
  fromLocation: string,
  toLocation: string,
  incidentDate: string,        // "YYYY-MM-DD"
  incidentTime: string,        // "HH:MM"
  status: "Ongoing" | "Operation Completed",
  passengerCount?: number,     // present on list endpoints only
  createdAt: string
}
```

### `IncidentPassenger` (row inside `IncidentWithPassengers.passengers`)
```ts
{
  id: number,
  name: string,
  seatNumber: string,
  cnic: string | null,
  latestStatus: string | null,        // overall latest across ALL teams
  latestGoStatus: string | null,      // latest from GO Team submitter
  latestStationStatus: string | null, // latest from Station Team submitter
  latestSatStatus: string | null,     // latest from SAT - Volunteers submitter
  latestRelativeInfo: RelativeInfoUpdate | null,
  createdAt: string
}
```

### `IncidentWithPassengers`
```ts
{
  id: number,
  companyId: number,
  flightNumber: string,
  fromLocation: string,
  toLocation: string,
  incidentDate: string,
  incidentTime: string,
  status: "Ongoing" | "Operation Completed",
  createdAt: string,
  passengers: IncidentPassenger[]
}
```

### `Passenger` (legacy list, and station list)
```ts
{
  id: number,
  companyId: number,
  name: string,
  seatNumber: string,
  flightNumber: string | null,
  fromLocation: string | null,
  toLocation: string | null,
  departureTime: string | null,   // ISO-8601 or null
  arrivalTime: string | null,
  createdAt: string,
  latestStatus?: string | null,
  latestStatusAt?: string | null
}
```

### `PassengerUpdate`
```ts
{
  id: number,
  passengerId: number,
  teamMemberId: number,
  submittedBy: string,        // denormalised — survives team member deletion
  status: "Safe" | "Injured" | "Critical" | "Deceased" | "Unconfirmed",
  remarks: string | null,
  createdAt: string
}
```

### `PassengerUpdateWithTeam` (ERC-only, adds team context)
```ts
PassengerUpdate & { teamType: string }
```

### `RelativeInfoUpdate`
```ts
{
  id: number,
  passengerId: number,
  teamMemberId: number,
  updatedByName: string,     // denormalised
  contactName: string,
  relationship: string,
  telephoneNumbers: string,  // free-form text — commas / newlines allowed
  address: string | null,
  createdAt: string
}
```

---

## 13. Endpoint checklist (backend dev tick-list)

Total: **48 endpoints**.

- [ ] `GET  /api/healthz`
- [ ] `POST /api/auth/login`
- [ ] `POST /api/auth/logout`
- [ ] `GET  /api/auth/me`
- [ ] `PATCH /api/auth/password`
- [ ] `GET  /api/auth/whoami` **(add — see §2)**
- [ ] `GET  /api/companies`
- [ ] `POST /api/companies`
- [ ] `GET  /api/companies/stats`
- [ ] `GET  /api/companies/{id}`
- [ ] `PATCH /api/companies/{id}`
- [ ] `DELETE /api/companies/{id}`
- [ ] `PATCH /api/companies/{id}/password`
- [ ] `POST /api/company/auth/login`
- [ ] `POST /api/company/auth/logout`
- [ ] `GET  /api/company/auth/me`
- [ ] `GET  /api/company/team-members`
- [ ] `POST /api/company/team-members`
- [ ] `PATCH /api/company/team-members/{id}`
- [ ] `DELETE /api/company/team-members/{id}`
- [ ] `GET  /api/company/passengers`
- [ ] `POST /api/company/passengers`
- [ ] `DELETE /api/company/passengers/{id}`
- [ ] `GET  /api/company/incidents`
- [ ] `POST /api/company/incidents`
- [ ] `GET  /api/company/incidents/{id}`
- [ ] `PATCH /api/company/incidents/{id}/status`
- [ ] `POST /api/company/incidents/{id}/passengers`
- [ ] `POST /api/station/auth/login`
- [ ] `POST /api/station/auth/logout`
- [ ] `GET  /api/station/auth/me`
- [ ] `GET  /api/station/passengers`
- [ ] `GET  /api/station/passengers/{id}/updates`
- [ ] `POST /api/station/passengers/{id}/updates`
- [ ] `POST /api/go/auth/login` **(add — missing in ref OpenAPI)**
- [ ] `POST /api/go/auth/logout`
- [ ] `GET  /api/go/auth/me`
- [ ] `GET  /api/go/incidents`
- [ ] `GET  /api/go/incidents/{id}/passengers`
- [ ] `GET  /api/go/passengers/{id}/updates`
- [ ] `POST /api/go/passengers/{id}/updates`
- [ ] `POST /api/erc/auth/login`
- [ ] `POST /api/erc/auth/logout`
- [ ] `GET  /api/erc/auth/me`
- [ ] `GET  /api/erc/incidents`
- [ ] `GET  /api/erc/incidents/{id}/passengers`
- [ ] `GET  /api/erc/passengers/{id}/relative-info`
- [ ] `POST /api/erc/passengers/{id}/relative-info`
- [ ] `GET  /api/erc/passengers/{id}/updates`
- [ ] `POST /api/uploads`

---

## 14. Seeding — required on first deploy

The old Node backend auto-seeds a SuperAdmin at boot. Replicate this in Laravel with a **seeder** (`php artisan db:seed --class=SuperAdminSeeder`) and document credentials in `HANDOFF_CHECKLIST.md`.

Default seed:
```
email:    admin@companysuite.local
password: ChangeMe1234!   (must be updated on first login)
```

---

## 15. Questions the backend dev should raise before starting

1. Should ERC also see `Operation Completed` incidents in the list (read-only), or filter them out entirely? Current Node behaviour: filters out.
2. Does GO Team see only `Ongoing` incidents? OpenAPI doesn't clarify — verify with product.
3. Is there a cap on `passengers[]` in the bulk endpoint? Recommend ≤ 500.
4. Rate-limit login endpoints? Recommend 5/min per IP.
5. Session TTL? Node uses 1 day — mirror this (Laravel `SESSION_LIFETIME=1440` minutes).
