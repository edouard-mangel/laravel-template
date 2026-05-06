<?php

declare(strict_types=1);

namespace App\Application\Product;

use App\Http\Requests\CreateProductRequest;

final readonly class CreateProductData
{
    public function __construct(
        public readonly string $name,
        public readonly string $sku,
        public readonly int $priceInCents,
        public readonly string $ownerId,
    ) {}

    public static function fromRequest(CreateProductRequest $request): self
    {
        return new self(
            name: $request->validated('name'),
            sku: $request->validated('sku'),
            priceInCents: $request->validated('price_in_cents'),
            ownerId: $request->user()->id,
        );
    }
}
