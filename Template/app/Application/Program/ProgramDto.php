<?php

declare(strict_types=1);

namespace App\Application\Program;

use DateTimeImmutable;

final readonly class ProgramDto
{
    public function __construct(
        public readonly string $id,
        public readonly string $title,
        public readonly ?string $description,
        public readonly int $durationMinutes,
        public readonly string $genre,
        public readonly string $ownerId,
        public readonly DateTimeImmutable $createdAt,
    ) {}
}
