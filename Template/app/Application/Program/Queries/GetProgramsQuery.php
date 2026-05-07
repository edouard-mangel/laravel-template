<?php

declare(strict_types=1);

namespace App\Application\Program\Queries;

use App\Application\Contracts\Finders\ProgramFinderInterface;
use App\Application\Program\ProgramDto;
use App\Application\Program\ProgramFilter;
use Illuminate\Pagination\LengthAwarePaginator;

final class GetProgramsQuery
{
    public function __construct(
        private readonly ProgramFinderInterface $finder,
    ) {}

    /** @return LengthAwarePaginator<int, ProgramDto> */
    public function handle(ProgramFilter $filter): LengthAwarePaginator
    {
        return $this->finder->findAll($filter);
    }
}
