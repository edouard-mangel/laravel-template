# Observability

Structured logging, request correlation, and monitoring with Laravel Telescope.

---

## Correlation IDs

Every request gets a unique `X-Correlation-Id` header that is propagated through logs. Implemented
in `CorrelationIdMiddleware`:

```php
final class CorrelationIdMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $correlationId = $request->header('X-Correlation-Id') ?? (string) Str::uuid();

        $request->headers->set('X-Correlation-Id', $correlationId);

        // Add to all log context for this request
        Log::shareContext(['correlation_id' => $correlationId]);

        $response = $next($request);
        $response->headers->set('X-Correlation-Id', $correlationId);

        return $response;
    }
}
```

Register early in the middleware stack in `bootstrap/app.php`.

---

## Structured Logging

Always log key-value data, not interpolated strings:

```php
// BAD
Log::info("User {$userId} created product {$productId}");

// GOOD
Log::info('Product created', [
    'product_id' => $productId,
    'owner_id' => $userId,
    'sku' => $sku,
]);
```

Log levels and when to use them:

| Level | Use |
|-------|-----|
| `debug` | Detailed flow information (dev only) |
| `info` | Significant events (product created, user authenticated) |
| `notice` | Normal but significant conditions |
| `warning` | Non-critical issues (deprecated usage, fallback triggered) |
| `error` | Errors that require attention but don't stop the app |
| `critical` | Critical failures (queue permanently failed, payment processing error) |
| `emergency` | System unusable |

---

## Laravel Telescope

Telescope is the Laravel development debugger. It records requests, queries, jobs, emails, and more.

```bash
php artisan telescope:install
php artisan migrate
```

Access at: `http://localhost:8000/telescope`

Restrict in production to admin users only in `TelescopeServiceProvider`:

```php
protected function gate(): void
{
    Gate::define('viewTelescope', function (User $user): bool {
        return $user->hasRole('admin');
    });
}
```

Disable Telescope in tests (`.env.testing`):

```env
TELESCOPE_ENABLED=false
```

---

## Horizon Dashboard

Horizon provides real-time queue metrics. Access at `http://localhost:8000/horizon`.

Key metrics to watch:
- **Throughput** — jobs processed per minute
- **Runtime** — average job execution time
- **Wait time** — time from dispatch to processing
- **Failed jobs** — should be zero; alert if non-zero

---

## Health Checks

Register a health check endpoint for load balancer and uptime monitoring:

```php
// routes/api.php (no auth middleware)
Route::get('/health', function (): JsonResponse {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toIso8601String(),
        'database' => DB::connection()->getPdo() ? 'ok' : 'error',
    ]);
});
```

For deeper health checks, use `spatie/laravel-health`:

```php
Health::checks([
    DatabaseCheck::new(),
    RedisCheck::new(),
    QueueCheck::new(),
    HorizonCheck::new(),
]);
```

---

## Query Logging in Development

Log all SQL queries in local environment to catch N+1 problems:

```php
// AppServiceProvider::boot()
if (app()->environment('local')) {
    DB::listen(function (QueryExecuted $query): void {
        if ($query->time > 100) {
            Log::warning('Slow query', [
                'sql' => $query->sql,
                'bindings' => $query->bindings,
                'time_ms' => $query->time,
            ]);
        }
    });
}
```

---

## Error Tracking (Production)

Integrate Sentry or Bugsnag for production error tracking:

```php
// config/logging.php
'channels' => [
    'sentry' => [
        'driver' => 'sentry',
        'level' => 'error',
    ],
    'stack' => [
        'driver' => 'stack',
        'channels' => ['single', 'sentry'],
    ],
],
```

Set `LOG_CHANNEL=stack` in production to send errors to both the log file and Sentry.
