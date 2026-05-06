<?php

declare(strict_types=1);

namespace App\Application\Product\Queries;

use App\Application\Contracts\Finders\ProductFinderInterface;
use App\Application\Product\ProductFilter;
use Illuminate\Pagination\LengthAwarePaginator;

final class GetProductsQuery
{
    public function __construct(
        private readonly ProductFinderInterface $finder,
    ) {}

    public function handle(ProductFilter $filter): LengthAwarePaginator
    {
        return $this->finder->findAll($filter);
    }
}
