# Templates

Copy-paste starter files for a new Laravel API project. Every file here is intentionally
standalone — copy, rename, and adapt. Do not symlink or inherit from this template.

---

## What's In This Folder

```
templates/
├── .editorconfig                   # PHP + JS formatting rules
├── .gitattributes                  # LF line endings
├── .gitignore                      # PHP, Laravel, Node, IDE ignores
├── .env.example                    # Environment variable template
├── .github/
│   └── workflows/
│       └── ci.yml                  # GitHub Actions CI (test, lint, analyse, e2e)
├── artisan                         # Laravel CLI entry point
├── composer.json                   # PHP dependencies (pinned versions)
├── phpstan.neon                    # PHPStan Level 8 configuration
├── phpunit.xml                     # Pest/PHPUnit configuration
├── pint.json                       # Laravel Pint formatting rules
│
├── bootstrap/
│   ├── app.php                     # Application bootstrap (Laravel 12+ style)
│   └── providers.php               # Service provider list
│
├── config/
│   ├── app.php
│   ├── auth.php
│   ├── cache.php
│   ├── cors.php                    # CRITICAL: CORS for API consumers
│   ├── database.php
│   ├── logging.php                 # CRITICAL: log channel definitions
│   ├── queue.php
│   ├── sanctum.php                 # CRITICAL: stateful domain config
│   └── session.php                 # CRITICAL: required by Sanctum auth
│
├── public/
│   └── index.php                   # HTTP entry point
│
├── routes/
│   └── api.php                     # API routes (auth:sanctum group)
│
├── app/
│   ├── Domain/
│   │   ├── Shared/
│   │   │   ├── AggregateRoot.php   # Base class: recordEvent, releaseEvents
│   │   │   ├── ValueObject.php     # Marker class for value objects
│   │   │   ├── DomainEvent.php     # Marker interface for domain events
│   │   │   └── Exceptions/
│   │   │       ├── DomainException.php
│   │   │       ├── InvalidInputException.php    # User input errors → 422
│   │   │       └── ResourceNotFoundException.php # Not found → 404
│   │   └── Program/                # WORKED EXAMPLE — delete after understanding
│   │       ├── Program.php
│   │       ├── ProgramId.php
│   │       ├── ProgramTitle.php
│   │       ├── ProgramDescription.php
│   │       ├── ProgramDuration.php
│   │       ├── ProgramGenre.php
│   │       └── Events/
│   │           ├── ProgramCreated.php
│   │           └── ProgramUpdated.php
│   │
│   ├── Application/
│   │   ├── Contracts/
│   │   │   ├── Finders/
│   │   │   │   └── ProgramFinderInterface.php      # Read-only query interface
│   │   │   └── Repositories/
│   │   │       └── ProgramRepositoryInterface.php
│   │   └── Program/
│   │       ├── ProgramDto.php
│   │       ├── CreateProgramData.php
│   │       ├── UpdateProgramData.php
│   │       ├── ProgramFilter.php
│   │       ├── Actions/
│   │       │   ├── CreateProgramAction.php
│   │       │   ├── UpdateProgramAction.php
│   │       │   └── DeleteProgramAction.php
│   │       └── Queries/
│   │           ├── GetProgramByIdQuery.php
│   │           └── GetProgramsQuery.php
│   │
│   ├── Infrastructure/
│   │   ├── Persistence/
│   │   │   ├── Scopes/
│   │   │   │   └── OwnerScope.php  # Ownership-based global scope
│   │   │   └── Program/
│   │   │       ├── ProgramEloquentModel.php
│   │   │       ├── ProgramRepository.php
│   │   │       └── ProgramFinder.php
│   │   └── Providers/
│   │       └── InfrastructureServiceProvider.php  # Repository bindings
│   │
│   └── Http/
│       ├── Controllers/
│       │   └── ProgramController.php
│       ├── Middleware/
│       │   ├── CorrelationIdMiddleware.php
│       │   └── AccessContextMiddleware.php
│       ├── Requests/
│       │   ├── CreateProgramRequest.php
│       │   └── UpdateProgramRequest.php
│       └── Resources/
│           └── ProgramResource.php
│
├── database/
│   ├── migrations/
│   │   ├── 2026_01_01_000000_create_users_table.php
│   │   ├── 2026_01_01_000001_create_programs_table.php
│   │   ├── 2026_01_01_000002_create_password_reset_tokens_table.php
│   │   ├── 2026_01_01_000003_create_jobs_table.php    # jobs, job_batches, failed_jobs
│   │   └── 2026_01_01_000004_create_cache_table.php   # cache, cache_locks
│   ├── factories/
│   │   ├── UserFactory.php
│   │   └── ProgramFactory.php
│   └── seeders/
│       ├── DatabaseSeeder.php      # Entry point for php artisan db:seed
│       └── DevDataSeeder.php
│
├── tests/
│   ├── Unit/
│   │   └── Program/
│   │       └── ProgramTest.php
│   ├── Feature/
│   │   └── Program/
│   │       ├── CreateProgramTest.php
│   │       └── ProgramCrudTest.php  # GET list/show, PUT, DELETE coverage
│   └── Architecture/
│       ├── DomainLayerTest.php
│       ├── ApplicationLayerTest.php
│       └── NamingConventionTest.php
│
├── githooks/
│   ├── pre-commit                  # Run Pint on staged files
│   └── pre-push                    # Check migrations + run tests
│
├── docker/
│   ├── dev/
│   │   └── docker-compose.yml      # Postgres, Redis, Mailpit
│   ├── prod/
│   │   └── docker-compose.yml      # Production reference
│   └── Dockerfile                  # PHP-FPM + Nginx multi-stage
│
└── e2e/                            # Playwright API tests
    ├── package.json
    ├── playwright.config.ts
    └── api/
        └── programs.spec.ts        # WORKED EXAMPLE — replace with your domain
```

