<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Infrastructure\Persistence\Product\ProductEloquentModel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

final class DevDataSeeder extends Seeder
{
    public function run(): void
    {
        $ownerId = (string) Str::uuid();

        ProductEloquentModel::factory()
            ->count(10)
            ->ownedBy($ownerId)
            ->create();
    }
}
