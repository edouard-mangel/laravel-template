# E2E Test Design: TV Program API

**Date:** 2026-05-06
**Status:** Approved

---

## Context

The `Template/` scaffold currently uses `Product` as its worked example aggregate. This spec
replaces `Product` with `Program` (TV program) throughout the entire scaffold, then adds a
Playwright API E2E test that exercises the full CRUD cycle against a running Laravel server.

The change has two parts:
1. **Domain replacement** — swap every `Product` file for a `Program` equivalent
2. **E2E test** — add `POST /api/login` auth endpoint + Playwright API test suite

---

## Domain Model

```
Program (aggregate root)
├── ProgramId          UUID, generated on create
├── ProgramTitle       string, required, 1–255 chars
├── ProgramDescription string, optional (nullable)
├── ProgramDuration    int (minutes), must be > 0
├── ProgramGenre       string, required, 1–100 chars
└── ownerId            string — ownership-scoped via OwnerScope

Events
├── ProgramCreated     (programId, title, durationMinutes, occurredAt)
└── ProgramUpdated     (programId, title, durationMinutes, occurredAt)
```

---

## Files to Delete

All files under the `Product` namespace:

```
app/Domain/Product/
app/Application/Product/
app/Application/Contracts/Repositories/ProductRepositoryInterface.php
app/Application/Contracts/Finders/ProductFinderInterface.php
app/Infrastructure/Persistence/Product/
app/Http/Controllers/ProductController.php
app/Http/Requests/CreateProductRequest.php
app/Http/Requests/UpdateProductRequest.php
app/Http/Resources/ProductResource.php
database/migrations/2026_01_01_000001_create_products_table.php
database/factories/ProductFactory.php
tests/Unit/Product/
tests/Feature/Product/
```

---

## Files to Create — Domain Layer

| File | Key rules |
|------|-----------|
| `app/Domain/Program/ProgramId.php` | UUID, `Ramsey\Uuid\Uuid::uuid4()`, `fromString()`, `equals()` |
| `app/Domain/Program/ProgramTitle.php` | `readonly`, non-empty, max 255, throws `InvalidInputException` |
| `app/Domain/Program/ProgramDescription.php` | `readonly`, nullable (empty string allowed), max 2000 chars |
| `app/Domain/Program/ProgramDuration.php` | `readonly`, int minutes, must be > 0 |
| `app/Domain/Program/ProgramGenre.php` | `readonly`, non-empty, max 100 |
| `app/Domain/Program/Program.php` | aggregate, `create()`, `reconstitute()`, `update(title, description, duration)` |
| `app/Domain/Program/Events/ProgramCreated.php` | `readonly`, implements `DomainEvent` |
| `app/Domain/Program/Events/ProgramUpdated.php` | `readonly`, implements `DomainEvent` |

---

## Files to Create — Application Layer

| File | Notes |
|------|-------|
| `app/Application/Contracts/Repositories/ProgramRepositoryInterface.php` | `save`, `findById`, `delete` |
| `app/Application/Contracts/Finders/ProgramFinderInterface.php` | `findById`, `findAll` |
| `app/Application/Program/ProgramDto.php` | `readonly`, 6 fields: id, title, description, durationMinutes, genre, ownerId, createdAt |
| `app/Application/Program/CreateProgramData.php` | `fromRequest(CreateProgramRequest)` factory |
| `app/Application/Program/UpdateProgramData.php` | `fromRequest(UpdateProgramRequest)` factory |
| `app/Application/Program/ProgramFilter.php` | `genre?: string`, `perPage: int`, `page: int`, `fromRequest()` |
| `app/Application/Program/Actions/CreateProgramAction.php` | `DB::transaction`, releases events |
| `app/Application/Program/Actions/UpdateProgramAction.php` | loads via repository, throws `ResourceNotFoundException` |
| `app/Application/Program/Actions/DeleteProgramAction.php` | loads via repository, throws `ResourceNotFoundException` |
| `app/Application/Program/Queries/GetProgramByIdQuery.php` | delegates to `ProgramFinderInterface` |
| `app/Application/Program/Queries/GetProgramsQuery.php` | returns `LengthAwarePaginator` |

---

## Files to Create — Infrastructure Layer

