<?php

declare(strict_types=1);

namespace App\Application\Program\Actions;

use App\Application\Contracts\Repositories\ProgramRepositoryInterface;
use App\Application\Program\CreateProgramData;
use App\Domain\Program\Program;
use App\Domain\Program\ProgramDescription;
use App\Domain\Program\ProgramDuration;
use App\Domain\Program\ProgramGenre;
use App\Domain\Program\ProgramId;
use App\Domain\Program\ProgramTitle;
use Illuminate\Support\Facades\DB;

final class CreateProgramAction
{
    public function __construct(
        private readonly ProgramRepositoryInterface $programRepository,
    ) {}

    public function handle(CreateProgramData $data): ProgramId
    {
        return DB::transaction(function () use ($data): ProgramId {
            $program = Program::create(
                id: ProgramId::generate(),
                title: new ProgramTitle($data->title),
                description: new ProgramDescription($data->description),
                duration: new ProgramDuration($data->durationMinutes),
                genre: new ProgramGenre($data->genre),
                ownerId: $data->ownerId,
            );

            $this->programRepository->save($program);

            foreach ($program->releaseEvents() as $domainEvent) {
                event($domainEvent);
            }

            return $program->id();
        });
    }
}
