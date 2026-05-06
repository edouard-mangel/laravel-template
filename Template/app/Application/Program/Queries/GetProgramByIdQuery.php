<?php

declare(strict_types=1);

namespace App\Application\Program\Queries;

use App\Application\Contracts\Finders\ProgramFinderInterface;
use App\Application\Program\ProgramDto;
use App\Domain\Program\ProgramId;

final class GetProgramByIdQuery
{
    public function __construct(
        private readonly ProgramFinderInterface $finder,
    ) {}

    public function handle(ProgramId $id): ?ProgramDto
    {
        return $this->finder->findById($id);
    }
}
