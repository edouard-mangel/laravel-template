# Laravel Template — Claude Code Guide

## Project Overview

This is a **documentation-driven scaffolding template** for API applications built with
**Laravel 12 (PHP 8.4+)**. It contains no runnable application — only reference files,
patterns, and starter code to copy into a real project.

**Your job when using this template:** read the documentation, copy the starter files, adapt them to
your domain, and build your feature using the established patterns. This guide tells you where to look
and in what order.

---

## Stack Summary

| Layer | Technology |
|-------|-----------|
| Language | PHP 8.4+ |
| Framework | Laravel 12.x |
| Database | PostgreSQL 17 via Eloquent ORM |
| Authentication | Laravel Sanctum (JWT-style SPA auth) |
| Queue | Laravel Horizon + Redis |
| Testing | Pest PHP 3.x |
| Code Quality | PHPStan Level 8, Laravel Pint |
| E2E Testing | Playwright (API tests) |
| CI/CD | GitHub Actions |
| Containers | Docker + Docker Compose |

---

## Architecture Overview

The project uses **Clean Architecture** with a strict dependency rule:

```
Http (Controllers)
    ↓
Application (Actions + Queries)
    ↓
Domain (Value Objects, Aggregates, Events)

Infrastructure (Repositories, Eloquent Models)
    ↓
Application (implements contracts)
    ↓
Domain
```

### Layer Responsibilities

| Layer | Location | Responsibility |
|-------|----------|---------------|
| Domain | `app/Domain/` | Pure PHP — value objects, aggregates, domain events. Zero Laravel dependencies. |
| Application | `app/Application/` | Action classes (write) and Query classes (read). Orchestrates domain. |
| Infrastructure | `app/Infrastructure/` | Eloquent models, repositories, external services. Implements Application contracts. |
| Http | `app/Http/` | Controllers, Form Requests, API Resources. Thin — delegates to Application. |

---

## Reading Order (Do This First)

Before writing any code, read these files **in this order**:

1. This file (CLAUDE.md) — you are here
2. [`documentation/Glossary.md`](documentation/Glossary.md) — understand the vocabulary
3. [`documentation/ArchitectureDecisions.md`](documentation/ArchitectureDecisions.md) — understand the WHY
4. [`documentation/WorkedExample.md`](documentation/WorkedExample.md) — the canonical Product slice
5. [`documentation/ActionHandlers.md`](documentation/ActionHandlers.md) — CQRS write path
6. [`documentation/QueryHandlers.md`](documentation/QueryHandlers.md) — CQRS read path
7. [`documentation/DatabaseAndPersistence.md`](documentation/DatabaseAndPersistence.md) — Eloquent, repositories
8. [`documentation/Permissions.md`](documentation/Permissions.md) — ownership-based access control
9. [`documentation/DomainEvents.md`](documentation/DomainEvents.md) — event-driven design
10. [`documentation/QueuePattern.md`](documentation/QueuePattern.md) — reliable event delivery
11. [`documentation/SpecificationPattern.md`](documentation/SpecificationPattern.md) — composable queries
12. [`documentation/Testing.md`](documentation/Testing.md) — test strategy and factories
13. [`documentation/API.md`](documentation/API.md) — REST endpoint patterns
14. [`documentation/Configuration.md`](documentation/Configuration.md) — .env and config
15. [`documentation/Observability.md`](documentation/Observability.md) — logging and monitoring
16. [`documentation/SeedData.md`](documentation/SeedData.md) — factories and seeders
17. [`documentation/DevelopmentWorkflows.md`](documentation/DevelopmentWorkflows.md) — day-to-day commands
18. [`documentation/GettingStarted.md`](documentation/GettingStarted.md) — local setup
19. [`templates/README.md`](templates/README.md) — starter files index

---

## First Use: Scaffold From Template

Follow these steps to bootstrap a new project from this template:

### Step 1 — Copy the templates/ folder

```bash
cp -r laravel-template/templates/ my-new-project/
cd my-new-project
```

### Step 2 — Configure environment

```bash
cp .env.example .env
# Edit .env: APP_NAME, DB_DATABASE, SANCTUM_STATEFUL_DOMAINS
```

### Step 3 — Install dependencies

```bash
composer install
```

### Step 4 — Start infrastructure

```bash
docker compose -f docker/dev/docker-compose.yml up -d
```

### Step 5 — Bootstrap the database

```bash
php artisan key:generate
php artisan migrate
php artisan db:seed --class=DevDataSeeder
```

### Step 6 — Start the application

```bash
php artisan serve          # API on :8000
php artisan horizon        # Queue worker
```

### Step 7 — Verify everything works

