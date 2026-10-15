# Mobile API Contract for Flutter

## 1. Purpose

This document defines the mobile-friendly API contract for Flutter clients.

Important rule:
- The existing web/session flow stays unchanged.
- The browser-based routes in [routes/api.php](routes/api.php) remain the web app contract.
- New mobile routes are added separately and use token-based authentication instead of Laravel session cookies.

This avoids breaking the current browser flow while allowing Flutter to work without CSRF/cookie handling.

---

## 2. Current app behavior (do not change)

The current app uses Laravel session auth, not bearer-token auth.

Evidence from the project:
- [routes/api.php](routes/api.php): all API routes are wrapped in `Route::middleware('web')`
- [config/auth.php](config/auth.php): all guards use `driver => 'session'`
- [app/Http/Controllers/ApiController.php](app/Http/Controllers/ApiController.php): login methods call `Auth::guard(...)->login(...)` and `$request->session()->regenerate()`
- [resources/js/api.js](resources/js/api.js): browser client sends `X-CSRF-TOKEN` and uses `credentials: 'include'`

This browser flow is correct for the web app and must remain untouched.

---

## 3. Mobile auth model

Flutter should use a separate mobile API layer with token auth.

Recommended approach:
- Use Laravel Sanctum for mobile token authentication
- Keep the existing `web` session routes intact
- Add a new mobile route group such as `/api/mobile/*` or `/api/v1/mobile/*`
- Use `Authorization: Bearer <token>` on protected requests
- Do not use CSRF-protected browser endpoints from Flutter

---

## 4. Auth endpoints for Flutter

### 4.1 Superadmin login

- Method: `POST`
- Route: `/api/mobile/auth/superadmin/login`
- Purpose: Sign in as Superadmin from Flutter
- Request body:
  ```json
  {
    "email": "admin@example.com",
    "password": "secret"
  }
  ```
- Success response:
  ```json
  {
    "token": "<sanctum_token>",
    "user": {
      "id": 1,
      "email": "admin@example.com",
      "role": "superadmin"
    }
  }
  ```
- Auth: Not required

### 4.2 Company login

- Method: `POST`
- Route: `/api/mobile/auth/company/login`
- Purpose: Sign in as a company account
- Request body:
  ```json
  {
    "email": "company@example.com",
    "password": "secret"
  }
  ```
- Success response:
  ```json
  {
    "token": "<sanctum_token>",
    "user": {
      "id": 12,
      "email": "company@example.com",
      "role": "company"
    }
  }
  ```
- Auth: Not required

### 4.3 Station / GO / ERC login

- Method: `POST`
- Route: `/api/mobile/auth/station/login`
- Purpose: Station team member login

- Method: `POST`
- Route: `/api/mobile/auth/go/login`
- Purpose: GO team member login

- Method: `POST`
- Route: `/api/mobile/auth/erc/login`
- Purpose: ERC team member login

- Request body for each:
  ```json
  {
    "email": "member@example.com",
    "password": "secret"
  }
  ```
- Success response:
  ```json
  {
    "token": "<sanctum_token>",
    "user": {
      "id": 44,
      "email": "member@example.com",
      "role": "station"
    }
  }
  ```
- Auth: Not required

### 4.4 Current user

- Method: `GET`
- Route: `/api/mobile/auth/me`
- Purpose: Get current authenticated user details
- Auth: Required (`Bearer token`)
- Response:
  ```json
  {
    "id": 1,
    "email": "admin@example.com",
    "role": "superadmin",
    "name": "System Admin"
  }
  ```

### 4.5 Logout

- Method: `POST`
- Route: `/api/mobile/auth/logout`
- Purpose: Revoke token and logout
- Auth: Required (`Bearer token`)
- Response:
  ```json
  {
    "success": true
  }
  ```

---

## 5. Role-based mobile endpoints

Each role should have dedicated token-secured mobile routes.

### 5.1 Superadmin mobile routes

- `GET /api/mobile/companies`
- `POST /api/mobile/companies`
- `GET /api/mobile/companies/stats`
- `GET /api/mobile/companies/{id}`
- `PATCH /api/mobile/companies/{id}`
- `DELETE /api/mobile/companies/{id}`
- `PATCH /api/mobile/companies/{id}/password`

Purpose:
- Manage companies from mobile admin dashboard

Auth:
- `superadmin` role via Sanctum

---

### 5.2 Company mobile routes

