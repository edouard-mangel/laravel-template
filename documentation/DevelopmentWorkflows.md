# Development Workflows

Day-to-day development commands and processes.

---

## Daily Commands

```bash
# Start the full stack
docker compose -f docker/dev/docker-compose.yml up -d   # Postgres + Redis + Mailpit
php artisan serve                                         # API on :8000
php artisan horizon                                       # Queue worker
cd client && pnpm start                                   # Angular on :4200

# Verify everything is working
php artisan test                                          # PHP tests
cd client && pnpm test                                    # Frontend tests
```

---

## Database Migrations

### Creating a Migration

```bash
php artisan make:migration create_orders_table
php artisan make:migration add_description_to_products_table
```

Laravel automatically sets the timestamp prefix. Follow the naming convention:
- `create_{table}_table` for new tables
- `add_{column}_to_{table}_table` for new columns
- `drop_{table}_table` for removing tables

### Running Migrations

```bash
php artisan migrate                    # Run pending migrations
php artisan migrate:status             # Show migration status
php artisan migrate:rollback           # Roll back the last batch
php artisan migrate:fresh              # Drop all tables and re-run
php artisan migrate:fresh --seed       # Fresh + seed dev data
```

### Testing Migrations

Always run `php artisan migrate:fresh --env=testing` in CI to verify migrations are consistent.
Never use `php artisan migrate` in CI — always start from a fresh state.

---

## Adding a New Aggregate

Follow the checklist in [`WorkedExample.md`](WorkedExample.md). In brief:

1. Create domain classes in `app/Domain/{Entity}/`
2. Create application contracts + actions + queries in `app/Application/{Entity}/`
3. Create Eloquent model + repository in `app/Infrastructure/Persistence/{Entity}/`
4. Register repository binding in `InfrastructureServiceProvider`
5. Create controller + requests + resource in `app/Http/`
6. Register routes in `routes/api.php`
7. Create migration in `database/migrations/`
8. Create factory in `database/factories/`
9. Write unit tests + feature tests + architecture tests
10. Update the seeder if needed

---

## Code Quality

```bash
# Format code (run before every commit)
./vendor/bin/pint

# Check formatting (CI mode — fails if any changes needed)
./vendor/bin/pint --test

# Static analysis
./vendor/bin/phpstan analyse

# Generate baseline (only when adding PHPStan to an existing project)
./vendor/bin/phpstan analyse --generate-baseline
```

Configure PHPStan in `phpstan.neon`:

```neon
includes:
    - vendor/larastan/larastan/extension.neon

parameters:
    level: 8
    paths:
        - app
    ignoreErrors:
        - '#Dynamic call to static method Illuminate\\#'
```

---

## Git Hooks

Install hooks by configuring the hooks path:

```bash
git config core.hooksPath githooks
chmod +x githooks/pre-commit githooks/pre-push
```

### `githooks/pre-commit`

```bash
#!/bin/sh
# Run Pint on staged PHP files
STAGED=$(git diff --cached --name-only --diff-filter=ACM | grep '\.php$')
if [ -n "$STAGED" ]; then
    ./vendor/bin/pint $STAGED
    git add $STAGED
fi
```

### `githooks/pre-push`

```bash
#!/bin/sh
# Fail if there are pending migrations
php artisan migrate:status | grep -q "Pending" && {
    echo "ERROR: You have pending migrations. Run: php artisan migrate"
    exit 1
}

# Run tests
php artisan test --bail || exit 1
```

---

## GitLab CI Pipeline

See `templates/.gitlab-ci.yml` for the full pipeline. Summary:

| Stage | Jobs |
|-------|------|
| `build` | `composer install`, `pnpm install`, `pnpm build` |
| `test` | Unit tests, Feature tests, Architecture tests, Vitest |
| `quality` | Pint (format check), PHPStan, ESLint |
| `package` | Docker image build + push to registry |
| `deploy` | Deployment placeholder |

---

## Local Development Tips

### Debugging Queued Jobs

In local development, set `QUEUE_CONNECTION=sync` in `.env` to run jobs synchronously. This means
queue listeners execute inline in the request, making debugging easier.

To test actual async behavior, use `QUEUE_CONNECTION=database` and run:

```bash
php artisan queue:work --once   # Process one job and exit
php artisan queue:listen        # Continuously process jobs
```

### Telescope for Request Debugging

Access `http://localhost:8000/telescope` to see:
- All HTTP requests and responses
- SQL queries (with bindings)
- Queued jobs and their results
- Mails sent (with Mailpit preview at `:8025`)
- Cache hits and misses

### Mailpit for Email Preview

Dev compose file runs Mailpit at `http://localhost:8025`. All emails sent by the app are captured
here — no emails actually leave the machine.

---

## Frontend Development

```bash
cd client

pnpm start                    # Dev server on :4200 with hot reload
pnpm test                     # Vitest unit tests (watch mode)
pnpm run test:ci              # Vitest (single run, no watch)
pnpm run e2e:cypress          # Cypress integration tests
pnpm run e2e:playwright       # Playwright E2E tests

# Regenerate API types from Laravel OpenAPI spec
pnpm run openapi              # Pulls from http://localhost:8000/api/documentation.json
```

---

## Dependency Updates

PHP:
```bash
composer outdated              # View outdated packages
composer update {package}      # Update a specific package
```

JavaScript:
```bash
pnpm outdated                  # View outdated packages
pnpm update {package}          # Update a specific package
```

After any PHP package update, run the full test suite. After any breaking change, update the
relevant entry in [`UPGRADING.md`](../UPGRADING.md).