```bash
php artisan test           # Run Pest test suite
```

### Step 8 — Delete the Product worked example

Once you understand the patterns, delete the worked example and replace it with your domain:

```bash
# Delete Product worked example
rm -rf app/Domain/Product
rm -rf app/Application/Product
rm -rf app/Infrastructure/Persistence/Product
rm -rf app/Http/Controllers/Product
rm -rf tests/Unit/Product
rm -rf tests/Feature/Product

# Delete the migration and seeder
# Remove from database/migrations/
# Remove from database/seeders/DevDataSeeder.php
```

### Step 9 — Build your first aggregate

Follow [`documentation/WorkedExample.md`](documentation/WorkedExample.md) as a reference for each layer.
The file checklist at the end of that document lists every file you need to create.

---

## Acceptance Checklist

Before marking any feature as complete, verify:

### Domain Layer
- [ ] Value objects are `readonly` PHP classes with validation in the constructor
- [ ] Aggregate root extends `AggregateRoot` and uses `recordEvent()` for domain events
- [ ] Domain classes have **zero** `use Illuminate\...` imports
- [ ] Domain events implement `DomainEvent` interface

### Application Layer
- [ ] Each write operation is a single `Action` class with `handle()` method
- [ ] Actions declare dependencies via `__construct()` — no static calls, no `app()`
- [ ] Actions are wrapped in a database transaction via `DB::transaction()` or Pipeline
- [ ] Each read operation is a `Query` class returning a typed result
- [ ] No `Eloquent\Model` imports in the Application layer

### Infrastructure Layer
- [ ] Repository classes implement the Application-layer interface
- [ ] Repository auto-registered by naming convention `{Entity}Repository`
- [ ] Eloquent models are in `app/Infrastructure/Persistence/` — **not** in `app/Models/`
- [ ] Factories are adjacent to Eloquent models, not in domain

### Http Layer
- [ ] Controllers are thin — they validate input, call an Action, return a Resource
- [ ] Form Requests validate and authorize (use policies)
- [ ] API Resources control the response shape — no `.toArray()` on Eloquent directly
- [ ] No business logic in controllers

### Testing
- [ ] Unit tests cover domain value object validation and aggregate behavior
- [ ] Feature tests cover the happy path and key failure cases
- [ ] Architecture tests enforce layer boundaries (see `tests/Architecture/`)
- [ ] Test data built via factories — no manual `new Entity()` in tests

### Code Quality
- [ ] `./vendor/bin/pint` passes with zero violations
- [ ] `./vendor/bin/phpstan analyse` passes at Level 8
- [ ] `php artisan migrate:fresh --env=testing` runs without errors

---

## Quick Reference

### Artisan Commands

```bash
php artisan make:migration create_products_table
php artisan migrate
php artisan migrate:rollback
php artisan migrate:fresh --seed

php artisan db:seed --class=DevDataSeeder

php artisan route:list
php artisan config:clear && php artisan cache:clear

php artisan horizon           # Start queue worker
php artisan queue:work        # Single queue worker (dev)
php artisan queue:failed      # View failed jobs

php artisan telescope:prune   # Clean Telescope data
```

### Test Commands

```bash
php artisan test                           # All tests
php artisan test --filter=ProductTest      # Single test class
php artisan test tests/Unit/               # Unit tests only
php artisan test tests/Feature/            # Feature tests only

./vendor/bin/pest --coverage               # With coverage report
./vendor/bin/pest --parallel               # Parallel execution
```

### Code Quality

```bash
./vendor/bin/pint                          # Format code
./vendor/bin/pint --test                   # Check formatting (CI)
./vendor/bin/phpstan analyse               # Static analysis
```

### E2E Tests

```bash
cd e2e
npm install
npx playwright test --project=api          # Playwright API tests (requires php artisan serve)
```

---

## Naming Conventions

These conventions are enforced by architecture tests in `tests/Architecture/`:

| Pattern | Auto-resolved? | Example |
|---------|---------------|---------|
| `{Entity}Repository` | Yes | `ProductRepository` |
| `Create{Entity}Action` | No (manual DI) | `CreateProductAction` |
| `Get{Entity}ByIdQuery` | No (manual DI) | `GetProductByIdQuery` |
| `{Entity}EloquentModel` | Yes | `ProductEloquentModel` |
| `{Entity}Controller` | Laravel route | `ProductController` |
| `{Entity}Resource` | Manual | `ProductResource` |
| `{Action}{Entity}Request` | Manual | `CreateProductRequest` |

---

## Important Invariants

### No Eloquent in Domain or Application

The Domain layer must be framework-agnostic. The Application layer must not depend on Eloquent models.
This allows domain testing without a database and ensures clean dependency direction.

