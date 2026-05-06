<?php

declare(strict_types=1);

namespace App\Domain\Product;

use App\Domain\Shared\Exceptions\InvalidInputException;
use App\Domain\Shared\ValueObject;

final readonly class ProductPrice extends ValueObject
{
    public function __construct(public readonly int $valueInCents)
    {
        if ($this->valueInCents < 0) {
            throw new InvalidInputException('Product price cannot be negative.');
        }
    }

    public static function fromCents(int $cents): self
    {
        return new self($cents);
    }

    public function toFloat(): float
    {
        return $this->valueInCents / 100;
    }
}
