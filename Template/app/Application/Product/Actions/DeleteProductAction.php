<?php

declare(strict_types=1);

namespace App\Application\Product\Actions;

use App\Application\Contracts\Repositories\ProductRepositoryInterface;
use App\Domain\Product\ProductId;
use App\Domain\Shared\Exceptions\ResourceNotFoundException;
use Illuminate\Support\Facades\DB;

final class DeleteProductAction
{
    public function __construct(
        private readonly ProductRepositoryInterface $productRepository,
    ) {}

    public function handle(ProductId $id): void
    {
        DB::transaction(function () use ($id): void {
            $product = $this->productRepository->findById($id);

            if ($product === null) {
                throw new ResourceNotFoundException("Product {$id} not found.");
            }

            $this->productRepository->delete($id);
        });
    }
}
