# Configuration

Laravel configuration via `.env` files and `config/` PHP files.

---

## Environment File

All environment-specific values live in `.env`. Never hardcode values in config files.

```env
APP_NAME="My Laravel App"
APP_ENV=local                      # local | staging | production
APP_KEY=                           # php artisan key:generate
APP_DEBUG=true                     # false in production
APP_URL=http://localhost:8000

# Database
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=myapp
DB_USERNAME=myapp
DB_PASSWORD=secret

# Queue
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379

# Cache
CACHE_STORE=redis

# Sanctum
SANCTUM_STATEFUL_DOMAINS=localhost:3000  # API consumer dev URL
SESSION_DOMAIN=localhost
SESSION_SECURE_COOKIE=false             # true in production (HTTPS)

# Mail
MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025

# Logging
LOG_CHANNEL=stack
LOG_LEVEL=debug                    # error in production
```

---

## Options Pattern (Typed Config)

For complex configuration, create typed config classes rather than reading from `config()` directly.

```php
// app/Configuration/DatabaseOptions.php
final readonly class DatabaseOptions
{
    public function __construct(
        public readonly string $connection,
        public readonly string $host,
        public readonly int $port,
        public readonly string $database,
    ) {}

    public static function fromConfig(): self
    {
        return new self(
            connection: config('database.default'),
            host: config('database.connections.pgsql.host'),
            port: (int) config('database.connections.pgsql.port'),
            database: config('database.connections.pgsql.database'),
        );
    }
}
```

Bind in a service provider:

```php
$this->app->singleton(DatabaseOptions::class, fn () => DatabaseOptions::fromConfig());
```

---

## Key Config Files

| File | Purpose |
|------|---------|
| `config/app.php` | Application name, timezone, locale, providers |
| `config/auth.php` | Guard and provider definitions |
| `config/database.php` | Database connections |
| `config/queue.php` | Queue connections and defaults |
| `config/sanctum.php` | Stateful domains, expiration |
| `config/cors.php` | CORS allowed origins for API consumers |
| `config/horizon.php` | Horizon queue workers and balancing |
| `config/logging.php` | Log channels and levels |

---

## CORS for API Consumers

Configure allowed origins in `config/cors.php`:

```php
return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['*'],
    'allowed_origins' => [
        env('FRONTEND_URL', 'http://localhost:3000'),
        env('APP_URL'),              // Production URL
    ],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,  // Required for Sanctum cookies
];
```

---

## Sanctum (SPA Authentication)

Sanctum handles both token-based authentication (Bearer tokens) and cookie-based SPA authentication.

```php
// config/sanctum.php
return [
    'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', '')),
    'expiration' => null,  // null = session lifetime
    'middleware' => [
        'encrypt_cookies' => App\Http\Middleware\EncryptCookies::class,
        'validate_csrf_token' => App\Http\Middleware\VerifyCsrfToken::class,
    ],
];
```

The Angular app must:
1. Call `GET /sanctum/csrf-cookie` before any state-changing request
2. Include the `X-XSRF-TOKEN` header (automatic with Angular's `HttpClient`)
3. Include `withCredentials: true` in HTTP requests

---

## Logging

Use Laravel's structured logging with channels:

```php
// config/logging.php
'channels' => [
    'stack' => [
        'driver' => 'stack',
        'channels' => ['single', 'stderr'],
    ],
    'single' => [
        'driver' => 'single',
        'path' => storage_path('logs/laravel.log'),
        'level' => env('LOG_LEVEL', 'debug'),
    ],
],
```

Log structured data, not strings:

```php
// WRONG
Log::info("Product created: $productId");

// CORRECT
Log::info('Product created', [
    'product_id' => (string) $productId,
    'owner_id' => $data->ownerId,
]);
```

---

## Rate Limiting

Configure rate limits in `RouteServiceProvider` or using middleware:

```php
// routes/api.php
Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
    // ...
});

// config/app.php → RateLimiter configuration
// In AppServiceProvider::boot():
RateLimiter::for('api', function (Request $request) {
    return $request->user()
        ? Limit::perMinute(60)->by($request->user()->id)
        : Limit::perMinute(10)->by($request->ip());
});
```
