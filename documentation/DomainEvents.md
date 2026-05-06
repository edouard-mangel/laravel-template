# Domain Events

Domain events record facts that happened in the domain. They are the mechanism for decoupling
side effects (emails, audit logs, notifications) from the core business operation.

---

## Domain Event Interface

```php
namespace App\Domain\Shared;

interface DomainEvent {}
```

All domain events implement this marker interface. Events are `readonly` classes.

---

## Recording Events in Aggregates

The `AggregateRoot` base class provides event recording:

```php
abstract class AggregateRoot
{
    private array $domainEvents = [];

    protected function recordEvent(DomainEvent $event): void
    {
        $this->domainEvents[] = $event;
    }

    public function releaseEvents(): array
    {
        $events = $this->domainEvents;
        $this->domainEvents = [];
        return $events;
    }
}
```

Aggregates call `recordEvent()` during state changes:

```php
public static function create(...): self
{
    $product = new self(...);
    $product->recordEvent(new ProductCreated(
        productId: $id,
        name: $name->value,
        occurredAt: new \DateTimeImmutable(),
    ));
    return $product;
}
```

---

## Dispatching Events After Commit

Events are released in the Action, **after** the transaction saves the aggregate:

```php
return DB::transaction(function () use ($data): ProductId {
    $product = Product::create(...);
    $this->productRepository->save($product);

    // Release events only after the save is successful
    foreach ($product->releaseEvents() as $event) {
        event($event);
    }

    return $product->id();
});
```

This ensures events are only dispatched when the database state is committed. The `event()` call
triggers all registered listeners for that event type.

---

## Event Classes

Events carry the data needed for listeners to react without querying the database:

```php
final readonly class ProductCreated implements DomainEvent
{
    public function __construct(
        public readonly ProductId $productId,
        public readonly string $name,
        public readonly int $priceInCents,
        public readonly \DateTimeImmutable $occurredAt,
    ) {}
}
```

**Name events in past tense** — they describe something that already happened.

---

## Listeners

Laravel event listeners respond to domain events. Register in `EventServiceProvider`:

```php
protected $listen = [
    ProductCreated::class => [
        SendWelcomeEmailListener::class,
        NotifyInventorySystemListener::class,
    ],
];
```

### Synchronous Listener

```php
final class NotifyInventorySystemListener
{
    public function handle(ProductCreated $event): void
    {
        // Called synchronously during the same request
        // Use only for fast, reliable operations
    }
}
```

### Queued Listener (Recommended for Side Effects)

```php
final class SendWelcomeEmailListener implements ShouldQueue
{
    public $afterCommit = true;   // Ensures job is dispatched after DB commit
    public $tries = 3;
    public $backoff = [30, 120];  // Seconds between retries

    public function handle(ProductCreated $event): void
    {
        Mail::to($event->ownerEmail)->send(new ProductCreatedMail($event));
    }

    public function failed(ProductCreated $event, \Throwable $exception): void
    {
        Log::error('SendWelcomeEmail failed', [
            'product_id' => (string) $event->productId,
            'error' => $exception->getMessage(),
        ]);
    }
}
```

The `$afterCommit = true` property ensures the queued job is not dispatched until **after** the
current database transaction commits. This prevents the queue from picking up a job that references
a record not yet committed.

---

## When to Use Domain Events vs. Direct Action

| Use Domain Events | Call Directly |
|-------------------|---------------|
| Side effects that can be async (email, notifications) | Immediate, synchronous sub-operations |
| Side effects that can retry independently | Operations that must be in the same transaction |
| Cross-aggregate updates | Within-aggregate updates |

---

## Event Data Guidelines

- Include the aggregate ID so listeners can query more detail if needed
- Include a timestamp (`occurredAt`) for audit purposes
- Include denormalized data that is needed for the side effect, to avoid extra queries in the listener
- Do **not** include mutable objects — only primitives and value objects
