# Query Handlers (CQRS Read Path)

Queries are the read side of CQRS. They return data without modifying state.

---

## What Is a Query?

A Query is a PHP class with a `handle()` method that:
1. Receives typed input (IDs, filters, pagination parameters)
2. Fetches data from the database — directly, without going through the domain
3. Returns typed DTOs (never Eloquent models)
4. Never modifies state

```php
final class GetProductByIdQuery
{
    public function __construct(
        private readonly ProductFinder $finder,
    ) {}

    public function handle(ProductId $id): ?ProductDto
    {
        return $this->finder->findById($id);
    }
}
```

---

## Finders vs Repositories

| | Finder | Repository |
|--|--------|------------|
| Layer | Infrastructure (impl) + Application (interface) | Application (interface) + Infrastructure (impl) |
| Purpose | Read-only queries | Write + minimal reads for domain reconstitution |
| Returns | DTOs | Domain aggregates |
| Uses | Raw Eloquent queries, joins, pagination | Simple lookups by ID |
| Transaction | No | Yes (via Action) |

Use a **Finder** for list pages, search, and complex reads.
Use a **Repository** inside Actions when you need to load and save a domain aggregate.

---

## Finder Implementation

```php
final class ProductFinder
{
    public function findById(ProductId $id): ?ProductDto
    {
        $model = ProductEloquentModel::find((string) $id);

        if ($model === null) {
            return null;
        }

        return $this->toDto($model);
    }

    public function findAll(ProductFilter $filter): LengthAwarePaginator
    {
        $query = ProductEloquentModel::query();

        if ($filter->name !== null) {
            $query->where('name', 'like', "%{$filter->name}%");
        }

        return $query
            ->orderBy('created_at', 'desc')
            ->paginate($filter->perPage ?? 15);
    }

    private function toDto(ProductEloquentModel $model): ProductDto
    {
        return new ProductDto(
            id: $model->id,
            name: $model->name,
            sku: $model->sku,
            priceInCents: $model->price_in_cents,
            ownerId: $model->owner_id,
            createdAt: $model->created_at->toDateTimeImmutable(),
        );
    }
}
```

---

## Query Naming Conventions

| Pattern | Example |
|---------|---------|
| `Get{Entity}ByIdQuery` | `GetProductByIdQuery` |
| `Get{Entities}Query` | `GetProductsQuery` |
| `Search{Entities}Query` | `SearchProductsQuery` |

---

## Why Queries Bypass the Domain

The repository loads domain aggregates so they can enforce business rules. For read operations,
we don't need the aggregate — we need a DTO shaped for the client. Going through the domain would:

1. Add unnecessary object construction overhead
2. Prevent efficient SQL (joins, select specific columns)
3. Force us to reconstitute full aggregates just to get a list of names

Queries go directly to Eloquent/SQL and return DTOs.

---

## Pagination

Return paginated results using Laravel's `LengthAwarePaginator`:

```php
final class GetProductsQuery
{
    public function __construct(
        private readonly ProductFinder $finder,
    ) {}

    public function handle(ProductFilter $filter): LengthAwarePaginator
    {
        return $this->finder->findAll($filter);
    }
}
```

In the controller:

```php
public function index(Request $request, GetProductsQuery $query): AnonymousResourceCollection
{
    $filter = ProductFilter::fromRequest($request);
    $products = $query->handle($filter);

    return ProductResource::collection($products);
}
```

Laravel's `Resource::collection()` automatically handles paginator metadata when a paginator
is passed.

---

## Filter Objects

Use typed filter objects instead of raw arrays:

```php
final readonly class ProductFilter
{
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?int $perPage = 15,
        public readonly ?int $page = 1,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            name: $request->query('name'),
            perPage: (int) $request->query('per_page', 15),
            page: (int) $request->query('page', 1),
        );
    }
}
```

---

## No State Modification in Queries

Queries must never call `save()`, `delete()`, or `event()`. If you find yourself modifying state in
a query, split the operation: use an Action for the write, then a Query for the subsequent read.
