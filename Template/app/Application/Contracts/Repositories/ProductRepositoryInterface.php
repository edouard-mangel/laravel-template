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
