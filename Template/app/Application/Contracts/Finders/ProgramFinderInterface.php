<?php

declare(strict_types=1);

namespace App\Application\Contracts\Finders;

use App\Application\Program\ProgramDto;
use App\Application\Program\ProgramFilter;
use App\Domain\Program\ProgramId;
use Illuminate\Pagination\LengthAwarePaginator;

interface ProgramFinderInterface
{
    public function findById(ProgramId $id): ?ProgramDto;

    /** @return LengthAwarePaginator<int, ProgramDto> */
    public function findAll(ProgramFilter $filter): LengthAwarePaginator;
}
