# Queue Pattern (Reliable Event Delivery)

This template uses Laravel Queues with Horizon for reliable, asynchronous delivery of side effects
triggered by domain events.

---

## Why Queues for Domain Events?

Side effects (emails, notifications, third-party API calls) should not run synchronously in the
request lifecycle because:
1. A failure in the side effect should not roll back the business operation
2. External service failures should be retried without user impact
3. Long-running operations (image processing, report generation) block the request

Laravel Queues provide at-least-once delivery with configurable retries and backoff.

---

## Architecture

```
HTTP Request
    → Action (DB::transaction)
        → Domain aggregate saves
        → event() dispatched after commit
            → Queued Listener (ShouldQueue, $afterCommit = true)
                → Redis Queue
                    → Horizon Worker picks up job
                        → Listener::handle() runs
                            → On failure: failed_jobs table
```

---

## Listener Configuration

```php
final class SendWelcomeEmailListener implements ShouldQueue
{
    public string $queue = 'notifications';  // Target specific queue
    public int $tries = 3;                   // Max retry attempts
    public array $backoff = [30, 120, 600];  // Seconds between retries
    public bool $afterCommit = true;         // Wait for DB commit before dispatching

    public function handle(ProductCreated $event): void
    {
        Mail::to($event->ownerEmail)->queue(new ProductCreatedMail($event));
    }

    public function failed(ProductCreated $event, \Throwable $exception): void
    {
        Log::error('SendWelcomeEmail permanently failed', [
            'product_id' => (string) $event->productId,
            'error' => $exception->getMessage(),
        ]);
    }
}
```

---

## Horizon Configuration

Horizon manages queue workers and provides a dashboard at `/horizon`.

```php
// config/horizon.php
'environments' => [
    'production' => [
        'supervisor-default' => [
            'connection' => 'redis',
            'queue' => ['default', 'notifications', 'reports'],
            'balance' => 'auto',
            'minProcesses' => 1,
            'maxProcesses' => 10,
            'tries' => 3,
        ],
    ],
    'local' => [
        'supervisor-default' => [
            'connection' => 'redis',
            'queue' => ['default', 'notifications'],
            'balance' => 'simple',
            'processes' => 3,
            'tries' => 3,
        ],
    ],
],
```

Access Horizon dashboard: `http://localhost:8000/horizon`

Protect Horizon in production by restricting access in `HorizonServiceProvider`:

```php
protected function gate(): void
{
    Gate::define('viewHorizon', function (User $user): bool {
        return $user->hasRole('admin');
    });
}
```

---

## Queue vs. Outbox Pattern

The dotnet template uses a Transactional Outbox because .NET lacks a built-in equivalent of
Laravel Queues with `$afterCommit`. In Laravel:

| Concern | Laravel Solution |
|---------|-----------------|
| Atomicity (event + data in same transaction) | `$afterCommit = true` on the listener |
| Retry on failure | Built-in `$tries` + `$backoff` |
| Visibility into failures | `failed_jobs` table |
| Replay failed jobs | `php artisan queue:retry {id}` |
| Real-time monitoring | Horizon dashboard |

The `$afterCommit = true` flag makes Laravel Queues safe for domain event delivery without the
complexity of a custom outbox implementation.

---

## Queue Connections

```env
QUEUE_CONNECTION=redis     # Production
QUEUE_CONNECTION=database  # Acceptable alternative without Redis
QUEUE_CONNECTION=sync      # Tests only (runs listener synchronously)
```

Set `QUEUE_CONNECTION=sync` in `phpunit.xml` for tests so listeners run inline and you can
assert on their effects.

---

## Handling Failed Jobs

Failed jobs land in the `failed_jobs` table after exhausting all retries.

```bash
php artisan queue:failed          # List all failed jobs
php artisan queue:retry {id}      # Retry a specific job
php artisan queue:retry all       # Retry all failed jobs
php artisan queue:flush           # Delete all failed jobs
```

The `failed()` method on the listener is called for each permanent failure. Use it for alerting:

```php
public function failed(ProductCreated $event, \Throwable $exception): void
{
    // Send alert, log to monitoring, trigger incident
    Log::critical('Critical queue failure', [
        'listener' => static::class,
        'event' => $event::class,
        'error' => $exception->getMessage(),
    ]);
}
```

---

## Idempotency

Queued listeners may run more than once (at-least-once delivery). Write listeners to be idempotent:

```php
public function handle(ProductCreated $event): void
{
    // Check if we've already processed this event
    if (EmailLog::where('product_id', (string) $event->productId)->exists()) {
        return; // Already handled
    }

    Mail::to($event->ownerEmail)->send(new ProductCreatedMail($event));

    EmailLog::create(['product_id' => (string) $event->productId]);
}
```

---

## Dedicated Queues by Priority

Route listeners to specific queues:

```php
public string $queue = 'critical';    // Highest priority, most workers
public string $queue = 'default';     // Standard operations
public string $queue = 'reports';     // Long-running background tasks
public string $queue = 'notifications'; // Email/SMS/push
```

Configure Horizon to allocate more workers to higher-priority queues.
