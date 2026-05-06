<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Infrastructure\Persistence\Program\ProgramEloquentModel;
use App\Infrastructure\Persistence\User\UserEloquentModel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

final class DevDataSeeder extends Seeder
{
    public function run(): void
    {
        $user = UserEloquentModel::updateOrCreate(
            ['email' => 'test@example.com'],
            [
                'id' => '00000000-0000-0000-0000-000000000001',
                'name' => 'Test User',
                'password' => Hash::make('password'),
            ]
        );

        ProgramEloquentModel::factory()
            ->count(10)
            ->ownedBy($user->id)
            ->create();
    }
}
