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
