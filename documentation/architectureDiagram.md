# Architecture Diagram

Layer boundaries and dependency direction.

---

## Layer Overview

```
┌─────────────────────────────────────────────────────────────────┐
│                        HTTP Layer                               │
│   app/Http/Controllers/     app/Http/Requests/                 │
│   app/Http/Resources/       routes/api.php                     │
└──────────────────────────────┬──────────────────────────────────┘
                               │ calls
┌──────────────────────────────▼──────────────────────────────────┐
│                     Application Layer                           │
│   app/Application/Actions/    app/Application/Queries/         │
│   app/Application/Contracts/  (interfaces for repositories)    │
└─────────────────┬──────────────────────────────────────────────┘
                  │ uses                         │ implements (via DI)
┌─────────────────▼────────────┐  ┌─────────────▼────────────────┐
│       Domain Layer           │  │   Infrastructure Layer        │
│   app/Domain/                │  │   app/Infrastructure/         │
│   Pure PHP — value objects,  │  │   Eloquent models,            │
│   aggregates, domain events  │  │   repositories, external APIs │
└──────────────────────────────┘  └───────────────────────────────┘
```

**Dependency rule:** Arrows point inward only. Infrastructure depends on Application (to implement
interfaces). Application depends on Domain. HTTP depends on Application. Domain depends on nothing.

---

## Request Flow (Write Operation)

```
POST /api/products
        │
        ▼
CreateProductRequest::authorize()    ← Laravel Form Request (validates + authorizes)
        │
        ▼
ProductController::store()           ← Thin controller
        │
        ▼
CreateProductData::fromRequest()     ← Build typed DTO
        │
        ▼
CreateProductAction::handle()        ← Business orchestration
        │
        ├── DB::transaction() begin
        │
        ├── Product::create()        ← Domain aggregate (value object construction, event recording)
        │
        ├── ProductRepository::save()  ← Repository (domain → Eloquent mapping)
        │       └── ProductEloquentModel::updateOrCreate()  ← Eloquent (SQL)
        │
        ├── DB::transaction() commit
        │
        └── event(new ProductCreated())  ← Domain event dispatch
                └── SendWelcomeEmailListener::handle()  ← Queued listener (via Horizon)

        │
        ▼
response()->json(['id' => ...], 201)
```

---

## Request Flow (Read Operation)

```
GET /api/products
        │
        ▼
ProductController::index()
        │
        ▼
GetProductsQuery::handle()
        │
        ▼
ProductFinder::findAll()           ← Bypasses domain, reads Eloquent directly
        │
        ▼
ProductEloquentModel::query()      ← With OwnerScope applied automatically
        │ (WHERE owner_id = ?)
        ▼
[ProductEloquentModel, ...]
        │
        ▼
Collection of ProductDto           ← Mapped in Finder::toDto()
        │
        ▼
ProductResource::collection()      ← JSON response shaping
        │
        ▼
{ "data": [...], "meta": {...} }
```

---

## Module Dependency Graph

```
           ┌─────────┐
           │  Domain  │  (no dependencies)
           └────┬─────┘
                │ ◄──────────────────────────────────────┐
           ┌────▼──────────────────────────────────────┐  │
           │           Application                      │  │
           │  Actions, Queries, Contracts (interfaces)  │  │
           └────┬──────────────────────────────────────┘  │
                │                          │               │
      ┌─────────▼─────────┐       ┌───────▼──────────────┐│
      │    HTTP Layer      │       │   Infrastructure      ││
      │  Controllers,      │       │  Repositories,        ││
      │  Resources,        │       │  Eloquent models      │┤
      │  FormRequests      │       │  (implements          ││
      └───────────────────┘       │   Application         ││
                                  │   contracts)          ││
                                  └───────────────────────┘│
                                           │               │
                                  ┌────────▼───────────────┘
                                  │  Database (PostgreSQL)
                                  └────────────────────────
```

---

## Infrastructure Dependencies

```
                           ┌──────────────────┐
                           │  Redis (Queue +  │
                           │  Cache + Session) │
                           └────────┬─────────┘
                                    │
┌─────────────┐   ┌─────────────────▼──────────────────────┐
│  PostgreSQL │◄──│               Laravel App               │
│  (Primary   │   │  (PHP 8.3, Laravel 12, Horizon)         │
│   Database) │   └────────────────────────────────────────┬┘
└─────────────┘                                            │
                                                           │
                   ┌───────────────────────────────────────▼─┐
                   │             API Consumers                 │
                   │  (any HTTP client — REST + Sanctum)       │
                   └──────────────────────────────────────────┘
```

---

## Test Layer Mapping

| Test Layer | Tests | Speed | Isolation |
|-----------|-------|-------|-----------|
| Architecture | Pest `arch()` | Instant | No DB |
| Unit | Domain value objects, aggregates | Instant | No DB |
| Feature | Full HTTP stack with in-memory SQLite | Fast | SQLite |
| E2E (Playwright API) | Full HTTP cycle against live server | Slow | Real DB (SQLite file) |
