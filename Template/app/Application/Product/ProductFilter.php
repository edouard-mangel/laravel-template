<?php

declare(strict_types=1);

namespace App\Application\Product;

use Illuminate\Http\Request;

final readonly class ProductFilter
{
    public function __construct(
        public readonly ?string $name = null,
        public readonly int $perPage = 15,
        public readonly int $page = 1,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            name: $request->query('name'),
            perPage: (int) $request->query('per_page', 15),
            page: (int) $request->query('page', 1),
        );
    }
}
