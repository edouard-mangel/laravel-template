<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Product;

use App\Application\Contracts\Finders\ProductFinderInterface;
use App\Application\Product\ProductDto;
use App\Application\Product\ProductFilter;
use App\Domain\Product\ProductId;
use Illuminate\Pagination\LengthAwarePaginator;

final class ProductFinder implements ProductFinderInterface
{
    public function findById(ProductId $id): ?ProductDto
    {
        $model = ProductEloquentModel::find((string) $id);

        return $model !== null ? $this->toDto($model) : null;
    }

    public function findAll(ProductFilter $filter): LengthAwarePaginator
    {
        $query = ProductEloquentModel::query();

        if ($filter->name !== null) {
            $query->where('name', 'like', "%{$filter->name}%");
        }

        return $query
            ->orderBy('created_at', 'desc')
            ->paginate($filter->perPage, ['*'], 'page', $filter->page);
    }

    private function toDto(ProductEloquentModel $model): ProductDto
    {
        return new ProductDto(
            id: $model->id,
            name: $model->name,
            sku: $model->sku,
            priceInCents: $model->price_in_cents,
            ownerId: $model->owner_id,
            createdAt: $model->created_at->toDateTimeImmutable(),
        );
    }
}
