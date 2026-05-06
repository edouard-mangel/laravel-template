<?php

declare(strict_types=1);

namespace App\Application\Product;

use App\Http\Requests\UpdateProductRequest;

final readonly class UpdateProductData
{
    public function __construct(
        public readonly string $name,
        public readonly int $priceInCents,
    ) {}

    public static function fromRequest(UpdateProductRequest $request): self
    {
        return new self(
            name: $request->validated('name'),
            priceInCents: $request->validated('price_in_cents'),
        );
    }
}
