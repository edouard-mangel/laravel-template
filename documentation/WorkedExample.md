# Worked Example: Product Aggregate

This document walks through the complete `Product` vertical slice — every layer, every file. Keep
this open while building your first feature. The patterns shown here apply to every aggregate in
the system.

---

## What We're Building

A `Product` aggregate with:
- Create, update, delete operations
- List and get-by-id queries
- Domain events on create and update
- Ownership-based access control

---

## Domain Layer

### `app/Domain/Product/ProductId.php`

```php
<?php

declare(strict_types=1);

namespace App\Domain\Product;

use App\Domain\Shared\ValueObject;
use InvalidArgumentException;

final readonly class ProductId extends ValueObject
{
    public function __construct(public readonly string $value)
    {
        if (empty($this->value)) {
            throw new InvalidArgumentException('ProductId cannot be empty.');
        }
    }

    public static function generate(): self
    {
        return new self((string) \Illuminate\Support\Str::uuid());
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
```

### `app/Domain/Product/ProductName.php`

```php
<?php

declare(strict_types=1);

namespace App\Domain\Product;

use App\Domain\Shared\ValueObject;
use App\Domain\Shared\Exceptions\InvalidInputException;

final readonly class ProductName extends ValueObject
{
    public function __construct(public readonly string $value)
    {
        if (empty(trim($this->value))) {
            throw new InvalidInputException('Product name cannot be empty.');
        }

        if (mb_strlen($this->value) > 255) {
            throw new InvalidInputException('Product name cannot exceed 255 characters.');
        }
    }
}
```

### `app/Domain/Product/ProductPrice.php`

```php
<?php

declare(strict_types=1);

namespace App\Domain\Product;

use App\Domain\Shared\ValueObject;
use App\Domain\Shared\Exceptions\InvalidInputException;

final readonly class ProductPrice extends ValueObject
{
    public function __construct(public readonly int $valueInCents)
    {
        if ($this->valueInCents < 0) {
            throw new InvalidInputException('Product price cannot be negative.');
        }
    }

    public static function fromCents(int $cents): self
    {
        return new self($cents);
    }

    public function toFloat(): float
    {
        return $this->valueInCents / 100;
    }
}
```

### `app/Domain/Product/Events/ProductCreated.php`

```php
<?php

declare(strict_types=1);

namespace App\Domain\Product\Events;

use App\Domain\Shared\DomainEvent;
use App\Domain\Product\ProductId;

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

### `app/Domain/Product/Product.php`

```php
<?php

declare(strict_types=1);

namespace App\Domain\Product;

use App\Domain\Shared\AggregateRoot;
use App\Domain\Product\Events\ProductCreated;
use App\Domain\Product\Events\ProductUpdated;

final class Product extends AggregateRoot
{
    private function __construct(
        private readonly ProductId $id,
        private ProductName $name,
        private ProductSku $sku,
        private ProductPrice $price,
        private readonly string $ownerId,
    ) {}

    public static function create(
        ProductId $id,
        ProductName $name,
        ProductSku $sku,
        ProductPrice $price,
        string $ownerId,
    ): self {
        $product = new self($id, $name, $sku, $price, $ownerId);

        $product->recordEvent(new ProductCreated(
            productId: $id,
            name: $name->value,
            priceInCents: $price->valueInCents,
            occurredAt: new \DateTimeImmutable(),
        ));

        return $product;
    }

    public function update(ProductName $name, ProductPrice $price): void
    {
        $this->name = $name;
        $this->price = $price;

        $this->recordEvent(new ProductUpdated(
            productId: $this->id,
            name: $name->value,
            priceInCents: $price->valueInCents,
            occurredAt: new \DateTimeImmutable(),
        ));
    }

    public function id(): ProductId { return $this->id; }
    public function name(): ProductName { return $this->name; }
    public function sku(): ProductSku { return $this->sku; }
    public function price(): ProductPrice { return $this->price; }
    public function ownerId(): string { return $this->ownerId; }
}
```

---

## Application Layer

### `app/Application/Contracts/Repositories/ProductRepositoryInterface.php`

```php
<?php

declare(strict_types=1);

namespace App\Application\Contracts\Repositories;

use App\Domain\Product\Product;
use App\Domain\Product\ProductId;

interface ProductRepositoryInterface
{
    public function save(Product $product): void;
    public function findById(ProductId $id): ?Product;
    public function delete(ProductId $id): void;
}
```

### `app/Application/Product/Actions/CreateProductAction.php`

```php
<?php

declare(strict_types=1);

