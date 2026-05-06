<?php

declare(strict_types=1);

namespace App\Infrastructure\Providers;

use App\Application\Contracts\Finders\ProductFinderInterface;
use App\Application\Contracts\Repositories\ProductRepositoryInterface;
use App\Infrastructure\Persistence\Product\ProductFinder;
use App\Infrastructure\Persistence\Product\ProductRepository;
use Illuminate\Support\ServiceProvider;

final class InfrastructureServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            ProductRepositoryInterface::class,
            ProductRepository::class,
        );

        $this->app->bind(
            ProductFinderInterface::class,
            ProductFinder::class,
        );
    }
}
