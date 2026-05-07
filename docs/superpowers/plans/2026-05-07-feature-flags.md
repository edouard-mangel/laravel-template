# Feature Flags Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add `GET /api/features` backed by Laravel Pennant — returns a JSON map of all defined feature flags and their boolean state for the authenticated user.

**Architecture:** Laravel Pennant stores flag state per-user in a `features` DB table (skipped in tests via `PENNANT_STORE=array`). Two example flags are defined in `AppServiceProvider::boot()`. A thin `FeatureController` calls `Feature::all()` and returns the result as JSON — no application or domain layer needed for this feature.

**Tech Stack:** `laravel/pennant`, `Illuminate\Support\Lottery`

---

## Working directory

All paths are relative to `Template/`. All git operations from the repo root:
`C:\Users\edoua\source\repos\laravel-template`

## Docker commands

**Composer install:**
```powershell
docker run --rm -v "C:\Users\edoua\source\repos\laravel-template\Template:/app" -w /app composer:2 composer require laravel/pennant --no-interaction --ignore-platform-req=ext-pcntl --ignore-platform-req=php
```

**Test runner:**
```powershell
docker run --rm -v "C:\Users\edoua\source\repos\laravel-template\Template:/app" -w /app -e APP_KEY="base64:kzPY7a4SgaTU99WPLKwNQVBXjkDEi92Ot5I3+wXuLDk=" -e DB_CONNECTION=sqlite -e "DB_DATABASE=:memory:" -e CACHE_STORE=array -e QUEUE_CONNECTION=sync -e SESSION_DRIVER=array -e TELESCOPE_ENABLED=false laravel-test:local php -d memory_limit=512M -d display_errors=stderr vendor/bin/pest --no-coverage
```

**Artisan (for vendor:publish):**
```powershell
docker run --rm -v "C:\Users\edoua\source\repos\laravel-template\Template:/app" -w /app -e APP_KEY="base64:kzPY7a4SgaTU99WPLKwNQVBXjkDEi92Ot5I3+wXuLDk=" -e DB_CONNECTION=sqlite -e "DB_DATABASE=:memory:" -e CACHE_STORE=array -e QUEUE_CONNECTION=sync -e SESSION_DRIVER=array -e TELESCOPE_ENABLED=false laravel-test:local php artisan vendor:publish --tag=pennant-migrations --ansi
```

**Patch platform check** (run after every `composer` operation):
```bash
echo '<?php // bypassed' > "C:/Users/edoua/source/repos/laravel-template/Template/vendor/composer/platform_check.php"
```

---

## Files

| Action | Path |
|--------|------|
| Modify | `Template/composer.json` — add `laravel/pennant` |
| Create | `Template/database/migrations/*_create_features_table.php` — published by Pennant |
| Modify | `Template/phpunit.xml` — add `PENNANT_STORE=array` |
| Modify | `Template/app/Providers/AppServiceProvider.php` — add flag definitions in `boot()` |
| Create | `Template/app/Http/Controllers/FeatureController.php` |
| Modify | `Template/routes/api.php` — add `GET /api/features` |
| Create | `Template/tests/Feature/FeatureTest.php` |

---

## Task 1: Install Pennant and configure the test environment

**Files:**
- Modify: `Template/composer.json`
- Create: `Template/database/migrations/*_create_features_table.php` (via artisan)
- Modify: `Template/phpunit.xml`

- [ ] **Step 1: Install the package**

Run the composer command from the Docker section above. Expected output ends with:
```
  - Installing laravel/pennant (v1.x.x): ...
  ...
  24 packages you are using are looking for funding.
```

- [ ] **Step 2: Patch platform_check.php**

```bash
echo '<?php // bypassed' > "C:/Users/edoua/source/repos/laravel-template/Template/vendor/composer/platform_check.php"
```

- [ ] **Step 3: Publish the Pennant migration**

Run the artisan vendor:publish command from the Docker section above.
Expected output: `INFO  Publishing [pennant-migrations] assets.`

A new file will appear in `Template/database/migrations/` named something like
`0001_01_01_000002_create_feature_flags_table.php`.

- [ ] **Step 4: Add `PENNANT_STORE=array` to `phpunit.xml`**

In `Template/phpunit.xml`, add this line inside the `<php>` block after the existing env entries:

```xml
<env name="PENNANT_STORE" value="array"/>
```

The full `<php>` block must be:

```xml
    <php>
        <env name="APP_ENV" value="testing"/>
        <env name="APP_KEY" value="base64:TestKeyForTestingOnlyDoNotUse="/>
        <env name="BCRYPT_ROUNDS" value="4"/>
        <env name="CACHE_STORE" value="array"/>
        <env name="DB_CONNECTION" value="sqlite"/>
        <env name="DB_DATABASE" value=":memory:"/>
        <env name="MAIL_MAILER" value="array"/>
        <env name="QUEUE_CONNECTION" value="sync"/>
        <env name="SESSION_DRIVER" value="array"/>
        <env name="TELESCOPE_ENABLED" value="false"/>
        <env name="PENNANT_STORE" value="array"/>
    </php>
```

- [ ] **Step 5: Run existing tests — all 24 must still pass**

Run the test runner command from the Docker section above.
Expected: `Tests: 24 passed`

If Pennant causes a boot error, verify `vendor/composer/platform_check.php` was patched
in Step 2.

- [ ] **Step 6: Commit**

```bash
cd C:\Users\edoua\source\repos\laravel-template
git add Template/composer.json Template/composer.lock Template/database/migrations Template/phpunit.xml
git commit -m "feat: install laravel/pennant, publish migration, configure test env"
```