namespace App\Application\Product\Actions;

use App\Application\Contracts\Repositories\ProductRepositoryInterface;
use App\Application\Product\CreateProductData;
use App\Domain\Product\Product;
use App\Domain\Product\ProductId;
use App\Domain\Product\ProductName;
use App\Domain\Product\ProductPrice;
use App\Domain\Product\ProductSku;
use Illuminate\Support\Facades\DB;

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

            foreach ($product->releaseEvents() as $domainEvent) {
                event($domainEvent);
            }

            return $product->id();
        });
    }
}
```

### `app/Application/Product/Queries/GetProductByIdQuery.php`

```php
<?php

declare(strict_types=1);

namespace App\Application\Product\Queries;

use App\Application\Product\ProductDto;
use App\Domain\Product\ProductId;
use App\Infrastructure\Persistence\Product\ProductFinder;

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

### `app/Application/Product/ProductDto.php`

```php
<?php

declare(strict_types=1);

namespace App\Application\Product;

final readonly class ProductDto
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $sku,
        public readonly int $priceInCents,
        public readonly string $ownerId,
        public readonly \DateTimeImmutable $createdAt,
    ) {}
}
```

---

## Infrastructure Layer

### `app/Infrastructure/Persistence/Product/ProductEloquentModel.php`

```php
<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Product;

use Illuminate\Database\Eloquent\Model;
use App\Infrastructure\Persistence\Scopes\OwnerScope;

final class ProductEloquentModel extends Model
{
    protected $table = 'products';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id', 'name', 'sku', 'price_in_cents', 'owner_id',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new OwnerScope());
    }
}
```

### `app/Infrastructure/Persistence/Product/ProductRepository.php`

```php
<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Product;

use App\Application\Contracts\Repositories\ProductRepositoryInterface;
use App\Domain\Product\Product;
use App\Domain\Product\ProductId;
use App\Domain\Product\ProductName;
use App\Domain\Product\ProductPrice;
use App\Domain\Product\ProductSku;

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

        if ($model === null) {
            return null;
        }

        return $this->toDomain($model);
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

---

## HTTP Layer

### `app/Http/Controllers/ProductController.php`

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Product\Actions\CreateProductAction;
use App\Application\Product\Actions\UpdateProductAction;
use App\Application\Product\Actions\DeleteProductAction;
use App\Application\Product\Queries\GetProductByIdQuery;
use App\Application\Product\Queries\GetProductsQuery;
use App\Application\Product\CreateProductData;
use App\Domain\Product\ProductId;
use App\Http\Requests\CreateProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class ProductController extends Controller
{
    public function index(GetProductsQuery $query): AnonymousResourceCollection
    {
        $products = $query->handle();

        return ProductResource::collection($products);
    }

    public function show(string $id, GetProductByIdQuery $query): ProductResource|JsonResponse
    {
        $product = $query->handle(ProductId::fromString($id));

        if ($product === null) {
            return response()->json(['message' => 'Product not found.'], 404);
        }

        return new ProductResource($product);
    }

    public function store(CreateProductRequest $request, CreateProductAction $action): JsonResponse
    {
        $productId = $action->handle(CreateProductData::fromRequest($request));

        return response()->json(['id' => (string) $productId], 201);
    }

    public function update(string $id, UpdateProductRequest $request, UpdateProductAction $action): JsonResponse
    {
        $action->handle(ProductId::fromString($id), UpdateProductData::fromRequest($request));

        return response()->json(null, 204);
    }

    public function destroy(string $id, DeleteProductAction $action): JsonResponse
    {
        $action->handle(ProductId::fromString($id));

        return response()->json(null, 204);
    }
}
```

### `app/Http/Requests/CreateProductRequest.php`

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Ownership checked via policy or AccessContext
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['required', 'string', 'max:100'],
            'price_in_cents' => ['required', 'integer', 'min:0'],
        ];
    }
}
```

### `app/Http/Resources/ProductResource.php`

```php
<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Application\Product\ProductDto;
use Illuminate\Http\Resources\Json\JsonResource;

