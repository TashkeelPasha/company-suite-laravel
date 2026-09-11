# Database Changes — Migration Workflow

**Read this before touching anything schema-related.** Getting this wrong means broken production. Getting this right is a 5-minute routine.

---

## The rule

**Never edit an existing migration file.** Always add a NEW migration.

Existing migrations have already been run on production and every dev's local DB. If you edit an old one, Laravel doesn't re-run it — your schema change is invisible to everyone except a fresh install.

**Never run `migrate:fresh` on production.** That drops every table.

---

## The 5 scenarios

### 1. Add a new column to an existing table

```bash
# Locally
php artisan make:migration add_cnic_verified_to_passengers_table --table=passengers
```
Edit the new file at `database/migrations/YYYY_MM_DD_HHMMSS_add_cnic_verified_to_passengers_table.php`:

```php
public function up(): void
{
    Schema::table('passengers', function (Blueprint $table) {
        $table->boolean('cnic_verified')->default(false)->after('cnic');
    });
}

public function down(): void
{
    Schema::table('passengers', function (Blueprint $table) {
        $table->dropColumn('cnic_verified');
    });
}
```

Test locally:
```bash
php artisan migrate                # apply
php artisan migrate:rollback       # verify down() works
php artisan migrate                # apply again for real
```

Update the model (`app/Models/Passenger.php`) if the column should be mass-assignable — add to `$fillable` — or cast — add to `$casts`.

**Commit both files** — the migration AND the model change.

### 2. Add a new table (new feature)

```bash
php artisan make:migration create_flight_manifests_table --create=flight_manifests
```

The generator scaffolds the `Schema::create(...)` block. Fill in the columns, then create the model:

```bash
php artisan make:model FlightManifest
```

Test locally as above.

### 3. Add a new package that ships its own migration (like Bilal's Sanctum)

```bash
# Add the dep
php composer.phar require laravel/sanctum
# (or `composer require ...` if composer is in PATH)
```

Laravel package auto-discovery usually publishes the migration into `database/migrations/`. If not, run:
```bash
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
```

Then:
```bash
php artisan migrate
```

**Commit BOTH** `composer.json` AND `composer.lock`. If you commit only `composer.json`, CI fails (`Setup Node 20` hit this — see git history). The lock file is the single source of truth for which package versions get installed.

If any config was published (e.g. `config/sanctum.php`), commit that too.

### 4. Rename or drop a column

Same as scenario 1 — new migration, `Schema::table('...', fn ($table) => $table->renameColumn('old', 'new'))` or `$table->dropColumn('name')`.

Note: renaming columns in SQLite has historically been fragile. Doctrine DBAL is auto-installed by Laravel for these operations. If it fails locally, the safer pattern is: add new column → data-copy in a separate migration → drop old column in a third migration.

### 5. Backfill data

Create a migration with a data step:

```php
public function up(): void
{
    Schema::table('team_members', function (Blueprint $t) {
        $t->string('slug')->nullable()->after('email');
    });

    // Backfill
    DB::table('team_members')->orderBy('id')->chunk(500, function ($rows) {
        foreach ($rows as $r) {
            DB::table('team_members')->where('id', $r->id)
                ->update(['slug' => \Str::slug($r->full_name . '-' . $r->id)]);
        }
    });

    // Now enforce NOT NULL + unique in a follow-up migration
    // (SQLite can't ALTER add constraints — but Postgres/MySQL can)
}
```

Separating "add nullable column + backfill" from "enforce constraint" is safer — the second migration can be a no-op on SQLite and only kick in on prod DB.

---

## Local test — the pre-push ritual

Before pushing ANY schema change:

```bash
# 1. Verify migration is valid + reversible
php artisan migrate
php artisan migrate:rollback
php artisan migrate

# 2. Nuke and re-apply from scratch — catches "add column that already exists" bugs
rm database/database.sqlite && touch database/database.sqlite
php artisan migrate --seed
php artisan db:seed --class=TestAccountsSeeder

# 3. Verify affected endpoint still works
php artisan serve
# Hit the endpoints that read/write the changed column in Brave
```

If step 2 fails → your migration or seeder is broken. Fix before pushing.

---

## How it deploys to production

**On `git push origin main`:**

