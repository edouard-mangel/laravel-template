<?php

declare(strict_types=1);

namespace App\Providers;

use App\Http\Services\AccessContext;
use App\Infrastructure\Persistence\User\UserEloquentModel;
use App\Infrastructure\Providers\InfrastructureServiceProvider;
use Illuminate\Support\Lottery;
use Illuminate\Support\ServiceProvider;
use Laravel\Pennant\Feature;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AccessContext::class);
        $this->app->register(InfrastructureServiceProvider::class);
    }

    public function boot(): void
    {
        Feature::define('new-dashboard', fn (UserEloquentModel $user): bool => $user->created_at?->isAfter(now()->subDays(30)) ?? false
        );

        Feature::define('beta-export', Lottery::odds(1, 5));
    }
}
