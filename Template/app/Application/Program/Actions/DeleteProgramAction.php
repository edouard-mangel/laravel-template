<?php

declare(strict_types=1);

namespace App\Application\Program\Actions;

use App\Application\Contracts\Repositories\ProgramRepositoryInterface;
use App\Domain\Program\ProgramId;
use App\Domain\Shared\Exceptions\ResourceNotFoundException;
use Illuminate\Support\Facades\DB;

final class DeleteProgramAction
{
    public function __construct(
        private readonly ProgramRepositoryInterface $programRepository,
    ) {}

    public function handle(ProgramId $id): void
    {
        DB::transaction(function () use ($id): void {
            $program = $this->programRepository->findById($id);

            if ($program === null) {
                throw new ResourceNotFoundException("Program {$id} not found.");
            }

            $this->programRepository->delete($id);
        });
    }
}
