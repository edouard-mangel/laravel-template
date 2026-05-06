# CI Pipeline Design: GitHub Actions

**Date:** 2026-05-07
**Status:** Approved

---

## Context

The `laravel-template` repo is on GitHub with no CI. There is a root `.gitlab-ci.yml` that references
file paths assuming the project is at the repo root — but the actual runnable scaffold is in
`Template/`. This spec adds a GitHub Actions workflow that correctly scopes all jobs to `Template/`.

---

## File

`.github/workflows/ci.yml`

---

## Triggers

```yaml
on:
  push:
    branches: [main]
  pull_request:
    branches: [main]
```

---

## Jobs (5 total)

All PHP jobs use `shivammathur/setup-php@v2` to install PHP 8.4 with extensions in one step.
All jobs use `defaults: run: working-directory: Template` so relative paths work without repetition.

### Job 1: `test`

**Runtime:** Ubuntu latest, PHP 8.4
**Extensions:** pdo, pdo_sqlite, pcntl, mbstring
**Steps:**
1. Checkout
2. Setup PHP 8.4 with extensions
3. Cache `Template/vendor/` keyed on `Template/composer.lock`
4. `composer install --no-interaction --no-progress --prefer-dist`
5. `vendor/bin/pest --no-coverage`

**Environment variables (via env:):**
```
APP_ENV: testing
APP_KEY: "base64:kzPY7a4SgaTU99WPLKwNQVBXjkDEi92Ot5I3+wXuLDk="
DB_CONNECTION: sqlite
DB_DATABASE: ":memory:"
CACHE_STORE: array
QUEUE_CONNECTION: sync
SESSION_DRIVER: array
TELESCOPE_ENABLED: "false"
```

---

### Job 2: `lint`

**Runtime:** Ubuntu latest, PHP 8.4
**Steps:**
1. Checkout
2. Setup PHP 8.4
3. Cache `Template/vendor/` (same key as `test`)
4. `composer install --no-interaction --no-progress --prefer-dist`
5. `vendor/bin/pint --test`

Fails the build if any file would be reformatted.

---

### Job 3: `analyse`

**Runtime:** Ubuntu latest, PHP 8.4
**Steps:**
1. Checkout
2. Setup PHP 8.4
3. Cache `Template/vendor/` (same key as `test`)
4. `composer install --no-interaction --no-progress --prefer-dist`
5. `vendor/bin/phpstan analyse --memory-limit=512M`

---

### Job 4: `frontend-build`

**Runtime:** Ubuntu latest, Node 22
**Steps:**
1. Checkout
2. Setup Node 22
3. Install pnpm: `npm install -g pnpm`
4. Cache `~/.pnpm-store` keyed on `Template/client/package.json`
5. `cd client && pnpm install --frozen-lockfile`

`pnpm run build` is omitted — `angular.json` is not yet scaffolded so the Angular CLI build
would fail. Re-add once the Angular entry point is complete.

---

### Job 5: `e2e`

**Runtime:** Ubuntu latest, PHP 8.4 + Node 22
**Depends on:** `test` (only runs if unit/feature/architecture tests pass)
**Extensions:** pdo, pdo_sqlite, pcntl, mbstring

`:memory:` SQLite cannot be shared between `php artisan serve` (a separate process) and the
migration step. This job uses a **SQLite file** at `/tmp/laravel-e2e.sqlite`.

**Steps:**
1. Checkout
2. Setup PHP 8.4 with extensions
3. Cache `Template/vendor/`
4. `composer install --no-interaction --no-progress --prefer-dist`
5. `touch /tmp/laravel-e2e.sqlite`
7. `php artisan migrate:fresh --seed --force`
8. `php artisan serve --host=0.0.0.0 --port=8000 &`
9. Wait for server: `npx wait-on http://localhost:8000/up --timeout 15000`
10. Setup Node 22
11. Install pnpm: `npm install -g pnpm`
12. `cd client && pnpm install --frozen-lockfile`
13. `cd client && pnpm run e2e:api`

**Environment variables:**
```
APP_ENV: testing
APP_KEY: "base64:kzPY7a4SgaTU99WPLKwNQVBXjkDEi92Ot5I3+wXuLDk="
DB_CONNECTION: sqlite
DB_DATABASE: /tmp/laravel-e2e.sqlite
CACHE_STORE: array
QUEUE_CONNECTION: sync
SESSION_DRIVER: array
TELESCOPE_ENABLED: "false"
```

`wait-on` is used (from the `wait-on` npm package, invoked via npx) to poll `GET /up`
until the Laravel health endpoint responds, ensuring Playwright doesn't start before the
server is ready. Timeout: 15 seconds.

---

## Caching Strategy

| Cache key | Path | Scope |
|-----------|------|-------|
| `composer-${{ hashFiles('Template/composer.lock') }}` | `Template/vendor/` | test, lint, analyse, e2e |
| `pnpm-${{ hashFiles('Template/client/package.json') }}` | `~/.pnpm-store` | frontend-build, e2e |

---

## Job Dependency Graph

```
test ──────────────────────────┐
lint                           ├─→ (all must pass for branch protection)
analyse                        │
frontend-build                 │
test → e2e ────────────────────┘
```

`e2e` needs: `[test]` — if unit/feature tests fail, E2E is skipped.

---

## Out of Scope

- Docker image build / push (no registry configured)
- Deploy jobs
- Coverage reporting
- Updating the root `.gitlab-ci.yml`
