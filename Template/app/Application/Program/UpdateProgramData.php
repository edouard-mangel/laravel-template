<?php

declare(strict_types=1);

namespace App\Application\Program;

use App\Http\Requests\UpdateProgramRequest;

final readonly class UpdateProgramData
{
    public function __construct(
        public readonly string $title,
        public readonly ?string $description,
        public readonly int $durationMinutes,
    ) {}

    public static function fromRequest(UpdateProgramRequest $request): self
    {
        return new self(
            title: $request->validated('title'),
            description: $request->validated('description'),
            durationMinutes: $request->validated('duration_minutes'),
        );
    }
}
