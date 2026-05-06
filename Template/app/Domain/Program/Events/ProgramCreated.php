<?php

declare(strict_types=1);

namespace App\Domain\Program\Events;

use App\Domain\Program\ProgramId;
use App\Domain\Shared\DomainEvent;
use DateTimeImmutable;

final readonly class ProgramCreated implements DomainEvent
{
    public function __construct(
        public readonly ProgramId $programId,
        public readonly string $title,
        public readonly int $durationMinutes,
        public readonly DateTimeImmutable $occurredAt,
    ) {}
}
