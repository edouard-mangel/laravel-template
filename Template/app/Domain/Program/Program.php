<?php

declare(strict_types=1);

namespace App\Domain\Program;

use App\Domain\Program\Events\ProgramCreated;
use App\Domain\Program\Events\ProgramUpdated;
use App\Domain\Shared\AggregateRoot;
use DateTimeImmutable;

final class Program extends AggregateRoot
{
    private function __construct(
        private readonly ProgramId $id,
        private ProgramTitle $title,
        private ProgramDescription $description,
        private ProgramDuration $duration,
        private ProgramGenre $genre,
        private readonly string $ownerId,
    ) {}

    public static function create(
        ProgramId $id,
        ProgramTitle $title,
        ProgramDescription $description,
        ProgramDuration $duration,
        ProgramGenre $genre,
        string $ownerId,
    ): self {
        $program = new self($id, $title, $description, $duration, $genre, $ownerId);

        $program->recordEvent(new ProgramCreated(
            programId: $id,
            title: $title->value,
            durationMinutes: $duration->minutes,
            occurredAt: new DateTimeImmutable,
        ));

        return $program;
    }

    public static function reconstitute(
        ProgramId $id,
        ProgramTitle $title,
        ProgramDescription $description,
        ProgramDuration $duration,
        ProgramGenre $genre,
        string $ownerId,
    ): self {
        return new self($id, $title, $description, $duration, $genre, $ownerId);
    }

    public function update(
        ProgramTitle $title,
        ProgramDescription $description,
        ProgramDuration $duration,
    ): void {
        $this->title = $title;
        $this->description = $description;
        $this->duration = $duration;

        $this->recordEvent(new ProgramUpdated(
            programId: $this->id,
            title: $title->value,
            durationMinutes: $duration->minutes,
            occurredAt: new DateTimeImmutable,
        ));
    }

    public function id(): ProgramId
    {
        return $this->id;
    }

    public function title(): ProgramTitle
    {
        return $this->title;
    }

    public function description(): ProgramDescription
    {
        return $this->description;
    }

    public function duration(): ProgramDuration
    {
        return $this->duration;
    }

    public function genre(): ProgramGenre
    {
        return $this->genre;
    }

    public function ownerId(): string
    {
        return $this->ownerId;
    }
}
