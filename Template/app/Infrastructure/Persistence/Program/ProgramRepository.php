<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Program;

use App\Application\Contracts\Repositories\ProgramRepositoryInterface;
use App\Domain\Program\Program;
use App\Domain\Program\ProgramDescription;
use App\Domain\Program\ProgramDuration;
use App\Domain\Program\ProgramGenre;
use App\Domain\Program\ProgramId;
use App\Domain\Program\ProgramTitle;

final class ProgramRepository implements ProgramRepositoryInterface
{
    public function save(Program $program): void
    {
        ProgramEloquentModel::withoutGlobalScopes()->updateOrCreate(
            ['id' => (string) $program->id()],
            [
                'title' => $program->title()->value,
                'description' => $program->description()->value,
                'duration_minutes' => $program->duration()->minutes,
                'genre' => $program->genre()->value,
                'owner_id' => $program->ownerId(),
            ]
        );
    }

    public function findById(ProgramId $id): ?Program
    {
        $model = ProgramEloquentModel::withoutGlobalScopes()->find((string) $id);

        return $model !== null ? $this->toDomain($model) : null;
    }

    public function delete(ProgramId $id): void
    {
        ProgramEloquentModel::withoutGlobalScopes()->destroy((string) $id);
    }

    private function toDomain(ProgramEloquentModel $model): Program
    {
        return Program::reconstitute(
            id: ProgramId::fromString($model->id),
            title: new ProgramTitle($model->title),
            description: new ProgramDescription($model->description),
            duration: new ProgramDuration($model->duration_minutes),
            genre: new ProgramGenre($model->genre),
            ownerId: $model->owner_id,
        );
    }
}
