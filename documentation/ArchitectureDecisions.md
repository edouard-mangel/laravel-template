# Architecture Decisions

Each decision records the choice made, the alternatives considered, and the rationale.
Status: **Accepted** unless noted otherwise.

---

## ADR-001: Clean Architecture over Traditional Laravel MVC

**Decision:** Organize code into Domain / Application / Infrastructure / Http layers with a strict
dependency rule, rather than using Laravel's default `app/Models`, `app/Http/Controllers` layout.

**Alternatives considered:**
- Traditional Laravel MVC (models + controllers + service classes in a flat structure)
- Hexagonal Architecture (same idea, different terminology)
- CQRS + Event Sourcing (too heavy for most use cases)

**Rationale:** Traditional Laravel MVC conflates Eloquent models with domain logic, making testing
painful and domains hard to reason about. Clean Architecture keeps domain logic framework-agnostic
and testable without a database. The extra folder ceremony is worth it for projects with real
business logic.

**Trade-off:** More files, more indirection. Smaller projects may find this over-engineered.

---

## ADR-002: CQRS via Action and Query Classes

**Decision:** Separate write operations (Actions) from read operations (Queries) rather than using
a single `ProductService` class with mixed methods.

**Alternatives considered:**
- Single service class per aggregate
- Laravel Actions package (Loris Leiva)
- MediatR-style command/query bus (via PHP equivalent)

**Rationale:** CQRS prevents service classes from growing into god objects. Each Action class is
small, focused, and independently testable. Query classes can be optimized independently (raw SQL,
caching) without affecting the write path.

**Trade-off:** More classes than a single service. Mapping from controller to Action requires
explicit DTO construction.

---

## ADR-003: Eloquent ORM with Custom Repositories

**Decision:** Use Eloquent for persistence, but behind custom repository interfaces. Eloquent models
live in Infrastructure; domain aggregates live in Domain.

**Alternatives considered:**
- Eloquent models used directly everywhere (standard Laravel approach)
- Doctrine ORM (complex setup, less community support in Laravel)
- Raw PDO / query builder only (no ORM)

**Rationale:** Using Eloquent directly everywhere couples domain logic to persistence concerns.
Custom repositories allow the domain to stay pure while still benefiting from Eloquent's migration
system, relationships, and query builder.

**Trade-off:** Two "Product" classes exist: `Product` (domain) and `ProductEloquentModel` (infrastructure).
This mapping must be kept in sync.

---

## ADR-004: PostgreSQL as Primary Database

**Decision:** Target PostgreSQL 17 exclusively. No SQLite for production. SQLite is permitted for
unit tests only.

**Alternatives considered:**
- MySQL/MariaDB
- SQLite (for simplicity)
- Multi-database support

**Rationale:** PostgreSQL offers superior JSON support, full-text search, LISTEN/NOTIFY, and strict
type enforcement. These features are used in migrations. Supporting multiple databases would require
avoiding PostgreSQL-specific features.

**Trade-off:** Local development requires Docker. Cannot use SQLite for feature tests without
potential divergence.

---

## ADR-005: Pest PHP for Testing

**Decision:** Use Pest PHP 3.x as the test framework for all PHP tests.

**Alternatives considered:**
- PHPUnit only (Pest runs on top of PHPUnit, so it is always available as fallback)
- Behat (BDD-style, more complex setup)

**Rationale:** Pest provides a more expressive syntax than raw PHPUnit, built-in architecture
testing via `arch()`, dataset support, and parallel execution. Pest 3 is the standard for modern
Laravel projects.

**Trade-off:** Developers unfamiliar with Pest need to learn the `it()` / `test()` / `describe()`
style. The `arch()` API changes between major versions.

---

## ADR-006: PHPStan at Level 8

**Decision:** Run PHPStan static analysis at Level 8 (maximum strictness) on every CI run.

**Alternatives considered:**
- Level 5 or 6 (default for many projects)
- Psalm (equivalent tool, less Laravel community support)
- No static analysis

**Rationale:** Level 8 catches real bugs, not just style issues. It enforces null safety, return
type correctness, and property initialization. The investment in fixing Level 8 issues pays off
in production reliability.

**Trade-off:** Initial setup requires suppressing false positives with `phpstan.neon` baselines.
Some Laravel magic (dynamic relationships, macro) requires stubs.

---

## ADR-007: Laravel Sanctum for Authentication

**Decision:** Use Laravel Sanctum with cookie-based SPA authentication for the Angular frontend.

