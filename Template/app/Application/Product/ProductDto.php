<?php

declare(strict_types=1);

namespace App\Application\Product;

use DateTimeImmutable;

final readonly class ProductDto
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $sku,
        public readonly int $priceInCents,
        public readonly string $ownerId,
        public readonly DateTimeImmutable $createdAt,
    ) {}
}