- `POST /api/mobile/company/auth/login`
- `GET /api/mobile/company/auth/me`
- `POST /api/mobile/company/auth/logout`
- `GET /api/mobile/company/team-members`
- `POST /api/mobile/company/team-members`
- `PATCH /api/mobile/company/team-members/{id}`
- `DELETE /api/mobile/company/team-members/{id}`
- `GET /api/mobile/company/passengers`
- `POST /api/mobile/company/passengers`
- `DELETE /api/mobile/company/passengers/{id}`
- `GET /api/mobile/company/incidents`
- `POST /api/mobile/company/incidents`
- `GET /api/mobile/company/incidents/{id}`
- `PATCH /api/mobile/company/incidents/{id}/status`
- `POST /api/mobile/company/incidents/{id}/passengers`

Purpose:
- Company operations, team management, passenger tracking, incident updates

Auth:
- `company` role

---

### 5.3 Station / SAT mobile routes

- `POST /api/mobile/station/auth/login`
- `GET /api/mobile/station/auth/me`
- `POST /api/mobile/station/auth/logout`
- `GET /api/mobile/station/passengers`
- `GET /api/mobile/station/passengers/{id}/erp`
- `GET /api/mobile/station/passengers/{id}/updates`
- `POST /api/mobile/station/passengers/{id}/updates`

Purpose:
- Station and SAT staff passenger details, manifest review, status updates, and partial PRC answers

SAT volunteers use the Station login and Station routes. Their `teamType` in the login response remains `SAT - Volunteers`.

Auth:
- `station` role via Sanctum; this includes Station Team and SAT - Volunteers

---

### 5.4 GO mobile routes

- `POST /api/mobile/go/auth/login`
- `GET /api/mobile/go/auth/me`
- `POST /api/mobile/go/auth/logout`
- `GET /api/mobile/go/incidents`
- `GET /api/mobile/go/incidents/{id}/passengers`
- `GET /api/mobile/go/passengers/{id}/erp`
- `GET /api/mobile/go/passengers/{id}/updates`
- `POST /api/mobile/go/passengers/{id}/updates`

Purpose:
- GO team incident and passenger details, manifest review, status updates, and partial PRC answers

Auth:
- `go` role

---

### 5.5 ERC mobile routes

- `POST /api/mobile/erc/auth/login`
- `GET /api/mobile/erc/auth/me`
- `POST /api/mobile/erc/auth/logout`
- `GET /api/mobile/erc/incidents`
- `GET /api/mobile/erc/incidents/{id}/passengers`
- `GET /api/mobile/erc/passengers/{id}/relative-info`
- `POST /api/mobile/erc/passengers/{id}/relative-info`
- `GET /api/mobile/erc/passengers/{id}/updates`

Purpose:
- ERC incident detail and relative info handling

Auth:
- `erc` role

---

## 6. Request format for Flutter

All protected mobile requests must include:

```http
Authorization: Bearer <token>
Accept: application/json
Content-Type: application/json
```

For uploads:

```http
Authorization: Bearer <token>
Accept: application/json
```

Do not send:
- `X-CSRF-TOKEN`
- `X-XSRF-TOKEN`
- browser cookies

---

## 7. Error handling

Mobile API should return standard Laravel JSON errors.

Examples:

```json
{
  "message": "Unauthenticated."
}
```

```json
{
  "message": "Unauthorized."
}
```

```json
{
  "message": "Validation failed",
  "errors": {
    "email": ["The email field is required."]
  }
}
```

---

## 8. Recommended implementation rule for backend team

Keep these two worlds completely separate:

1. Web world (existing)
   - [routes/api.php](routes/api.php)
   - session cookies
   - CSRF enabled
   - browser flow remains exact

2. Mobile world (new)
   - new routes under `/api/mobile/*`
   - token-based auth (Sanctum)
   - uses same business logic and same role guards, but via token middleware

This ensures:
- web UI keeps working as-is
- Flutter app can authenticate without browser cookie/CSRF issues
- role-based access remains consistent

---

## 9. Recommended naming for Flutter developer

For Flutter, the easiest contract is:

- Auth endpoints: `/api/mobile/auth/*`
- Role-specific endpoints: `/api/mobile/{role}/*`
- Current user: `/api/mobile/auth/me`
- Token header: `Authorization: Bearer ...`

Example:
- Company login: `/api/mobile/auth/company/login`
- Company incidents: `/api/mobile/company/incidents`
- Station passengers: `/api/mobile/station/passengers`

---

## 10. Final recommendation

Do not make Flutter hit the current web routes in [routes/api.php](routes/api.php).

Use a dedicated mobile route layer with token auth. That is the correct long-term design for this project because the app today is intentionally built around session-based browser auth.

---

## 11. GO / Station / SAT passenger workflow

