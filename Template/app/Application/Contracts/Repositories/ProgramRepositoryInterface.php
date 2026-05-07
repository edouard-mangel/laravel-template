<?php

declare(strict_types=1);

namespace App\Application\Contracts\Repositories;

use App\Domain\Program\Program;
use App\Domain\Program\ProgramId;

interface ProgramRepositoryInterface
{
    public function save(Program $program): void;

    public function findById(ProgramId $id): ?Program;

    public function delete(ProgramId $id): void;
}
