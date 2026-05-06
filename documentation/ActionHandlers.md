# Action Handlers (CQRS Write Path)

Actions are the write side of CQRS in this template. Each action handles exactly one operation.

---

## What Is an Action?

An Action is a PHP class with a single `handle()` method that:
1. Receives a typed input DTO (Data Transfer Object)
2. Orchestrates domain objects and repositories
3. Wraps everything in a database transaction
4. Dispatches domain events after the transaction commits
5. Returns a typed result (typically an ID or void)

```php
final class CreateProductAction
{
    public function __construct(
        private readonly ProductRepositoryInterface $productRepository,
    ) {}

    public function handle(CreateProductData $data): ProductId
    {
        return DB::transaction(function () use ($data): ProductId {
            $product = Product::create(
                id: ProductId::generate(),
                name: new ProductName($data->name),
                sku: new ProductSku($data->sku),
                price: ProductPrice::fromCents($data->priceInCents),
                ownerId: $data->ownerId,
            );

            $this->productRepository->save($product);

            foreach ($product->releaseEvents() as $event) {
                event($event);
            }

            return $product->id();
        });
    }
}
```

---

## Input DTOs

Actions receive data via typed DTOs, not raw arrays or request objects.

```php
final readonly class CreateProductData
{
    public function __construct(
        public readonly string $name,
        public readonly string $sku,
        public readonly int $priceInCents,
        public readonly string $ownerId,
    ) {}

    public static function fromRequest(CreateProductRequest $request): self
    {
        return new self(
            name: $request->validated('name'),
            sku: $request->validated('sku'),
            priceInCents: $request->validated('price_in_cents'),
            ownerId: $request->user()->id,
        );
    }
}
```

The `fromRequest()` factory method lives on the DTO — the controller calls it. This keeps the
controller thin and the Action unaware of HTTP.

---

## Transaction Boundary

Every action must be transactional. Use `DB::transaction()`:

```php
public function handle(CreateProductData $data): ProductId
{
    return DB::transaction(function () use ($data): ProductId {
        // All database operations here are atomic
    });
}
```

**Why not a decorator?** The dotnet template uses a `UnitOfWorkCommandHandlerDecorator`. In Laravel,
decorating is more complex. Explicit `DB::transaction()` in each action is simpler and equally safe.
The tradeoff is repetition; the benefit is transparency.

---

## Event Dispatch After Commit

Domain events must be dispatched **after** the transaction commits, not inside it:

```php
return DB::transaction(function () use ($data): ProductId {
    $product = Product::create(...);
    $this->productRepository->save($product);

    foreach ($product->releaseEvents() as $event) {
        event($event);
    }

    return $product->id();
});
```

Laravel's `DB::transaction()` runs the closure before committing. The `event()` calls inside will
fire only if the transaction succeeds. If the transaction rolls back, events are not dispatched.

For queued listeners, use `afterCommit: true` on the listener class to ensure the job is not
dispatched until after commit:

```php
class SendWelcomeEmailListener implements ShouldQueue
{
    public $afterCommit = true;
    // ...
}
```

---

## Action Naming Conventions

| Operation | Name | Example |
|-----------|------|---------|
| Create | `Create{Entity}Action` | `CreateProductAction` |
| Update | `Update{Entity}Action` | `UpdateProductAction` |
| Delete | `Delete{Entity}Action` | `DeleteProductAction` |
| Custom | `{Verb}{Entity}Action` | `PublishProductAction` |

---

## Registering Actions

Actions are resolved by Laravel's container via constructor injection. No manual registration needed —
declare the dependency in the constructor, and the container will resolve it.

In the controller:

```php
public function store(CreateProductRequest $request, CreateProductAction $action): JsonResponse
{
    $productId = $action->handle(CreateProductData::fromRequest($request));
    return response()->json(['id' => (string) $productId], 201);
}
```

Laravel injects both the `$request` (type-hinted FormRequest) and `$action` (type-hinted Action class)
automatically when the route is dispatched.

---

## What Actions Must NOT Do

- **No HTTP logic** — no `request()`, no `response()`, no redirects
- **No static calls** — no `Product::find()`, no `DB::table()` — use injected repositories
- **No domain logic** — validation belongs in value objects, business rules in the aggregate
- **No direct Eloquent usage** — use the repository interface
- **No multiple transactions** — one action = one transaction boundary

---

## Using Laravel's Pipeline (Optional Decorator)

For cross-cutting concerns (authorization, logging, rate limiting), you can wrap actions with
Laravel's `Pipeline`:

```php
final class PipelineActionRunner
{
    public function run(object $action, callable $handler): mixed
    {
        return app(Pipeline::class)
            ->send($action)
            ->through([
                AuthorizationPipe::class,
                LoggingPipe::class,
            ])
            ->then($handler);
    }
}
```

This is the equivalent of the dotnet decorator chain. Use it only when multiple actions share the
same cross-cutting logic — otherwise, explicit code in each action is clearer.
