<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Application\Product\ProductDto;
use Illuminate\Http\Resources\Json\JsonResource;

final class ProductResource extends JsonResource
{
    public function toArray($request): array
    {
        /** @var ProductDto $dto */
        $dto = $this->resource;

        return [
            'id' => $dto->id,
            'name' => $dto->name,
            'sku' => $dto->sku,
            'price_in_cents' => $dto->priceInCents,
            'price_formatted' => '$' . number_format($dto->priceInCents / 100, 2),
            'created_at' => $dto->createdAt->format('c'),
        ];
    }
}