---

## How to Use These Files

### Step 1: Copy the template structure

```bash
cp -r laravel-template/templates/. your-project/
```

### Step 2: Delete the worked example

```bash
# After reading WorkedExample.md and understanding the patterns:
rm -rf app/Domain/Program
rm -rf app/Application/Program
rm -rf app/Application/Contracts/Finders/ProgramFinderInterface.php
rm -rf app/Application/Contracts/Repositories/ProgramRepositoryInterface.php
rm -rf app/Infrastructure/Persistence/Program
rm -rf app/Http/Controllers/ProgramController.php
rm -rf app/Http/Requests/CreateProgramRequest.php
rm -rf app/Http/Requests/UpdateProgramRequest.php
rm -rf app/Http/Resources/ProgramResource.php
rm -rf database/migrations/2026_01_01_000001_create_programs_table.php
rm -rf database/factories/ProgramFactory.php
rm -rf tests/Unit/Program
rm -rf tests/Feature/Program
rm -rf e2e/api/programs.spec.ts
# Remove the programs route from routes/api.php
```

### Step 3: Update `composer.json`

Set `APP_NAME` and your team/org in `composer.json`. Run `composer install`.

### Step 4: Set up infrastructure

```bash
cp .env.example .env
php artisan key:generate
docker compose -f docker/dev/docker-compose.yml up -d
php artisan migrate
```

### Step 5: Add the GitHub Actions secret

The CI pipeline reads `APP_KEY` from a GitHub secret. Add it once:

```
GitHub repo → Settings → Secrets and variables → Actions → New repository secret
Name: APP_KEY
Value: (paste the value of APP_KEY from your .env after key:generate)
```

---

## PHP Dependency Versions (composer.json)

> PHP 8.4+ required — the resolved package set as of 2026 requires PHP 8.4. See UPGRADING.md.

| Package | Version | Purpose |
|---------|---------|---------|
| `laravel/framework` | `^12.0` | Framework |
| `laravel/sanctum` | `^4.0` | API token + cookie authentication |
| `laravel/horizon` | `^5.0` | Queue management |
| `laravel/telescope` | `^5.0` | Debugging (dev only) |
| `pestphp/pest` | `^3.0` | Testing |
| `pestphp/pest-plugin-laravel` | `^3.0` | Laravel Pest plugin |
| `mockery/mockery` | `^1.6` | Test mocking (required by Laravel test helpers) |
| `larastan/larastan` | `^3.0` | PHPStan for Laravel |
| `laravel/pint` | `^1.0` | Code formatting |
| `darkaonline/l5-swagger` | `^9.0` | OpenAPI generation |

---

## E2E Dependency Versions (e2e/package.json)

| Package | Version | Purpose |
|---------|---------|---------|
| `@playwright/test` | `^1.45.0` | API E2E tests |