1. GitHub Actions `deploy.yml` runs (if secrets are set — see [`DEVELOPER_GUIDE.md § 6`](DEVELOPER_GUIDE.md#6-deployment-flow--what-happens-when-you-push))
2. It calls `VersionControl/update` — cPanel pulls your commit
3. Uploads new `public/build/` (Vite output)
4. Calls `VersionControlDeployment/create` — cPanel runs `.cpanel.yml` tasks
5. **`.cpanel.yml` includes `php artisan migrate --force`** — your new migration runs against production DB
6. Health check hits `/api/healthz`

**If you added a new composer package** — `.cpanel.yml` has `composer install --no-dev -o` which reads the updated `composer.lock` and installs it. That's why committing `composer.lock` matters.

**Manual fallback if auto-deploy is stuck** — this is what I've been doing until GitHub secrets get added. Run from your local machine (needs cPanel API token in vault):

```bash
TOKEN=$(bash ~/OneDrive/Documents/vault/cpannel/decrypt.sh | grep cpanel_api_token | awk '{print $2}')
AUTH="Authorization: cpanel bsstesting457:$TOKEN"

# 1. Pull latest git commit onto server
curl -s -H "$AUTH" -G \
  --data-urlencode "repository_root=/home/bsstesting457/repositories/company-suite-laravel" \
  --data-urlencode "branch=main" \
  "https://stageserverofbss.com:2083/execute/VersionControl/update"

# 2. Trigger .cpanel.yml — runs composer install + artisan migrate + caches
curl -s -H "$AUTH" -G \
  --data-urlencode "repository_root=/home/bsstesting457/repositories/company-suite-laravel" \
  "https://stageserverofbss.com:2083/execute/VersionControlDeployment/create"

# 3. Verify migration landed
curl -s "https://companysuite.stageserverofbss.com/api/healthz"
```

---

## When you added a new PHP dependency (composer package)

The cPanel deploy runs `composer install`, but SOME cPanel installs have flaky `composer` on the server. If deploy stops working after adding a dep, the safe pattern is:

1. On your machine: `php composer.phar install --no-dev -o` — this builds a full `vendor/` locally
2. Zip vendor: `python C:/Users/LENOVO/AppData/Local/.../scratchpad/zipit.py` (or `zip -r vendor.zip vendor/`)
3. Upload `vendor.zip` via Fileman API (see the redeploy pattern used for Bilal's Sanctum merge — commit `de59523`)
4. Fire a `_redeploy.php` helper that extracts + migrates + caches (see git history for the template)

Yes, this is annoying. Long-term fix — ask hosting for `composer` on `PATH` and shell access. Until then, this pattern works.

---

## The 5 mistakes that have already burned us

1. **Committing composer.json without composer.lock** — CI's Setup Node fails, deploy fails. Always commit both.
2. **Editing an old migration** — schema drift between local and prod. Add a new migration.
3. **Referencing a controller method that doesn't exist** in `routes/*.php` — 500 on every hit to that route. Run `php artisan route:list` before pushing.
4. **Route inside/outside `Route::middleware(...)->group()`** — silently wrong prefix, urls end up as `/api/api/mobile/*` or land unauthenticated. Verify with `route:list`.
5. **Bcrypt password_hash column name** — Laravel's `Authenticatable` expects `password`, ours is `password_hash`. Every Authenticatable model must override `getAuthPassword()` to return `$this->password_hash`. Already done for SuperAdmin/Company/TeamMember — don't remove.

---

## Rolling back a bad migration

If you deployed a broken migration and prod is on fire:

**Option A — Rollback via git revert (safe):**
```bash
git checkout main && git pull
git revert -m 1 <bad-merge-sha>
git push
```
This adds a REVERT commit. Auto-deploy fires. Laravel's migration table still has the bad migration recorded — but the code that used the new column is now gone, so the app boots.
Then locally: `php artisan make:migration remove_<column>_from_<table>_table --table=<table>` — add `down()` logic — push.

**Option B — Rollback via API on server (faster, only if you can articulate the fix):**
```bash
# via decrypt.sh + API pattern above — run:
# artisan migrate:rollback --step=1
# But we don't have shell — need a _rollback.php helper via API. Ask Claude to write it.
```

Never `migrate:refresh` or `migrate:fresh` on prod. Both drop tables.

---

*See also:* [`DEVELOPER_GUIDE.md § 4`](DEVELOPER_GUIDE.md#4-backend-architecture) for the models + guards architecture; [`LOCAL_TESTING.md`](LOCAL_TESTING.md) for the pre-push checklist.