---

## Task 2: FeatureController, route, flag definitions (TDD)

**Files:**
- Create: `Template/tests/Feature/FeatureTest.php`
- Create: `Template/app/Http/Controllers/FeatureController.php`
- Modify: `Template/app/Providers/AppServiceProvider.php`
- Modify: `Template/routes/api.php`

- [ ] **Step 1: Write the failing test**

Create `Template/tests/Feature/FeatureTest.php`:

```php
<?php

use App\Infrastructure\Persistence\User\UserEloquentModel;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns all feature flags for an authenticated user', function (): void {
    $user = UserEloquentModel::factory()->create();

    $this->actingAs($user)
        ->getJson('/api/features')
        ->assertStatus(200)
        ->assertJsonStructure(['new-dashboard', 'beta-export'])
        ->assertJsonFragment(['new-dashboard' => true]);
});

it('returns 401 for unauthenticated requests', function (): void {
    $this->getJson('/api/features')->assertStatus(401);
});
```

- [ ] **Step 2: Run the test — confirm FAIL**

Run the test runner. Expected: 2 failures:
- "404 was not 200" (route not defined yet)
- Or similar routing error

- [ ] **Step 3: Create `Template/app/Http/Controllers/FeatureController.php`**

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Laravel\Pennant\Feature;

final class FeatureController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Feature::all());
    }
}
```

- [ ] **Step 4: Add the route to `Template/routes/api.php`**

The file currently contains:
```php
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function (): void {
    Route::apiResource('programs', ProgramController::class);
});
```

Replace with:
```php
<?php

declare(strict_types=1);

use App\Http\Controllers\AuthController;
use App\Http\Controllers\FeatureController;
use App\Http\Controllers\ProgramController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/features', [FeatureController::class, 'index']);
    Route::apiResource('programs', ProgramController::class);
});
```

- [ ] **Step 5: Define the flags in `Template/app/Providers/AppServiceProvider.php`**

Replace the entire file with:

```php
<?php

declare(strict_types=1);

namespace App\Providers;

use App\Http\Services\AccessContext;
use App\Infrastructure\Persistence\Providers\InfrastructureServiceProvider;
use App\Infrastructure\Persistence\User\UserEloquentModel;
use Illuminate\Support\Lottery;
use Illuminate\Support\ServiceProvider;
use Laravel\Pennant\Feature;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AccessContext::class);
        $this->app->register(InfrastructureServiceProvider::class);
    }

    public function boot(): void
    {
        Feature::define('new-dashboard', fn (UserEloquentModel $user): bool =>
            $user->created_at->isAfter(now()->subDays(30))
        );

        Feature::define('beta-export', Lottery::odds(1, 5));
    }
}
```

**Note on `beta-export`:** The spec listed `Feature::lottery(0.20)` but that method does not exist in Pennant. The correct API is `Lottery::odds(1, 5)` from `Illuminate\Support\Lottery` — passes `true` to 1 in 5 users (20%). Pennant accepts a `Lottery` instance directly as a resolver.

**Note on the namespace import:** The current `AppServiceProvider` imports `App\Infrastructure\Providers\InfrastructureServiceProvider`. Verify the actual namespace by reading `Template/app/Infrastructure/Providers/InfrastructureServiceProvider.php` if the test fails with a class-not-found error — adjust the use statement accordingly.

- [ ] **Step 6: Run ALL tests — 26 must pass**

Run the full test runner command.

Expected:
```
PASS  Tests\Unit\Program\ProgramTest          (13 tests)
PASS  Tests\Feature\Program\CreateProgramTest  (5 tests)
PASS  Tests\Feature\FeatureTest                (2 tests)
PASS  Tests\Architecture\ApplicationLayerTest  (2 tests)
PASS  Tests\Architecture\DomainLayerTest       (2 tests)
PASS  Tests\Architecture\NamingConventionTest  (2 tests)

Tests: 26 passed
```

If `FeatureTest` fails with "new-dashboard not found in response":
- Pennant's `Feature::all()` only returns flags that are **defined**. Verify `AppServiceProvider::boot()` has both `Feature::define()` calls and the service provider is registered.
- Check `bootstrap/providers.php` contains `App\Providers\AppServiceProvider::class`.

If `FeatureTest` fails with "true is not false" for `new-dashboard`:
- A factory-created user has `created_at = now()` which IS within 30 days → should be `true`. Verify the Pennant store is `array` (not `database`) — the `phpunit.xml` env var controls this.

- [ ] **Step 7: Commit and push**

```bash
cd C:\Users\edoua\source\repos\laravel-template
git add Template/app Template/routes Template/tests/Feature/FeatureTest.php
git commit -m "feat: GET /api/features — Pennant feature flags per authenticated user"
git push
```

---

## Self-Review

| Spec requirement | Task |
|---|---|
| Install `laravel/pennant` | Task 1 Step 1 |
| Publish `features` table migration | Task 1 Step 3 |
| `PENNANT_STORE=array` in test env | Task 1 Step 4 |
| `Feature::define('new-dashboard', ...)` — 30-day user rule | Task 2 Step 5 |
| `Feature::define('beta-export', ...)` — 20% lottery | Task 2 Step 5 |
| `FeatureController::index()` calls `Feature::all()` | Task 2 Step 3 |
| `GET /api/features` inside `auth:sanctum` group | Task 2 Step 4 |
| Test: 200 + correct structure + new-dashboard=true for fresh user | Task 2 Step 1 |
| Test: 401 for unauthenticated | Task 2 Step 1 |
| Angular sections | **Skipped** — `client/` removed from template |
