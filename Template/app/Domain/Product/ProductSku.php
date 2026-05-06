<?php

declare(strict_types=1);

namespace App\Domain\Product;

use App\Domain\Shared\Exceptions\InvalidInputException;
use App\Domain\Shared\ValueObject;

final readonly class ProductSku extends ValueObject
{
    public function __construct(public readonly string $value)
    {
        if (empty(trim($this->value))) {
            throw new InvalidInputException('Product SKU cannot be empty.');
        }

        if (mb_strlen($this->value) > 100) {
            throw new InvalidInputException('Product SKU cannot exceed 100 characters.');
        }
    }
}
