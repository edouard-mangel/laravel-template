<?php

declare(strict_types=1);

namespace App\Domain\Program;

use App\Domain\Shared\ValueObject;
use InvalidArgumentException;
use Ramsey\Uuid\Uuid;

final readonly class ProgramId extends ValueObject
{
    public function __construct(public readonly string $value)
    {
        if (empty($this->value)) {
            throw new InvalidArgumentException('ProgramId cannot be empty.');
        }
    }

    public static function generate(): self
    {
        return new self((string) Uuid::uuid4());
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
