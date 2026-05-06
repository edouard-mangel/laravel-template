# Templates

Copy-paste starter files for a new Laravel + Angular project. Every file here is intentionally
standalone — copy, rename, and adapt. Do not symlink or inherit from this template.

---

## What's In This Folder

```
templates/
├── .editorconfig                   # PHP + JS formatting rules
├── .gitattributes                  # LF line endings
├── .gitignore                      # PHP, Laravel, Angular, IDE ignores
├── .env.example                    # Environment variable template
├── .gitlab-ci.yml                  # Full CI pipeline (5 stages)
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
│   ├── cors.php                    # CRITICAL: CORS for Angular frontend
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
│   │   └── Product/                # WORKED EXAMPLE — delete after understanding
│   │       ├── Product.php
│   │       ├── ProductId.php
│   │       ├── ProductName.php
│   │       ├── ProductSku.php
│   │       ├── ProductPrice.php
│   │       └── Events/
│   │           ├── ProductCreated.php
│   │           └── ProductUpdated.php
│   │
│   ├── Application/
│   │   ├── Contracts/
│   │   │   ├── Finders/
│   │   │   │   └── ProductFinderInterface.php      # Read-only query interface
│   │   │   └── Repositories/
│   │   │       └── ProductRepositoryInterface.php
│   │   └── Product/
│   │       ├── ProductDto.php
│   │       ├── CreateProductData.php
│   │       ├── UpdateProductData.php
│   │       ├── ProductFilter.php
│   │       ├── Actions/
│   │       │   ├── CreateProductAction.php
│   │       │   ├── UpdateProductAction.php
│   │       │   └── DeleteProductAction.php
│   │       └── Queries/
│   │           ├── GetProductByIdQuery.php
│   │           └── GetProductsQuery.php
│   │
│   ├── Infrastructure/
│   │   ├── Persistence/
│   │   │   ├── Scopes/
│   │   │   │   └── OwnerScope.php  # Ownership-based global scope
│   │   │   └── Product/
│   │   │       ├── ProductEloquentModel.php
│   │   │       ├── ProductRepository.php
│   │   │       └── ProductFinder.php
│   │   └── Providers/
│   │       └── InfrastructureServiceProvider.php  # Repository bindings
│   │
│   └── Http/
│       ├── Controllers/
│       │   └── ProductController.php
│       ├── Middleware/
│       │   ├── CorrelationIdMiddleware.php
│       │   └── AccessContextMiddleware.php
│       ├── Requests/
│       │   ├── CreateProductRequest.php
│       │   └── UpdateProductRequest.php
│       └── Resources/
│           └── ProductResource.php
│
├── database/
│   ├── migrations/
│   │   ├── 2026_01_01_000000_create_users_table.php
│   │   └── 2026_01_01_000001_create_products_table.php
│   ├── factories/
│   │   ├── UserFactory.php
│   │   └── ProductFactory.php
│   └── seeders/
│       ├── DatabaseSeeder.php      # Entry point for php artisan db:seed
│       └── DevDataSeeder.php
│
├── tests/
│   ├── Unit/
│   │   └── Product/
│   │       └── ProductTest.php
│   ├── Feature/
│   │   └── Product/
│   │       └── CreateProductTest.php
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
└── client/                         # Angular 20 SPA (copy verbatim)
    ├── package.json
    ├── angular.json
    ├── tsconfig.json
    ├── vitest.config.ts
    ├── cypress.config.ts
    ├── playwright.config.ts
    └── src/app/
        ├── core/                   # Auth, interceptors
        └── features/product/       # WORKED EXAMPLE — delete after understanding
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
rm -rf app/Domain/Product
rm -rf app/Application/Product
rm -rf app/Infrastructure/Persistence/Product
rm -rf app/Http/Controllers/Product
rm -rf app/Http/Requests/CreateProductRequest.php
rm -rf app/Http/Requests/UpdateProductRequest.php
rm -rf app/Http/Resources/ProductResource.php
rm -rf database/migrations/2026_01_01_000001_create_products_table.php
rm -rf database/factories/ProductFactory.php
rm -rf tests/Unit/Product
rm -rf tests/Feature/Product
rm -rf client/src/app/features/product
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

---

## PHP Dependency Versions (composer.json)

> PHP 8.4+ required — the resolved package set as of 2026 requires PHP 8.4. See UPGRADING.md.

| Package | Version | Purpose |
|---------|---------|---------|
| `laravel/framework` | `^12.0` | Framework |
| `laravel/sanctum` | `^4.0` | SPA authentication |
| `laravel/horizon` | `^5.0` | Queue management |
| `laravel/telescope` | `^5.0` | Debugging (dev only) |
| `pestphp/pest` | `^3.0` | Testing |
| `pestphp/pest-plugin-laravel` | `^3.0` | Laravel Pest plugin |
| `mockery/mockery` | `^1.6` | Test mocking (required by Laravel test helpers) |
| `larastan/larastan` | `^3.0` | PHPStan for Laravel |
| `laravel/pint` | `^1.0` | Code formatting |
| `darkaonline/l5-swagger` | `^9.0` | OpenAPI generation |

---

## JavaScript Dependency Versions (client/package.json)

| Package | Version | Purpose |
|---------|---------|---------|
| `@angular/core` | `~20.0.0` | Framework |
| `primeng` | `~18.0.0` | UI components |
| `openapi-typescript` | `^7.0.0` | API type generation |
| `vitest` | `^2.0.0` | Unit testing |
| `cypress` | `^13.0.0` | Integration testing |
| `@playwright/test` | `^1.45.0` | E2E testing |
