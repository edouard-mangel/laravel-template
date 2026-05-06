# Feature Flags Design

**Date:** 2026-05-07
**Status:** Approved

---

## Context

The `Template/` scaffold has no feature flag infrastructure. This spec adds a self-hosted
feature flagging capability using Laravel Pennant (backend) and an Angular service (frontend).
The design follows the existing Clean Architecture patterns: thin controller, no business logic
outside domain/application layers, dependency via interfaces.

---

## Package

**`laravel/pennant`** — Laravel's official feature flag package. Compatible with Laravel 12.
Stores flag states in a `features` DB table (one row per user+flag combination). Evaluates
flags per-user at request time.

Install: `composer require laravel/pennant`
Publish migration: `php artisan vendor:publish --tag=pennant-migrations`

---

## Flag Definitions

Flags are defined in `AppServiceProvider::boot()` using `Feature::define()`. Two example
flags ship with the scaffold — delete and replace with real flags when building your domain.

```php
use Laravel\Pennant\Feature;

Feature::define('new-dashboard', fn (UserEloquentModel $user): bool =>
    $user->created_at->isAfter(now()->subDays(30))
);

Feature::define('beta-export', fn (UserEloquentModel $user): bool =>
    Feature::lottery(0.20) // 20% of users
);
```

**Naming convention:** kebab-case strings (e.g. `new-dashboard`, `beta-export`). Use the same
names in Angular.

---

## Backend — New Files

### `app/Http/Controllers/FeatureController.php`

```php
final class FeatureController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Feature::all());
    }
}
```

`Feature::all()` returns every defined flag and its boolean state for the authenticated user.
No manual flag-by-flag checking needed.

### `config/pennant.php`

Published via `php artisan vendor:publish --tag=pennant-config`. Default driver: `database`.
In testing (`APP_ENV=testing`), override to `array` driver so tests don't need migrations.

---

## Backend — Modified Files

### `routes/api.php`

Add inside the `auth:sanctum` group:

```php
Route::get('/features', [FeatureController::class, 'index']);
```

### `app/Providers/AppServiceProvider.php`

Add flag definitions in `boot()`:

```php
public function boot(): void
{
    Feature::define('new-dashboard', fn (UserEloquentModel $user): bool =>
        $user->created_at->isAfter(now()->subDays(30))
    );

    Feature::define('beta-export', fn (UserEloquentModel $user): bool =>
        Feature::lottery(0.20)
    );
}
```

### `database/migrations/` (published)

Run `php artisan vendor:publish --tag=pennant-migrations` to publish the `features` table
migration. This creates `features(id, scope_type, scope_id, name, value, created_at, updated_at)`.

---

## API Contract

```
GET /api/features
Authorization: Bearer <token>

Response 200:
{
  "new-dashboard": true,
  "beta-export": false
}

Response 401: (unauthenticated)
{ "message": "Unauthenticated." }
```

---

## Backend Tests

### `tests/Feature/FeatureTest.php`

```php
uses(RefreshDatabase::class);

it('returns all feature flags for authenticated user', function (): void {
    $user = UserEloquentModel::factory()->create();

    $this->actingAs($user)
        ->getJson('/api/features')
        ->assertStatus(200)
        ->assertJsonStructure(['new-dashboard', 'beta-export'])
        ->assertJsonFragment(['new-dashboard' => true]); // new user, created within last 30 days
});

it('returns 401 for unauthenticated requests', function (): void {
    $this->getJson('/api/features')->assertStatus(401);
});
```

**Testing note:** Pennant defaults to the `database` driver, but tests set `APP_ENV=testing`
and should configure Pennant to use the `array` (in-memory) driver to avoid needing migrations.
Add to `phpunit.xml`:
```xml
<env name="PENNANT_STORE" value="array"/>
```

---

## Angular Frontend — New Files

### `client/src/app/core/feature-flag.service.ts`

```typescript
import { Injectable, signal } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { tap, map, catchError } from 'rxjs/operators';
import { of } from 'rxjs';

@Injectable({ providedIn: 'root' })
export class FeatureFlagService {
  private readonly flags = signal<Record<string, boolean>>({});

  constructor(private http: HttpClient) {}

  load(): Observable<void> {
    return this.http.get<Record<string, boolean>>('/api/features').pipe(
      tap(flags => this.flags.set(flags)),
      map(() => void 0),
      catchError(() => {
        this.flags.set({});
        return of(void 0);
      })
    );
  }

  isEnabled(flag: string): boolean {
    return this.flags()[flag] ?? false;
  }
}
```

### `client/src/app/core/feature-flag.initializer.ts`

```typescript
import { inject } from '@angular/core';
import { Observable } from 'rxjs';
import { FeatureFlagService } from './feature-flag.service';

export function featureFlagInitializer(): () => Observable<void> {
  const service = inject(FeatureFlagService);
  return () => service.load();
}
```

Wire up in `app.config.ts` (or equivalent bootstrap):
```typescript
provideAppInitializer(featureFlagInitializer())
```

---

## Angular Tests

### `client/src/app/core/feature-flag.service.spec.ts`

```typescript
describe('FeatureFlagService', () => {
  it('returns true for an enabled flag', () => {
    const service = TestBed.inject(FeatureFlagService);
    // Simulate load response
    service['flags'].set({ 'new-dashboard': true, 'beta-export': false });

    expect(service.isEnabled('new-dashboard')).toBe(true);
    expect(service.isEnabled('beta-export')).toBe(false);
  });

  it('returns false for unknown flags', () => {
    const service = TestBed.inject(FeatureFlagService);
    service['flags'].set({});

    expect(service.isEnabled('unknown-flag')).toBe(false);
  });

  it('defaults all flags to false when load fails', async () => {
    // Mock HTTP failure
    const http = TestBed.inject(HttpClient);
    jest.spyOn(http, 'get').mockReturnValue(throwError(() => new Error('Network error')));
    const service = TestBed.inject(FeatureFlagService);

    await firstValueFrom(service.load());
    expect(service.isEnabled('new-dashboard')).toBe(false);
  });
});
```

---

## Out of Scope

- Admin UI for toggling flags
- Percentage rollout beyond Pennant's `lottery()` helper
- Flag variants / multivariate flags
- Audit log of flag changes
- Angular SSR considerations