| File | Notes |
|------|-------|
| `app/Infrastructure/Persistence/Program/ProgramEloquentModel.php` | table `programs`, UUID PK, `newFactory()` override, `OwnerScope` |
| `app/Infrastructure/Persistence/Program/ProgramRepository.php` | `withoutGlobalScopes()` for write ops, `toDomain()` via `reconstitute()` |
| `app/Infrastructure/Persistence/Program/ProgramFinder.php` | implements `ProgramFinderInterface`, filters by `genre`, paginates |
| `app/Infrastructure/Providers/InfrastructureServiceProvider.php` | replace Product bindings with Program |

---

## Files to Create — HTTP Layer

| File | Notes |
|------|-------|
| `app/Http/Controllers/AuthController.php` | `POST /api/login` → issues Sanctum personal access token |
| `app/Http/Requests/LoginRequest.php` | validates `email` (required, email) + `password` (required, string) |
| `app/Http/Controllers/ProgramController.php` | thin, 5 methods, delegates to Actions/Queries |
| `app/Http/Requests/CreateProgramRequest.php` | validates title, description (nullable), duration_minutes (int > 0), genre |
| `app/Http/Requests/UpdateProgramRequest.php` | authorizes via owner check; validates same fields |
| `app/Http/Resources/ProgramResource.php` | wraps `ProgramDto` |

---

## Login Endpoint Contract

```
POST /api/login
Content-Type: application/json

Request:  { "email": "test@example.com", "password": "password" }
Response 200: { "token": "<plaintext>", "user": { "id", "name", "email" } }
Response 401: { "message": "Invalid credentials." }
```

Public route — no `auth:sanctum` middleware.

---

## Database

**Migration:** `database/migrations/2026_01_01_000001_create_programs_table.php`

```sql
programs
├── id               uuid PRIMARY KEY
├── title            string
├── description      text NULLABLE
├── duration_minutes unsigned int
├── genre            string
├── owner_id         string  (indexed)
└── timestamps
```

**Factory:** `database/factories/ProgramFactory.php` — faker title, lorem description,
random duration 30–180, random genre from `['drama','comedy','documentary','thriller','animation']`.

---

## Seeders

`DevDataSeeder` seeds:
1. One known test user: `test@example.com` / `password` (id fixed as `00000000-0000-0000-0000-000000000001`)
2. Ten programs owned by a random `ownerId`

---

## Tests

**Unit:** `tests/Unit/Program/ProgramTest.php`
- `ProgramCreated` event recorded on `create()`
- Events released only once
- `reconstitute()` records no events
- `ProgramTitle` rejects empty and > 255
- `ProgramDuration` rejects `<= 0`

**Feature:** `tests/Feature/Program/CreateProgramTest.php`
- 201 + id on valid create
- 422 on missing title
- 401 for unauthenticated

**Architecture tests:** unchanged (enforce patterns, not names)

---

## E2E Test

**File:** `client/playwright/api/programs.spec.ts`
**Tool:** Playwright `request` fixture (no browser)
**Base URL:** `http://localhost:8000`

```
beforeAll  POST /api/login                        → 200, capture token
test 1     POST /api/programs                     → 201, capture id
test 2     GET  /api/programs/:id                 → 200, assert title/genre/duration
test 3     GET  /api/programs                     → 200, assert data[] + meta.total
test 4     PUT  /api/programs/:id                 → 204
test 5     GET  /api/programs/:id (after update)  → 200, assert new title
test 6     DELETE /api/programs/:id               → 204
test 7     GET  /api/programs/:id (after delete)  → 404
test 8     POST /api/login (bad password)         → 401
```

**playwright.config.ts change:** add `api` project:
```typescript
{ name: 'api', testDir: './playwright/api', use: { baseURL: 'http://localhost:8000' } }
```
The existing `chromium` project (Angular browser tests, `:4200`) is unchanged.

**Run command:** `pnpm run e2e:api` → maps to `playwright test --project=api`

`client/package.json` gains: `"e2e:api": "playwright test --project=api"`

---

## Routes change

```php
// Public
Route::post('/login', [AuthController::class, 'login']);

// Authenticated
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('programs', ProgramController::class);
});
```

---

## Out of Scope

- `app/Domain/Shared/` — untouched
- Architecture tests — untouched
- Angular client files — untouched
- Docker, CI, config files — untouched
