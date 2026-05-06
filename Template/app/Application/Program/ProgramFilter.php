<?php

declare(strict_types=1);

namespace App\Application\Program;

use Illuminate\Http\Request;

final readonly class ProgramFilter
{
    public function __construct(
        public readonly ?string $genre = null,
        public readonly int $perPage = 15,
        public readonly int $page = 1,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            genre: $request->query('genre'),
            perPage: (int) $request->query('per_page', 15),
            page: (int) $request->query('page', 1),
        );
    }
}
