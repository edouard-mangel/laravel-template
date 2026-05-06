# Glossary

Precise definitions used throughout this template. When these terms appear in documentation or code,
they mean exactly what is written here.

---

## Domain Layer Terms

### Aggregate

A cluster of domain objects (entities + value objects) that is treated as a single unit for data
changes. The aggregate root is the only entry point — external code never modifies child objects
directly. Example: `Product` is an aggregate root; `ProductPrice` is a value object inside it.

### AggregateRoot

The PHP class that serves as the entry point for an aggregate. It extends the `AggregateRoot` base
class, which provides `recordEvent()` and `releaseEvents()`. All mutation methods (`create()`,
`update()`, `delete()`) are on the aggregate root.

### DomainEvent

A PHP class implementing the `DomainEvent` interface that records something that happened in the
domain. Events are named in past tense: `ProductCreated`, `ProductUpdated`. They carry the data
that changed — enough for a listener to react without querying the database.

### ValueObject

An immutable PHP `readonly class` that represents a domain concept with validation. Two value objects
are equal if all their values are equal. Example: `ProductPrice` rejects negative values in its
constructor.

### Specification

A composable query predicate implementing `SpecificationInterface`. Specifications can be combined
with `and()`, `or()`, `not()`. Used to build database queries in repositories and finders without
leaking query logic into the domain.

---

## Application Layer Terms

### Action

A single-responsibility class that handles one write operation. The equivalent of a Command Handler
in CQRS. An Action has a `handle()` method, declares all dependencies in `__construct()`, and
wraps its work in a database transaction. Example: `CreateProductAction`.

### Query

A single-responsibility class that handles one read operation. Queries return typed DTOs and never
modify state. Queries bypass the repository layer and may use raw database queries for performance.
Example: `GetProductByIdQuery`.

### DTO (Data Transfer Object)

A plain PHP class (or `readonly class`) used to pass data between layers. Actions receive input
DTOs (e.g., `CreateProductData`). Queries return output DTOs (e.g., `ProductDto`). DTOs must not
contain business logic.

### Repository (Interface)

The Application-layer contract for persisting and retrieving aggregate roots. Only declared in
`app/Application/Contracts/Repositories/`. The implementation lives in Infrastructure. Actions
depend on the interface, not the implementation.

### Finder

A read-only query class in the Infrastructure layer that queries the database directly (raw SQL or
Eloquent) for complex read operations. Finders implement an interface from Application but may use
pagination, joins, and filters not possible through the domain repository.

---

## Infrastructure Layer Terms

### EloquentModel

The Eloquent `Model` subclass used for database persistence. Always suffixed `EloquentModel` to
distinguish it from the domain aggregate (e.g., `ProductEloquentModel` vs `Product`). Lives in
`app/Infrastructure/Persistence/`. **Never** used outside the Infrastructure layer.

### Repository (Implementation)

The concrete class implementing the Application-layer repository interface. It maps between domain
objects and Eloquent models. Handles saving, loading, and deleting aggregates. Named
`{Entity}Repository` (e.g., `ProductRepository`).

### Factory

A Laravel `Factory` class used for generating test data. Lives in `database/factories/`. Factories
produce `EloquentModel` instances, not domain aggregates. Tests may convert factory-produced models
to domain objects via the repository.

---

## HTTP Layer Terms

### Controller

A thin Laravel controller that receives an HTTP request, calls a Form Request for validation, calls
one Action or Query, and returns an API Resource. Controllers contain no business logic.

### Form Request

A Laravel `FormRequest` subclass that validates and authorizes the HTTP request. Uses Laravel Policies
for authorization. Returns validated data that is used to build an input DTO for the Action.

### API Resource

A Laravel `JsonResource` subclass that controls the JSON shape of a response. Transforms domain DTOs
into API response format. Handles hiding internal IDs, formatting dates, and nesting related resources.

### Access Context

A per-request object (bound as a singleton in the service container) that contains the authenticated
user's ID and resolved owner context. Used by policies and repositories for ownership filtering.

---

## Testing Terms

### DAMP

"Descriptive and Meaningful Phrases" — tests should be readable without tracing all the way to
shared setup. Prefer duplicating 3 lines of setup in a test over hiding them in a base class that
obscures intent. Opposite of DRY for test code.

### Factory (Test)

In tests, "factory" means a Laravel `Factory`. Call `ProductFactory::new()->create()` to get a
persisted Eloquent model, or `->make()` for an unpersisted one. Do not use raw `new ProductEloquentModel()`
in tests — always go through the factory so defaults are applied consistently.

### Feature Test

A Pest test that exercises the full HTTP stack: sends an HTTP request via `$this->postJson(...)`,
runs through controller → action → repository → database. Uses an in-memory SQLite database or
runs migrations before each test.

### Unit Test

A Pest test that exercises a single domain class with no database, no HTTP, no Laravel container.
Tests value object validation, aggregate behavior, and specification composition. Should be instant.

### Architecture Test

A Pest test using `arch()` assertions that verifies dependency direction, naming conventions, and
layer boundaries. Fails if a domain class imports `Illuminate\` or a controller calls a repository
directly.

---

## Queue / Event Terms

### Domain Event

Recorded by an aggregate root during a state change. Released by `releaseEvents()` after the aggregate
is saved. Dispatched via `event()` which triggers Laravel event listeners.

### Queued Listener

A Laravel event listener implementing `ShouldQueue` that processes domain events asynchronously.
Processed by Horizon. If the listener fails, it is retried with backoff and moved to the failed
jobs table after exhausting retries.

### Failed Job

A job that has exhausted all retry attempts. Stored in the `failed_jobs` table. Can be retried
manually with `php artisan queue:retry {id}` or replayed in bulk.

---

## Patterns

### Ownership-Based Access Control

Rather than role-based ACL, resources are filtered by ownership. The `AccessContext` carries the
authenticated user's `owner_id`. Eloquent global scopes filter all queries to records where
`owner_id` matches — users can only see their own data by default. Policies enforce ownership on
mutations.

### Unit of Work

A pattern where all database changes within a single Action are committed in one atomic transaction.
In this template, `DB::transaction()` inside each Action provides the Unit of Work boundary. There
is no shared UoW object across actions.

### Pipeline (Decorator equivalent)

Laravel's `Pipeline` class allows wrapping an Action call with cross-cutting concerns (logging,
transactions, authorization checks). Used as the equivalent of .NET's decorator chain.
