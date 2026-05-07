<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Program;

use App\Application\Contracts\Finders\ProgramFinderInterface;
use App\Application\Program\ProgramDto;
use App\Application\Program\ProgramFilter;
use App\Domain\Program\ProgramId;
use Illuminate\Pagination\LengthAwarePaginator;

final class ProgramFinder implements ProgramFinderInterface
{
    public function findById(ProgramId $id): ?ProgramDto
    {
        $model = ProgramEloquentModel::find((string) $id);

        return $model !== null ? $this->toDto($model) : null;
    }

    /** @return LengthAwarePaginator<int, ProgramDto> */
    public function findAll(ProgramFilter $filter): LengthAwarePaginator
    {
        $query = ProgramEloquentModel::query();

        if ($filter->genre !== null) {
            $query->where('genre', $filter->genre);
        }

        /** @var LengthAwarePaginator<int, ProgramDto> */
        return $query
            ->orderBy('created_at', 'desc')
            ->paginate($filter->perPage, ['*'], 'page', $filter->page)
            ->through(fn (ProgramEloquentModel $model) => $this->toDto($model));
    }

    private function toDto(ProgramEloquentModel $model): ProgramDto
    {
        return new ProgramDto(
            id: $model->id,
            title: $model->title,
            description: $model->description,
            durationMinutes: $model->duration_minutes,
            genre: $model->genre,
            ownerId: $model->owner_id,
            createdAt: $model->created_at?->toDateTimeImmutable() ?? new \DateTimeImmutable,
        );
    }
}
