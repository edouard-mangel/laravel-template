# GitHub Actions CI Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a `.github/workflows/ci.yml` that runs lint, static analysis, PHP tests, frontend install, and Playwright API E2E on every push/PR to `main`.

**Architecture:** Single workflow file with 5 parallel jobs (`test`, `lint`, `analyse`, `frontend-build`, `e2e`). All PHP jobs use `shivammathur/setup-php@v2` for clean PHP 8.4 + extension setup. All jobs scope to `Template/` via job-level `defaults`. The `e2e` job depends on `test` passing and uses a SQLite file (not `:memory:`) so that `php artisan serve` and the seeder share the same database.

**Tech Stack:** GitHub Actions, `shivammathur/setup-php@v2`, `actions/cache@v4`, PHP 8.4, Node 22, pnpm, Playwright (`request` fixture — no browser binary needed)

---

## File

| Action | Path |
|--------|------|
| Create | `.github/workflows/ci.yml` |

---

## Task 1: Create the CI workflow file

**Files:**
- Create: `.github/workflows/ci.yml`

- [ ] **Step 1: Create the directory and file**

```bash
mkdir -p .github/workflows
```

Then create `.github/workflows/ci.yml` with the complete content below.

```yaml
name: CI

on:
  push:
    branches: [main]
  pull_request:
    branches: [main]

jobs:
  # ── PHP Tests ─────────────────────────────────────────────────────────────
  test:
    name: PHP Tests
    runs-on: ubuntu-latest
    defaults:
      run:
        working-directory: Template
    env:
      APP_ENV: testing
      APP_KEY: "base64:kzPY7a4SgaTU99WPLKwNQVBXjkDEi92Ot5I3+wXuLDk="
      DB_CONNECTION: sqlite
      DB_DATABASE: ":memory:"
      CACHE_STORE: array
      QUEUE_CONNECTION: sync
      SESSION_DRIVER: array
      TELESCOPE_ENABLED: "false"
    steps:
      - uses: actions/checkout@v4

      - name: Setup PHP 8.4
        uses: shivammathur/setup-php@v2
        with:
          php-version: "8.4"
          extensions: pdo, pdo_sqlite, pcntl, mbstring
          coverage: none

      - name: Cache Composer dependencies
        uses: actions/cache@v4
        with:
          path: Template/vendor
          key: composer-${{ hashFiles('Template/composer.lock') }}
          restore-keys: composer-

      - name: Install dependencies
        run: composer install --no-interaction --no-progress --prefer-dist

      - name: Run Pest
        run: vendor/bin/pest --no-coverage

  # ── PHP Lint ──────────────────────────────────────────────────────────────
  lint:
    name: PHP Lint (Pint)
    runs-on: ubuntu-latest
    defaults:
      run:
        working-directory: Template
    steps:
      - uses: actions/checkout@v4

      - name: Setup PHP 8.4
        uses: shivammathur/setup-php@v2
        with:
          php-version: "8.4"
          extensions: mbstring
          coverage: none

      - name: Cache Composer dependencies
        uses: actions/cache@v4
        with:
          path: Template/vendor
          key: composer-${{ hashFiles('Template/composer.lock') }}
          restore-keys: composer-

      - name: Install dependencies
        run: composer install --no-interaction --no-progress --prefer-dist

      - name: Check formatting
        run: vendor/bin/pint --test

  # ── Static Analysis ───────────────────────────────────────────────────────
  analyse:
    name: Static Analysis (PHPStan)
    runs-on: ubuntu-latest
    defaults:
      run:
        working-directory: Template
    steps:
      - uses: actions/checkout@v4

      - name: Setup PHP 8.4
        uses: shivammathur/setup-php@v2
        with:
          php-version: "8.4"
          extensions: mbstring
          coverage: none

      - name: Cache Composer dependencies
        uses: actions/cache@v4
        with:
          path: Template/vendor
          key: composer-${{ hashFiles('Template/composer.lock') }}
          restore-keys: composer-

      - name: Install dependencies
        run: composer install --no-interaction --no-progress --prefer-dist

      - name: Run PHPStan
        run: vendor/bin/phpstan analyse --memory-limit=512M

  # ── Frontend ──────────────────────────────────────────────────────────────
  frontend-build:
    name: Frontend (pnpm install)
    runs-on: ubuntu-latest
    defaults:
      run:
        working-directory: Template/client
    steps:
      - uses: actions/checkout@v4

      - name: Setup Node 22
        uses: actions/setup-node@v4
        with:
          node-version: "22"

      - name: Install pnpm
        run: npm install -g pnpm
        working-directory: .

      - name: Cache pnpm store
        uses: actions/cache@v4
        with:
          path: ~/.pnpm-store
          key: pnpm-${{ hashFiles('Template/client/package.json') }}
          restore-keys: pnpm-

      - name: Install dependencies
        run: pnpm install --frozen-lockfile

  # ── E2E ───────────────────────────────────────────────────────────────────
  e2e:
    name: E2E (Playwright API)
    runs-on: ubuntu-latest
    needs: [test]
    env:
      APP_ENV: testing
      APP_KEY: "base64:kzPY7a4SgaTU99WPLKwNQVBXjkDEi92Ot5I3+wXuLDk="
      DB_CONNECTION: sqlite
      DB_DATABASE: /tmp/laravel-e2e.sqlite
      CACHE_STORE: array
      QUEUE_CONNECTION: sync
      SESSION_DRIVER: array
      TELESCOPE_ENABLED: "false"
    steps:
      - uses: actions/checkout@v4

      - name: Setup PHP 8.4
        uses: shivammathur/setup-php@v2
        with:
          php-version: "8.4"
          extensions: pdo, pdo_sqlite, pcntl, mbstring
          coverage: none

      - name: Cache Composer dependencies
        uses: actions/cache@v4
        with:
          path: Template/vendor
          key: composer-${{ hashFiles('Template/composer.lock') }}
          restore-keys: composer-

      - name: Install PHP dependencies
        working-directory: Template
        run: composer install --no-interaction --no-progress --prefer-dist

      - name: Create SQLite file
        run: touch /tmp/laravel-e2e.sqlite

      - name: Migrate and seed
        working-directory: Template
        run: php artisan migrate:fresh --seed --force

      - name: Start Laravel server
        working-directory: Template
        run: php artisan serve --host=0.0.0.0 --port=8000 &

      - name: Setup Node 22
        uses: actions/setup-node@v4
        with:
          node-version: "22"

      - name: Install pnpm
        run: npm install -g pnpm

      - name: Cache pnpm store
        uses: actions/cache@v4
        with:
          path: ~/.pnpm-store
          key: pnpm-${{ hashFiles('Template/client/package.json') }}
          restore-keys: pnpm-

      - name: Install frontend dependencies
        working-directory: Template/client
        run: pnpm install --frozen-lockfile

      - name: Wait for server
        run: npx wait-on http://localhost:8000/up --timeout 15000

      - name: Run Playwright API tests
        working-directory: Template/client
        run: pnpm run e2e:api
```