The company remains responsible for entering the passenger manifest (including Excel-imported details). GO, Station, and SAT only read those company fields; they do not update manifest data. Their writable data is a status update (`status`, optional `remarks`) and optional partial ERP-001 Passenger Record Card answers (`prc`). Each successful submission records its team member and timestamp. A passenger detail response includes manifest details, status-update history, and saved PRC versions. Station/SAT responses do not include ERP-002 information-form history; GO follows the existing web view and can see it read-only.

### Read passenger details

```http
GET /api/mobile/station/passengers/{passengerId}/erp
GET /api/mobile/go/passengers/{passengerId}/erp
Authorization: Bearer <token>
Accept: application/json
```

Response shape:

```json
{
  "incident": {
    "id": 7,
    "flightNumber": "EX123",
    "fromLocation": "LHE",
    "toLocation": "JED",
    "via": "DXB",
    "status": "Ongoing"
  },
  "passenger": {
    "id": 42,
    "incidentId": 7,
    "name": "Amina Khan",
    "seatNumber": "12A",
    "firstName": "Amina",
    "lastName": "Khan",
    "dateOfBirth": "1990-01-02",
    "nationality": "PK",
    "passportNumber": "P1234567",
    "effectiveRoute": {
      "from": "LHE",
      "to": "JED",
      "via": "DXB"
    },
    "travellingWith": []
  },
  "updates": [],
  "contactPerson": null,
  "recordCards": [],
  "informationForms": []
}
```

The passenger object also returns all manifest fields. `recordCards` and `updates` are newest-first. Station/SAT receive an empty `informationForms` array; GO receives the existing read-only PIF data when available.

### Submit status and optional partial PRC answers

Both team roles use the same request shape at their own route:

```http
POST /api/mobile/station/passengers/{passengerId}/updates
POST /api/mobile/go/passengers/{passengerId}/updates
Authorization: Bearer <token>
Accept: application/json
Content-Type: application/json
```

```json
{
  "status": "Safe",
  "remarks": "Passenger contacted",
  "prc": {
    "contactMade": true,
    "contactDetails": "Family notified",
    "familyAssistOffered": false
  }
}
```

`status` is required and must be `Safe`, `Injured`, `Critical`, `Deceased`, or `Unconfirmed`. `remarks` and `prc` are optional. `prc` can contain only the answers currently known; do not send placeholder answers for fields not known yet. The answers are **merged** onto the passenger's latest card: a field you leave out keeps its current value (including answers saved by another team), and an explicit `null` clears it. When the response has a `recordCard` object, the merged card was saved as a new version — it is the full current card, not only the fields you sent. If no sent answer changed anything, `recordCard` is `null`.

Successful response (`201`):

```json
{
  "id": 18,
  "passengerId": 42,
  "teamMemberId": 5,
  "submittedBy": "Station Member",
  "status": "Safe",
  "remarks": "Passenger contacted",
  "createdAt": "2026-10-14T10:30:00.000000Z",
  "recordCard": {
    "id": 3,
    "passengerId": 42,
    "teamMemberId": 5,
    "submittedBy": "Station Member",
    "source": "form",
    "contactMade": true,
    "contactDetails": "Family notified",
    "familyAssistOffered": false,
    "createdAt": "2026-10-14T10:30:00.000000Z"
  }
}
```

### Verify locally before sharing a response with the Flutter developer

1. Run the focused backend tests:

   ```powershell
   php artisan test --filter=MobileTeamWorkflowTest
   ```

2. Start the Laravel server with `php artisan serve`, then use a real Station/SAT or GO team account. Replace the URL, credentials, and passenger ID with the local values. Run the PowerShell commands separately:

   ```powershell
   $base = 'http://127.0.0.1:8000'
   $login = Invoke-RestMethod -Method Post -Uri "$base/api/mobile/auth/station/login" -ContentType 'application/json' -Body (@{ email = 'station@example.com'; password = 'your-password' } | ConvertTo-Json)
   $token = $login.token
   $headers = @{ Authorization = "Bearer $token"; Accept = 'application/json' }
   Invoke-RestMethod -Method Get -Uri "$base/api/mobile/station/passengers/42/erp" -Headers $headers
   ```

3. To test a write, send the sample JSON to the corresponding Station or GO `/updates` route. Share the JSON response body (remove passenger personal/medical details and the bearer token first). Use a non-production/test passenger for write testing.

Expected errors: invalid `status` or PRC fields return `422`; a passenger outside the logged-in member's company returns `404`; a missing/invalid token returns `401`; using a GO token on a Station route (or vice versa) returns `403`.
