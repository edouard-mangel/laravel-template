<?php

declare(strict_types=1);

namespace App\Application\Product\Queries;

use App\Application\Contracts\Finders\ProductFinderInterface;
use App\Application\Product\ProductDto;
use App\Domain\Product\ProductId;

final class GetProductByIdQuery
{
    public function __construct(
        private readonly ProductFinderInterface $finder,
    ) {}

    public function handle(ProductId $id): ?ProductDto
    {
        return $this->finder->findById($id);
    }
}