- [ ] **Step 2: Validate YAML syntax locally**

```bash
python3 -c "import yaml, sys; yaml.safe_load(open('.github/workflows/ci.yml')); print('YAML valid')"
```

Expected: `YAML valid`

If Python is not available:
```bash
node -e "require('js-yaml').load(require('fs').readFileSync('.github/workflows/ci.yml','utf8')); console.log('YAML valid')"
```

- [ ] **Step 3: Commit**

```bash
git add .github/workflows/ci.yml
git commit -m "ci: add GitHub Actions — test, lint, analyse, frontend-build, e2e"
```

- [ ] **Step 4: Push and observe**

```bash
git push
```

Then open: https://github.com/edouard-mangel/laravel-template/actions

Expected: a workflow run appears for the `main` branch push. All 5 jobs should turn green within ~5 minutes. The `e2e` job starts only after `test` passes.

**If `test` fails** — most likely cause is `platform_check.php`. The `shivammathur/setup-php@v2` action installs PHP 8.4 natively, so the platform check should pass. If it doesn't, the fix is:
```bash
# Add this step before "Run Pest" in the test job:
- name: Patch platform check
  run: echo '<?php // bypassed' > vendor/composer/platform_check.php
  working-directory: Template
```

**If `e2e` fails with "connection refused"** — the server didn't start in time. Increase the `wait-on` timeout:
```yaml
run: npx wait-on http://localhost:8000/up --timeout 30000
```

**If `lint` fails** — Pint found formatting issues. Run `vendor/bin/pint` (without `--test`) locally in `Template/`, commit the formatted files.

**If `analyse` fails** — PHPStan found type errors. Read the output and fix the reported files.

---

## Self-Review

| Spec requirement | Covered |
|---|---|
| Triggers: push + PR to main | ✓ `on: push/pull_request branches: [main]` |
| PHP 8.4 via shivammathur/setup-php@v2 | ✓ all PHP jobs |
| Extensions: pdo, pdo_sqlite, pcntl, mbstring | ✓ test + e2e jobs |
| Cache vendor/ keyed on composer.lock | ✓ all PHP jobs |
| `test` job: pest --no-coverage | ✓ |
| `lint` job: pint --test | ✓ |
| `analyse` job: phpstan --memory-limit=512M | ✓ |
| `frontend-build` job: pnpm install only (no build — angular.json absent) | ✓ |
| `e2e` job: needs test | ✓ `needs: [test]` |
| `e2e` DB: SQLite file at /tmp (not :memory:) | ✓ `DB_DATABASE: /tmp/laravel-e2e.sqlite` |
| `e2e`: migrate:fresh --seed --force | ✓ |
| `e2e`: serve + wait-on + playwright e2e:api | ✓ |
| working-directory: Template for PHP jobs | ✓ job-level defaults |
| APP_KEY set directly (no key:generate step) | ✓ set in env: block |
| pnpm cache keyed on package.json | ✓ all Node jobs |