```php
// WRONG — Eloquent leaking into Application
use App\Infrastructure\Persistence\ProductEloquentModel;

// CORRECT — use the repository interface
use App\Application\Contracts\Repositories\ProductRepositoryInterface;
```

### Actions are Always Transactional

All write actions must be wrapped in a database transaction. Use the `TransactionalPipeline` or
`DB::transaction()` explicitly:

```php
// CORRECT
public function handle(CreateProductData $data): ProductId
{
    return DB::transaction(function () use ($data) {
        $product = Product::create($data->name, $data->sku, $data->price);
        $this->productRepository->save($product);
        return $product->id();
    });
}
```

### Repository Saves Domain Objects, Not Eloquent Models

The repository interface is in Application layer. The implementation is in Infrastructure. The Action
only knows about the interface:

```php
// Application layer — Action only sees this interface
interface ProductRepositoryInterface {
    public function save(Product $product): void;
    public function findById(ProductId $id): ?Product;
}

// Infrastructure layer — Eloquent implementation
class ProductRepository implements ProductRepositoryInterface { ... }
```

### Domain Events Are Recorded, Not Dispatched

Domain objects call `$this->recordEvent(new ProductCreated(...))`. The repository or Action is
responsible for dispatching recorded events after the transaction commits:

```php
// After repository->save(), dispatch events
foreach ($product->releaseEvents() as $event) {
    event($event);
}
```

---

## Auto-Registration Conventions

### Repositories

Repository classes in `app/Infrastructure/Persistence/` that implement an interface named
`{Entity}RepositoryInterface` are auto-bound in `InfrastructureServiceProvider`:

```php
// Automatically bound:
// App\Application\Contracts\Repositories\ProductRepositoryInterface
//   → App\Infrastructure\Persistence\Product\ProductRepository
```

### Finders (Read-Only Queries)

Query classes are **not** auto-registered. They are resolved directly via the container or
instantiated in the Action/Query handler.

---

## Key Architecture Tests

Architecture tests in `tests/Architecture/` enforce the rules above. If you add code that violates
the dependency direction or naming conventions, these tests will fail. **Do not skip them.**

Key assertions:
- Domain classes have no `Illuminate\` imports
- Application contracts have no `EloquentModel` references
- Repository implementations are only in `Infrastructure\Persistence\`
- Controllers only call Application layer classes

---

## File Structure Reference

```
app/
├── Domain/
│   ├── Shared/
│   │   ├── AggregateRoot.php           # Base class for aggregates
│   │   ├── ValueObject.php             # Base class for value objects
│   │   ├── DomainEvent.php             # Domain event interface
│   │   └── Exceptions/                 # Domain exception hierarchy
│   └── Product/                        # WORKED EXAMPLE aggregate
│       ├── Product.php                 # Aggregate root
│       ├── ProductId.php               # Value object
│       ├── ProductName.php
│       ├── ProductSku.php
│       ├── ProductPrice.php
│       └── Events/
│           ├── ProductCreated.php
│           └── ProductUpdated.php
│
├── Application/
│   ├── Contracts/
│   │   └── Repositories/
│   │       └── ProductRepositoryInterface.php
│   └── Product/
│       ├── ProductData.php             # DTO for action input
│       ├── ProductDto.php              # DTO for query output
│       ├── Actions/
│       │   ├── CreateProductAction.php
│       │   ├── UpdateProductAction.php
│       │   └── DeleteProductAction.php
│       └── Queries/
│           ├── GetProductByIdQuery.php
│           └── GetProductsQuery.php
│
├── Infrastructure/
│   └── Persistence/
│       └── Product/
│           ├── ProductEloquentModel.php
│           ├── ProductRepository.php
│           └── ProductFinder.php       # Read-only queries (raw DB)
│
└── Http/
    ├── Controllers/
    │   └── ProductController.php
    ├── Requests/
    │   ├── CreateProductRequest.php
    │   └── UpdateProductRequest.php
    └── Resources/
        └── ProductResource.php

database/
├── migrations/
│   └── 2026_01_01_000001_create_products_table.php
├── factories/
│   └── ProductFactory.php
└── seeders/
    └── DevDataSeeder.php

tests/
├── Unit/
│   └── Product/
│       └── ProductTest.php
├── Feature/
│   └── Product/
│       └── CreateProductTest.php
└── Architecture/
    ├── DomainLayerTest.php
    ├── ApplicationLayerTest.php
    └── NamingConventionTest.php

e2e/                                    # Playwright API tests
├── package.json
├── playwright.config.ts
└── api/
    └── programs.spec.ts
```
