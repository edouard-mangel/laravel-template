<?php

declare(strict_types=1);

namespace App\Domain\Program;

use App\Domain\Shared\Exceptions\InvalidInputException;
use App\Domain\Shared\ValueObject;

final readonly class ProgramDuration extends ValueObject
{
    public function __construct(public readonly int $minutes)
    {
        if ($this->minutes <= 0) {
            throw new InvalidInputException('Program duration must be greater than 0 minutes.');
        }
    }
}
