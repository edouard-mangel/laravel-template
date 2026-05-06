<?php

declare(strict_types=1);

namespace App\Providers;

use App\Http\Services\AccessContext;
use App\Infrastructure\Providers\InfrastructureServiceProvider;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AccessContext::class);
        $this->app->register(InfrastructureServiceProvider::class);
    }

    public function boot(): void {}
}
