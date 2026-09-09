# Contributing — how the two devs work together

## Branches

| Branch | Purpose | Who can push directly |
|---|---|---|
| `main` | Production — auto-deploys to cPanel on every push | Nobody (merge via PR only) |
| `develop` (optional) | Staging integration | Nobody (merge via PR only) |
| `feature/<short-name>` | Your working branch | You — push as often as you like |
| `fix/<short-name>` | Bugfix branch | You |

## Daily flow

```bash
# 1. Start a feature
git checkout main
git pull
git checkout -b feature/passenger-search

# 2. Work, commit, push
git add .
git commit -m "add passenger search endpoint"
git push -u origin feature/passenger-search

# 3. Open a PR on GitHub → base: main, compare: feature/passenger-search
#    - CI runs automatically (PHP lint, route resolve, migrations, vite build)
#    - Wait for green ✅ before requesting review
#    - CODEOWNERS auto-assigns a reviewer

# 4. After approval + merge → CD auto-deploys to cPanel within ~2 min.
```

## What CI checks on every push and PR

- **PHP**: syntax lint on every file in `app/ config/ database/ routes/ bootstrap/`
- `php artisan route:list` — catches missing controllers, class-not-found, wrong middleware
- `php artisan config:cache` — catches broken config
- `php artisan migrate` on a fresh SQLite DB — catches broken migrations
- **Frontend**: `npm ci` + `npm run build` — catches JS/Vite errors + missing deps

If any of these fail, the PR is blocked. Fix locally and push again.

## What CD does when main is updated

1. GitHub Actions builds `public/build/` with Vite
2. `rsync` uploads `public/build/` to cPanel
3. SSHes into cPanel and runs:
   - `git pull origin main`
   - `composer install --no-dev -o`
   - `php artisan migrate --force`
   - `php artisan optimize:clear && config:cache && route:cache && view:cache`
4. Hits `https://your-domain/up` to confirm the site is alive

If the health check fails, the deploy job goes red and pings the assignee.

## Required GitHub secrets (add once in repo Settings → Secrets → Actions)

| Secret | Value |
|---|---|
| `CPANEL_SSH_HOST` | e.g. `your-domain.com` or the cPanel-provided host |
| `CPANEL_SSH_PORT` | usually `22` — leave unset if 22 |
| `CPANEL_SSH_USER` | your cPanel username |
| `CPANEL_SSH_KEY` | private SSH key (paste the whole `-----BEGIN OPENSSH PRIVATE KEY-----` block) |
| `CPANEL_APP_PATH` | e.g. `/home/<user>/company-suite-laravel` |
| `APP_URL` | e.g. `https://your-domain.com` (used by the health check) |

### How to generate the SSH key (do this ONCE)

On your machine:
```bash
ssh-keygen -t ed25519 -f ~/.ssh/company_suite_cpanel -C "github-actions"
# Copy the PUBLIC key content:
cat ~/.ssh/company_suite_cpanel.pub
```
Then in cPanel:
- **cPanel → "SSH Access" → "Manage SSH Keys" → "Import Key"**
- Paste the PUBLIC key. Give it a name. Save.
- Click **"Manage"** next to the new key → **"Authorize"**

Then paste the PRIVATE key content (`cat ~/.ssh/company_suite_cpanel`, the whole block) as the `CPANEL_SSH_KEY` secret in GitHub.

## Rules of the road

1. **Never push to `main` directly.** Always go via PR.
2. **Never edit an existing migration.** Add a new one instead.
3. **Never commit `.env`.** Copy `.env.example` on the server and edit there.
4. **Never commit `vendor/` or `node_modules/`.** Both are auto-installed by CI/CD.
5. **API changes update `API.md`.** Frontend team relies on it — out-of-sync means broken production.
6. **DB schema changes → also update the schema section of `API.md`.**
7. If CI is red, don't merge. Fix locally. If you're stuck, ping the reviewer in the PR.

## Rollback (if a deploy breaks production)

Fastest — revert the merge commit on GitHub:
```bash
git checkout main
git pull
git revert -m 1 <bad-merge-sha>
git push
```
CD picks it up and re-deploys the previous state within ~2 min.

Or manually on cPanel via SSH:
```bash
cd ~/company-suite-laravel
git reset --hard <known-good-sha>
php artisan optimize:clear && php artisan config:cache && php artisan route:cache && php artisan view:cache
```
