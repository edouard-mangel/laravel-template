<?php

declare(strict_types=1);

namespace App\Application\Product\Actions;

use App\Application\Contracts\Repositories\ProductRepositoryInterface;
use App\Application\Product\UpdateProductData;
use App\Domain\Product\ProductId;
use App\Domain\Product\ProductName;
use App\Domain\Product\ProductPrice;
use App\Domain\Shared\Exceptions\ResourceNotFoundException;
use Illuminate\Support\Facades\DB;

final class UpdateProductAction
{
    public function __construct(
        private readonly ProductRepositoryInterface $productRepository,
    ) {}

    public function handle(ProductId $id, UpdateProductData $data): void
    {
        DB::transaction(function () use ($id, $data): void {
            $product = $this->productRepository->findById($id);

            if ($product === null) {
                throw new ResourceNotFoundException("Product {$id} not found.");
            }

            $product->update(
                name: new ProductName($data->name),
                price: ProductPrice::fromCents($data->priceInCents),
            );

            $this->productRepository->save($product);

            foreach ($product->releaseEvents() as $domainEvent) {
                event($domainEvent);
            }
        });
    }
}
