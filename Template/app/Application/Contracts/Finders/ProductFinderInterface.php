<?php

declare(strict_types=1);

namespace App\Application\Contracts\Finders;

use App\Application\Product\ProductDto;
use App\Application\Product\ProductFilter;
use App\Domain\Product\ProductId;
use Illuminate\Pagination\LengthAwarePaginator;

interface ProductFinderInterface
{
    public function findById(ProductId $id): ?ProductDto;

    public function findAll(ProductFilter $filter): LengthAwarePaginator;
}
