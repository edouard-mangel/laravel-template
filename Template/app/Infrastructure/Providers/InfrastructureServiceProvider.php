<?php

declare(strict_types=1);

namespace App\Infrastructure\Providers;

use App\Application\Contracts\Finders\ProgramFinderInterface;
use App\Application\Contracts\Repositories\ProgramRepositoryInterface;
use App\Infrastructure\Persistence\Program\ProgramFinder;
use App\Infrastructure\Persistence\Program\ProgramRepository;
use Illuminate\Support\ServiceProvider;

final class InfrastructureServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            ProgramRepositoryInterface::class,
            ProgramRepository::class,
        );

        $this->app->bind(
            ProgramFinderInterface::class,
            ProgramFinder::class,
        );
    }
}