final class ProductResource extends JsonResource
{
    public function toArray($request): array
    {
        /** @var ProductDto $dto */
        $dto = $this->resource;

        return [
            'id' => $dto->id,
            'name' => $dto->name,
            'sku' => $dto->sku,
            'price_in_cents' => $dto->priceInCents,
            'created_at' => $dto->createdAt->format('c'),
        ];
    }
}
```

---

## Migration

### `database/migrations/2026_01_01_000001_create_products_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('sku')->unique();
            $table->unsignedInteger('price_in_cents');
            $table->string('owner_id');
            $table->timestamps();

            $table->index('owner_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
```

---

## Tests

### `tests/Unit/Product/ProductTest.php`

```php
<?php

use App\Domain\Product\Product;
use App\Domain\Product\ProductId;
use App\Domain\Product\ProductName;
use App\Domain\Product\ProductSku;
use App\Domain\Product\ProductPrice;
use App\Domain\Product\Events\ProductCreated;
use App\Domain\Shared\Exceptions\InvalidInputException;

describe('Product', function (): void {
    describe('create', function (): void {
        it('records a ProductCreated event', function (): void {
            $product = Product::create(
                id: ProductId::generate(),
                name: new ProductName('Test Product'),
                sku: new ProductSku('SKU-001'),
                price: ProductPrice::fromCents(1000),
                ownerId: 'owner-1',
            );

            $events = $product->releaseEvents();

            expect($events)->toHaveCount(1)
                ->and($events[0])->toBeInstanceOf(ProductCreated::class);
        });
    });

    describe('ProductName', function (): void {
        it('rejects empty names', function (): void {
            expect(fn () => new ProductName(''))->toThrow(InvalidInputException::class);
        });

        it('rejects names over 255 characters', function (): void {
            expect(fn () => new ProductName(str_repeat('a', 256)))->toThrow(InvalidInputException::class);
        });
    });

    describe('ProductPrice', function (): void {
        it('rejects negative prices', function (): void {
            expect(fn () => ProductPrice::fromCents(-1))->toThrow(InvalidInputException::class);
        });

        it('accepts zero price', function (): void {
            $price = ProductPrice::fromCents(0);
            expect($price->valueInCents)->toBe(0);
        });
    });
});
```

### `tests/Feature/Product/CreateProductTest.php`

```php
<?php

use App\Infrastructure\Persistence\Product\ProductEloquentModel;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('POST /api/products', function (): void {
    it('creates a product and returns 201', function (): void {
        $user = \App\Infrastructure\Persistence\User\UserEloquentModel::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/products', [
                'name' => 'Test Product',
                'sku' => 'SKU-001',
                'price_in_cents' => 1000,
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['id']);

        expect(ProductEloquentModel::count())->toBe(1);
    });

    it('rejects missing name with 422', function (): void {
        $user = \App\Infrastructure\Persistence\User\UserEloquentModel::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/products', [
                'sku' => 'SKU-001',
                'price_in_cents' => 1000,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    });
});
```

---

## File Checklist for a New Aggregate

When adding a new aggregate (e.g., `Order`), create these files:

### Domain
- [ ] `app/Domain/Order/OrderId.php`
- [ ] `app/Domain/Order/Order.php` (aggregate root)
- [ ] `app/Domain/Order/` (value objects for each attribute)
- [ ] `app/Domain/Order/Events/OrderCreated.php`
- [ ] `app/Domain/Order/Events/OrderUpdated.php`

### Application
- [ ] `app/Application/Contracts/Repositories/OrderRepositoryInterface.php`
- [ ] `app/Application/Order/OrderDto.php`
- [ ] `app/Application/Order/CreateOrderData.php`
- [ ] `app/Application/Order/Actions/CreateOrderAction.php`
- [ ] `app/Application/Order/Actions/UpdateOrderAction.php`
- [ ] `app/Application/Order/Actions/DeleteOrderAction.php`
- [ ] `app/Application/Order/Queries/GetOrderByIdQuery.php`
- [ ] `app/Application/Order/Queries/GetOrdersQuery.php`

### Infrastructure
- [ ] `app/Infrastructure/Persistence/Order/OrderEloquentModel.php`
- [ ] `app/Infrastructure/Persistence/Order/OrderRepository.php`
- [ ] `app/Infrastructure/Persistence/Order/OrderFinder.php`
- [ ] `database/migrations/YYYY_MM_DD_000001_create_orders_table.php`
- [ ] `database/factories/OrderFactory.php`

### HTTP
- [ ] `app/Http/Controllers/OrderController.php`
- [ ] `app/Http/Requests/CreateOrderRequest.php`
- [ ] `app/Http/Requests/UpdateOrderRequest.php`
- [ ] `app/Http/Resources/OrderResource.php`
- [ ] Route registration in `routes/api.php`

### Tests
- [ ] `tests/Unit/Order/OrderTest.php`
- [ ] `tests/Feature/Order/CreateOrderTest.php`
- [ ] `tests/Feature/Order/GetOrderTest.php`