**Alternatives considered:**
- Laravel Passport (OAuth2 — too heavy for internal SPA)
- Keycloak (external IdP — same as dotnet template)
- JWT packages (tymon/jwt-auth)

**Rationale:** Sanctum is the recommended Laravel approach for SPA authentication. It handles CSRF
protection, session management, and token issuance without requiring a separate OAuth2 server.
For external IdP requirements (SSO, enterprise), Keycloak can be added alongside Sanctum.

**Trade-off:** Sanctum does not support third-party OAuth flows natively. If OAuth2 delegation is
needed, Passport is the upgrade path.

---

## ADR-008: Ownership-Based Access Control via Eloquent Global Scopes

**Decision:** Filter all queries by an `owner_id` using Eloquent global scopes backed by an
`AccessContext` singleton. Use Laravel Policies for mutation authorization.

**Alternatives considered:**
- Role-based access control (RBAC) via `spatie/laravel-permission`
- Attribute-based access control (ABAC)
- No multi-tenancy (single-owner)

**Rationale:** Ownership-based ACL prevents data leakage between users without requiring complex
role hierarchies. Most SaaS applications need per-resource ownership, not just role membership.
Global scopes ensure that queries cannot accidentally bypass the filter.

**Trade-off:** Admins need an explicit scope bypass. Global scopes can surprise developers who
forget they are applied.

---

## ADR-009: Domain Events + Queued Listeners (Queue Pattern)

**Decision:** Use Laravel's event system with `ShouldQueue` listeners for all side effects triggered
by domain events. Use the `failed_jobs` table for retry tracking.

**Alternatives considered:**
- Transactional Outbox Pattern (like the dotnet template)
- Synchronous listeners (risk of data inconsistency)
- Event sourcing (too heavy)

**Rationale:** Laravel Queues with database or Redis driver provide at-least-once delivery with retry
semantics. The `failed_jobs` table provides visibility into failures. This is simpler than implementing
a full transactional outbox for most use cases.

**Trade-off:** If a job is dispatched before the transaction commits, it may fail. Mitigated by
dispatching events **after** `DB::transaction()` completes (using `afterCommit` in listeners).

---

## ADR-010: Laravel Horizon for Queue Management

**Decision:** Use Horizon for production queue management instead of raw `php artisan queue:work`.

**Alternatives considered:**
- Raw queue workers (no dashboard)
- Supervisor only (no metrics)
- Third-party queue service (SQS, etc.)

**Rationale:** Horizon provides real-time queue metrics, failed job visibility, and job retry UI.
It requires Redis, which is a necessary dependency anyway for session and cache.

**Trade-off:** Redis is now a required infrastructure component. Local development needs a Redis
container.

---

## ADR-011: Angular 20 + PrimeNG 18 for Frontend

**Decision:** Use Angular 20 as the SPA framework with PrimeNG 18 for UI components.

**Alternatives considered:**
- Inertia.js + Vue 3 (tightly coupled to Laravel routing)
- React + shadcn/ui
- Livewire (server-rendered, no SPA)

**Rationale:** Angular provides strong typing, a structured architecture, and the same team can
maintain both backend and frontend. PrimeNG offers a comprehensive component library. Keeping the
frontend decoupled from Laravel routing allows it to be replaced or evolved independently.

**Trade-off:** Angular has more boilerplate than Vue or React. The Angular + Laravel setup requires
CORS configuration and explicit Sanctum cookie handling.

---

## ADR-012: openapi-typescript for Frontend API Types

**Decision:** Generate TypeScript types from the Laravel API's OpenAPI spec using `openapi-typescript`.

**Alternatives considered:**
- Manual type definitions (error-prone, quickly stale)
- tRPC (tight coupling between frontend and backend)
- GraphQL + code generation

**Rationale:** The Laravel API is documented with `l5-swagger` or `scribe`. The generated OpenAPI
spec drives TypeScript type generation, keeping frontend types in sync with backend responses.

**Trade-off:** Requires running code generation after API changes. Types may lag during active
development.

---

## ADR-013: GitLab CI/CD

**Decision:** Use GitLab CI with a 5-stage pipeline: build → test → quality → package → deploy.

**Alternatives considered:**
- GitHub Actions (would require migrating repository)
- Jenkins (heavier setup)
- Plain shell scripts

**Rationale:** GitLab provides integrated CI, registry, and deployment in one platform. The
pipeline structure mirrors the dotnet template for consistency across teams using both stacks.

**Trade-off:** GitLab-specific YAML syntax. Teams on GitHub need to adapt the pipeline to GitHub
Actions manually.
