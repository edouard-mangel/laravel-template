<?php

declare(strict_types=1);

namespace App\Application\Program\Actions;

use App\Application\Contracts\Repositories\ProgramRepositoryInterface;
use App\Application\Program\UpdateProgramData;
use App\Domain\Program\ProgramDescription;
use App\Domain\Program\ProgramDuration;
use App\Domain\Program\ProgramId;
use App\Domain\Program\ProgramTitle;
use App\Domain\Shared\Exceptions\ResourceNotFoundException;
use Illuminate\Support\Facades\DB;

final class UpdateProgramAction
{
    public function __construct(
        private readonly ProgramRepositoryInterface $programRepository,
    ) {}

    public function handle(ProgramId $id, UpdateProgramData $data): void
    {
        DB::transaction(function () use ($id, $data): void {
            $program = $this->programRepository->findById($id);

            if ($program === null) {
                throw new ResourceNotFoundException("Program {$id} not found.");
            }

            $program->update(
                title: new ProgramTitle($data->title),
                description: new ProgramDescription($data->description),
                duration: new ProgramDuration($data->durationMinutes),
            );

            $this->programRepository->save($program);

            foreach ($program->releaseEvents() as $domainEvent) {
                event($domainEvent);
            }
        });
    }
}
