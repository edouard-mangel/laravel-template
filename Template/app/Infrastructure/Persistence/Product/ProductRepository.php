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
        ProductEloquentModel::withoutGlobalScopes()->updateOrCreate(
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
        $model = ProductEloquentModel::withoutGlobalScopes()->find((string) $id);

        return $model !== null ? $this->toDomain($model) : null;
    }

    public function delete(ProductId $id): void
    {
        ProductEloquentModel::withoutGlobalScopes()->destroy((string) $id);
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
