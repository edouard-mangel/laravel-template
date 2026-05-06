# Testing

Pest PHP 3.x for all PHP tests. Architecture tests enforce layer boundaries. Factories provide
test data.

---

## Test Pyramid

```
                    ┌─────────┐
                    │   E2E   │  Playwright (Angular frontend)
                   ┌┴─────────┴┐
                   │Integration│  Cypress (Angular + API)
                  ┌┴───────────┴┐
                  │   Feature   │  Pest (HTTP layer, full stack)
                 ┌┴─────────────┴┐
                 │     Unit      │  Pest (Domain, fast)
                └───────────────┘
         + Architecture  (Pest arch() — runs with unit tests)
```

---

## Unit Tests (`tests/Unit/`)

Test domain value objects, aggregates, and specifications in isolation. No database, no container,
no HTTP. These should be instant.

```php
// tests/Unit/Product/ProductTest.php
describe('ProductPrice', function (): void {
    it('rejects negative values', function (): void {
        expect(fn () => ProductPrice::fromCents(-1))
            ->toThrow(InvalidInputException::class, 'cannot be negative');
    });

    it('converts cents to float', function (): void {
        expect(ProductPrice::fromCents(1050)->toFloat())->toBe(10.50);
    });
});

describe('Product', function (): void {
    it('records ProductCreated event on creation', function (): void {
        $product = Product::create(
            id: ProductId::generate(),
            name: new ProductName('Widget'),
            sku: new ProductSku('SKU-001'),
            price: ProductPrice::fromCents(1000),
            ownerId: 'owner-1',
        );

        $events = $product->releaseEvents();

        expect($events)->toHaveCount(1)
            ->and($events[0])->toBeInstanceOf(ProductCreated::class)
            ->and($events[0]->name)->toBe('Widget');
    });
});
```

---

## Feature Tests (`tests/Feature/`)

Test the full HTTP stack: request → controller → action → database → response. Uses `RefreshDatabase`
to roll back after each test.

```php
// tests/Feature/Product/CreateProductTest.php
uses(RefreshDatabase::class);

describe('POST /api/products', function (): void {
    it('creates a product and returns 201 with the new ID', function (): void {
        $user = UserEloquentModel::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/products', [
                'name' => 'Test Widget',
                'sku' => 'W-001',
                'price_in_cents' => 2500,
            ])
            ->assertStatus(201)
            ->assertJsonStructure(['id']);

        expect(ProductEloquentModel::count())->toBe(1);
    });

    it('returns 422 when name is missing', function (): void {
        $user = UserEloquentModel::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/products', ['sku' => 'W-001', 'price_in_cents' => 100])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    });

    it('returns 401 for unauthenticated requests', function (): void {
        $this->postJson('/api/products', ['name' => 'Test'])->assertStatus(401);
    });
});
```

---

## Architecture Tests (`tests/Architecture/`)

Pest's `arch()` API enforces layer boundaries at the type level.

```php
// tests/Architecture/DomainLayerTest.php
describe('Domain layer', function (): void {
    it('has no Illuminate imports', function (): void {
        arch('domain classes')
            ->expect('App\Domain')
            ->not->toUse('Illuminate');
    });

    it('has no Eloquent model references', function (): void {
        arch('domain classes')
            ->expect('App\Domain')
            ->not->toUse('Illuminate\Database\Eloquent\Model');
    });
});

// tests/Architecture/ApplicationLayerTest.php
describe('Application layer', function (): void {
    it('does not reference Eloquent models', function (): void {
        arch('application classes')
            ->expect('App\Application')
            ->not->toUse('App\Infrastructure\Persistence');
    });

    it('only depends on Domain and Application\Contracts', function (): void {
        arch('actions')
            ->expect('App\Application')
            ->not->toUse('App\Http');
    });
});

// tests/Architecture/NamingConventionTest.php
describe('Naming conventions', function (): void {
    it('repository implementations are in Infrastructure\Persistence', function (): void {
        arch()
            ->expect('App\Infrastructure\Persistence')
            ->classes()
            ->toHaveSuffix('Repository')
            ->when(fn ($class) => str_ends_with($class->getName(), 'Repository'));
    });
});
```

---

## Test Data: Factories

All test data is created through Laravel factories. Never manually construct Eloquent models in tests.

```php
// Persist to database
$product = ProductEloquentModel::factory()->create();

// Override specific fields
$product = ProductEloquentModel::factory()->create([
    'name' => 'Custom Name',
    'owner_id' => $user->id,
]);

// Unpersisted model (for unit tests on Eloquent-adjacent code)
$product = ProductEloquentModel::factory()->make();

// Multiple records
$products = ProductEloquentModel::factory()->count(5)->create();
```

### Factory State Methods

Use state methods for common scenarios:

```php
final class ProductFactory extends Factory
{
    public function expensive(): self
    {
        return $this->state(['price_in_cents' => 100000]);
    }

    public function ownedBy(User $user): self
    {
        return $this->state(['owner_id' => $user->id]);
    }
}

// In tests:
$product = ProductEloquentModel::factory()->expensive()->ownedBy($user)->create();
```

---

## DAMP Principle

Tests should be **Descriptive and Meaningful Phrases**, not DRY. Duplicate 3 lines rather than
hiding them in a shared base class that obscures intent.

```php
// BAD — requires reading createAuthenticatedProduct() to understand
it('returns 403 when not owner', function (): void {
    [$user, $product] = createAuthenticatedProduct();
    $other = UserEloquentModel::factory()->create();
    $this->actingAs($other)->putJson("/api/products/{$product->id}")->assertStatus(403);
});

// GOOD — all context in the test
it('returns 403 when not owner', function (): void {
    $owner = UserEloquentModel::factory()->create();
    $product = ProductEloquentModel::factory()->ownedBy($owner)->create();
    $other = UserEloquentModel::factory()->create();

    $this->actingAs($other)
        ->putJson("/api/products/{$product->id}", ['name' => 'Hijack'])
        ->assertStatus(403);
});
```

---

## Running Tests

```bash
php artisan test                            # All tests
php artisan test --filter=ProductTest       # Single class
php artisan test tests/Unit/                # Unit only
php artisan test tests/Feature/             # Feature only

./vendor/bin/pest --coverage               # With coverage
./vendor/bin/pest --parallel               # Parallel execution
./vendor/bin/pest --bail                   # Stop on first failure
```

---

## PHPUnit XML Configuration

```xml
<!-- phpunit.xml -->
<phpunit>
    <php>
        <env name="APP_ENV" value="testing"/>
        <env name="QUEUE_CONNECTION" value="sync"/>
        <env name="DB_CONNECTION" value="sqlite"/>
        <env name="DB_DATABASE" value=":memory:"/>
        <env name="CACHE_STORE" value="array"/>
        <env name="SESSION_DRIVER" value="array"/>
    </php>
    <testsuites>
        <testsuite name="Unit">
            <directory>tests/Unit</directory>
        </testsuite>
        <testsuite name="Feature">
            <directory>tests/Feature</directory>
        </testsuite>
        <testsuite name="Architecture">
            <directory>tests/Architecture</directory>
        </testsuite>
    </testsuites>
</phpunit>
```

The `QUEUE_CONNECTION=sync` setting means queued listeners run inline during tests, allowing
you to assert on their effects.
