<?php

declare(strict_types=1);

namespace App\Domain\Program;

use App\Domain\Shared\Exceptions\InvalidInputException;
use App\Domain\Shared\ValueObject;

final readonly class ProgramTitle extends ValueObject
{
    public function __construct(public readonly string $value)
    {
        if (empty(trim($this->value))) {
            throw new InvalidInputException('Program title cannot be empty.');
        }

        if (mb_strlen($this->value) > 255) {
            throw new InvalidInputException('Program title cannot exceed 255 characters.');
        }
    }
}
