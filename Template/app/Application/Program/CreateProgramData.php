<?php

declare(strict_types=1);

namespace App\Application\Program;

use App\Http\Requests\CreateProgramRequest;

final readonly class CreateProgramData
{
    public function __construct(
        public readonly string $title,
        public readonly ?string $description,
        public readonly int $durationMinutes,
        public readonly string $genre,
        public readonly string $ownerId,
    ) {}

    public static function fromRequest(CreateProgramRequest $request): self
    {
        return new self(
            title: $request->validated('title'),
            description: $request->validated('description'),
            durationMinutes: $request->validated('duration_minutes'),
            genre: $request->validated('genre'),
            ownerId: $request->user()->id,
        );
    }
}
