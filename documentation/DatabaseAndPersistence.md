# Database and Persistence

Eloquent ORM with custom repositories behind interfaces. Eloquent models live only in the
Infrastructure layer.

---

## Two "Product" Classes

This template has two representations of the same concept:

| Class | Layer | Purpose |
|-------|-------|---------|
| `App\Domain\Product\Product` | Domain | Business logic, rules, events |
| `App\Infrastructure\Persistence\Product\ProductEloquentModel` | Infrastructure | Database mapping |

**Never** expose `ProductEloquentModel` outside of `app/Infrastructure/`. The domain aggregate
is the only thing Actions and Queries work with (aggregates via repositories, DTOs via finders).

---

## Repository Pattern

### Interface (Application Layer)

```php
namespace App\Application\Contracts\Repositories;

interface ProductRepositoryInterface
{
    public function save(Product $product): void;
    public function findById(ProductId $id): ?Product;
    public function delete(ProductId $id): void;
}
```

### Implementation (Infrastructure Layer)

```php
namespace App\Infrastructure\Persistence\Product;

final class ProductRepository implements ProductRepositoryInterface
{
    public function save(Product $product): void
    {
        ProductEloquentModel::updateOrCreate(
            ['id' => (string) $product->id()],
            [
                'name' => $product->name()->value,
                'sku' => $product->sku()->value,
                'price_in_cents' => $product->price()->valueInCents,
                'owner_id' => $product->ownerId(),
            ]
        );
    }

    public function findById(ProductId $id): ?Product
    {
        $model = ProductEloquentModel::find((string) $id);

        return $model ? $this->toDomain($model) : null;
    }

    public function delete(ProductId $id): void
    {
        ProductEloquentModel::destroy((string) $id);
    }

    private function toDomain(ProductEloquentModel $model): Product
    {
        return Product::reconstitute(
            id: ProductId::fromString($model->id),
            name: new ProductName($model->name),
            sku: new ProductSku($model->sku),
            price: ProductPrice::fromCents($model->price_in_cents),
            ownerId: $model->owner_id,
        );
    }
}
```

The `reconstitute()` static method on the aggregate exists to rebuild the object from persistence
without triggering domain events:

```php
public static function reconstitute(
    ProductId $id,
    ProductName $name,
    ProductSku $sku,
    ProductPrice $price,
    string $ownerId,
): self {
    return new self($id, $name, $sku, $price, $ownerId);
}
```

---

## Auto-Registration of Repositories

In `InfrastructureServiceProvider`, repositories are bound by convention:

```php
final class InfrastructureServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            ProductRepositoryInterface::class,
            ProductRepository::class,
        );

        // Add more repository bindings here as the domain grows
        $this->app->bind(
            CategoryRepositoryInterface::class,
            CategoryRepository::class,
        );
    }
}
```

---

## Eloquent Models in Infrastructure

Eloquent models live in `app/Infrastructure/Persistence/{Entity}/` — **not** in `app/Models/`.

### Configuration

```php
final class ProductEloquentModel extends Model
{
    protected $table = 'products';
    protected $keyType = 'string';
    public $incrementing = false;          // UUID primary key

    protected $fillable = [
        'id', 'name', 'sku', 'price_in_cents', 'owner_id',
    ];

    protected $casts = [
        'price_in_cents' => 'integer',
        'created_at' => 'immutable_datetime',
        'updated_at' => 'immutable_datetime',
    ];
}
```

### Global Scopes for Ownership

All models that are owned must apply the `OwnerScope` global scope:

```php
protected static function booted(): void
{
    static::addGlobalScope(new OwnerScope());
}
```

The `OwnerScope` reads from `AccessContext` (bound per-request) and adds a `WHERE owner_id = ?`
clause to every query. See [Permissions.md](Permissions.md) for details.

---

## Migrations

Migrations live in `database/migrations/`. Use UUID primary keys for all aggregates.

```php
Schema::create('products', function (Blueprint $table): void {
    $table->uuid('id')->primary();
    $table->string('name');
    $table->string('sku')->unique();
    $table->unsignedInteger('price_in_cents');
    $table->string('owner_id');
    $table->timestamps();

    $table->index('owner_id');
});
```

**Naming conventions for migration files:**

| Type | Pattern | Example |
|------|---------|---------|
| Create table | `YYYY_MM_DD_HHMMSS_create_{table}_table.php` | `2026_01_01_000001_create_products_table.php` |
| Add column | `YYYY_MM_DD_HHMMSS_add_{column}_to_{table}_table.php` | `2026_01_02_000001_add_description_to_products_table.php` |
| Drop table | `YYYY_MM_DD_HHMMSS_drop_{table}_table.php` | `2026_06_01_000001_drop_legacy_items_table.php` |

---

## Factories (for Testing)

Laravel factories produce `EloquentModel` instances — not domain aggregates.

```php
final class ProductFactory extends Factory
{
    protected $model = ProductEloquentModel::class;

    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid(),
            'name' => $this->faker->words(3, true),
            'sku' => strtoupper($this->faker->unique()->bothify('SKU-###')),
            'price_in_cents' => $this->faker->numberBetween(100, 100000),
            'owner_id' => (string) Str::uuid(),
        ];
    }
}
```

In tests, use the factory to seed data, then call the Query (or read directly) to verify:

```php
it('finds a product by id', function (): void {
    $model = ProductEloquentModel::factory()->create(['name' => 'Widget']);

    $result = (new GetProductByIdQuery(new ProductFinder()))
        ->handle(ProductId::fromString($model->id));

    expect($result)->not->toBeNull()
        ->and($result->name)->toBe('Widget');
});
```

---

## Soft Deletes

If an aggregate requires soft deletes, add `SoftDeletes` to the Eloquent model and a
`deleted_at` column to the migration. The global scope will still apply on top of soft delete scoping.

```php
use Illuminate\Database\Eloquent\SoftDeletes;

final class ProductEloquentModel extends Model
{
    use SoftDeletes;
    // ...
}
```

The domain aggregate does **not** know about soft deletes — that is a persistence concern.
