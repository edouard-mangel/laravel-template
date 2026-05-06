<?php

declare(strict_types=1);

namespace App\Domain\Product\Events;

use App\Domain\Product\ProductId;
use App\Domain\Shared\DomainEvent;
use DateTimeImmutable;

final readonly class ProductUpdated implements DomainEvent
{
    public function __construct(
        public readonly ProductId $productId,
        public readonly string $name,
        public readonly int $priceInCents,
        public readonly DateTimeImmutable $occurredAt,
    ) {}
}
