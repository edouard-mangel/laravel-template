<?php

declare(strict_types=1);

namespace App\Domain\Program;

use App\Domain\Shared\Exceptions\InvalidInputException;
use App\Domain\Shared\ValueObject;

final readonly class ProgramDescription extends ValueObject
{
    public function __construct(public readonly ?string $value)
    {
        if ($this->value !== null && mb_strlen($this->value) > 2000) {
            throw new InvalidInputException('Program description cannot exceed 2000 characters.');
        }
    }
}
