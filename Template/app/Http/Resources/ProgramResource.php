<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Application\Program\ProgramDto;
use Illuminate\Http\Resources\Json\JsonResource;

final class ProgramResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray($request): array
    {
        /** @var ProgramDto $dto */
        $dto = $this->resource;

        return [
            'id' => $dto->id,
            'title' => $dto->title,
            'description' => $dto->description,
            'duration_minutes' => $dto->durationMinutes,
            'genre' => $dto->genre,
            'created_at' => $dto->createdAt->format('c'),
        ];
    }
}
